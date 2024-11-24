<?php

//Namespace
namespace App\Mail;

//Namespaces utilizados
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

//Classe de email para quando o entregador marca pedido como entregue
class EntregueMail extends Mailable
{
    use Queueable, SerializesModels;

    //Método construtor
    public function __construct(public $nomeCliente,  public $telefoneLoja, public $nomeLoja)
    {
        
    }

    //Determina o assunto do email
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido Entregue - LA Doceria',
        );
    }

    //Determina a view a ser exibida no email
    public function content(): Content
    {
        return new Content(
            view: 'emails.pedidoentregue',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
