<?php

//Namespace
namespace App\Models\Api;

//Namespaces utilizados
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//Classe modelo de entregadores
class Entregador extends Model
{
    use HasFactory;

    protected $table = 'entregadores';//Representa essa tabela

    protected $fillable = [//Campos que podem ser preenchidos
        'id_usuario',
        'telefone',
        'id_tipo_veiculo',
        'placa',
    ];

    public function usuario()//Estabelecimento de relacionamento com tabela "usuarios"
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function pedidos()//Estabelecimento de relacionamento com tabela "pedidos"
    {
        return $this->hasMany(Pedido::class, 'id_entregador');
    }

    public function tipoVeiculo()//Estabelecimento de relacionamento com tabela "tipos_veiculos"
    {
        return $this->belongsTo(TipoVeiculo::class, 'id_tipo_veiculo');
    }
}
