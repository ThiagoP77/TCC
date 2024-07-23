<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entregador extends Model
{
    use HasFactory;

    protected $table = 'entregadores';

    protected $fillable = [
        'id_usuario',
        'telefone',
        'tipo_veiculo',
        'placa',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'id_entregador');
    }
}
