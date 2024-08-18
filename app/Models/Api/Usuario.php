<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

//Classe modelo de usuario
class Usuario extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $table = 'usuarios';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'nome',
        'email',
        'senha',
        'cpf',
        'foto_login',
        'id_categoria',
        'aceito_admin',
    ];


    protected $hidden = [//Campos ocultos
        'senha',
        'remember_token',
    ];

    protected function casts(): array//Função do próprio Laravel
    {
        return [
            'email_verified_at' => 'datetime',
            'senha' => 'hashed',
        ];
    }

    public function getAuthPassword()//Sobrescrevendo a variável "password" do Sanctum para "senha"
    {
        return $this->senha;
    }
    
    public function categoria()//Estabelecimento de relacionamento com tabela "categorias_usuarios"
    {
        return $this->belongsTo(CategoriaUsuario::class, 'id_categoria');
    }

    public function cliente()//Estabelecimento de relacionamento com tabela "clientes"
    {
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function vendedor()//Estabelecimento de relacionamento com tabela "vendedores"
    {
        return $this->hasOne(Vendedor::class, 'id_usuario');
    }

    public function admin()//Estabelecimento de relacionamento com tabela "admins"
    {
        return $this->hasOne(Admin::class, 'id_usuario');
    }

    public function entregador()//Estabelecimento de relacionamento com tabela "entregadores"
    {
        return $this->hasOne(Entregador::class, 'id_usuario');
    }
}
