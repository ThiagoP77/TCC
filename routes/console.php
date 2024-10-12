<?php

//Namespaces utilizados
use App\Services\ExcluirTokensExpiradosService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
*/

//Comando agendado de limpar os tokens expirados todos os dias às 23:00
Schedule::call(new ExcluirTokensExpiradosService)->daily()->at('23:00');