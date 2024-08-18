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

//Classe de email de recusa de usuário
class AceitoAMail extends Mailable
{
    use Queueable, SerializesModels;

    //Atributos utilizados na montagem da mensagem do email
    public $nome;
    public $funcao;

    //Metodo construtor com os atributos necessários
    public function __construct($nome, $funcao)
    {
        $this->nome = $nome;
        $this->funcao = $funcao;
    }

    //Função com o assunto do email
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Aceito - LA Doceria',
        );
    }

    //Função que determina a view correspondente ao conteúdo deste email
    public function content(): Content
    {
        return new Content(
            view: 'emails.aceitoa',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
