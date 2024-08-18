<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Usuario;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe padrão de rodar as seeds
class DatabaseSeeder extends Seeder
{
    
    //Função de rodar as seeds
    public function run(): void
    {
        $this->call(CategoriasUSeeder::class);//Roda a seed CategoriasUSeeder
        $this->call(MetodosPagSeeder::class);//Roda a seed MetodosPagSeeder
        $this->call(TipoVeiculoSeeder::class);//Roda a seed TipoVeiculoSeeder
        $this->call(UsuarioASeeder::class);//Roda a seed UsuarioASeeder
        $this->call(AdminsSeeder::class);//Roda a seed AdminsSeeder
        
        // User::factory(10)->create();

        /*
        Usuario::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */
    }
}
