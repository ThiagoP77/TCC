<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

//Classe para criar os usuários admin
class UsuarioASeeder extends Seeder
{
    
    //Função de rodar as seeds
    public function run(): void
    {
        if(!Usuario::where('email', 'thiagopifferlauro@gmail.com')->exists()){//Cria o usuário do admin Thiago (mais gostoso do site) com seus dados
            Usuario::create([
                'nome' => 'Incelso da Silva Jr.',
                'email' => 'thiagopifferlauro@gmail.com',
                'senha' => Hash::make("TimeBolsonaro123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '196.236.737-10',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Thiago.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'thalescasaro@gmail.com')->exists()){//Cria o usuário do admin Thales com seus dados
            Usuario::create([
                'nome' => 'Mico-Ladrão-Safado',
                'email' => 'thalescasaro@gmail.com',
                'senha' => Hash::make("suamaeehminha"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '129.133.337-10',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Thales.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'lara.calegario43@gmail.com')->exists()){//Cria o usuário da admin Lara com seus dados
            Usuario::create([
                'nome' => 'LaraGuedes',
                'email' => 'lara.calegario43@gmail.com',
                'senha' => Hash::make("LaraIncrivel123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '111.111.111-12',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Lara.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'bernardopereira5000@gmail.com')->exists()){//Cria o usuário do admin Bernardo com seus dados
            Usuario::create([
                'nome' => 'BolsonaroMito',
                'email' => 'bernardopereira5000@gmail.com',
                'senha' => Hash::make("micoladraosafado"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '170.368.467-20',
                'foto_login' => 'storage/imagens_usuarios/Imagem_Admin_Bernardo.jpg',
                'id_categoria' => 1,
                'aceito_admin' => true,
            ]);
        }
    }
}
