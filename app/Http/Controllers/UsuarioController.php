<?php

namespace App\Http\Controllers;

use App\Models\Api\Cliente;
use App\Models\Api\Entregador;
use App\Models\Api\Usuario;
use App\Models\Api\Vendedor;
use App\Rules\CnpjValidacao;
use App\Rules\CpfValidacao;
use App\Rules\TelWhaValidacao;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    
    public function cadastro(Request $r): JsonResponse { 
        //dd($r->url());

        if (!$r->has('usuario')) {
            return response()->json(['mensagem' => 'Campo "usuario" não encontrado na requisição.'], 400);
        }

        $requestData = $r->all();

        try {
            // Decodifica o JSON do campo 'usuario' para um array associativo
            $usuarioData = json_decode($requestData['usuario'], true, 512, JSON_THROW_ON_ERROR);
    
            // Verifica se houve algum erro na decodificação do JSON
            if (json_last_error() != JSON_ERROR_NONE) {
                return response()->json(['mensagem' => 'Erro ao processar os dados do usuário.'], 400);
            }
        } catch (\JsonException $e) {
            // Captura exceções lançadas ao decodificar o JSON
            return response()->json(['mensagem' => 'Erro ao processar os dados do usuário.', 'erro' => $e->getMessage()], 400);
        }


        $validator = Validator::make($usuarioData, [
            'nome' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
            ],
    
            'id_categoria' => 'required|integer|in:2,3,4',
    
            'email' => 'required|email|unique:usuarios,email',
    
            'senha' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^\S*$/'
            ],
    
            'cpf' => [
                'required',
                'unique:usuarios,cpf',
                'string',
                'size:14',
                new CpfValidacao()
            ],
    
        ], [
            'id_categoria.in' => 'Tipo de usuário inválido.',
            'nome.regex' => 'Nome não pode conter caracteres especiais.',
            'senha.regex' => 'A senha não pode conter espaços.'
        ]);

        $validator2 = Validator::make($r->all(), [
            'foto_login' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16384'
        ]);
    
        //dd($r->url());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); 
        }

        if ($validator2->fails()) {
            return response()->json(['errors' => $validator2->errors()], 422); 
        }

        $dadosValidadosU = $validator->validated();

        $id_categoria = $dadosValidadosU['id_categoria'];

        //Cliente
        if($id_categoria == 2) {
            $validator = Validator::make($usuarioData, [
                'telefone' => [
                    'required',
                    'string',
                    'size:15',
                    'regex:/^\(\d{2}\) \d{5}-\d{4}$/',
                    new TelWhaValidacao()
                ],
            ], [
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
            ]);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        
            $dadosValidados = $validator->validated();

            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {

                DB::beginTransaction();

                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 2; // Garantir que seja 2
                $usuario->aceito_admin = true;

                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();

                $cliente = new Cliente();
                $cliente->telefone = $dadosValidados['telefone'];
                $cliente->id_usuario = $usuario->id;
                $cliente->save();

                DB::commit();

                return response()->json(['mensagem' => 'Cliente cadastrado com sucesso.'], 200);

            } catch (Exception $e) {

                DB::rollback();

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } 
        
        //Vendedor
        else if ($id_categoria == 3) {
            $validator = Validator::make($usuarioData, [
                'telefone' => [
                    'required',
                    'string',
                    'size:15',
                    'regex:/^\(\d{2}\) \d{5}-\d{4}$/',
                    new TelWhaValidacao
                ],
        
                'whatsapp' => [
                    'nullable',
                    'string',
                    'size:15',
                    'regex:/^\(\d{2}\) \d{5}-\d{4}$/',
                    new TelWhaValidacao
                ],
        
                'endereco' => 'required|string|max:255',
        
                'cnpj' => [
                    'nullable',
                    'string',
                    'size:18',
                    'unique:vendedores,cnpj',
                    new CnpjValidacao
                ]
            ], [
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
                'whatsapp.regex' => 'O Whatsapp deve seguir o formato (XX) XXXXX-XXXX.'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        
            $dadosValidados = $validator->validated();

            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {

                DB::beginTransaction();

                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 3;
                $usuario->aceito_admin = false;

                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();

                $vendedor = new Vendedor();
                $vendedor->telefone = $dadosValidados['telefone'];
                $vendedor->whatsapp = $dadosValidados['whatsapp'];
                $vendedor->endereco = $dadosValidados['endereco'];
                $vendedor->cnpj = $dadosValidados['cnpj'];
                $vendedor->id_usuario = $usuario->id;
                $vendedor->save();

                DB::commit();

                return response()->json(['mensagem' => 'Vendedor cadastrado com sucesso, aguarde autorização de algum admin.'], 200);

            } catch (Exception $e) {

                DB::rollback();

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } 
        
        //Entregador
        else if ($id_categoria == 4) {
            $validator = Validator::make($usuarioData, [
                'telefone' => [
                    'required',
                    'string',
                    'size:15',
                    'regex:/^\(\d{2}\) \d{5}-\d{4}$/',
                    new TelWhaValidacao
                ],
            
                'id_tipo_veiculo' => 'required|integer|in:1,2,3',
            
                'placa' => [
                    'required', 
                    'regex:/^[A-Z]{3}\-\d{4}$/'
                ],
            ], [
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
                'placa.regex' => 'A placa deve seguir o formato XXX-XXXX.'
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $dadosValidados = $validator->validated();

            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {

                DB::beginTransaction();

                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 4;
                $usuario->aceito_admin = false;

                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();

                $entregador = new Entregador();
                $entregador->telefone = $dadosValidados['telefone'];
                $entregador->id_tipo_veiculo = $dadosValidados['id_tipo_veiculo'];
                $entregador->placa = $dadosValidados['placa'];
                $entregador->id_usuario = $usuario->id;
                $entregador->save();

                DB::commit();

                return response()->json(['mensagem' => 'Entregador cadastrado com sucesso, aguarde autorização de algum admin.'], 200);

            } catch (Exception $e) {

                DB::rollback();

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } else {
            return response()->json([
                'mensagem' => 'Erro ao cadastrar usuário.'
            ], 400);
        }
    }

    public function login(){
        
    }

    public function logout(){
        
    }

}
