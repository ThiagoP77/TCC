<?php

namespace Database\Seeders;

use App\Models\Api\TipoVeiculo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoVeiculoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            'Moto',
            'Carro',
            'Caminhão'
        ];

        foreach ($tipos as $nome) {
            if (!TipoVeiculo::where('nome', $nome)->exists()) {
                TipoVeiculo::create([
                    'nome' => $nome
                ]);
            }
        }
    }
}
