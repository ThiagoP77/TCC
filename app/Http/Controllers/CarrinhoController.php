<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Carrinho;
use App\Models\Api\Produto;
use App\Models\Api\Vendedor;
use App\Services\LimparCarrinhosService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

//Classe controladora de carrinho
class CarrinhoController extends Controller
{
    
    protected $limpacarrinhoservice;//Atributo com o serviço de consulta de CEP

    //Construtor já com a criação do serviço
    public function __construct(LimparCarrinhosService $limpacarrinhoservice)
    {
        $this->limpacarrinhoservice = $limpacarrinhoservice;
    }

    //Função de adicionar produto ao carrinho
    public function adicionarAoCarrinho (Request $r) {

        try {//Testa se tem exceção

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'id_loja' => [
                    'required', 
                    'integer'
                ],

                'id_produto' => [
                    'required', 
                    'integer'
                ],

                'qtd' => [
                    'required', 
                    'integer', 
                    'min:1',
                ]
            ], [

                'id_loja.required' => 'O ID da loja é obrigatório.',
                'id_loja.integer' => 'O ID da loja deve ser um número inteiro.',
                'id_produto.required' => 'O ID do produto é obrigatório.',
                'id_produto.integer' => 'O ID do produto deve ser um número inteiro.',
                'qtd.required' => 'A quantidade deve ser informada.',
                'qtd.integer' => 'A quantidade deve ser um número inteiro.',
                'qtd.min' => 'A quantidade deve ser de no mínimo um.',

            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;

            //Obtém a loja
            $loja = Vendedor::find($dadosValidados['id_loja']);
    
            //Verifica se o usuário, o cliente e a loja existem
            if (!$user || !$cliente || !$loja) {
                return response()->json([
                    'mensagem' => 'Falha ao identificar usuários.',
                ], 404);
            }

            //Encontrando o produto
            $produto = Produto::find($dadosValidados['id_produto']);

            //Verifica a existência do produto, se ele é daquela loja e se está ativo
            if (!$produto || $produto->status == 'desativado' || !Produto::where('id', $dadosValidados['id_produto'])
            ->where('id_vendedor', $dadosValidados['id_loja'])->exists()) {
                return response()->json([
                    'mensagem' => 'Falha ao identificar o produto ou ele não está ativo.',
                ], 404);
            }

            //Procura se esse item já esta no carrinho
            $existe = Carrinho::where('id_vendedor', $loja->id)
            ->where('id_cliente', $cliente->id)
            ->where('id_produto', $produto->id)
            ->exists();

            //Pega a quantidade em estoque do produto em questão
            $quantidade_estoque = $produto->qtde_estoque;

            //Caso o registro do carrinho exista
            if ($existe) {

                //Instância do carrinho existente
                $carrinho= Carrinho::where('id_cliente', $cliente->id)
                          ->where('id_vendedor', $loja->id)
                          ->where('id_produto', $produto->id)->first();

                //Pega o status e a quantidade do carrinho
                $status = $carrinho->status;
                $quantidade = $carrinho->qtde;

                //Caso o carrinho ainda esteja com status de reservado
                if ($status == "Reservado.") {

                    //Caso queira uma quantidade maior que a em estoque, retorna uma mensagem de falha
                    if ($quantidade_estoque < $dadosValidados['qtd']) {

                        return response()->json([
                            'mensagem' => 'Não foi possível alterar no carrinho, pois a quantidade em estoque é insuficiente.',
                        ], 400);
        
                    } else {//Caso contrário

                        DB::beginTransaction();//Inicia transação no banco

                        //Modificações no carrinho
                        $carrinho->qtde = $quantidade + $dadosValidados['qtd'];
                        $carrinho->expires_at = Carbon::now()->addHour();
                        $carrinho->total = ($quantidade + $dadosValidados['qtd']) * ($produto->preco_atual);
                        $carrinho->save();

                        //Modificações no produto
                        $produto->qtde_estoque = $quantidade_estoque - $dadosValidados['qtd'];
                        $produto->save();

                        DB::commit();//Realiza commit das alterações

                        //Envia mensagem de sucesso
                        return response()->json([
                            'mensagem' => 'Carrinho modificado com sucesso.',
                        ], 200);

                    }

                } else {//Caso contrário, envia mensagem de erro

                    return response()->json([
                        'mensagem' => 'Erro na operação ou sua reserva expirou.',
                    ], 400);

                }/*else if ($status == "Expirado.") {//Caso o registro já tenha sido expirado

                    //Deve pegar toda
                    $quantidade_total = $quantidade + $dadosValidados['qtd'];

                    if ($quantidade_estoque < $quantidade_total) {

                        return response()->json([
                            'mensagem' => 'Não foi possível alterar no carrinho, pois a quantidade em estoque é insuficiente.',
                        ], 400);
        
                    } else {

                        DB::beginTransaction();

                        $carrinho->qtde = $quantidade_total;
                        $carrinho->expires_at = Carbon::now()->addHour();
                        $carrinho->total = ($quantidade_total) * ($produto->preco_atual);
                        $carrinho->save();

                        $produto->qtde_estoque = $quantidade_estoque - $quantidade_total;
                        $produto->save();

                        DB::commit();

                        return response()->json([
                            'mensagem' => 'Carrinho modificado com sucesso.',
                        ], 200);

                    }

                } else {//Caso contrário, envia mensagem de erro

                    return response()->json([
                        'mensagem' => 'Erro na operação.',
                    ], 400);

                }*/

            } else {//Caso não exista registro

                //Verifica se a quantidade é válida
                if ($quantidade_estoque < $dadosValidados['qtd']) {

                    return response()->json([
                        'mensagem' => 'Não foi possível adicionar ao carrinho, quantidade em estoque é insuficiente.',
                    ], 400);

                } else {//Caso seja válida

                    DB::beginTransaction();//Começa a transação no banco de dados

                    //Cria um registro e ele recebe os valores
                    $registro = new Carrinho();
                    $registro->id_cliente = $cliente->id; 
                    $registro->id_vendedor = $loja->id; 
                    $registro->id_produto = $produto->id;
                    $registro->qtde = $dadosValidados['qtd'];
                    $registro->expires_at = Carbon::now()->addHour();
                    $registro->total = ($dadosValidados['qtd']) * ($produto->preco_atual);
                    $registro->save();

                    //Produto sofre alterações no estoque
                    $produto->qtde_estoque = $quantidade_estoque - $dadosValidados['qtd'];
                    $produto->save();

                    DB::commit();//Realizando commit

                    return response()->json([//Envia mensagem de sucesso
                        'mensagem' => 'Produto adicionado com sucesso ao carrinho.',
                    ], 200);
                }
            }

            
        } catch (Exception $e) {//Envia mensagem de erro em caso de exceção 

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro na operação.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Função para modificar registro existente no carrinho
    public function modificarCarrinho (Request $r) {
        
        try {//Testa se tem erro

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'id_loja' => [
                    'required', 
                    'integer'
                ],

                'id_produto' => [
                    'required', 
                    'integer'
                ],

                'qtd' => [
                    'required', 
                    'integer', 
                    'min:1',
                ]
            ], [

                'id_loja.required' => 'O ID da loja é obrigatório.',
                'id_loja.integer' => 'O ID da loja deve ser um número inteiro.',
                'id_produto.required' => 'O ID do produto é obrigatório.',
                'id_produto.integer' => 'O ID do produto deve ser um número inteiro.',
                'qtd.required' => 'A quantidade deve ser informada.',
                'qtd.integer' => 'A quantidade deve ser um número inteiro.',
                'qtd.min' => 'A quantidade deve ser de no mínimo um.',

            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;

            //Obtém a loja
            $loja = Vendedor::find($dadosValidados['id_loja']);
    
            //Verifica se o usuário e cliente existem
            if (!$user || !$cliente || !$loja) {
                return response()->json([
                    'mensagem' => 'Falha ao identificar usuários.',
                ], 404);
            }

            //Procura e faz a instância do produto
            $produto = Produto::find($dadosValidados['id_produto']);

            //Verificação do produto
            if (!$produto || $produto->status == 'desativado' || !Produto::where('id', $dadosValidados['id_produto'])
            ->where('id_vendedor', $dadosValidados['id_loja'])->exists()) {
                return response()->json([
                    'mensagem' => 'Falha ao identificar o produto.',
                ], 404);
            }

            //Verifica se existe o registro
            $existe = Carrinho::where('id_vendedor', $loja->id)
            ->where('id_cliente', $cliente->id)
            ->where('id_produto', $produto->id)
            ->exists();

            //Pega a quantidade em estoque do produto
            $quantidade_estoque = $produto->qtde_estoque;

            if ($existe) {//Caso ela exista

                //Instância do carrinho
                $carrinho = Carrinho::firstWhere([
                    'id_vendedor' => $loja->id,
                    'id_cliente' => $cliente->id,
                    'id_produto' => $produto->id,
                ]);

                //Pega o status e a quantidade de itens do registro
                $status = $carrinho->status;
                $quantidade = $carrinho->qtde;

                //Verifica se o item ainda esta reservado
                if ($status == "Reservado.") {

                    //Se a quantidade for maior que a anterior
                    if ($quantidade < $dadosValidados['qtd']) {

                        //Diferença entre as quantidades
                        $diferenca = $dadosValidados['qtd'] - $quantidade;

                        //Caso não tenha em estoque, envia mensagem de erro
                        if ($quantidade_estoque < $diferenca) {

                            return response()->json([
                                'mensagem' => 'Não foi possível alterar no carrinho, pois a quantidade em estoque é insuficiente.',
                            ], 400);
        
                        } else {//Caso tenha em estoque

                            DB::beginTransaction();//Começa a transação com o banco

                            //Atualiza o carrinho com os dados
                            $carrinho->qtde = $dadosValidados['qtd'];
                            $carrinho->expires_at = Carbon::now()->addHour();
                            $carrinho->total = ($dadosValidados['qtd']) * ($produto->preco_atual);
                            $carrinho->save();

                            //Atualiza o estoque do produto
                            $produto->qtde_estoque = $quantidade_estoque - $diferenca;
                            $produto->save();

                            DB::commit();//Dá commit nas alterações

                            return response()->json([//Envia mensagem de sucesso
                                'mensagem' => 'Carrinho modificado com sucesso.',
                            ], 200);

                        }

                    } else if ($quantidade > $dadosValidados['qtd']) {//Caso a quantidade seja menor

                        DB::beginTransaction();//Começa a transação com o banco

                        //Atualiza o carrinho com os dados
                        $carrinho->qtde = $dadosValidados['qtd'];
                        $carrinho->expires_at = Carbon::now()->addHour();
                        $carrinho->total = ($dadosValidados['qtd']) * ($produto->preco_atual);
                        $carrinho->save();

                        //Atualiza o estoque do produto
                        $produto->qtde_estoque = ($quantidade_estoque + ($quantidade - $dadosValidados['qtd']));
                        $produto->save();

                        DB::commit();//Dá commit nas alterações

                        return response()->json([//Envia mensagem de sucesso
                            'mensagem' => 'Carrinho modificado com sucesso.',
                        ], 200);

                    } else {//Envia mensagem de erro caso não exista registro

                        return response()->json([
                            'mensagem' => 'Nada a ser modificado.',
                        ], 200);

                    }

                } else {//Mensagem de erro

                    return response()->json([
                        'mensagem' => 'Erro na operação ou sua reserva expirou.',
                    ], 400);

                }/*else if ($status == "Expirado.") {

                    if ($quantidade_estoque < $dadosValidados['qtd']) {

                        return response()->json([
                            'mensagem' => 'Não foi possível modificar no carrinho, pois a quantidade em estoque é insuficiente.',
                        ], 400);
    
                    }

                    DB::beginTransaction();

                    $carrinho->qtde = $dadosValidados['qtd'];
                    $carrinho->expires_at = Carbon::now()->addHour();
                    $carrinho->total = ($dadosValidados['qtd']) * ($produto->preco_atual);
                    $carrinho->status = "Reservado.";
                    $carrinho->save();

                    $produto->qtde_estoque = $quantidade_estoque - $dadosValidados['qtd'];
                    $produto->save();

                    DB::commit();

                    return response()->json([
                        'mensagem' => 'Carrinho modificado com sucesso.',
                    ], 200);

                } */

            } else {//Mensagem de erro caso não exista registro

                    return response()->json([
                        'mensagem' => 'Produto não encontrado no carrinho.',
                    ], 400);

            }
        } catch (Exception $e) {//Envia mensagem de erro em caso de exceção

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro na operação.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Função para remover produto do carrinho
    public function removerDoCarrinho(Request $r, $id) {

        try {//Testa se tem exceção

            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Verifica se o usuário e o cliente existem
            if (!$user || !$user->cliente) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }
    
            //Obtém o cliente
            $cliente = $user->cliente;
    
            //Verifica se o ID informado é numérico e existe no carrinho
            if (!is_numeric($id) || !Carrinho::where('id_produto', $id)
                ->where('id_cliente', $cliente->id)
                ->exists()) {
                return response()->json([
                    'mensagem' => 'Produto não encontrado no carrinho.'
                ], 404);
            }
    
            //Instância do carrinho
            $carrinho = Carrinho::where('id_produto', $id)
            ->where('id_cliente', $cliente->id)->first();

            //Instância de produto
            $produto = Produto::find($id);

            //Verifica se o produto ainda está reservado
            if ($carrinho->status == "Reservado.") {

                $produto->qtde_estoque += $carrinho->qtde;
                $produto->save();

            }

            //Deleta o produto do carrinho
            Carrinho::where('id_produto', $id)
                ->where('id_cliente', $cliente->id)
                ->delete();
    
            return response()->json([//Envia mensagem de sucesso
                'mensagem' => 'Produto excluído com sucesso do carrinho.'
            ], 200);
    
        } catch (Exception $e) {//Envia mensagem de erro em caso de exceção
            return response()->json([
                'mensagem' => 'Erro ao excluir produto do carrinho.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de esvaziar carrinho
    public function esvaziarCarrinho(Request $r, $id) {
        
        try {//Testa se tem exceção

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;

            //Verifica se o id informado é numérico e se existem itens no carrinho
            if (!is_numeric($id) || !Carrinho::where('id_vendedor', $id)
                ->where('id_cliente', $cliente->id)
                ->exists()) {
                return response()->json([
                    'mensagem' => 'Nenhum registro encontrado.'
                ], 404);
            }

            //Obtém o vendedor
            $vendedor = Vendedor::find($id);

            //Caso o usuário, cliente ou vendedor não sejam encontrados
            if (!$user || !$cliente || !$vendedor) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário ou a loja.'
                ], 404);
            }

            //Obtém os registros do carrinho para o cliente e vendedor específicos
            $carrinhos = Carrinho::where('id_vendedor', $id)
                ->where('id_cliente', $cliente->id)
                ->get();

            DB::beginTransaction();//IComeça a transação com o banco

            foreach ($carrinhos as $carrinho) {//Para cada registro, vai apagando e devolvendo para o estoque

                //Verifica se o status é "Reservado."
                if ($carrinho->status == "Reservado.") {
                   
                    //Devolve para o estoque
                    $produto = Produto::find($carrinho->id_produto);
                    $produto->qtde_estoque += $carrinho->qtde;
                    $produto->save();

                    //Deleta o item do carrinho
                    $carrinho->delete();
                }
            }

            DB::commit();//Dá o commit nas alterações

            return response()->json([//Envia mensagem de sucesso
                'mensagem' => 'Carrinho esvaziado com sucesso.'
            ], 200);

        } catch (Exception $e) {//Envia mensagem de erro em caso de exceção

            DB::rollback(); // Reverte a transação em caso de erro

            return response()->json([
                'mensagem' => 'Erro ao esvaziar o carrinho.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    public function listarCarrinho (Request $r, $id) {

        try {

        } catch (Exception $e) {

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao excluir do carrinho.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    public function finalizarCarrinho (Request $r, $id) {

        try {

        } catch (Exception $e) {

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao excluir do carrinho.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }
}
