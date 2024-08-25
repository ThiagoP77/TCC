<?php

//Namespace
namespace App\Services;

//Namespaces utilizados
use Illuminate\Support\Facades\DB;

//Classe para excluir tokens expirados
class ExcluirTokensExpiradosService {

    //Função de excluir os tokens expirados
    public function excluirTokensExpirados() 
    {

        //Obtém a data atual
        $dataAtual = now();

        //Remove os tokens que já expiraram
        DB::table('personal_access_tokens')
            ->where('expires_at', '<', $dataAtual)
            ->delete();
 
    }

}