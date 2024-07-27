<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/*
    Obs. nos formatos:
        CPF: XXX.XXX.XXX-XX
        Telefone e Whatsapp: (XX) XXXXX-XXXX
        CNPJ: XX.XXX.XXX/XXXX-XX
        Placa: XXX-XXXX
*/

return new class extends Migration
{
 
    public function up(): void
    {
        Schema::create('categorias_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('metodos_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('tipos_veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('senha');
            $table->char('cpf', 14)->unique();
            $table->string('foto_login')->nullable()->default('storage/imagens_usuarios/imagem_default_usuario.jpg');
            $table->unsignedBigInteger('id_categoria');
            $table->boolean('aceito_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('id_categoria')->references('id')->on('categorias_usuarios')->onDelete('restrict');
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->char('telefone', 15)->nullable();
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');
        });

        Schema::create('enderecos_clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->string('descricao');
            $table->timestamps();

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
        });

        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->char('telefone', 15);
            $table->char('whatsapp', 15)->nullable();
            $table->string('endereco');
            $table->char('cnpj', 18)->nullable();
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->timestamps();

            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('restrict');
        });

        Schema::create('entregadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->char('telefone', 15);
            $table->char('placa', 8);
            $table->unsignedBigInteger('id_tipo_veiculo');
            $table->timestamps();

            $table->foreign('id_tipo_veiculo')->references('id')->on('tipos_veiculos')->onDelete('restrict');
            $table->foreign('id_usuario')->references('id')->on('usuarios')->onDelete('cascade');
        });

        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_vendedor');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2)->check('preco >= 0');
            $table->decimal('preco_atual', 10, 2)->check('preco_atual >= 0');
            $table->decimal('desconto', 5, 2)->default(0.00);
            $table->string('imagem_produto')->nullable();
            $table->unsignedInteger('qtde_estoque')->default(0)->check('qtd_estoque >= 0');
            $table->timestamps();

            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');
        });

        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_vendedor');
            $table->tinyInteger('avaliacao')->unsigned()->check('avaliacao >= 0 AND avaliacao <= 5');
            $table->timestamps();

            $table->primary(['id_cliente', 'id_vendedor']);
            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');
        });

        Schema::create('carrinhos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_vendedor');
            $table->unsignedBigInteger('id_produto');
            $table->unsignedInteger('qtde')->default(1)->check('qtde >= 0');
            $table->decimal('total', 10, 2)->check('total >= 0');
            $table->timestamps();

            $table->primary(['id_cliente', 'id_vendedor', 'id_produto']);

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');
            $table->foreign('id_produto')->references('id')->on('produtos')->onDelete('cascade');
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_vendedor');
            $table->unsignedBigInteger('id_entregador')->nullable();
            $table->unsignedBigInteger('id_pagamento');
            $table->boolean('precisa_troco')->default(false);
            $table->decimal('troco', 5, 2)->default(0.00);
            $table->decimal('total', 10, 2)->check('total >= 0');
            $table->string('endereco_cliente');
            $table->boolean('aceito_vendedor')->default(false);
            $table->boolean('aceito_entregador')->default(false);
            $table->timestamp('data_criacao')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->enum('status', ['pendente', 'aceito pelo vendedor', 'aceito para entrega'])->default('pendente');
            $table->timestamps();

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('id_vendedor')->references('id')->on('vendedores')->onDelete('cascade');
            $table->foreign('id_pagamento')->references('id')->on('metodos_pagamentos')->onDelete('restrict');
            $table->foreign('id_entregador')->references('id')->on('entregadores')->onDelete('set null');
        });

        Schema::create('itens_pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pedido');
            $table->unsignedBigInteger('id_produto');
            $table->unsignedInteger('qtde')->default(1)->check('qtde >= 0');
            $table->decimal('preco', 10, 2)->check('preco >= 0');
            $table->timestamps();

            $table->primary(['id_pedido', 'id_produto']);

            $table->foreign('id_pedido')->references('id')->on('pedidos')->onDelete('cascade');
            $table->foreign('id_produto')->references('id')->on('produtos')->onDelete('cascade');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_usuarios');
        Schema::dropIfExists('metodo_pagamentos');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('enderecos_clientes');
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
