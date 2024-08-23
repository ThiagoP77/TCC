<?php

//Namespaces Utilizados
use App\Http\Controllers\VerifyEmailController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

/*
Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify', function () {
    return response()->json([
        'message' => 'Verifique seu email antes de tentar acessar essa página.'
    ], 400);
})->middleware('auth:sanctum')->name('verification.notice');
*/

//Rota utilizada para enviar o email de verificação
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed'])->name('verification.verify');
