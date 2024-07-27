<?php

use App\Http\Controllers\CategoriaUsuarioController;
use App\Http\Controllers\MetodoPagamentoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/users', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

//Rota para exibir todas as categorias de usuario (id e nome) no cadastro
Route::get('/categoriasusuario', [CategoriaUsuarioController::class, 'categoriasUsuarios']);

//Rota para exibir todos os metodos de pagamento (id e nome) ao finalizar o carrinho
Route::get('/metodospagamento', [MetodoPagamentoController::class, 'metodosPagamentos']);

//Rotas com funções básicas de usuário
Route::prefix('usuarios')->group(function () {
    Route::post('/cadastro', [UsuarioController::class, 'cadastro']);//Realizar cadastro de novo usuário
    Route::post('/login', [UsuarioController::class, 'login']);//Logar no site
    Route::delete('/logout', [UsuarioController::class, 'logout']);//Deslogar do site
});