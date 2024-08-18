<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de avaliações
class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_cliente',
        'id_vendedor',
        'avaliacao',
    ];

    public function cliente()//Estabelecimento de relacionamento com tabela "clientes"
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }
}
