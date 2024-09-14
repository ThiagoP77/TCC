<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\Request;

//Classe controladora de cliente
class ClienteController extends Controller
{
    
    //Função de listar clientes
    public function listarClientes () {
        try {//Testa erro

            //Código que lista os clientes
            $c = Usuario::where('id_categoria', 2)

              ->with(['cliente' => function($query) {
                $query->select('id','id_usuario', 'telefone');
                }])

              ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'status')
              ->orderBy('id')
              ->get();

            return response()->json($c, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os clientes.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de listar clientes
    public function listarClientesPesquisa (Request $r) {
        try {//Testa erro

            $q = null;//Define a query como null

            if ($r->has('query')) {//Se tiver chave definida, a query recebe seu valor
                $requestData = $r->all();

                $q =  $requestData['query'];
            }

            //Código que lista os clientes
            $c = Usuario::where('id_categoria', 2)
                ->where(function($query) use ($q) {//Início do agrupamento
                    $query->where('nome', 'like', "%$q%")//Filtro por nome
                        ->orWhere('email', 'like', "%$q%");//Filtro por email
                })
              ->with(['cliente' => function($query) {
                $query->select('id','id_usuario', 'telefone');
                }])

              ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'status')
              ->orderBy('id')
              ->get();

            return response()->json($c, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os clientes.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }
}
