<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    //Função de listar vendedores para o admin
    public function listarVendedoresAdmin () {
        try {//Testa erro
    
            //Código que lista os vendedores
            $v = Usuario::where('id_categoria', 3)
            ->where('aceito_admin', 1)
    
            ->with(['vendedor' => function($query) {
                $query->select('id','id_usuario', 'telefone', 'whatsapp', 'cnpj', 'descricao')
                      ->with('endereco:id_vendedor,cep,logradouro,bairro,localidade,uf,numero');
            }])
    
            ->select('id', 'nome', 'email', 'cpf', 'foto_login','status')
            ->orderBy('id')
            ->get();

            //Coletar os IDs dos vendedores
            $vendedorIds = $v->pluck('vendedor.id')->unique();

            //Consulta para obter a média e a quantidade de avaliações
            $avaliacoes = DB::table('avaliacoes')
                ->select('id_vendedor', 
                        DB::raw('IFNULL(AVG(avaliacao), 0) as media_avaliacao'), 
                        DB::raw('IFNULL(COUNT(*), 0) as quantidade_avaliacoes'))
                ->whereIn('id_vendedor', $vendedorIds)
                ->groupBy('id_vendedor')
                ->get()
                ->keyBy('id_vendedor');
            
            //Adicionar as informações de média e quantidade de avaliações aos vendedores
            $v->each(function ($usuario) use ($avaliacoes) {
                $vendedor = $usuario->vendedor;
                if ($vendedor) {
                    $avaliacao = $avaliacoes->get($vendedor->id);

                    $vendedor->avaliacoes = [
                        'media' => $avaliacao ? number_format($avaliacao->media_avaliacao, 1) : '0.0',
                        'quantidade' => $avaliacao ? $avaliacao->quantidade_avaliacoes : 0
                    ];
                }
            });
    
            return response()->json($v, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
            return response()->json([
                'mensagem' => 'Falha ao carregar as lojas.',
                'erro' => $e->getMessage()
            ], 400);
    
        }
    }

    //Função de listar vendedores
    public function listarVendedoresCliente () {
        try {//Testa erro
    
            //Código que lista os vendedores
            $v = Usuario::where('id_categoria', 3)
            ->where('aceito_admin', 1)
            ->where('status', 'ativo')
            ->with(['vendedor' => function($query) {
                $query->select('id','id_usuario', 'telefone', 'whatsapp', 'cnpj', 'descricao')
                      ->with('endereco:id_vendedor,cep,logradouro,bairro,localidade,uf,numero');
            }])
    
            ->select('id', 'nome', 'email', 'cpf', 'foto_login')
            ->orderBy('id')
            ->get();

            //Coletar os IDs dos vendedores
            $vendedorIds = $v->pluck('vendedor.id')->unique();

            //Consulta para obter a média e a quantidade de avaliações
            $avaliacoes = DB::table('avaliacoes')
                ->select('id_vendedor', 
                        DB::raw('IFNULL(AVG(avaliacao), 0) as media_avaliacao'), 
                        DB::raw('IFNULL(COUNT(*), 0) as quantidade_avaliacoes'))
                ->whereIn('id_vendedor', $vendedorIds)
                ->groupBy('id_vendedor')
                ->get()
                ->keyBy('id_vendedor');
            
            //Adicionar as informações de média e quantidade de avaliações aos vendedores
            $v->each(function ($usuario) use ($avaliacoes) {
                $vendedor = $usuario->vendedor;
                if ($vendedor) {
                    $avaliacao = $avaliacoes->get($vendedor->id);

                    $vendedor->avaliacoes = [
                        'media' => $avaliacao ? number_format($avaliacao->media_avaliacao, 1) : '0.0',
                        'quantidade' => $avaliacao ? $avaliacao->quantidade_avaliacoes : 0
                    ];
                }
            });
    
            return response()->json($v, 200);//Retorno de sucesso em json

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
            return response()->json([
                'mensagem' => 'Falha ao carregar as lojas.',
                'erro' => $e->getMessage()
            ], 400);
    
        }
    }
}
