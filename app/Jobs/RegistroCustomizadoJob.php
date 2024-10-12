<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Events\RegistroCustomizadoEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//Classe com o job responsável por enviar o email de verificação ao usuário
class RegistroCustomizadoJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas
    public $tries = 5;

    //Atributo
    private $u;

    //Método construtor
    public function __construct($u)
    {
        $this->u = $u;
    }

    //Tarefa realizada
    public function handle(): void
    {
        event(new RegistroCustomizadoEvent($this->u));
    }
}
