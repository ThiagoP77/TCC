<?php

namespace Database\Seeders;

use App\Models\Api\Usuario;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategoriasUSeeder::class);
        $this->call(MetodosPagSeeder::class);
        $this->call(TipoVeiculoSeeder::class);
        $this->call(UsuarioASeeder::class);
        $this->call(AdminsSeeder::class);
        // User::factory(10)->create();

        /*
        Usuario::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */
    }
}
