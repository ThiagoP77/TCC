<?php

namespace App\Http\Controllers;

use App\Models\Api\Usuario;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntregadorController extends Controller
{
    
    public function entregadoresAguardandoAceitar(): JsonResponse {
        try {

            $ent = Usuario::where('id_categoria', 4)
              ->where('aceito_admin', 0)

              ->with(['entregador' => function($query) {
                  $query->select('id_usuario', 'telefone', 'placa', 'id_tipo_veiculo')
                        ->with('tipoVeiculo:id,nome');
              }])

              ->select('id', 'nome', 'email', 'cpf', 'foto_login')
              ->orderBy('id')
              ->get();

            return response()->json($ent, 200);

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao carregar os entregadores que aguardam aceitação.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

}
