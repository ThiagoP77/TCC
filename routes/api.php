<?php

//Namespaces utilizados
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\CategoriaUsuarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CoisasUteisController;
use App\Http\Controllers\EnderecoClienteController;
use App\Http\Controllers\EntregadorController;
use App\Http\Controllers\MetodoPagamentoController;
use App\Http\Controllers\ProdutoController;
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

//Rota que pesquisa CEP
Route::post('/procurarCEP', [CoisasUteisController::class, 'procurarCEP']);

//Informa a média de avaliações de uma loja e a quantidade de pessoas que avaliou
Route::get('/avaliacoesLoja/{id_loja}', [AvaliacaoController::class, 'mediaAvaliacao'])->middleware(['auth:sanctum']);

//Rotas gerais de produto
Route::get('/dadosProduto/{id_produto}', [ProdutoController::class, 'dadosProduto'])->middleware(['auth:sanctum']);//Pegar dados do produto por id
Route::get('/fotoProduto/{id_produto}', [ProdutoController::class, 'fotoProduto'])->middleware(['auth:sanctum']);//Pegar foto do produto por id

//Rotas com funções básicas de usuário
Route::prefix('usuarios')->group(function () {

    //Rotas de manipulação de usuários
    Route::post('/cadastro', [UsuarioController::class, 'cadastro']);//Realizar cadastro de novo usuário
    Route::get('/dadosUsuario/{id}', [UsuarioController::class, 'dadosUsuario'])->middleware(['auth:sanctum']);//Pegar dados do usuário por id
    Route::get('/perfil', [UsuarioController::class, 'exibirPerfil'])->middleware(['auth:sanctum']);//Pegar dados do usuário logado
    Route::get('/fotoUsuario/{id}', [UsuarioController::class, 'fotoUsuario'])->middleware(['auth:sanctum']);//Pegar foto do usuário
    Route::delete('/excluirFoto', [UsuarioController::class, 'excluirFoto'])->middleware(['auth:sanctum']);//Excluir foto do usuário
    Route::post('/alterarFoto', [UsuarioController::class, 'alterarFoto'])->middleware(['auth:sanctum']);//Alterar foto do usuário
    Route::put('/alterarUsuario', [UsuarioController::class, 'alterarUsuario'])->middleware(['auth:sanctum']);//Alterar foto do usuário
    Route::post('/confirmarPorSenha', [UsuarioController::class, 'confirmarPorSenha'])->middleware(['auth:sanctum']);//Alterar foto do usuário

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
    Route::delete('/mudarStatusUsuario/{id}', [UsuarioController::class, 'mudarStatus']);//Excluir usuário não admin

    //Rotas de listar tipo de usuário
    Route::get('/listarClientes', [ClienteController::class, 'listarClientes']);//Lista os clientes do site
    Route::post('/listarClientesPesquisa', [ClienteController::class, 'listarClientesPesquisa']);//Lista os clientes do site
    Route::get('/listarEntregadores', [EntregadorController::class, 'listarEntregadores']);//Lista os entregadores do site
    Route::post('/listarEntregadoresPesquisa', [EntregadorController::class, 'listarEntregadoresPesquisa']);//Lista os entregadores do site
    Route::get('/listarVendedores', [VendedorController::class, 'listarVendedoresAdmin']);//Rota de listar os vendedores do site
    Route::post('/listarVendedoresPesquisa', [VendedorController::class, 'listarVendedoresAdminPesquisa']);//Rota de listar os vendedores do site
    
    Route::get('/cardapioLoja/{id_loja}', [ProdutoController::class, 'listarProdutosLoja']);//Pegar produtos de uma loja por id
    Route::post('/cardapioLojaPesquisa/{id_loja}', [ProdutoController::class, 'listarProdutosLojaPesquisa']);//Pegar produtos de uma loja por id
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

    //Rotas para listar o que é necessário para cliente
    Route::get('/listarVendedores', [VendedorController::class, 'listarVendedoresCliente']);//Rota de listar os vendedores do site
    Route::post('/listarVendedoresPesquisa', [VendedorController::class, 'listarVendedoresClientePesquisa']);//Rota de listar os vendedores do site
    Route::get('/cardapioLoja/{id_loja}', [ProdutoController::class, 'listarProdutosLojaCliente']);//Pegar produtos de uma loja por id
    Route::post('/cardapioLojaPesquisa/{id_loja}', [ProdutoController::class, 'listarProdutosLojaClientePesquisa']);//Pegar produtos de uma loja por id

    //Rotas voltadas para manipulação dos carrinhos
    Route::post('/adicionarCarrinho', [CarrinhoController::class, 'adicionarAoCarrinho']);//Rota de adicionar produto ao carrinho
    Route::put('/modificarCarrinho', [CarrinhoController::class, 'modificarCarrinho']);//Rota de modificar quantidade de produto do carrinho
    Route::delete('/removerCarrinho/{id}', [CarrinhoController::class, 'removerDoCarrinho']);//Rota de remover produto do carrinho
    Route::delete('/esvaziarCarrinho/{id}', [CarrinhoController::class, 'esvaziarCarrinho']);//Rota de esvaziar carrinho em determinada loja
    Route::post('/finalizarCarrinho/{id}', [CarrinhoController::class, 'finalizarCarrinho']);//Rota de finalizar carrinho em determinada rota
    Route::get('/listarCarrinho/{id}', [CarrinhoController::class, 'listarCarrinho']);//Rota de listar carrinho em determinada loja
});

//Rotas utilizadas por usuários cliente
Route::prefix('vendedores')->middleware(['auth:sanctum', 'abilities:vendedor'])->group(function () {

    //Rotas de manipulação de produto
    Route::post('/cadastrarProduto', [ProdutoController::class, 'cadastrarProduto']);//Realizar cadastro de novo produto
    Route::delete('/mudarStatusProduto/{id}', [ProdutoController::class, 'mudarStatus']);//Excluir produto
    Route::delete('/excluirFotoProduto/{id}', [ProdutoController::class, 'excluirFoto']);//Excluir foto do produto
    Route::post('/alterarFotoProduto/{id}', [ProdutoController::class, 'alterarFoto']);//Alterar foto do produto
    Route::put('/alterarProduto/{id}', [ProdutoController::class, 'alterarProduto']);//Alterar dados do produto
    Route::get('/meusProdutos', [ProdutoController::class, 'listarMeusProdutos']);//Pegar produtos do vendedor cadastrado
    Route::post('/meusProdutosPesquisa', [ProdutoController::class, 'listarMeusProdutosPesquisa']);//Pegar produtos do vendedor cadastrado

    //Rotas de manipulação de desconto
    Route::post('/aplicarDesconto/{id}', [ProdutoController::class, 'aplicarDesconto']);//Colocar ou alterar desconto
    Route::delete('/tirarDesconto/{id}', [ProdutoController::class, 'tirarDesconto']);//Excluir desconto
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

