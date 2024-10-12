<?php

//Namespace
namespace App\Listeners;

//Namespaces utilizados
use App\Events\RegistroCustomizadoEvent;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

//Classe que herda a classe listener de registro
class RegistroCustomizadoListener implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    //Método alterado da classe pai
    public function handle(RegistroCustomizadoEvent $event)
    {
        if ($event->user instanceof MustVerifyEmail && ! $event->user->hasVerifiedEmail()) {
            $event->user->sendEmailVerificationNotification();
        }
    }
}
