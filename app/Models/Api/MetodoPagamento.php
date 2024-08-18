<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo dos metodos de pagamento
class MetodoPagamento extends Model
{
    use HasFactory;

    protected $table = 'metodos_pagamentos';//Representa essa tabela

    protected $fillable = ['nome'];//Campos que podem ser preenchidos

    public function pedidos()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->hasMany(Pedido::class, 'id_pagamento');
    }
}
