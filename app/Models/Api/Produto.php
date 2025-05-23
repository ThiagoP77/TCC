<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de produtos
class Produto extends Model
{
    use HasFactory;

    protected $table = 'produtos';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_vendedor',
        'nome',
        'descricao',
        'preco',
        'preco_atual',
        'desconto',
        'imagem_produto',
        'qtde_estoque',
        'status'
    ];

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }

    public function carrinhos()//Estabelecimento de relacionamento com tabela "carrinhos"
    {
        return $this->hasMany(Carrinho::class, 'id_produto');
    }
    
    public function itensPedidos()//Estabelecimento de relacionamento com tabela "itens_pedidos"
    {
        return $this->hasMany(ItemPedido::class, 'id_produto');
    }

    public function pedidos()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->belongsToMany(Pedido::class, 'itens_pedidos', 'id_produto', 'id_pedido')
                    ->withPivot('qtde', 'preco');
    }
}
