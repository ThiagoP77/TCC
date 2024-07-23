<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores';

    protected $fillable = [
        'id_usuario',
        'telefone',
        'whatsapp',
        'endereco',
        'cnpj',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class, 'id_vendedor');
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class, 'id_vendedor');
    }

    public function carrinhos()
    {
        return $this->hasMany(Carrinho::class, 'id_vendedor');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_vendedor');
    }
}
