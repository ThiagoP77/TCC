<?php

namespace App\Models\Api;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cpf',
        'foto_login',
        'id_categoria',
        'aceito_admin',
    ];


    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'senha' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }
    
    public function categoria()
    {
        return $this->belongsTo(CategoriaUsuario::class, 'id_categoria');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function vendedor()
    {
        return $this->hasOne(Vendedor::class, 'id_usuario');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id_usuario');
    }

    public function entregador()
    {
        return $this->hasOne(Entregador::class, 'id_usuario');
    }
}
