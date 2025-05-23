<?php

//Namespace
namespace App\Events;

//Namespaces utilizados
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

//Classe de evento lançado ao recusar entregador ou vendedor
class RecusadoAEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    //Atributos 
    public $email;
    public $nome;
    public $funcao;

    //Construtor com atributos
    public function __construct($email, $nome, $funcao)
    {
        $this->email = $email;
        $this->nome = $nome;
        $this->funcao = $funcao;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
