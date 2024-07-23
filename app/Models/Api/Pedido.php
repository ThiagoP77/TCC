<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function entregador()
    {
        return $this->belongsTo(Entregador::class, 'id_entregador');
    }

    public function metodoPagamento()
    {
        return $this->belongsTo(MetodoPagamento::class, 'id_pagamento');
    }

    public function itens()
    {
        return $this->hasMany(ItemPedido::class, 'id_pedido');
    }

    public function produtos()
    {
        return $this->belongsToMany(Produto::class, 'itens_pedidos', 'id_pedido', 'id_produto')
                    ->withPivot('qtde', 'preco');
    }
}
