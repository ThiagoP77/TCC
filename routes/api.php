<?php

//Namespaces utilizados
use App\Http\Controllers\CategoriaUsuarioController;
use App\Http\Controllers\CepController;
use App\Http\Controllers\EntregadorController;
use App\Http\Controllers\MetodoPagamentoController;
use App\Http\Controllers\TipoVeiculoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\VerifyEmailController;
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
    Route::post('/login', [UsuarioController::class, 'login'])->name('login');//Logar no site
    Route::delete('/logout/{id}', [UsuarioController::class, 'logout'])->middleware(['auth:sanctum']);//Deslogar do site (precisa logar primeiro, obviamente)
    Route::post('/esqueceu-senha', [UsuarioController::class, 'esqueceuSenha']);//Realizar cadastro de novo usuário
    Route::post('/validar-codigo', [UsuarioController::class, 'validarCodigo']);//Realizar cadastro de novo usuário
    Route::post('/resetar-senha', [UsuarioController::class, 'resetarSenha']);//Realizar cadastro de novo usuário
    Route::post('/reenviar-verificar-email', [VerifyEmailController::class, 'resendNotification']);//Reenviar o email de verificação
});

//Rotas utilizadas por usuários admin
Route::prefix('admins')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/entregadoresAguardando', [EntregadorController::class, 'entregadoresAguardandoAceitar']);//Lista os entregadores ainda não aceitos no site
    Route::get('/vendedoresAguardando', [VendedorController::class, 'vendedoresAguardandoAceitar']);//Lista os vendedores ainda não aceitos no site
    Route::put('/aceitaradmin/{id}', [UsuarioController::class, 'aceitarAdmin']);//Aceita o vendedor ou entregador correspondente ao id inserido
    Route::delete('/recusaradmin/{id}', [UsuarioController::class, 'recusarAdmin']);//Rejeita o vendedor ou entregador correspondente ao id inserido, além de excluir seus dados
});

//Modelo de teste  
/*
Route::get('/orders', function () {
        return response()->json([
            'message' => 'Deu certo.'
        ], 200); 
})->middleware(['auth:sanctum']);
*/
