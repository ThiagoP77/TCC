<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de itens_pedidos
class ItemPedido extends Model
{
    use HasFactory;

    protected $table = 'itens_pedidos';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_pedido',
        'id_produto',
        'qtde',
        'preco',
    ];

    public function pedido()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function produto()//Estabelecimento de relacionamento com tabela "produtos"
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }
}
