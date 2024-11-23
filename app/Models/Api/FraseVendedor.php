<?php

///Namespace
namespace App\Models\Api;

//Namespaces utilizados
use App\Models\Api\Vendedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo para 'frases_vendedores'
class FraseVendedor extends Model
{
    use HasFactory;

    protected $table = 'frases_vendedores';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_vendedor',
        'frase',
    ];

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }
}
