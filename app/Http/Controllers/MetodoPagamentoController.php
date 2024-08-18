<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\MetodoPagamento;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//Classe de controle para "metodos_pagamentos"
class MetodoPagamentoController extends Controller
{

    //Função de listar os metodos de pagamento
    public function metodosPagamentos(): JsonResponse {
        try {//Verfiica exceção
            $met = MetodoPagamento::select('id', 'nome')->orderBy('id')->get();//Recebe os metodos de pagamento
            return response()->json($met, 200);//Retorno de sucesso com o json
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao carregar os metodos de pagamento.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

}
