<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVeiculo extends Model
{
    use HasFactory;

    protected $table = 'tipos_veiculos';

    protected $fillable = ['nome'];

    public function entregador()
    {
        return $this->hasMany(Entregador::class, 'id_tipo_veiculo');
    }
}
