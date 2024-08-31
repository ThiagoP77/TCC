<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Produto;
use App\Models\Api\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

//Seeders de produtos de teste
class ProdutosTesteSeeder extends Seeder
{
    
    //Função que roda as seeders
    public function run(): void
    {

        if(Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 1,
                'nome' => 'Bolsonaro de Chocolate',
                'descricao' => 'Explosão de sabores e mitada.',
                'preco' => 100000,
                'preco_atual' => 100000,
                'qtde_estoque' => 5
            ]);
        }

        if(Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 1,
                'nome' => 'Bolsonaro de Baunilha',
                'descricao' => 'Explosão de sabores e mitada.',
                'preco' => 200500,
                'preco_atual' => 200500,
                'qtde_estoque' => 2
            ]);
        }

        if(Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 1,
                'nome' => 'Bolsonaro de Morango',
                'descricao' => 'Explosão de sabores e mitada.',
                'preco' => 5000,
                'preco_atual' => 5000,
                'qtde_estoque' => 20
            ]);
        }

        if(Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 1,
                'nome' => 'Bolsonaro de Nutella (Skin Matador de Lula)',
                'descricao' => 'Explosão de sabores e mitada.',
                'preco' => 99999999,
                'preco_atual' => 99999999,
                'qtde_estoque' => 1
            ]);
        }
        
    }
}
