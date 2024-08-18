<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\TipoVeiculo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe para criar os tipos de veículo
class TipoVeiculoSeeder extends Seeder
{
    
    //Função de rodar as seeds
    public function run(): void
    {
        $tipos = [//Lista com os tipos de veiculo aceitos no site
            'Moto',
            'Carro',
            'Caminhão'
        ];

        foreach ($tipos as $nome) {//Função que usa o Array de tipos de veiculo para criar um por um
            if (!TipoVeiculo::where('nome', $nome)->exists()) {//Cria o tipo caso ainda não exista
                TipoVeiculo::create([
                    'nome' => $nome
                ]);
            }
        }
    }
}
