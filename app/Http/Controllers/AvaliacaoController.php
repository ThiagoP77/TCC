<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Avaliacao;
use App\Models\Api\Vendedor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

//Classe controladora de avaliação
class AvaliacaoController extends Controller
{
    //Função de avaliar loja
    public function avaliarLoja(Request $r) {

        try {//Testa se tem exceção

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'id_loja' => [
                    'required', 
                    'integer'
                ],

                'nota' => [
                    'required', 
                    'numeric', 
                    'between:0,5'
                ]
            ], [

                'id_loja.required' => 'O ID da loja é obrigatório.',
                'id_loja.integer' => 'O ID da loja deve ser um número inteiro.',

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
            $u = $r->user(); 

            //Obtém o cliente
            $cliente = $u->cliente;

            //Instância do vendedor de id fornecido
            $vend = Vendedor::find($dadosValidados['id_loja']);

             //Caso o usuário, cliente ou loja não sejam encontrados, envia mensagem de erro
             if (!$u || !$cliente) {
                return response()->json([
                    'error' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }

            //Verifica se o vendedor existe e, caso não, envia mensagem de erro
            if (!$vend) {
                return response()->json([
                    'error' => 'Falha ao encontrar o vendedor.',
                ], 404);
            }

            //Verifica se já existe avaliação desse cliente para esse vendedor
            $avaliacaoExistente = Avaliacao::where('id_cliente', $cliente->id)
                ->where('id_vendedor', $vend->id)
                ->exists();

            //Se sim, atualiza
            if ($avaliacaoExistente) {

                //Acha a avaliação existente e atualiza a nota dada
                Avaliacao::where('id_cliente', $cliente->id)
                ->where('id_vendedor', $vend->id)
                ->update(['avaliacao' => $dadosValidados['nota']]);

                //Envia mensagem de sucesso
                return response()->json([
                    'message' => 'Avaliação atualizada com sucesso.'
                ], 200);

            } else {//Caso não exista, cria uma nova

                //Criando e informando os dados
                $avaliacao = new Avaliacao();
                $avaliacao->id_cliente = $cliente->id;
                $avaliacao->id_vendedor = $vend->id;
                $avaliacao->avaliacao = $dadosValidados['nota'];
                $avaliacao->save();//Salvando no banco
    
                //Envia mensagem de sucesso
                return response()->json([
                    'message' => 'Avaliação registrada com sucesso.'
                ], 200);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                return response()->json([
                    'mensagem' => 'Erro ao avaliar a loja.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Função de excluir avaliação
    public function excluirAvaliacao(Request $r, $id) {
        
        try{//Testa se tem exceção

            //Obtém o usuário autenticado
            $u = $r->user(); 

            //Obtém o cliente
            $cliente = $u->cliente;
            
            //Instancia do vendedor pelo id informado
            $vend = Vendedor::find($id);
            
            //Caso o usuário, cliente ou loja não sejam encontrados, envia mensagem de erro
            if (!$u || !$cliente) {
                return response()->json([
                    'error' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }
            
            //Verifica se o vendedor existe e, caso não, envia mensagem de erro
            if (!$vend) {
                return response()->json([
                    'error' => 'Falha ao encontrar o vendedor.',
                ], 404);
            }
            
            //Verifica se a avaliação existe
            $avaliacaoExistente = Avaliacao::where('id_cliente', $cliente->id)
                ->where('id_vendedor', $vend->id)
                ->exists();
            
            //Caso exista, exclui
            if ($avaliacaoExistente) {
            
                //Excluindo
                Avaliacao::where('id_cliente', $cliente->id)
                            ->where('id_vendedor', $vend->id)
                            ->delete();
            
                //Envia mensagem de sucesso
                return response()->json([
                    'message' => 'Avaliação excluída com sucesso.'
                ], 200);
            
            } else {//Caso não exista, envia mensagem de erro
            
                return response()->json([
                    'mensagem' => 'Você ainda não avaliou esse vendedor.'
                ], 400);
            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Erro ao excluir avaliação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Verifica se avaliação foi feita
    public function verificarAvaliacao(Request $r, $id) {
        
        try{//Testa se tem exceção

            //Obtém o usuário autenticado
            $u = $r->user(); 

            //Obtém o cliente
            $cliente = $u->cliente;
            
            //Instancia do vendedor pelo id informado
            $vend = Vendedor::find($id);
            
            //Caso o usuário, cliente ou loja não sejam encontrados, envia mensagem de erro
            if (!$u || !$cliente) {
                return response()->json([
                    'error' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }
            
            //Verifica se o vendedor existe e, caso não, envia mensagem de erro
            if (!$vend) {
                return response()->json([
                    'error' => 'Falha ao encontrar o vendedor.',
                ], 404);
            }
            
            //Verifica se a avaliação existe
            $avaliacaoExistente = Avaliacao::where('id_cliente', $cliente->id)
                ->where('id_vendedor', $vend->id)
                ->exists();
            
            //Se existir, status true
            if ($avaliacaoExistente) {
            
                //Pega a nota dada
                $avaliacao = Avaliacao::where('id_cliente', $cliente->id)
                ->where('id_vendedor', $vend->id)
                ->pluck('avaliacao');

                //Pega o primeiro valor do Array
                $avaliacaoString = $avaliacao->get(0);

                //Mensagem de tru caso exista
                return response()->json([
                    'status' => true,
                    'nota' => $avaliacaoString
                ], 200);
            
            } else {//Se não existir, status false
            
                //Mensagem de false caso não exista
                return response()->json([
                    'status' => false
                ], 400);
            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Erro ao verificar avaliação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de fornecer a média de avaliação da loja
    public function mediaAvaliacao ($id) {

        try {//Testa se tem exceção

            //Instancia do vendedor pelo id informado
            $vend = Vendedor::find($id);
            
            //Verifica se o vendedor existe e, caso não, envia mensagem de erro
            if (!$vend) {
                return response()->json([
                    'message' => 'Falha ao encontrar o vendedor.',
                ], 404);
            }

            //Obter as avaliações do vendedor
            $avaliacoes = Avaliacao::where('id_vendedor', $id);

            //Contar o número de avaliações
            $quantidade = $avaliacoes->count();

            //Verificar se há pelo menos uma avaliação
            if ($quantidade > 0) {

                //Calcular a média das avaliações
                $media = $avaliacoes->avg('avaliacao');

                //Formatando a média com uma casa decimal
                $media = number_format($media, 1);

            } else {

                // Se não há avaliações, definir média como null ou outro valor apropriado
                $media = 0;

            }
            
            //Retornar os dados em formato JSON
            return response()->json([
                'media' => $media,
                'quantidade' => $quantidade
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Erro ao mostrar avaliação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }
}

