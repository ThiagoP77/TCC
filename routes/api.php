<?php

use App\Http\Controllers\CategoriaUsuarioController;
use App\Http\Controllers\EntregadorController;
use App\Http\Controllers\MetodoPagamentoController;
use App\Http\Controllers\TipoVeiculoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VendedorController;
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

//Rota para exibir todos os tipos de veiculo
Route::get('/tiposveiculo', [TipoVeiculoController::class, 'tiposVeiculo']);

//Rotas com funções básicas de usuário
Route::prefix('usuarios')->group(function () {
    Route::post('/cadastro', [UsuarioController::class, 'cadastro']);//Realizar cadastro de novo usuário
    Route::post('/login', [UsuarioController::class, 'login']);//Logar no site
    Route::delete('/logout', [UsuarioController::class, 'logout']);//Deslogar do site
});

Route::prefix('admins')->group(function () {
    Route::get('/entregadoresAguardando', [EntregadorController::class, 'entregadoresAguardandoAceitar']);
    Route::get('/vendedoresAguardando', [VendedorController::class, 'vendedoresAguardandoAceitar']);
    Route::put('/aceitaradmin/{id}', [UsuarioController::class, 'aceitarAdmin']);
    Route::delete('/recusaradmin/{id}', [UsuarioController::class, 'recusarAdmin']);
});
