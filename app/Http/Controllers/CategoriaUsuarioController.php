<?php

namespace App\Http\Controllers;

use App\Models\Api\CategoriaUsuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaUsuarioController extends Controller
{
    
    public function categoriasUsuarios(): JsonResponse { 
        try {
            $cat = CategoriaUsuario::select('id', 'nome')->where('id', '!=', 1)->orderBy('id')->get();
            return response()->json($cat, 200);
        } catch (Exception $e) {
            return response()->json([
                'mensagem' => 'Falha ao carregar as categorias de usuário.',
                'erro' => $e->getMessage()
            ], 400);
        }
        
    }

}
