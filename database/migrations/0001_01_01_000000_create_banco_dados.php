<?php

//Namespaces utilizados
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/*
    Obs. nos formatos:
        CPF: XXX.XXX.XXX-XX
        Telefone e Whatsapp: (XX) XXXXX-XXXX
        CNPJ: XX.XXX.XXX/XXXX-XX
        CEP: XXXXX-XXX
        Placa: XXX-XXXX
*/

//Classe de criar as tabelas no banco de dados "tcc"
return new class extends Migration
{
 
    //Função de criar as tabelas no banco
    public function up(): void
    {
        Schema::create('categorias_usuarios', function (Blueprint $table) {//Tabela de categoria de usuários
            $table->id();//Chave primária id
            $table->string('nome');//String nome
            $table->timestamps();//Data de criação e alteração do registro
        });

        Schema::create('metodos_pagamentos', function (Blueprint $table) {//Tabela de metodos de pagamento
            $table->id();//Chave primária id
            $table->string('nome');//String nome
            $table->timestamps();//Data de criação e alteração do registro
        });

        Schema::create('tipos_veiculos', function (Blueprint $table) {//Tabela de tipos de veiculo
            $table->id();//Chave primária id
            $table->string('nome');//String nome
            $table->timestamps();//Data de criação e alteração do registro
        });

        Schema::create('usuarios', function (Blueprint $table) {//Tabela de usuários
            $table->id();//Chave primária id
            $table->string('nome');//String nome
            $table->string('email')->unique();//String e Unique email
            $table->timestamp('email_verified_at')->nullable();//Data de verificação do email
            $table->string('senha');//String senha
            $table->char('cpf', 14)->unique();//String e Unique cpf
            $table->string('foto_login')->nullable()->default('storage/imagens_usuarios/imagem_default_usuario.jpg');//String com a url de foto de perfil (tem uma default caso não seja adicionada)
            $table->unsignedBigInteger('id_categoria');//Chave estrangeira de "categorias_usuarios"
            $table->boolean('aceito_admin')->default(false);//Boolean de caso o usuário esteja aceito (true) ou não (false) no site
            $table->rememberToken();//String com o Token de acesso gerado ao fazer login
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_categoria')->references('id')->on('categorias_usuarios')->onDelete('restrict');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é restrict)
        });

        Schema::create('clientes', function (Blueprint $table) {//Tabela de clientes
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_usuario');//Chave estrangeira de "usuarios"
            $table->char('telefone', 15);//Char com o telefone
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('enderecos_clientes', function (Blueprint $table) {//Tabela com os endereços dos clientes
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_cliente');//Chave estrangeira de "clientes"
            $table->string('cep');//String cep
            $table->string('logradouro')->nullable();//String logradouro
            $table->string('bairro')->nullable();//String bairro
            $table->string('localidade')->nullable();//String localidade
            $table->string('uf')->nullable();//String uf
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('vendedores', function (Blueprint $table) {//Tabela de vendedores
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_usuario');//Chave estrangeira de "usuarios"
            $table->char('telefone', 15);//Char com o telefone
            $table->char('whatsapp', 15)->nullable();//Char com o whatsapp
            $table->char('cnpj', 18)->nullable();//Char com o cnpj
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('enderecos_vendedores', function (Blueprint $table) {//Tabela com os endereços dos vendedores
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_vendedor');//Chave estrangeira de "vendedores"
            $table->string('cep');//String cep
            $table->string('logradouro')->nullable();//String logradouro
            $table->string('bairro')->nullable();//String bairro
            $table->string('localidade')->nullable();//String localidade
            $table->string('uf')->nullable();//String uf
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('admins', function (Blueprint $table) {//Tabela de admins
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_usuario');//Chave estrangeira de "usuarios"
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('restrict');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é restrict)
        });

        Schema::create('entregadores', function (Blueprint $table) {//Tabela de entregadores
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_usuario');//Chave estrangeira de "usuarios"
            $table->char('telefone', 15);//Char com o telefone
            $table->char('placa', 8);//Char com a placa do veículo
            $table->unsignedBigInteger('id_tipo_veiculo');//Chave estrangeira de "tipos_veiculos"
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_tipo_veiculo')->references('id')->on('tipos_veiculos')->onDelete('restrict');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é restrict)
            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('produtos', function (Blueprint $table) {//Tabela de produtos
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_vendedor');//Chave estrangeira de "vendedores"
            $table->string('nome');//String nome
            $table->text('descricao')->nullable();//Text com a descrição do produto
            $table->decimal('preco', 10, 2)->check('preco >= 0');//Decimal preco (preco básico do produto)
            $table->decimal('preco_atual', 10, 2)->check('preco_atual >= 0');//Decimal preco_atual (preco do produto com desconto)
            $table->decimal('desconto', 5, 2)->default(0.00);//Decimal com o valor do desconto
            $table->string('imagem_produto')->nullable();//String com a url de foto de perfil (tem uma default caso não seja adicionada)
            $table->unsignedInteger('qtde_estoque')->default(0)->check('qtd_estoque >= 0');//Integer com a quantidade em estoque do produto
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('avaliacoes', function (Blueprint $table) {//Tabela de avaliações
            $table->unsignedBigInteger('id_cliente');//Chave estrangeira de "clientes"
            $table->unsignedBigInteger('id_vendedor');//Chave estrangeira de "vendedores"
            $table->tinyInteger('avaliacao')->unsigned()->check('avaliacao >= 0 AND avaliacao <= 5');//Integer entre 0 e 5 que indica a avaliação da loja pelo cliente
            $table->timestamps();//Data de criação e alteração do registro

            $table->primary(['id_cliente', 'id_vendedor']);
            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('carrinhos', function (Blueprint $table) {//Tabela de carrinhos
            $table->unsignedBigInteger('id_cliente');//Chave estrangeira de "clientes"
            $table->unsignedBigInteger('id_vendedor');//Chave estrangeira de "vendedores"
            $table->unsignedBigInteger('id_produto');//Chave estrangeira de "produtos"
            $table->unsignedInteger('qtde')->default(1)->check('qtde >= 0');//Integer com a quantidade do produto no carrinho
            $table->decimal('total', 10, 2)->check('total >= 0');//Decima do preco total do carrinho
            $table->timestamps();//Data de criação e alteração do registro

            $table->primary(['id_cliente', 'id_vendedor', 'id_produto']);//Chave primária composta por todas as estrangeiras

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_produto')->references('id')->on('produtos')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('pedidos', function (Blueprint $table) {//Tabela de pedidos
            $table->id();//Chave primária id
            $table->unsignedBigInteger('id_cliente');//Chave estrangeira de "clientes"
            $table->unsignedBigInteger('id_vendedor');//Chave estrangeira de "vendedores"
            $table->unsignedBigInteger('id_entregador')->nullable();//Chave estrangeira de "entregadores"
            $table->unsignedBigInteger('id_pagamento');//Chave estrangeira de "metodos_pagamentos"
            $table->boolean('precisa_troco')->default(false);//Bollean de caso precise de troco
            $table->decimal('troco', 5, 2)->default(0.00);//Decimal com o valor do troco
            $table->decimal('total', 10, 2)->check('total >= 0');//Decimal com o valor total do pedido
            $table->string('endereco_cliente');//String com o endereco do cliente
            $table->boolean('aceito_vendedor')->default(false);//Boolean para valor true ao ser aceito pelo vendedor
            $table->boolean('aceito_entregador')->default(false);//Boolean para valor true ao ser aceito pelo entregador
            $table->timestamp('data_criacao')->default(DB::raw('CURRENT_TIMESTAMP'));//Data de criação
            $table->enum('status', ['pendente', 'aceito pelo vendedor', 'aceito para entrega', 'entregue'])->default('pendente');//Status possíveis para o pedido
            $table->timestamps();//Data de criação e alteração do registro

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_pagamento')->references('id')->on('metodos_pagamentos')->onDelete('restrict');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é restrict)
            $table->foreign('id_entregador')->references('id')->on('entregadores')->onDelete('set null');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é set null)
        });

        Schema::create('itens_pedidos', function (Blueprint $table) {//Tabela de itens do pedido
            $table->unsignedBigInteger('id_pedido');//Chave estrangeira de "pedidos"
            $table->unsignedBigInteger('id_produto');//Chave estrangeira de "produtos"
            $table->unsignedInteger('qtde')->default(1)->check('qtde >= 0');//Integer com a quantidade do produto
            $table->decimal('preco', 10, 2)->check('preco >= 0');//Decimal com o preço
            $table->timestamps();//Data de criação e alteração do registro

            $table->primary(['id_pedido', 'id_produto']);//Chave primária composta por todas as estrangeiras

            $table->foreign('id_pedido')->references('id')->on('pedidos')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
            $table->foreign('id_produto')->references('id')->on('produtos')->onDelete('cascade');//Cria o relacionamento entre as tabelas (caso tenha registro, a exclusão é cascade)
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {//Tabela do próprio Laravel para resetar senha
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {//Tabela do próprio Laravel para uso de sessões
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }


    //Função de excluir as tabelas do banco
    public function down(): void
    {
        Schema::dropIfExists('categoria_usuarios');
        Schema::dropIfExists('metodo_pagamentos');
        Schema::dropIfExists('tipos_veiculos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('enderecos_clientes');
        Schema::dropIfExists('enderecos_vendedores');
        Schema::dropIfExists('vendedores');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('entregadores');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('avaliacoes');
        Schema::dropIfExists('carrinhos');
        Schema::dropIfExists('pedidos');
        Schema::dropIfExists('itens_pedidos');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
