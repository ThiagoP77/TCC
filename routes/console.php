<?php

//Namespaces utilizados
use App\Services\ExcluirTokensExpiradosService;
use App\Services\LimparCarrinhosService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
*/

//Comando agendado de limpar os tokens expirados todos os dias às 23:00
Schedule::call(new ExcluirTokensExpiradosService)->daily()->at('23:00')->timezone('America/Sao_Paulo');;

//Comando agendado para excluir os registros expirados na tabela de carrinho
Schedule::call(new LimparCarrinhosService)->everyTenMinutes()->name('limpar.carrinhos')->withoutOverlapping();