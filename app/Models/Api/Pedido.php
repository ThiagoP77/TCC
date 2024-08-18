<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de pedidos
class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_cliente',
        'id_vendedor',
        'id_entregador',
        'id_pagamento',
        'precisa_troco',
        'troco',
        'total',
        'endereco_cliente',
        'aceito_vendedor',
        'aceito_entregador',
        'status',
    ];

    public function cliente()//Estabelecimento de relacionamento com tabela "clientes"
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function entregador()//Estabelecimento de relacionamento com tabela "entregadores"
    {
        return $this->belongsTo(Entregador::class, 'id_entregador');
    }

    public function metodoPagamento()//Estabelecimento de relacionamento com tabela "metodos_pagamentos"
    {
        return $this->belongsTo(MetodoPagamento::class, 'id_pagamento');
    }

    public function itens()//Estabelecimento de relacionamento com tabela "itens_pedidos"
    {
        return $this->hasMany(ItemPedido::class, 'id_pedido');
    }

    public function produtos()//Estabelecimento de relacionamento com tabela "produtos"
    {
        return $this->belongsToMany(Produto::class, 'itens_pedidos', 'id_pedido', 'id_produto')
                    ->withPivot('qtde', 'preco');
    }
}
