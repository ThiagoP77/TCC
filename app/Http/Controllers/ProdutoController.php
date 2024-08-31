<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Produto;
use App\Models\Api\Vendedor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

//Classe controladora de produto
class ProdutoController extends Controller
{
    
    //Função de cadastrar um novo produto
    public function cadastrarProduto (Request $r) {
        try {//Testa se tem exceção

            //Envia mensagem de erro caso o json não apresente a key "produto"
            if (!$r->has('produto')) {
                return response()->json(['mensagem' => 'Campo "produto" não encontrado na requisição.'], 400);
            }

            //Recebe os dados do request
            $requestData = $r->all();

            try {//Testa exceção

                //Decodifica o JSON do campo 'produto' para um array associativo
                $produtoData = json_decode($requestData['produto'], true, 512, JSON_THROW_ON_ERROR);
        
                //Verifica se houve algum erro na decodificação do JSON
                if (json_last_error() != JSON_ERROR_NONE) {
                    return response()->json(['mensagem' => 'Erro ao processar os dados do produto.'], 400);
                }
    
            } catch (\JsonException $e) {//Captura exceções lançadas ao decodificar o JSON
                return response()->json(['mensagem' => 'Erro ao processar os dados do produto.', 'erro' => $e->getMessage()], 400);
            }

            //Realiza as validações fornecidas para os campos de produto
            $validator = Validator::make($produtoData, [
                'nome' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
                ],
        
                'descricao' => [
                    'nullable', 
                    'string', 
                    'max:200'
                ],
        
                'preco' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:99999999.99'
                ],
        
                'qtd_estoque' => [
                    'required',
                    'integer',
                    'min:0'
                ],
        
            ], [//Mensagens de erro personalizadas
                'nome.regex' => 'Nome não pode conter caracteres especiais.',
                'descricao.string' => 'O campo descrição deve ser uma string.',
                'descricao.max' => 'A descrição não pode passar de 200 caracteres.',
                'preco.required' => 'O campo preço é obrigatório.',
                'preco.numeric' => 'O campo preço deve ser numérico.',
                'preco.min' => 'O preço não pode ser negativo.',
                'preco.max' => 'O preço está acima do permitido no site.',
                'qtd_estoque.required' => 'O campo quantidade em estoque é obrigatório.',
                'qtd_estoque.integer' => 'O campo quantidade em estoque deve ser um número inteiro.',
                'qtd_estoque.min' => 'A quantidade em estoque não pode ser negativa.'
            ]);

            //Realiza as validações fornecidas para a imagem do produto
            $validator2 = Validator::make($r->all(), [
                'imagem' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16384'
            ]);

            //Caso haja falhas no primeiro validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }

            //Caso haja falhas no segundo validator, envia json de erro
            if ($validator2->fails()) {
                return response()->json(['errors' => $validator2->errors()], 422); 
            }

            //Encontra o usuário logado e o vendedor associado a ele
            $u = $r->user();
            $v = $u->vendedor;

            //Caso não encontre, envia mensagem de erro
            if (!$u || !$v) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Inicia transação no banco
            DB::beginTransaction();

            //Criação do produto com seus campos do resquest
            $p = new Produto();
            $p->nome = $dadosValidados['nome'];
            $p->descricao = $dadosValidados['descricao'];
            $p->preco = $dadosValidados['preco'];
            $p->preco_atual = $dadosValidados['preco'];
            $p->qtde_estoque = $dadosValidados['qtd_estoque']; 
            $p->id_vendedor = $v->id;

            //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
            if (isset($requestData['imagem']) && $r->hasFile('imagem') && $r->file('imagem')->isValid()) {
                $path = $r->file('imagem')->store('imagens_produtos', 'public');//Salva a imagem no diretório
                $p->imagem_produto = 'storage/'.$path;
            }

            $p->save();//Salvando o produto

            DB::commit();//Fazendo commit da operação

            return response()->json([//Retorno da mensagem de sucesso
                'mensagem' => 'Produto cadastrado com sucesso.'
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            DB::rollback();//Desfaz todas as operações realizadas no banco

            return response()->json([
                'mensagem' => 'Não foi possível cadastrar o produto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de excluir produto
    public function excluirProduto (Request $r, $id) {
        try {//Testa exceção

            //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }
    
            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

            //Caso não, envia mensagem de erro
            if (!$produtoExistente) {
                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);
            }

            //Recebe a URL da imagem do produto
            $fotoURL = $p->imagem_produto;

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_produtos/imagem_default_produto.png';

            //Caso consiga deletar o produto, irá entrar no if 
            if ($p->delete()) {

                //Verificar se a foto existe e é a default e, se não for, exclui ela do site
                if ($fotoURL && $fotoURL !== $defaultURL) {

                    $path = str_replace('storage/', '', $fotoURL);

                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);//Excluindo ela
                    }

                }
    
                $p->delete();//Deletando o produto

                return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                    'mensagem' => 'Produto excluído com sucesso.'
                ], 200);

            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao excluir produto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de aplicar desconto
    public function aplicarDesconto (Request $r, $id) {
        try {//Testa se tem exceção

            //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }
    
            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

             //Caso não, envia mensagem de erro
             if (!$produtoExistente) {

                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);

            }

            //Realiza as validações fornecidas para o desconto
            $validator = Validator::make($r->all(), [
                'desconto' => [
                    'required',
                    'numeric',
                    'max:100',
                    'gt:0'
                ]
            ]);

            //Caso haja falhas no primeiro validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Recebendo o desconto e preço com o desconto
            $p->desconto = $dadosValidados['desconto'];
            $p->preco_atual = ($p->preco - (($dadosValidados['desconto'] * $p->preco)/100));
            $p->save();//Salvando alterações

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Desconto aplicado com sucesso.'
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao aplicar desconto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de tirar desconto
    public function tirarDesconto (Request $r, $id) {
        try {//Testa se tem exceção

            //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }
    
            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

             //Caso não, envia mensagem de erro
             if (!$produtoExistente) {

                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);

            }

            //Caso não, envia mensagem de erro
            if (!($p->desconto > 0)) {

                return response()->json([
                    'mensagem' => 'O produto informado não está com desconto.'
                ], 400);

            }

            //0 o desconto e o preço volta a ser o total
            $p->desconto = 0;
            $p->preco_atual = $p->preco;
            $p->save();//Salvando alterações

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Desconto tirado com sucesso.'
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao tirar desconto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de excluir foto do produto
    public function excluirFoto (Request $r, $id) {
        try {//Testa se tem exceção

            //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }
    
            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

             //Caso não, envia mensagem de erro
             if (!$produtoExistente) {

                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);

            }

            //Pega a URL da imagem do usuário
            $fotoURL = $p->imagem_produto;

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_produtos/imagem_default_produto.png';

            //Verificar se a foto existe e é a default e, se não for, exclui ela do site
            if ($fotoURL && $fotoURL !== $defaultURL) {

                $path = str_replace('storage/', '', $fotoURL);

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);//Excluindo ela
                }

            } else {
                return response()->json([//Envia mensagem de erro caso a imagem seja a default
                    'mensagem' => 'Nenhuma imagem para ser excluída.'
                ], 400);
            }

            //Define a imagem do usuário como a default e salva
            $p->imagem_produto = 'storage/imagens_produtos/imagem_default_produto.png';
            $p->save();

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Imagem de produto excluída com sucesso.'
            ], 200);

        } catch (Exception $e){//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Não foi possível excluir a imagem.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de alterar foto do produto
    public function alterarFoto (Request $r, $id) {
        try {//Testa se tem exceção

           //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
           if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
            return response()->json([
                'mensagem' => 'Produto não encontrado.'
            ], 404);
            }

            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

            //Caso não, envia mensagem de erro
            if (!$produtoExistente) {

                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);

            }

            //Realiza as validações fornecidas para a imagem de produto
            $validator = Validator::make($r->all(), [
                'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:16384'
            ]);

            //Caso tenha erro na validação, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }

            //Captura a URL da imagem do usuário
            $fotoURL = $p->imagem_produto;

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_produtos/imagem_default_produto.png';

            //Verificar se a foto existe e é a default e, se não for, exclui ela do site
            if ($fotoURL && $fotoURL !== $defaultURL) {

                $path = str_replace('storage/', '', $fotoURL);

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);//Excluindo ela
                }

            } 

            //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
            if ($r->hasFile('imagem') && $r->file('imagem')->isValid()) {
                $path2 = $r->file('imagem')->store('imagens_produtos', 'public');//Salva a imagem no diretório
                $p->imagem_produto = 'storage/'.$path2;
            } 

            $p->save();//Salvando o usuário

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Imagem de produto alterada com sucesso.'
            ], 200);
            

        } catch (Exception $e){//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Não foi possível alterar a imagem.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de alterar dados do produto
    public function alterarProduto (Request $r, $id) {
        try {//Testa se tem exceção

            //Verifica se o id informado é númerico e existe na tabela de produtos. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Produto::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }
    
            //Encontra o produto informado pelo id
            $p = Produto::find($id);

            //Encontra o vendedor logado
            $v = $r->user()->vendedor;

            //Caso não ache o produto, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado.'
                ], 404);
            }

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Verifica se a produto existe e é do vendedor
            $produtoExistente = Produto::where('id', $id)
                ->where('id_vendedor', $v->id)
                ->exists();

             //Caso não, envia mensagem de erro
             if (!$produtoExistente) {

                return response()->json([
                    'mensagem' => 'O produto informado não é do seu usuário.'
                ], 401);

            }

            //Realiza as validações fornecidas para os campos de produto
            $validator = Validator::make($r->all(), [
                'nome' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
                ],
        
                'descricao' => [
                    'nullable', 
                    'string', 
                    'max:200'
                ],
        
                'preco' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:99999999.99'
                ],
        
                'qtd_estoque' => [
                    'required',
                    'integer',
                    'min:0'
                ],
        
            ], [//Mensagens de erro personalizadas
                'nome.regex' => 'Nome não pode conter caracteres especiais.',
                'descricao.string' => 'O campo descrição deve ser uma string.',
                'descricao.max' => 'A descrição não pode passar de 200 caracteres.',
                'preco.required' => 'O campo preço é obrigatório.',
                'preco.numeric' => 'O campo preço deve ser numérico.',
                'preco.min' => 'O preço não pode ser negativo.',
                'preco.max' => 'O preço está acima do permitido no site.',
                'qtd_estoque.required' => 'O campo quantidade em estoque é obrigatório.',
                'qtd_estoque.integer' => 'O campo quantidade em estoque deve ser um número inteiro.',
                'qtd_estoque.min' => 'A quantidade em estoque não pode ser negativa.'
            ]);

            //Caso haja falhas no primeiro validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Recebendo os dados
            $p->nome = $dadosValidados['nome'];
            $p->descricao = $dadosValidados['descricao'];
            $p->preco = $dadosValidados['preco'];
            $p->qtde_estoque = $dadosValidados['qtd_estoque'];

            //Recebe o desconto
            $d = $p->desconto;

            //Verifica se o preço apresenta desconto ou não
            if ($d != 0) {
                $p->preco_atual = ($p->preco - (($d * $p->preco)/100));
            } else {
                $p->preco_atual = $p->preco;
            }
            
            $p->save();//Salvando alterações

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Produto alterado com sucesso.'
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao alterar produto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de listar os produtos do vendedor logado
    public function listarMeusProdutos (Request $r) {
        try {//Testa se tem exceção

            //Obtém o usuário autenticado
            $u = $r->user(); 

            //Obtém o vendedor
            $v = $u->vendedor;

            //Caso o usuário ou cliente não sejam encontrados, envia mensagem de erro
            if (!$u || !$v) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }

            //Encontra os produtos do vendedor logado
            $resposta = Produto::where('id_vendedor', $v->id)
                            ->select('id', 'nome', 'descricao', 'preco', 'preco_atual', 'desconto', 'imagem_produto', 'qtde_estoque')
                            ->get();
            
            //Adiciona o campo booleano 'tem_desconto' a cada produto
            foreach ($resposta as $produto) {
                $produto->tem_desconto = $produto->desconto > 0;
            }

            //Fornece a resposta de sucesso com os produtos
            return response()->json($resposta, 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao listar seus produtos.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

     //Função de listar os produtos do vendedor de ID informado
     public function listarProdutosLoja ($id) {
        try {//Testa se tem exceção

            //Obtém o vendedor
            $v = Vendedor::find($id);

            //Caso o vendedor não seja encontrado, envia mensagem de erro
            if (!$v) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o vendedor.',
                ], 404);
            }

            //Encontra os produtos do vendedor
            $resposta = Produto::where('id_vendedor', $v->id)
                            ->select('id', 'nome', 'descricao', 'preco', 'preco_atual', 'desconto', 'imagem_produto', 'qtde_estoque')
                            ->get();
            
            //Adiciona o campo booleano 'tem_desconto' a cada produto
            foreach ($resposta as $produto) {
                $produto->tem_desconto = $produto->desconto > 0;
            }

            //Fornece a resposta de sucesso com os produtos
            return response()->json($resposta, 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao listar os produtos do vendedor.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de pegar dados do produto por id
    public function dadosProduto ($id) {
        try {//Testa se tem exceção

            //Obtém o produto
            $p = Produto::find($id);

            //Caso o produto não seja encontrado, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o produto.',
                ], 404);
            }

            //Encontra os dados do produto informado
            $resposta = Produto::where('id', $p->id)
                            ->select('id', 'nome', 'descricao', 'preco', 'preco_atual', 'desconto', 'imagem_produto', 'qtde_estoque')
                            ->get();
            
            //Adiciona o campo booleano 'tem_desconto' ao produto
            foreach ($resposta as $produto) {
                $produto->tem_desconto = $produto->desconto > 0;
            }

            //Fornece a resposta de sucesso com os dados do produto
            return response()->json($resposta, 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao mostrar dados do produto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de pegar a imagem do produto informado
    public function fotoProduto ($id) {
        try {//Testa se tem exceção

            //Obtém o produto
            $p = Produto::find($id);

            //Caso o produto não seja encontrado, envia mensagem de erro
            if (!$p) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o produto.',
                ], 404);
            }

            //Encontra o produto e pega a imagem
            $resposta = Produto::where('id', $p->id)
                            ->select('imagem_produto')
                            ->get();

            //Fornece a resposta de sucesso com a imagem
            return response()->json($resposta, 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao mostrar a foto do produto.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }
}

