<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de endereços do cliente
class EnderecoCliente extends Model
{
    use HasFactory;

    protected $table = 'enderecos_clientes';//Representa essa tabela

    protected $fillable = ['id_cliente', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero'];//Campos que podem ser preenchidos

    public function cliente()//Estabelecimento de relacionamento com tabela "clientes"
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
