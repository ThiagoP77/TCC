<?php

//Namespace
namespace App\Service;

//Namespaces utilizados
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

//Classe para validar o código enviado ao usuário via email para mudar senha
class ValidarCodigoService
{

    //Função de validar o código
    public function validarCodigo($email, $codigo): array
    {

        $tokensReset = DB::table('password_reset_tokens')->where('email', $email)->first();//Recebe o token de reset da tabela que corresponde ao email informado

        if(!$tokensReset){//Envia mensagem de erro caso não exista token correspondente 
            return [
                'status' => false,
                'message' => 'Código não encontrado!',
            ];
        }


        if(!Hash::check($codigo, $tokensReset->token)){//Descodifica o código e compara. Caso não sejam iguais, retorna mensagem de erro

            return [
                'status' => false,
                'message' => 'Código inválido!',
            ];
        }

        $tempoPassado = Carbon::parse($tokensReset->created_at)->diffInMinutes(Carbon::now());//Tempo entre a criação do código e o tempo atual

        if($tempoPassado > 60){//Envia mensagem de erro caso tenha passado uma hora de envio

            return [
                'status' => false,
                'message' => 'Código expirado!',
            ];

        }

        return [//Retorna sucesso
            'status' => true,
            'message' => 'Código válido!',
        ];

        
    }

}