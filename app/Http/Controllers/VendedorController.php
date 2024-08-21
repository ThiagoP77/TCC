<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//Classe de controle de "vendedores"
class VendedorController extends Controller
{

    //Função de listar os vendedores que ainda não foram aceitos no site para os admins
    public function vendedoresAguardandoAceitar(): JsonResponse {
        try {//Testa erro

            //Código que lista usuários vendedores (id_categoria 3) não aceitos (aceito_admin 0), incluindo dados do vendedor e da sua tabela de endereço
            $vend = Usuario::where('id_categoria', 3)
              ->where('aceito_admin', 0)

              ->with(['vendedor' => function($query) {
                $query->select('id','id_usuario', 'telefone', 'whatsapp', 'cnpj', 'descricao')
                      ->with('endereco:id_vendedor,cep,logradouro,bairro,localidade,uf,numero');
                }])

              ->select('id', 'nome', 'email', 'cpf', 'foto_login')
              ->orderBy('id')
              ->get();

            return response()->json($vend, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao carregar os vendedores que aguardam aceitação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

}
