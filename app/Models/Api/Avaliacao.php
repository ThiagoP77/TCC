<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'id_cliente',
        'id_vendedor',
        'avaliacao',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'id_vendedor');
    }
}
