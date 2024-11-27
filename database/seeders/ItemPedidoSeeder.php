<?php

//Namespace
namespace Database\Seeders;

///Namespaces utilizados
use App\Models\Api\ItemPedido;
use App\Models\Api\Pedido;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Classe para seeder dos itens de pedidos de teste
class ItemPedidoSeeder extends Seeder
{
    
    //Método para rodar as seeders
    public function run(): void
    {

        if(Pedido::where('id', 1)->exists()){//Verifica se o pedido 1 existe e cria seus itens 
            ItemPedido::create([
                'id_pedido' => 1,
                'id_produto' => 1,
                'qtde' => 3,
                'preco' => 50,
                'desconto' => 97,
            ]);

            ItemPedido::create([
                'id_pedido' => 1,
                'id_produto' => 2,
                'qtde' => 1,
                'preco' => 50,
                'desconto' => 99,
            ]);
        }

        if(Pedido::where('id', 2)->exists()){//Verifica se o pedido 2 existe e cria seus itens 
            ItemPedido::create([
                'id_pedido' => 2,
                'id_produto' => 1,
                'qtde' => 3,
                'preco' => 50,
                'desconto' => 97,
            ]);

            ItemPedido::create([
                'id_pedido' => 2,
                'id_produto' => 1,
                'qtde' => 1,
                'preco' => 50,
                'desconto' => 99,
            ]);
        }

        if(Pedido::where('id', 3)->exists()){//Verifica se o pedido 3 existe e cria seus itens 
            ItemPedido::create([
                'id_pedido' => 3,
                'id_produto' => 1,
                'qtde' => 3,
                'preco' => 50,
                'desconto' => 97,
            ]);

            ItemPedido::create([
                'id_pedido' => 3,
                'id_produto' => 1,
                'qtde' => 1,
                'preco' => 50,
                'desconto' => 99,
            ]);
        }

    }
}
