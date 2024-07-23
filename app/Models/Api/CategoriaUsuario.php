<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaUsuario extends Model
{
    use HasFactory;

    protected $table = 'categorias_usuarios';

    protected $fillable = ['nome'];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_categoria');
    }
}
