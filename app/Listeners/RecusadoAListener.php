<?php

//Namespace
namespace App\Listeners;

//Namespaces utilizados
use App\Events\RecusadoAEvent;
use App\Mail\RecusadoAMail;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

//Classe que lida com os enventos de RecusadoA
class RecusadoAListener
{
    
    //Construtor
    public function __construct()
    {
        //
    }

    //Função de lidar com o evento
    public function handle(RecusadoAEvent $event): void
    {
        try {//Tenta enviar o email e, caso não consiga, não afeta o resto da aplicação
            Mail::to($event->email)->send(new RecusadoAMail($event->nome, $event->funcao));
        } catch (Exception $e) {
            
        }
    }
}
