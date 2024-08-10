<?php

namespace App\Http\Controllers;

use App\Models\Api\MetodoPagamento;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetodoPagamentoController extends Controller
{

    public function metodosPagamentos(): JsonResponse {
        try {
            $met = MetodoPagamento::select('id', 'nome')->orderBy('id')->get();
            return response()->json($met, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensagem' => 'Falha ao carregar os metodos de pagamento.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

}
