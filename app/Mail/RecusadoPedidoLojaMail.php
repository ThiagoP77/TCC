<?php

//Namespace
namespace App\Mail;

//Namespaces utillizados
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

//Classe para envio de emaill no caso de uma loja recusar o pedido de um cliente
class RecusadoPedidoLojaMail extends Mailable
{
    use Queueable, SerializesModels;

    //Método construtor que recebe os dados necessários para envio de email
    public function __construct(public $nomeCliente, public $nomeLoja, public $telefoneLoja)
    {
        
    }

    //Definindo o assunto do email
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido Recusado pela Loja - LA Doceria',
        );
    }

    ///Definindo onde se encontra a view que deve ser mostrada
    public function content(): Content
    {
        return new Content(
            view: 'emails.pedidorecusadoloja',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
