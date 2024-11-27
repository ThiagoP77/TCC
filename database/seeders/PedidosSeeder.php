<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Pedido;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe seeder para os pedidos de teste
class PedidosSeeder extends Seeder
{
    
    //Método para executar as seeders
    public function run(): void
    {
        if(!Pedido::where('id', 1)->exists()){//Cria o pedido de teste 1
            Pedido::create([
                'id_cliente' => 1,
                'id_vendedor' => 1,
                'id_pagamento' => 1,
                'precisa_troco' => 0,
                'troco' => 0.0,
                'total' => 200,
                'lucro_loja' => 188,
                'lucro_adm' => 6,
                'lucro_entregador' => 6,
                'endereco_cliente' => 'Av. Pref. José Zouain, 944 - Centro, Colatina - ES, 29700-020',
            ]);
        }

        if(!Pedido::where('id', 2)->exists()){///Cria o pedido de teste 2
            Pedido::create([
                'id_cliente' => 1,
                'id_vendedor' => 1,
                'id_pagamento' => 1,
                'precisa_troco' => 0,
                'troco' => 0.0,
                'total' => 200,
                'lucro_loja' => 188,
                'lucro_adm' => 6,
                'lucro_entregador' => 6,
                'endereco_cliente' => 'Av. Pref. José Zouain, 944 - Centro, Colatina - ES, 29700-020',
            ]);
        }

        if(!Pedido::where('id', 3)->exists()){//Cria o pedido de teste 3
            Pedido::create([
                'id_cliente' => 1,
                'id_vendedor' => 1,
                'id_pagamento' => 1,
                'precisa_troco' => 0,
                'troco' => 0.0,
                'total' => 200,
                'lucro_loja' => 188,
                'lucro_adm' => 6,
                'lucro_entregador' => 6,
                'endereco_cliente' => 'Av. Pref. José Zouain, 944 - Centro, Colatina - ES, 29700-020',
            ]);
        }
    }
}
