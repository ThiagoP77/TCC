<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\CategoriaUsuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe para criar as categorias de usuário
class CategoriasUSeeder extends Seeder
{

    //Função de rodar as seeds
    public function run(): void
    {
        if(!CategoriaUsuario::where('nome', 'Admin')->exists()){//Cria a categoria admin
            CategoriaUsuario::create([
                'nome' => 'Admin'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Cliente')->exists()){//Cria a categoria cliente
            CategoriaUsuario::create([
                'nome' => 'Cliente'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Vendedor')->exists()){//Cria a categoria vendedor
            CategoriaUsuario::create([
                'nome' => 'Vendedor'
            ]);
        }

        if(!CategoriaUsuario::where('nome', 'Entregador')->exists()){//Cria a categoria entregador
            CategoriaUsuario::create([
                'nome' => 'Entregador'
            ]);
        }
    }
}
