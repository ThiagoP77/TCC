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

        if(Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 2,
                'nome' => 'Bolsonaro Presidente',
                'descricao' => 'Esperança do povo.',
                'preco' => 124000,
                'preco_atual' => 124000,
                'qtde_estoque' => 100
            ]);
        }

        if(Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 2,
                'nome' => 'Larápio Maldito',
                'descricao' => 'Petista safado.',
                'preco' => 0.1,
                'preco_atual' => 0.1,
                'qtde_estoque' => 100000
            ]);
        }

        if(Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 2,
                'nome' => 'Capitão do Povo, Vai Vencer de Novo',
                'descricao' => 'Ditadura petista jamais.',
                'preco' => 222222,
                'preco_atual' => 222222,
                'qtde_estoque' => 222222
            ]);
        }

        if(Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o produto relacionado
            Produto::create([
                'id_vendedor' => 2,
                'nome' => 'BolsoLula',
                'descricao' => 'Peça rara.',
                'preco' => 99999999,
                'preco_atual' => 99999999,
                'qtde_estoque' => 1
            ]);
        }
        
    }
}
