<?php

namespace App\Listeners;

use App\Events\RecusadoAEvent;
use App\Mail\RecusadoAMail;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class RecusadoAListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RecusadoAEvent $event): void
    {
        try {
            Mail::to($event->email)->send(new RecusadoAMail($event->nome, $event->funcao));
        } catch (Exception $e) {
            
        }
    }
}
