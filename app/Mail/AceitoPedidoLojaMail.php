<?php

//Namespace
namespace App\Mail;

///Namespaces utilizados
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

///Classe para envio de emails no caso de um vendedor aceitar o pedido do usuário
class AceitoPedidoLojaMail extends Mailable
{
    use Queueable, SerializesModels;

    //Método construtor que recebe os parâmetros necessários para envio de email
    public function __construct(public $nomeCliente, public $nomeLoja)
    {
        
    }

    //Definindo o assunto do email
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido Aceito pela Loja - LA Doceria',
        );
    }

    //Definindo o caminho da view que deve ser mostrada no emaill
    public function content(): Content
    {
        return new Content(
            view: 'emails.pedidoaceitoloja',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
