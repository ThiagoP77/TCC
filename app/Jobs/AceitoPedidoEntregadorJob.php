<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Mail\AceitoPedidoEntregadorMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

//Classe de job para envio de email quando o entregador aceita um pedido
class AceitoPedidoEntregadorJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas em caso de erro
    public $tries = 5;

    //Atributos
    private $emailCliente;
    private $nomeCliente;
    private $nomeEntregador;
    private $telefoneEntregador;
    private $nomeLoja;


    //Método construtor
    public function __construct($emailCliente, $nomeEntregador, $telefoneEntregador, $nomeCliente, $nomeLoja)
    {
        $this->emailCliente = $emailCliente;
        $this->nomeEntregador = $nomeEntregador;
        $this->telefoneEntregador = $telefoneEntregador;
        $this->nomeCliente = $nomeCliente;
        $this->nomeLoja = $nomeLoja;
    }

    //Executa job
    public function handle(): void
    {
        Mail::to($this->emailCliente)->queue(new AceitoPedidoEntregadorMail($this->nomeCliente, $this->nomeEntregador, $this->telefoneEntregador, $this->nomeLoja));
    }
}
