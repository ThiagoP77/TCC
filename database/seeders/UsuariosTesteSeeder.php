<?php

//Namespace
namespace Database\Seeders;

//Namespaces utilizados
use App\Models\Api\Cliente;
use App\Models\Api\EnderecoVendedor;
use App\Models\Api\Entregador;
use App\Models\Api\Usuario;
use App\Models\Api\Vendedor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

//Seeder dos usuários utilizados para teste
class UsuariosTesteSeeder extends Seeder
{

    //Função de rodar as seeds
    public function run(): void
    {
        
        if(!Usuario::where('email', 'testecliente1@gmail.com')->exists()){//Cria o cliente de teste 1
            Usuario::create([
                'nome' => 'BolsonaroMito1',
                'email' => 'testecliente1@gmail.com',
                'senha' => Hash::make("senha123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '517.511.910-95',
                'foto_login' => 'storage/imagens_usuarios/imagem_default_usuario.jpg',
                'id_categoria' => 2,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'testecliente2@gmail.com')->exists()){//Cria o cliente de teste 2
            Usuario::create([
                'nome' => 'BolsonaroMito2',
                'email' => 'testecliente2@gmail.com',
                'senha' => Hash::make("senha123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '088.546.080-41',
                'foto_login' => 'storage/imagens_usuarios/imagem_default_usuario.jpg',
                'id_categoria' => 2,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o vendedor de teste 1
            Usuario::create([
                'nome' => 'BolsonaroMitoLoja1',
                'email' => 'testeloja1@gmail.com',
                'senha' => Hash::make("senha123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '244.462.520-03',
                'foto_login' => 'storage/imagens_usuarios/imagem_default_usuario.jpg',
                'id_categoria' => 3,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'testeentregador1@gmail.com')->exists()){///Cria o entregador de teste 1
            Usuario::create([
                'nome' => 'BolsonaroMitoEntregador1',
                'email' => 'testeentregador1@gmail.com',
                'senha' => Hash::make("senha123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '423.560.380-94',
                'foto_login' => 'storage/imagens_usuarios/imagem_default_usuario.jpg',
                'id_categoria' => 4,
                'aceito_admin' => true,
            ]);
        }

        if(!Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o vendedor de teste 2
            Usuario::create([
                'nome' => 'BolsonaroMitoLoja2',
                'email' => 'testeloja2@gmail.com',
                'senha' => Hash::make("senha123"),
                'email_verified_at' => Carbon::now(),
                'cpf' => '244.462.520-02',
                'foto_login' => 'storage/imagens_usuarios/imagem_default_usuario.jpg',
                'id_categoria' => 3,
                'aceito_admin' => true,
            ]);
        }

        if(Usuario::where('email', 'testecliente1@gmail.com')->exists()){//Cria o cliente relacionado
            Cliente::create([
                'id_usuario' => 5,
                'telefone' => '(27) 99999-8888',
            ]);
        }

        if(Usuario::where('email', 'testecliente2@gmail.com')->exists()){//Cria o cliente relacionado
            Cliente::create([
                'id_usuario' => 6,
                'telefone' => '(27) 99999-8888',
            ]);
        }

        if(Usuario::where('email', 'testeloja1@gmail.com')->exists()){//Cria o vendedor relacionado
            Vendedor::create([
                'id_usuario' => 7,
                'telefone' => '(27) 99999-8888',
                'whatsapp' => '(27) 99999-8888',
                'cnpj' => '73.125.404/0001-85',
                'descricao' => 3,
            ]);
        }

        if(Usuario::where('email', 'testeentregador1@gmail.com')->exists()){//Cria o entregador relacionado
            Entregador::create([
                'id_usuario' => 8,
                'telefone' => '(27) 99999-8888',
                'placa' => 'AAA-4565',
                'id_tipo_veiculo' => 1,
            ]);
        }

        if(Usuario::where('email', 'testeloja2@gmail.com')->exists()){//Cria o vendedor relacionado
            Vendedor::create([
                'id_usuario' => 9,
                'telefone' => '(27) 99999-8888',
                'whatsapp' => '(27) 99999-8888',
                'cnpj' => '73.125.404/0001-84',
                'descricao' => 3,
            ]);
        }

        if(Vendedor::where('id', 1)->exists()) {//Cria o endereço relacionado ao vendedor
            EnderecoVendedor::create([
                "id_vendedor" => 1,
                "cep" => "29700-020",
                "logradouro" => "Avenida José Zouain",
                "bairro" => "Centro",
                "localidade" => "Colatina",
                "uf" => "ES",
                "numero" => "123"
            ]);
        }

        if(Vendedor::where('id', 2)->exists()) {//Cria o endereço relacionado ao vendedor
            EnderecoVendedor::create([
                "id_vendedor" => 2,
                "cep" => "29700-020",
                "logradouro" => "Avenida José Zouain",
                "bairro" => "Centro",
                "localidade" => "Colatina",
                "uf" => "ES",
                "numero" => "123"
            ]);
        }
    }
}
