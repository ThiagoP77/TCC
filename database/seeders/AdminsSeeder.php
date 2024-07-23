<?php

namespace Database\Seeders;

use App\Models\Api\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if(!Admin::where('id_usuario', 1)->exists()){
            Admin::create([
                'id_usuario' => 1
            ]);
        }

        if(!Admin::where('id_usuario', 2)->exists()){
            Admin::create([
                'id_usuario' => 2
            ]);
        }

        if(!Admin::where('id_usuario', 3)->exists()){
            Admin::create([
                'id_usuario' => 3
            ]);
        }
    }
}
