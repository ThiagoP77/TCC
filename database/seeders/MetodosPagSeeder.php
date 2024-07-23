<?php

namespace Database\Seeders;

use App\Models\Api\MetodoPagamento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MetodosPagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $metodosPagamento = [
            'Dinheiro',
            'Pix (na entrega)',
            'Visa Crédito',
            'Visa Débito',
            'MasterCard Crédito',
            'MasterCard Débito',
            'Elo Crédito',
            'Elo Débito',
            'Alelo Alimentação',
            'Alelo Refeição'
        ];

        foreach ($metodosPagamento as $nome) {
            if (!MetodoPagamento::where('nome', $nome)->exists()) {
                MetodoPagamento::create([
                    'nome' => $nome
                ]);
            }
        }
    }
}
