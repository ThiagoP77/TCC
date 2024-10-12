<?php

//Namespace
namespace App\Events;

//Namespaces utilizados
use Illuminate\Auth\Events\Registered;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

//Classe que herda a classe de evento de registro 
class RegistroCustomizadoEvent extends Registered implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

}
