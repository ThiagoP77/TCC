<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Mail\EntregueMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

//Classe de job para envio de email quando o entregador marca pedido como entregue
class EntregueJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas em caso de erro
    public $tries = 5;

    //Atributos
    private $emailCliente;
    private $nomeCliente;
    private $telefoneLoja;
    private $nomeLoja;


    //Método construtor
    public function __construct($emailCliente, $nomeCliente, $nomeLoja, $telefoneLoja)
    {
        $this->emailCliente = $emailCliente;
        $this->telefoneLoja = $telefoneLoja;
        $this->nomeCliente = $nomeCliente;
        $this->nomeLoja = $nomeLoja;
    }

    //Executa job
    public function handle(): void
    {
        Mail::to($this->emailCliente)->queue(new EntregueMail($this->nomeCliente,  $this->telefoneLoja, $this->nomeLoja));
    }
}
