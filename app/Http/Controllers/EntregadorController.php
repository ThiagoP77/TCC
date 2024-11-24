<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados

use App\Models\Api\Entregador;
use App\Models\Api\Pedido;
use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//Classe de controle de "entregadores"
class EntregadorController extends Controller
{
    
    //Função de listar os entregadores que ainda não foram aceitos no site para os admins
    public function entregadoresAguardandoAceitar(): JsonResponse {
        try {//Testa erro

            //Código que lista usuários entregadores (id_categoria 4) não aceitos (aceito_admin 0), incluindo dados do entregador e do tipo de veiculo
            $ent = Usuario::where('id_categoria', 4)
              ->where('aceito_admin', 0)

              ->with(['entregador' => function($query) {
                  $query->select('id', 'id_usuario', 'telefone', 'placa', 'id_tipo_veiculo')
                        ->with('tipoVeiculo:id,nome');
              }])

              ->select('id', 'nome', 'email', 'cpf', 'foto_login')
              ->orderBy('id')
              ->get();

            return response()->json($ent, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os entregadores que aguardam aceitação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de listar entregadores
    public function listarEntregadores () {
        try {//Testa erro

            //Código que lista os entregadores
            $e = Usuario::where('id_categoria', 4)
            ->where('aceito_admin', 1)

            ->with(['entregador' => function($query) {
                $query->select('id', 'id_usuario', 'telefone', 'placa', 'id_tipo_veiculo')
                      ->with('tipoVeiculo:id,nome');
            }])

            ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'status')
            ->orderBy('id')
            ->get();

            return response()->json($e, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os entregadores.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de listar entregadores
    public function listarEntregadoresPesquisa (Request $r) {
        try {//Testa erro

            $q = null;//Define a query como null

            if ($r->has('query')) {//Se tiver chave definida, a query recebe seu valor
                $requestData = $r->all();

                $q =  $requestData['query'];
            }

            //Código que lista os entregadores
            $e = Usuario::where('id_categoria', 4)
            ->where('aceito_admin', 1)

            ->where(function($query) use ($q) { // Início do agrupamento
                $query->where('nome', 'like', "%$q%") // Filtro por nome
                    ->orWhere('email', 'like', "%$q%"); // Filtro por email
            })

            ->with(['entregador' => function($query) {
                $query->select('id', 'id_usuario', 'telefone', 'placa', 'id_tipo_veiculo')
                      ->with('tipoVeiculo:id,nome');
            }])

            ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'status')
            ->orderBy('id')
            ->get();

            return response()->json($e, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os entregadores.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Rota de mostrar o número de pedidos finalizados por um entregador
    public function numeroEntrega ($id) {

        try {//Testa se tem exceção

            //Obtém o entregador pelo ID fornecido
            $entregador = Entregador::find($id); 

            //Caso o entregador não seja encontrado
            if (!$entregador) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o entregador informado.'
                ], 404);
            }

            //Recupera a contagem de pedidos finalizados por ele 
            $pedidos = Pedido::where('id_entregador', $entregador->id)
            ->where('status', 'Entregue.')
            ->count();

            //Envia mensagem de sucesso
            return response()->json([
                'quantidade' => $pedidos,
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                return response()->json([
                    'mensagem' => 'Erro ao informar a quantidade de entregas finalizadas.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

}
