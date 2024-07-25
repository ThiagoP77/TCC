<?php

namespace App\Http\Controllers;

use App\Models\Api\CategoriaUsuario;
use Illuminate\Http\Request;

class CategoriaUsuarioController extends Controller
{
    
    public function categoriasUsuarios() {
        $cat = CategoriaUsuario::select('id', 'nome')->get();
        return response()->json($cat);
    }

}
