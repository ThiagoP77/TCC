<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Mail\EsqueceuSenhaMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

//Classe com o job responsável por enviar o email de recuperação de senha ao usuário
class RecuperarSenhaJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas
    public $tries = 5;

    //Atributos
    private $u;
    private $codigo;
    private $data;
    private $tempo;

    //Método construtor
    public function __construct($u, $codigo, $data, $tempo)
    {
        $this->u = $u;
        $this->codigo = $codigo;
        $this->data = $data;
        $this->tempo = $tempo;
    }

    //Tarefa realizada
    public function handle(): void
    {
        Mail::to($this->u->email)->queue(new EsqueceuSenhaMail($this->u, $this->codigo, $this->data, $this->tempo));
    }
}
