<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = ['id_usuario', 'telefone'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function enderecos()
    {
        return $this->hasMany(EnderecoCliente::class, 'id_cliente');
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class, 'id_cliente');
    }

    public function carrinhos()
    {
        return $this->hasMany(Carrinho::class, 'id_cliente');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_cliente');
    }
}
