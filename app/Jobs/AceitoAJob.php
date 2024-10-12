<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Events\AceitoAEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//Classe com o job responsável por enviar um email de aceito ao usuário
class AceitoAJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas em caso de erro
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

    //Tarefa que deve ser executada
    public function handle(): void
    {
        event(new AceitoAEvent($this->email, $this->nome, $this->funcao));
    }
}
