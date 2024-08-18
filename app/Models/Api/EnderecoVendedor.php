<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use App\Models\Api\Vendedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de endereços do vendedor
class EnderecoVendedor extends Model
{
    use HasFactory;

    protected $table = 'enderecos_vendedores';//Representa essa tabela

    protected $fillable = ['id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf'];//Campos que podem ser preenchidos

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }
}
