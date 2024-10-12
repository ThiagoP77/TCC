<?php

//Namespace
namespace App\Listeners;

//Namespaces utilizados
use App\Events\AceitoAEvent;
use App\Mail\AceitoAMail;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

//Classe que lida com os enventos de AceitoA
class AceitoAListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    //Construtor
    public function __construct()
    {
        //
    }

    //Função de lidar com o evento
    public function handle(AceitoAEvent $event): void
    {
        try {//Tenta enviar o email e, caso não consiga, não afeta o resto da aplicação
            Mail::to($event->email)->send(new AceitoAMail($event->nome, $event->funcao));
        } catch (Exception $e) {
            
        }
    }
}
