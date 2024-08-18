<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de admins
class Admin extends Model
{
    use HasFactory;

    protected $table = 'admins';//Representa essa tabela

    protected $fillable = ['id_usuario'];//Campos que podem ser preenchidos

    public function usuario()//Estabelecimento de relacionamento com tabela "usuarios"
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
