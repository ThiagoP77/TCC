<?php

//Namespace
namespace App\Models\Api;

//Namespaces
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de categoria de usuário
class CategoriaUsuario extends Model
{
    use HasFactory;

    protected $table = 'categorias_usuarios';//Representa essa tabela

    protected $fillable = ['nome'];//Campos que podem ser preenchidos

    public function usuarios()//Estabelecimento de relacionamento com tabela "usuarios"
    {
        return $this->hasMany(Usuario::class, 'id_categoria');
    }
}
