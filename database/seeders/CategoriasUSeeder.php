<?php

namespace Database\Seeders;

use App\Models\Api\CategoriaUsuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriasUSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!CategoriaUsuario::where('nome', 'Admin')->exists()){
            CategoriaUsuario::create([
                'nome' => 'Admin'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Cliente')->exists()){
            CategoriaUsuario::create([
                'nome' => 'Cliente'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Vendedor')->exists()){
            CategoriaUsuario::create([
                'nome' => 'Vendedor'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Entregador')->exists()){
            CategoriaUsuario::create([
                'nome' => 'Entregador'
            ]);
        }
    }
}
