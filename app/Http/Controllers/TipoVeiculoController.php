<?php

namespace App\Http\Controllers;

use App\Models\Api\TipoVeiculo;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoVeiculoController extends Controller
{
    
    public function tiposVeiculo(): JsonResponse {
        try {
            $tipo = TipoVeiculo::select('id', 'nome')->orderBy('id')->get();
            return response()->json($tipo, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensagem' => 'Falha ao carregar os tipos de veiculo.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }
}
