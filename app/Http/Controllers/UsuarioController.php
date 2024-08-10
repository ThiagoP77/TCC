<?php

namespace App\Http\Controllers;

use App\Events\AceitoAEvent;
use App\Events\RecusadoAEvent;
use App\Mail\EsqueceuSenhaMail;
use App\Models\Api\Cliente;
use App\Models\Api\Entregador;
use App\Models\Api\Usuario;
use App\Models\Api\Vendedor;
use App\Rules\CnpjValidacao;
use App\Rules\CpfValidacao;
use App\Rules\TelWhaValidacao;
use App\Service\ValidarCodigoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
                    'regex:/^[A-Z0-9]{3}-[A-Z0-9]{4}$/'
                ],
            ], [
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
                'placa.regex' => 'A placa deve seguir o formato XXX-XXXX (letras ou números).'
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

    public function aceitarAdmin($id) {
        try {
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }
    
            $u = Usuario::find($id);

            if ($u->id_categoria !== 3 && $u->id_categoria !== 4) {
                return response()->json([
                    'message' => 'O usuário não pode ser aceito porque não é vendedor e nem entregador.'
                ], 403);
            }

            if ($u->aceito_admin === 1) {
                return response()->json([
                    'message' => 'O usuário já está ativo, não pode ser recusado.'
                ], 403);
            }
    
            if ($u) {
                $nome = $u->nome;
                $email = $u->email;

                $u->aceito_admin = true;
                $u->save();

                if ($u->id_categoria == 3) {
                    $funcao = "vendedor";
                    event(new AceitoAEvent($email, $nome, $funcao));
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    event(new AceitoAEvent($email, $nome, $funcao));
                }
    
                return response()->json([
                    'message' => 'Usuário aceito com sucesso.'
                ], 200);
                
            } else {

                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao aceitar usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
         
    }

    public function recusarAdmin($id) {
        try {
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }
    
            $u = Usuario::find($id);

            $idCategoria = $u->id_categoria;
            $fotoURL = $u->foto_login;
            $nome = $u->nome;
            $email = $u->email;

            if (($idCategoria !== 3 && $idCategoria !== 4)) {
                return response()->json([
                    'message' => 'O usuário não pode ser recusado.'
                ], 403);
            }

            if ($u->aceito_admin === 1) {
                return response()->json([
                    'message' => 'O usuário já está ativo, não pode ser recusado.'
                ], 403);
            }

            $defaultURL = 'storage/imagens_usuarios/imagem_default_usuario.jpg';

            if ($u->delete()) {
                if ($fotoURL && $fotoURL !== $defaultURL) {

                    $p = str_replace('storage/', '', $fotoURL);

                    if (Storage::disk('public')->exists($p)) {
                        Storage::disk('public')->delete($p);
                    }

                }

                if ($u->id_categoria == 3) {
                    $funcao = "vendedor";
                    event(new RecusadoAEvent($email, $nome, $funcao));
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    event(new RecusadoAEvent($email, $nome, $funcao));
                }
    
                $u->delete();

                return response()->json([
                    'message' => 'Usuário recusado com sucesso.'
                ], 200);

            } else {

                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao recusar usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    public function login(Request $r): JsonResponse{
        
        try {

            $r->validate([
                'email' => 'required|email',
    
                'senha' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^\S*$/'
                ],
            ]);

            $credentials = [
                'email' => $r->input('email'),
                'password' => $r->input('senha')
            ];

            if (Auth::attempt($credentials)) {

                $user = Auth::user();
                
                $hab = null;

                $caminho = null;

                if ($user->id_categoria == 1) {
                    $caminho = '/admins';
                    $hab = 'admin';
                } elseif ($user->id_categoria == 2) {
                    $caminho = '/clientes';
                    $hab = 'cliente';
                } else if ($user->id_categoria == 3) {
                    $caminho = '/vendedores';
                    $hab = 'vendedor';
                } else if ($user->id_categoria == 4) {
                    $caminho = '/entregadores';
                    $hab = 'entregador';
                } else {

                    return response()->json([
                        'message' => 'Usuário inválido.'
                    ], 404);

                }

                $token = $r->user()->createToken('token', [$hab])->plainTextToken;

                return response()->json([
                    'message' => true,
                    'caminho' => $caminho,
                    'id' => $user->id,
                    'token' => $token
                ], 200);
                
            } else {

                return response()->json([
                    'message' => 'Email ou senha incorretos.'
                ], 404);

            }

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao logar.',
                'erro' => $e->getMessage()
            ], 400);

        }

    }


    public function logout($id): JsonResponse{

        try {

            $u = Usuario::findOrFail($id);

            $u->tokens()->delete();

            return response()->json([
                'mensagem' => 'Deslogado com sucesso.',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
            ], 404);

        } catch (Exception $e) {

            return response()->json([
                'mensagem' => 'Falha ao deslogar.',
                'erro' => $e->getMessage()
            ], 400);

        }
        
    }

    public function esqueceuSenha(Request $r) {

        try {
            $r->validate([
                'email' => 'required|email'
                ],
            );

            $u = Usuario::where('email', $r->email)->first();

            if (!$u) {
                return response()->json([
                    'error' => 'Email não registrado.',
                ], 404);
            }

            try {

                $senhaReset = DB::table('password_reset_tokens')->where([
                    ['email', $r->email]
                ]);

                if($senhaReset){
                    $senhaReset->delete();
                }

                $code = mt_rand(100000, 999999);

                $token = Hash::make($code);

                $novaSenhaReset = DB::table('password_reset_tokens')->insert([
                    'email' => $r->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);

                if ($novaSenhaReset) {

                    $currentDate = Carbon::now();
    
                    $oneHourLater = $currentDate->addHour();
    
                    $formattedTime = $oneHourLater->format('H:i');
                    $formattedDate = $oneHourLater->format('d/m/Y');
    
                    Mail::to($u->email)->send(new EsqueceuSenhaMail($u, $code, $formattedDate, $formattedTime));
                }

                return response()->json([
                    'message' => 'Enviado e-mail com instruções para recuperar a senha. Acesse a sua caixa de e-mail para recuperar a senha!',
                ], 200);

            } catch (Exception $e) {
                return response()->json([
                    'mensagem' => 'Falha ao recuperar senha.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } catch (Exception $e) {
            return response()->json([
                'mensagem' => 'Falha ao recuperar senha.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    public function validarCodigo(Request $r, ValidarCodigoService $tokensReset) {
        try{

            $r->validate([
                'email' => 'required|email',
                'code' => 'required'
                ],
            );

            $valid = $tokensReset->validarCodigo($r->email, $r->code);

            if(!$valid['status']){
                return response()->json([
                    'message' => $valid['message'],
                ], 400);
            }

            $user = Usuario::where('email', $r->email)->first();

            if(!$user){
                
                return response()->json([
                    'message' => 'Usuário não encontrado!',
                ], 400);

            }

            return response()->json([
                'message' => 'Código válido!',
            ], 200);

        } catch (Exception $e){

            return response()->json([
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    public function resetarSenha(Request $r, ValidarCodigoService $tokensReset) {
        try{
            $r->validate([
                'email' => 'required|email',

                'code' => 'required',

                'senha' => [
                    'required',
                    'string',
                    'confirmed',
                    'min:8',
                    'regex:/^\S*$/'
                    ],
                ],
            );

            $valid = $tokensReset->validarCodigo($r->email, $r->code);

            if(!$valid['status']){
                
                return response()->json([
                    'message' => $valid['message'],
                ], 400);

            }

            $u = Usuario::where('email', $r->email)->first();

            if(!$u){

                return response()->json([
                    'message' => 'Usuário não encontrado!',
                ], 400);

            }

            $u->update([
                'senha' => Hash::make($r->senha)
            ]);

            $resetarSenha = DB::table('password_reset_tokens')->where('email', $r->email);

            if($resetarSenha){
                $resetarSenha->delete();
            }

            return response()->json([
                'status' => true,
                'message' => 'Senha atualizada com sucesso!',
            ], 200);


        } catch (Exception $e){

            return response()->json([
                'message' => 'Não foi possível alterar senha.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

}
