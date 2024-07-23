<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPagamento extends Model
{
    use HasFactory;

    protected $table = 'metodos_pagamentos';

    protected $fillable = ['nome'];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_pagamento');
    }
}
