<?php

namespace App\Service;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ValidarCodigoService
{

    public function validarCodigo($email, $code): array
    {

        $tokensReset = DB::table('password_reset_tokens')->where('email', $email)->first();

        // Verificar se encontrou o usuário no banco de dados com token de redefinição de senha
        if(!$tokensReset){

            return [
                'status' => false,
                'message' => 'Código não encontrado!',
            ];
        }


        if(!Hash::check($code, $tokensReset->token)){

            return [
                'status' => false,
                'message' => 'Código inválido!',
            ];
        }

        $tempoPassado = Carbon::parse($tokensReset->created_at)->diffInMinutes(Carbon::now());

        if($tempoPassado > 60){

            return [
                'status' => false,
                'message' => 'Código expirado!',
            ];

        }

        return [
            'status' => true,
            'message' => 'Código válido!',
        ];

        
    }

}