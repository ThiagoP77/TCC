<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AceitoAEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    
    public $email;
    public $nome;
    public $funcao;
    

    /**
     * Create a new event instance.
     */
    public function __construct( $email,  $nome,  $funcao)
    {
        
        $this->email = $email;
        $this->nome = $nome;
        $this->funcao = $funcao;
        
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
