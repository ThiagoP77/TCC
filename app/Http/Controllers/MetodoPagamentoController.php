<?php

namespace App\Http\Controllers;

use App\Models\Api\MetodoPagamento;
use Illuminate\Http\Request;

class MetodoPagamentoController extends Controller
{

    public function metodosPagamentos() {
        $met = MetodoPagamento::select('id', 'nome')->get();
        return response()->json($met);
    }

}
