<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Events\RecusadoAEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//Classe job responsável por enviar o email de recusado ao usuário
class RecusadoAJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas
    public $tries = 5;

    //Atributos
    private $email;
    private $nome;
    private $funcao;

    //Método construtor
    public function __construct($email, $nome, $funcao)
    {
        $this->email = $email;
        $this->nome = $nome;
        $this->funcao = $funcao;
    }

    //Tarefa que deve ser realizada
    public function handle(): void
    {
        event(new RecusadoAEvent($this->email, $this->nome, $this->funcao));
    }
}
