<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';

    protected $fillable = [
        'id_vendedor',
        'nome',
        'descricao',
        'preco',
        'preco_atual',
        'desconto',
        'imagem_produto',
        'qtde_estoque',
    ];

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function carrinhos()
    {
        return $this->hasMany(Carrinho::class, 'id_produto');
    }
    
    public function itensPedidos()
    {
        return $this->hasMany(ItemPedido::class, 'id_produto');
    }

    public function pedidos()
    {
        return $this->belongsToMany(Pedido::class, 'itens_pedidos', 'id_produto', 'id_pedido')
                    ->withPivot('qtde', 'preco');
    }
}
