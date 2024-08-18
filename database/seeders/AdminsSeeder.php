<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe para criar os usuários admin
class AdminsSeeder extends Seeder
{

    //Função de rodar as seeds
    public function run(): void
    {
        if(!Admin::where('id_usuario', 1)->exists()){//Cria o admin associado ao usuário de id 1
            Admin::create([
                'id_usuario' => 1
            ]);
        }

        if(!Admin::where('id_usuario', 2)->exists()){//Cria o admin associado ao usuário de id 2
            Admin::create([
                'id_usuario' => 2
            ]);
        }

        if(!Admin::where('id_usuario', 3)->exists()){//Cria o admin associado ao usuário de id 3
            Admin::create([
                'id_usuario' => 3
            ]);
        }

        if(!Admin::where('id_usuario', 4)->exists()){//Cria o admin associado ao usuário de id 4
            Admin::create([
                'id_usuario' => 4
            ]);
        }
    }
}
