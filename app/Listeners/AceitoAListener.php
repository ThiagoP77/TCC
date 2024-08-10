<?php

namespace App\Listeners;

use App\Events\AceitoAEvent;
use App\Mail\AceitoAMail;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class AceitoAListener
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
    public function handle(AceitoAEvent $event): void
    {
        try {
            Mail::to($event->email)->send(new AceitoAMail($event->nome, $event->funcao));
        } catch (Exception $e) {
            
        }
    }
}
