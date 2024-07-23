<?php

namespace Database\Seeders;

use App\Models\Api\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!Usuario::where('email', 'thiagopifferlauro@gmail.com')->exists()){
            Usuario::create([
                'nome' => 'Incelso da Silva Jr.',
                'email' => 'thiagopifferlauro@gmail.com',
                'senha' => Hash::make("TimeBolsonaro123"),
                'cpf' => '196.236.737-10',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Thiago.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'thalescasaro@gmail.com')->exists()){
            Usuario::create([
                'nome' => 'Mico-Ladrão-Safado',
                'email' => 'thalescasaro@gmail.com',
                'senha' => Hash::make("suamaeehminha"),
                'cpf' => '129.133.337-10',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Thales.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'lara.calegario43@gmail.com')->exists()){
            Usuario::create([
                'nome' => 'LaraGuedes',
                'email' => 'lara.calegario43@gmail.com',
                'senha' => Hash::make("LaraIncrivel123"),
                'cpf' => '111.111.111-12',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Lara.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'bernardopereira5000@gmail.com')->exists()){
            Usuario::create([
                'nome' => 'BolsonaroMito',
                'email' => 'bernardopereira5000@gmail.com',
                'senha' => Hash::make("micoladraosafado"),
                'cpf' => '170.368.467-20',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Bernardo.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }
    }
}
