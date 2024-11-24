<?php

//Namespace
namespace App\Jobs;

//Namespaces utilizados
use App\Events\RecusadoPedidoLojaEvent;
use App\Mail\RecusadoPedidoLojaMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

//Classe job para envio do email caso loja recuse pedido de cliente
class RecusadoPedidoLojaJob implements ShouldQueue
{
    use Queueable;

    //Quantidade de tentativas em caso de erro
    public $tries = 5;

    //Atributos
    private $emailCliente;
    private $nomeCliente;
    private $nomeLoja;
    private $telefoneLoja;

    //Método construtor
    public function __construct($emailCliente, $nomeLoja, $nomeCliente, $telefoneLoja)
    {
        $this->emailCliente = $emailCliente;
        $this->nomeLoja = $nomeLoja;
        $this->nomeCliente = $nomeCliente;
        $this->telefoneLoja = $telefoneLoja;
    }

    //Executa o job
    public function handle(): void
    {
        Mail::to($this->emailCliente)->queue(new RecusadoPedidoLojaMail($this->nomeCliente, $this->nomeLoja, $this->telefoneLoja));
    }
}
