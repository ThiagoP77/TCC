<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use App\Models\Api\EnderecoVendedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe de modelo para vendedor
class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_usuario',
        'telefone',
        'whatsapp',
        'cnpj',
        'descricao'
    ];

    public function usuario()//Estabelecimento de relacionamento com tabela "usuarios"
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function produtos()//Estabelecimento de relacionamento com tabela "produtos"
    {
        return $this->hasMany(Produto::class, 'id_vendedor');
    }

    public function avaliacoes()//Estabelecimento de relacionamento com tabela "avaliacoes"
    {
        return $this->hasMany(Avaliacao::class, 'id_vendedor');
    }

    public function carrinhos()//Estabelecimento de relacionamento com tabela "carrinhos"
    {
        return $this->hasMany(Carrinho::class, 'id_vendedor');
    }

    public function pedidos()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->hasMany(Pedido::class, 'id_vendedor');
    }

    public function endereco()//Estabelecimento de relacionamento com tabela "enderecos"
    {
        return $this->hasOne(EnderecoVendedor::class, 'id_vendedor');
    }
}
