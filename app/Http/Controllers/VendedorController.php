<?php

namespace App\Http\Controllers;

use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendedorController extends Controller
{

    public function vendedoresAguardandoAceitar(): JsonResponse {
        try {

            $vend = Usuario::where('id_categoria', 3)
              ->where('aceito_admin', 0)
              ->with(['vendedor:id_usuario,telefone,whatsapp,endereco,cnpj'])
              ->select('id', 'nome', 'email', 'cpf', 'foto_login')
              ->orderBy('id')
              ->get();

            return response()->json($vend, 200);

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao carregar os vendedores que aguardam aceitação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

}
