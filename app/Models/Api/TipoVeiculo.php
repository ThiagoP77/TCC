<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de tipos de veiculo
class TipoVeiculo extends Model
{
    use HasFactory;

    protected $table = 'tipos_veiculos';//Representa essa tabela

    protected $fillable = ['nome'];//Campo que pode ser preenchido

    public function entregador()//Estabelecimento de relacionamento com tabela "entregadores"
    {
        return $this->hasMany(Entregador::class, 'id_tipo_veiculo');
    }
}
