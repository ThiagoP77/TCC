<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\MetodoPagamento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe para criar os metodos de pagamento
class MetodosPagSeeder extends Seeder
{
    
    //Função de rodar as seeds
    public function run(): void
    {
        $metodosPagamento = [//Lista com os metodos de pagamento aceitos no site
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

        foreach ($metodosPagamento as $nome) {//Função que usa o Array de metodos de pagamento para criar um por um
            if (!MetodoPagamento::where('nome', $nome)->exists()) {//Cria o metodo caso ainda não exista
                MetodoPagamento::create([
                    'nome' => $nome
                ]);
            }
        }
    }
}
