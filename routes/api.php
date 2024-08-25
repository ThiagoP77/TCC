<?php

//Namespaces utilizados
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\CategoriaUsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EnderecoClienteController;
use App\Http\Controllers\EntregadorController;
use App\Http\Controllers\MetodoPagamentoController;
use App\Http\Controllers\TipoVeiculoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\VerifyEmailController;
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

//Informa a média de avaliações de uma loja e a quantidade de pessoas que avaliou
Route::get('/avaliacoesLoja/{id_loja}', [AvaliacaoController::class, 'mediaAvaliacao'])->middleware(['auth:sanctum']);

//Rota de listar os vendedores do site
Route::get('/listarVendedores', [VendedorController::class, 'listarVendedores'])->middleware(['auth:sanctum']);

//Rotas com funções básicas de usuário
Route::prefix('usuarios')->group(function () {

    //Rotas de manipulação de usuários
    Route::post('/cadastro', [UsuarioController::class, 'cadastro']);//Realizar cadastro de novo usuário
    Route::get('/dadosUsuario/{id}', [UsuarioController::class, 'dadosUsuario'])->middleware(['auth:sanctum']);//Pegar dados do usuário
    Route::get('/fotoUsuario/{id}', [UsuarioController::class, 'fotoUsuario'])->middleware(['auth:sanctum']);//Pegar foto do usuário

    //Rotas do sistema de login
    Route::post('/login', [UsuarioController::class, 'login'])->name('login');//Logar no site

    //Rotas de resetar senha
    Route::post('/esqueceu-senha', [UsuarioController::class, 'esqueceuSenha']);//Realizar cadastro de novo usuário
    Route::post('/validar-codigo', [UsuarioController::class, 'validarCodigo']);//Realizar cadastro de novo usuário
    Route::post('/resetar-senha', [UsuarioController::class, 'resetarSenha']);//Realizar cadastro de novo usuário

    //Rotas de verificação de email
    Route::post('/reenviar-verificar-email', [VerifyEmailController::class, 'resendNotification']);//Reenviar o email de verificação
});

//Rotas utilizadas por usuários admin
Route::prefix('admins')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {

    //Rotas de aceitar ou não vendedor e entregador no site
    Route::get('/entregadoresAguardando', [EntregadorController::class, 'entregadoresAguardandoAceitar']);//Lista os entregadores ainda não aceitos no site
    Route::get('/vendedoresAguardando', [VendedorController::class, 'vendedoresAguardandoAceitar']);//Lista os vendedores ainda não aceitos no site
    Route::put('/aceitarAdmin/{id}', [UsuarioController::class, 'aceitarAdmin']);//Aceita o vendedor ou entregador correspondente ao id inserido
    Route::delete('/recusarAdmin/{id}', [UsuarioController::class, 'recusarAdmin']);//Rejeita o vendedor ou entregador correspondente ao id inserido, além de excluir seus dados

    //Rota de exclusão de usuário
    Route::delete('/excluirUsuario/{id}', [UsuarioController::class, 'excluirUsuario']);//Excluir usuário não admin

    //Rotas de listar tipo de usuário
    Route::get('/listarClientes', [ClienteController::class, 'listarClientes']);//Lista os clientes do site
    Route::get('/listarEntregadores', [EntregadorController::class, 'listarEntregadores']);//Lista os entregadores do site
});

//Rotas utilizadas por usuários cliente
Route::prefix('clientes')->middleware(['auth:sanctum', 'abilities:cliente'])->group(function () {

    //Rotas voltadas para manipulação de endereços
    Route::get('/listarEnderecos', [EnderecoClienteController::class, 'listarEnderecos']);//Lista os endereços do cliente logado
    Route::get('/mostrarEndereco/{id_endereco}', [EnderecoClienteController::class, 'mostrarEndereco']);//Mostra o endereço de id fornecido
    Route::post('/adicionarEndereco', [EnderecoClienteController::class, 'adicionarEndereco']);//Adiciona um novo endereço ao cliente
    Route::put('/alterarEndereco/{id_endereco}', [EnderecoClienteController::class, 'alterarEndereco']);//Altera o endereço do cliente
    Route::delete('/excluirEndereco/{id_endereco}', [EnderecoClienteController::class, 'excluirEndereco']);//Exclui o endereço do cliente

    //Rotas voltadas para manipulação de avaliações
    Route::post('/avaliarLoja', [AvaliacaoController::class, 'avaliarLoja']);//Cria uma nova avaliação para a loja ou altera uma já existente
    Route::delete('/excluirAvaliacao/{id_loja}', [AvaliacaoController::class, 'excluirAvaliacao']);//Exclui avaliação de loja
    Route::get('/verificarAvaliacao/{id_loja}', [AvaliacaoController::class, 'verificarAvaliacao']);//Verifica se avaliou a loja
});

//Modelo de teste  
/*
Route::get('/orders', function () {
        if (Storage::disk('public')->exists('imagens_usuarios/Imagem_Admin_Bernardo.jpg')){
            return response()->json([
                'message' => 'Deu certo.'
            ], 200); 
        } else {
            return response()->json([
                'message' => 'Deu erro.'
            ], 500); 
        }
})->middleware(['auth:sanctum', 'abilities:cliente']);
*/