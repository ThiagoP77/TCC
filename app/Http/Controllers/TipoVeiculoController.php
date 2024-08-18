<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\TipoVeiculo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//Classe de controle para "tipos_veiculos"
class TipoVeiculoController extends Controller
{
    
    public function tiposVeiculo(): JsonResponse {
        try {//Verfiica exceção
            $tipo = TipoVeiculo::select('id', 'nome')->orderBy('id')->get();//Recebe os tipos de veiculo
            return response()->json($tipo, 200);//Retorno de sucesso com o json
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao carregar os tipos de veiculo.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }
}
