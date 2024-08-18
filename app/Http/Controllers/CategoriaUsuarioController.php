<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\CategoriaUsuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//Classe de controle para "categorias_usuarios"
class CategoriaUsuarioController extends Controller
{
    
    //Função de listar as categorias de usuário
    public function categoriasUsuarios(): JsonResponse { 
        try {//Verfiica exceção
            $cat = CategoriaUsuario::select('id', 'nome')->where('id', '!=', 1)->orderBy('id')->get();//Recebe as categorias (exceto a de admin)
            return response()->json($cat, 200);//Retorno de sucesso com o json
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao carregar as categorias de usuário.',
                'erro' => $e->getMessage()
            ], 400);
        }
        
    }

}
