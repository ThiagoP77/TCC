<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de clientes
class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';//Representa essa tabela

    protected $fillable = ['id_usuario', 'telefone'];//Campos que podem ser preenchidos

    public function usuario()//Estabelecimento de relacionamento com tabela "usuarios"
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function enderecos()//Estabelecimento de relacionamento com tabela "enderecos_clientes"
    {
        return $this->hasMany(EnderecoCliente::class, 'id_cliente');
    }

    public function avaliacoes()//Estabelecimento de relacionamento com tabela "avaliacoes"
    {
        return $this->hasMany(Avaliacao::class, 'id_cliente');
    }

    public function carrinhos()//Estabelecimento de relacionamento com tabela "carrinhos"
    {
        return $this->hasMany(Carrinho::class, 'id_cliente');
    }

    public function pedidos()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }
}
