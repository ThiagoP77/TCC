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

//Classe de email de aceitação de usuário
class EsqueceuSenhaMail extends Mailable
{
    use Queueable, SerializesModels;

    //Metodo construtor com os elementos necessários
    public function __construct(public $u, public $codigo, public $data, public $tempo)
    {
        //
    }

    //Função com o assunto do email
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperar Senha - LA Doceria',
        );
    }

    //Função que determina a view correspondente ao conteúdo deste email
    public function content(): Content
    {
        return new Content(
            view: 'emails.recuperarsenha',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
