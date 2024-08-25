<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Events\AceitoAEvent;
use App\Events\RecusadoAEvent;
use App\Mail\EsqueceuSenhaMail;
use App\Models\Api\Cliente;
use App\Models\Api\Entregador;
use App\Models\Api\Usuario;
use App\Models\Api\Vendedor;
use App\Models\Api\EnderecoVendedor;
use App\Rules\CepValidacao;
use App\Rules\CnpjValidacao;
use App\Rules\CpfValidacao;
use App\Rules\EmailValidacao;
use App\Rules\TelWhaValidacao;
use App\Services\ConsultaCEPService;
use App\Services\ExcluirTokensExpiradosService;
use App\Services\ValidarCodigoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

//Classe de controle de "usuarios"
class UsuarioController extends Controller
{

    protected $consultaCepService;//Atributo com o serviço de consulta de CEP

    //Construtor já com a criação do serviço
    public function __construct(ConsultaCEPService $consultaCepService)
    {
        $this->consultaCepService = $consultaCepService;
    }
    
    //Função de cadastro de usuários
    public function cadastro(Request $r): JsonResponse { 

        //Envia mensagem de erro caso o json não apresente a key "usuario"
        if (!$r->has('usuario')) {
            return response()->json(['mensagem' => 'Campo "usuario" não encontrado na requisição.'], 400);
        }

        //Recebe os dados do requeste
        $requestData = $r->all();

        try {//Testa exceção

            // Decodifica o JSON do campo 'usuario' para um array associativo
            $usuarioData = json_decode($requestData['usuario'], true, 512, JSON_THROW_ON_ERROR);
    
            // Verifica se houve algum erro na decodificação do JSON
            if (json_last_error() != JSON_ERROR_NONE) {
                return response()->json(['mensagem' => 'Erro ao processar os dados do usuário.'], 400);
            }

        } catch (\JsonException $e) {// Captura exceções lançadas ao decodificar o JSON
            return response()->json(['mensagem' => 'Erro ao processar os dados do usuário.', 'erro' => $e->getMessage()], 400);
        }
        
        try {
        //Realiza as validações fornecidas para os campos gerais de usuário
        $validator = Validator::make($usuarioData, [
            'nome' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
            ],
    
            'id_categoria' => 'required|integer|in:2,3,4',
    
            'email' => [
                'required',
                'email',
                'unique:usuarios,email',
                new EmailValidacao()
            ],
    
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
    
        ], [//Mensagens de erro personalizadas
            'id_categoria.in' => 'Tipo de usuário inválido.',
            'id_categoria.required' => 'Tipo de usuário deve ser informado.',
            'nome.regex' => 'Nome não pode conter caracteres especiais.',
            'senha.regex' => 'A senha não pode conter espaços.'
        ]);

        //Realiza as validações fornecidas para a imagem de usuário
        $validator2 = Validator::make($r->all(), [
            'foto_login' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16384'
        ]);
    
        //dd($r->url());

        //Caso haja falhas no primeiro validator, envia json de erro
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); 
        }

        //Caso haja falhas no segundo validator, envia json de erro
        if ($validator2->fails()) {
            return response()->json(['errors' => $validator2->errors()], 422); 
        }

        //Recebe os dados validados
        $dadosValidadosU = $validator->validated();

        //Pega o "id_categoria" do request
        $id_categoria = $dadosValidadosU['id_categoria'];

        //Cadastro de cliente caso o id_categoria seja 2
        if($id_categoria == 2) {

            //Validação dos dados específicos de cliente
            $validator = Validator::make($usuarioData, [
                'telefone' => [
                    'required',
                    'string',
                    'size:15',
                    'regex:/^\(\d{2}\) \d{5}-\d{4}$/',
                    new TelWhaValidacao()
                ],
            ], [//Mensagens de erro personalizadas
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
            ]);

            //Caso haja falhas no validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        
            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Junta os dados gerais e os específicos em um só
            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {//Testa exceção

                DB::beginTransaction();//Inicia a operação no banco

                //Criação do usuário com seus campos do resquest
                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 2; 
                $usuario->aceito_admin = true;

                //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');//Salva a imagem no diretório
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();//Salvando o usuário

                //Criação do cliente com seus campos do resquest
                $cliente = new Cliente();
                $cliente->telefone = $dadosValidados['telefone'];
                $cliente->id_usuario = $usuario->id;//Associando o cliente ao usuário
                $cliente->save();//Salvando cliente

                DB::commit();//Fazendo commit da operação

                event(new Registered($usuario));//Enviando email de verificação

                return response()->json(['mensagem' => 'Cliente cadastrado com sucesso, um email de verificação foi enviado.'], 200);//Retorno da mensagem de sucesso

            } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } 
        
        //Cadastro de vendedor caso o id_categoria seja 3
        else if ($id_categoria == 3) {

            //Validação dos dados específicos de vendedor
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
        
                'cnpj' => [
                    'nullable',
                    'string',
                    'size:18',
                    'unique:vendedores,cnpj',
                    new CnpjValidacao
                ],

                'cep' => [
                    'required', 
                    'string', 
                    new CepValidacao
                ],

                'numero' => [
                    'required', 
                    'string', 
                    'regex:/^\d+$/'
                ],

                'descricao' => [
                    'nullable', 
                    'string', 
                    'max:200'
                ],
            ], [//Mensagens de erro personalizadas
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
                'whatsapp.regex' => 'O Whatsapp deve seguir o formato (XX) XXXXX-XXXX.',
                'numero.regex' => 'O número deve conter apenas números.',
                'numero.required' => 'O campo número é obrigatório.',
                'numero.string' => 'O número deve ser uma string.',
                'descricao.string' => 'O campo descrição deve ser uma string.',
                'descricao.max' => 'A descrição não pode passar de 200 caracteres.'
            ]);

            //Caso haja falhas no validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
        
            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Junta os dados gerais e os específicos em um só
            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {//Testa exceção

                DB::beginTransaction();//Inicia a operação no banco

                //Criação do usuário com seus campos do resquest
                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 3;
                $usuario->aceito_admin = false;

                //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');//Salva a imagem no diretório
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();//Salvando usuário

                //Criação do vendedor com seus campos do resquest
                $vendedor = new Vendedor();
                $vendedor->telefone = $dadosValidados['telefone'];
                $vendedor->whatsapp = $dadosValidados['whatsapp'];
                $vendedor->cnpj = $dadosValidados['cnpj'];
                $vendedor->descricao = $dadosValidados['descricao'];
                $vendedor->id_usuario = $usuario->id;//Associando o vendedor ao usuário
                $vendedor->save();//Salvando vendedor

                //Instancia do serviço de consultar CEP
                $consultaCepService = $this->consultaCepService;

                //Recebe o CEP informado
                $cep = $dadosValidados['cep'];
        
                //Consulta o CEP e recebe o resultado
                $resultado = $consultaCepService->consultarCep($cep);

                //Percebe se houve erro na requisição e, caso tenha, envia mensagem de erro
                if ($resultado['status'] !== 200) {
                    return response()->json([
                        'mensagem' => $resultado['data']['mensagem'] ?? 'Erro ao consultar o CEP.',
                    ], $resultado['status']);
                }

                //Recebe os dados do endereço
                $cepData = $resultado['data'];

                //Criação do endereço do vendedor com os dados da requisição
                $enderecoV = new EnderecoVendedor();
                $enderecoV->cep = $cepData['cep'];
                $enderecoV->logradouro = $cepData['logradouro'];
                $enderecoV->bairro = $cepData['bairro'];
                $enderecoV->localidade = $cepData['localidade'];
                $enderecoV->uf = $cepData['uf'];
                $enderecoV->numero = $dadosValidados['numero'];
                $enderecoV->id_vendedor = $vendedor->id;//Associando o endereço ao vendedor
                $enderecoV->save();//Salvando endereço

                DB::commit();//Fazendo commit da operação

                event(new Registered($usuario));//Envia email de verificação

                return response()->json(['mensagem' => 'Vendedor cadastrado com sucesso, aguarde autorização de algum admin. Um email de verificação foi enviado!'], 200);//Retorno da mensagem de sucesso

            } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } 
        
        //Cadastro de entregador caso o id_categoria seja 4
        else if ($id_categoria == 4) {

            //Validação dos dados específicos de entregador
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
                    'unique:entregadores,placa',
                    'required', 
                    'regex:/^[A-Z0-9]{3}-[A-Z0-9]{4}$/'
                ],
            ], [//Mensagens de erro personalizadas
                'telefone.regex' => 'O telefone deve seguir o formato (XX) XXXXX-XXXX.',
                'placa.regex' => 'A placa deve seguir o formato XXX-XXXX (letras maiúsculas ou números).',
                'id_tipo_veiculo.required' => 'O tipo de veículo deve ser informado.',
                'id_tipo_veiculo.in' => 'O tipo de veículo é inválido.'
            ]);
            
            //Caso haja falhas no validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            //Junta os dados gerais e os específicos em um só
            $dadosValidados = array_merge($dadosValidadosU, $dadosValidados);

            try {//Testa exceção

                DB::beginTransaction();//Inicia a operação no banco

                //Criação do usuário com seus campos do resquest
                $usuario = new Usuario();
                $usuario->nome = $dadosValidados['nome'];
                $usuario->email = $dadosValidados['email'];
                $usuario->senha = Hash::make($dadosValidados['senha']);
                $usuario->cpf = $dadosValidados['cpf'];
                $usuario->id_categoria = 4;
                $usuario->aceito_admin = false;

                //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
                if (isset($requestData['foto_login']) && $r->hasFile('foto_login') && $r->file('foto_login')->isValid()) {
                    $path = $r->file('foto_login')->store('imagens_usuarios', 'public');//Salva a imagem no diretório
                    $usuario->foto_login = 'storage/'.$path;
                }

                $usuario->save();//Salvando usuário

                //Criação do entregador com seus campos do resquest
                $entregador = new Entregador();
                $entregador->telefone = $dadosValidados['telefone'];
                $entregador->id_tipo_veiculo = $dadosValidados['id_tipo_veiculo'];
                $entregador->placa = $dadosValidados['placa'];
                $entregador->id_usuario = $usuario->id;//Associando entregador ao usuário
                $entregador->save();//Salvando entregador

                DB::commit();//Fazendo commit da operação

                event(new Registered($usuario));//Envia email de verificação

                return response()->json(['mensagem' => 'Entregador cadastrado com sucesso, aguarde autorização de algum admin. Um email de verificação foi enviado!'], 200);//Retorno da mensagem de sucesso

            } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar usuário.',
                    'erro' => $e->getMessage()
                ], 400);

            }

        } else {//Envia mensagem de erro caso não se encaixe em nenhum if

            return response()->json([
                'mensagem' => 'Erro ao cadastrar usuário.'
            ], 400);
        }

    } catch (Exception $e) {
        return response()->json([
            'mensagem' => 'Erro ao cadastrar usuário.',
            'erro' => $e->getMessage()
        ], 400);
    }
    }

    //Função de aceitar entregador ou vendedor pelo admin
    public function aceitarAdmin($id) {
        try {//Testa exceção

            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);
            }
    
            //Encontra o usuário informado pelo id
            $u = Usuario::find($id);

            //Se o usuário não for entregador ou vendedor, retorna mensagem de erro
            if ($u->id_categoria !== 3 && $u->id_categoria !== 4) {
                return response()->json([
                    'mensagem' => 'O usuário não pode ser aceito porque não é vendedor e nem entregador.'
                ], 403);
            }

            //Se o usuário já for aceito, envia mensagem de erro
            if ($u->aceito_admin === 1) {
                return response()->json([
                    'mensagem' => 'O usuário já está ativo, não pode ser aceito de novo.'
                ], 403);
            }
    
            //Verifica se achou algum usuário
            if ($u) {

                //Pegando os dados do usuário
                $nome = $u->nome;
                $email = $u->email;

                //Aceitando ele e salvando
                $u->aceito_admin = true;
                $u->save();

                //Geração de evento, com função definida para entregador e para vendedor
                if ($u->id_categoria == 3) {
                    $funcao = "vendedor";
                    event(new AceitoAEvent($email, $nome, $funcao));
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    event(new AceitoAEvent($email, $nome, $funcao));
                }
    
                return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                    'mensagem' => 'Usuário aceito com sucesso.'
                ], 200);
                
            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao aceitar usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
         
    }

    //Função de recusar entregador ou vendedor pelo admin
    public function recusarAdmin($id) {
        try {//Testa exceção

            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);
            }
    
            //Encontra o usuário informado pelo id
            $u = Usuario::find($id);

            //Recebe os dados do usuário
            $idCategoria = $u->id_categoria;
            $fotoURL = $u->foto_login;
            $nome = $u->nome;
            $email = $u->email;

            //Se o usuário não for entregador ou vendedor, retorna mensagem de erro
            if (($idCategoria !== 3 && $idCategoria !== 4)) {
                return response()->json([
                    'mensagem' => 'O usuário não pode ser recusado.'
                ], 403);
            }

            //Se o usuário já for aceito, envia mensagem de erro
            if ($u->aceito_admin === 1) {
                return response()->json([
                    'mensagem' => 'O usuário já está ativo, não pode ser recusado.'
                ], 403);
            }

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_usuarios/imagem_default_usuario.jpg';

            //Caso consiga deletar o usuário, irá entrar no if 
            if ($u->delete()) {

                //Verificar se a foto existe e é a default e, se não for, exclui ela do site
                if ($fotoURL && $fotoURL !== $defaultURL) {

                    $p = str_replace('storage/', '', $fotoURL);

                    if (Storage::disk('public')->exists($p)) {
                        Storage::disk('public')->delete($p);//Excluindo ela
                    }

                }

                //Geração de evento, com função definida para entregador e para vendedor
                if ($u->id_categoria == 3) {
                    $funcao = "vendedor";
                    event(new RecusadoAEvent($email, $nome, $funcao));
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    event(new RecusadoAEvent($email, $nome, $funcao));
                }
    
                $u->delete();//Deletando o usuário

                return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                    'mensagem' => 'Usuário recusado com sucesso.'
                ], 200);

            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao recusar usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de fazer login no site
    public function login(Request $r): JsonResponse
    {
        try {//Testa exceção

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'email' => [
                    'required',
                    'email',
                    new EmailValidacao()
                ],

                'senha' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^\S*$/'
                ],
            ], [
                'senha.regex' => 'A senha não pode conter espaços.'
            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Recebe o usuário com o email fornecido
            $u = Usuario::where('email', $r->input('email'))->first();

            //Caso não encontre o usuário
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Email não registrado no sistema.',
                ], 404);
            }

            //Verifica se o usuário já verificou o email
            if($u->email_verified_at == null) {

                return response()->json([
                    'mensagem' => 'Login não permitido. Seu endereço de email ainda não foi verificado!',
                ], 400);

            }

            //Verifica se o usuário é entregador ou vendedor e se ja foi aceito no sistema. Caso não seja, envia mensagem de erro 
            if ($u->id_categoria == 3 || $u->id_categoria == 4){
                if ($u->aceito_admin == 0) {

                    return response()->json([
                        'mensagem' => 'Login não permitido. Seu usuário ainda não foi aceito no sistema!',
                    ], 400);

                }
            }
    
            //Credenciais necessárias para o login
            $credentials = [
                'email' => $r->input('email'),
                'password' => $r->input('senha')
            ];
    
            //Verifica se as credenciais estão presentes no banco
            if (Auth::attempt($credentials)) {

                //Recebe o usuário que apresenta as credenciais
                $user = Auth::user();
                
                //Criando as variaveis de abilities e caminho
                $hab = null;
                $caminho = null;
    
                //Utiliza o id_categoria para gerar o caminho e a ability correspondente
                switch ($user->id_categoria) {
                    case 1:
                        $caminho = '/adm';
                        $hab = 'admin';

                        //Instancia de Admin correspondente
                        $c = $u->admin;

                        //Pega id
                        if ($c) {
                            $id_c = $c->id;
                        } else {//Não achou
                            return response()->json([
                                'mensagem' => 'Falha ao logar.',
                            ], 404);
                        }

                        break;
                    case 2:
                        $caminho = '/cliente';
                        $hab = 'cliente';

                        //Instancia de cliente correspondente
                        $c = $u->cliente;

                        //Pega id
                        if ($c) {
                            $id_c = $c->id;
                        } else {//Não achou
                            return response()->json([
                                'mensagem' => 'Falha ao logar.',
                            ], 404);
                        }

                        break;
                    case 3:
                        $caminho = '/loja';
                        $hab = 'vendedor';

                        //Instancia de vendedor correspondente
                        $c = $u->vendedor;

                        //Pega id
                        if ($c) {
                            $id_c = $c->id;
                        } else {//Não achou
                            return response()->json([
                                'mensagem' => 'Falha ao logar.',
                            ], 404);
                        }

                        break;
                    case 4:
                        $caminho = '/entregador';
                        $hab = 'entregador';

                        //Instancia de entregador correspondente
                        $c = $u->entregador;

                        //Pega id
                        if ($c) {
                            $id_c = $c->id;
                        } else {//Não achou
                            return response()->json([
                                'mensagem' => 'Falha ao logar.',
                            ], 404);
                        }

                        break;
                    default://Caso não seja um id_categoria válido, envia mensagem de erro
                        return response()->json([
                            'mensagem' => 'Usuário inválido.'
                        ], 400);
                }
    
                //Gera um token de acesso com a ability fornecida
                $token = $r->user()->createToken('token', [$hab], now()->addWeek())->plainTextToken;
    
                //Instância do serviço de excluir tokens expirados
                $excluir = new ExcluirTokensExpiradosService();

                //Exclui os tokens
                $excluir->excluirTokensExpirados();

                //Envia mensagem de sucesso com informações necessárias para navegação no site
                return response()->json([
                    'message' => true,
                    'caminho' => $caminho,
                    'id_u' => $user->id,
                    'id_c' => $id_c,
                    'token' => $token
                ], 200);
                
            } else {//Envia mensagem de erro caso os dados não estejam no banco
                return response()->json([
                    'mensagem' => 'Senha incorreta.'
                ], 400);
            }
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao logar.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    /*
    //Função de logout
    public function logout(Request $r, $id): JsonResponse{
        try {//Testa exceção

            //Tenta encontrar usuário com base no id fornecido. Se não conseguir, envia mensagem de erro
            $u = Usuario::findOrFail($id);

            // Obtém o token da solicitação
            $token = $r->bearerToken();

            //Remove os caracteres antes do | e o | do token
            if (strpos($token, '|') !== false) {
                $token = strstr($token, '|');
                $token = substr($token, 1);
            }

            // Hash do token
            $hashedToken = hash('sha256', $token);

            //Procura pelo token compatível no banco de dados
            $userToken = PersonalAccessToken::where('token', $hashedToken)
                ->where('tokenable_id', $u->id)
                ->first();

            // Verifica se o token pertence ao usuário e o exclui se encontrado
            if ($userToken) {

                //Exclui o token especifico do usuário
                $userToken->delete();

                return response()->json([//Envia mensagem de sucesso
                    'mensagem' => 'Deslogado com sucesso.',
                ], 200);

            } else {//Mensagem de erro caso não bata a comparação

                return response()->json([
                    'error' => 'Token de acesso não encontrado ou não pertence ao usuário.',
                ], 400);

            }           

        } catch (ModelNotFoundException $e) {//Envia mensagem de erro caso o usuário não seja encontrado
            return response()->json([
                'error' => 'Usuário não encontrado.',
            ], 404);

        } catch (Exception $e) {//Envia mensagem de erro no caso de alguma outra exceção lançada

            return response()->json([
                'mensagem' => 'Falha ao deslogar.',
                'erro' => $e->getMessage()
            ], 400);

        }
        
    }*/

    //Função de informar que esqueceu a senha de login
    public function esqueceuSenha(Request $r): JsonResponse{
        try {//Testa exceção

            //Realiza validação dos campos com os parâmetros informados
            $validator = Validator::make($r->all(), [
                'email' => [
                    'required',
                    'email',
                    new EmailValidacao()
                ],
            ]);

            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Procura pelo usuário correspondente ao email recebido
            $u = Usuario::where('email', $r->input('email'))->first();

            //Caso o usuário não seja encontrado, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Email não registrado no sistema.',
                ], 404);
            }

            //Verifica se o usuário é entregador ou vendedor e se ja foi aceito no sistema. Caso não seja, envia mensagem de erro 
            if ($u->id_categoria == 3 || $u->id_categoria == 4){
                if ($u->aceito_admin == 0) {

                    return response()->json([
                        'mensagem' => 'Seu usuário ainda não foi aceito no sistema!',
                    ], 400);

                }
            }

            try {//Testa exceção

                //Gera um token de reset para o email fornecido
                $senhaReset = DB::table('password_reset_tokens')->where([
                    ['email', $r->input('email')]
                ]);

                //Retira tokens pré-existentes
                if ($senhaReset->exists()) {
                    $senhaReset->delete();
                }

                //Cria um código de 6 digitos aleatórios
                $codigo = mt_rand(100000, 999999);

                //Criptografa o código gerado
                $token = Hash::make($codigo);

                //Inclui um novo registro na tabela de reset de senhas
                $novaSenhaReset = DB::table('password_reset_tokens')->insert([
                    'email' => $r->input('email'),
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);

                //Entra no if se o registro existir
                if ($novaSenhaReset) {
                    $dataAtual = Carbon::now();//Pega a data atual
                    $dataMaisHora = $dataAtual->addHour();//Adiciona uma hora à data atual
                    $tempo = $dataMaisHora->format('H:i');//Deixa a hora no formato especificado 
                    $data = $dataMaisHora->format('d/m/Y');//Deixa a data no formato especificado

                    //Envia email com o código de reset de senha para o email informado
                    Mail::to($u->email)->send(new EsqueceuSenhaMail($u, $codigo, $data, $tempo));
                }

                return response()->json([//Envia mensagem de sucesso
                    'mensagem' => 'Enviado e-mail com instruções para recuperar a senha!',
                ], 200);

            } catch (Exception $e) {//Captura exceção e envia mensagem de erro
                return response()->json([
                    'mensagem' => 'Falha ao recuperar a senha.',
                    'erro' => $e->getMessage()
                ], 400);
            }
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao recuperar a senha.',
                'erro' => $e->getMessage()
            ], 400);
        }
}

    //Função que realiza a validação do código de reset de senha
    public function validarCodigo(Request $r, ValidarCodigoService $tokensReset) {
        try {//Testa exceção

            //Realiza a validação dos dados fornecidos
            $validator = Validator::make($r->all(), [
                'email' =>  [
                    'required',
                    'email',
                    new EmailValidacao()
                ],

                'codigo' => 'required|size:6|regex:/^\d+$/'

            ], [
                'codigo.required' => 'O campo código é obrigatório.',
                'codigo.regex' => 'O código só deve conter números.',
                'codigo.size' => 'O código deve ter seis caracteres.'
            ]);
    
            //Envia mensagem de erro no caso de falha na validação
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
    
            //Testa se o código fornecido bate com o gerado
            $valid = $tokensReset->validarCodigo($r->input('email'), $r->input('codigo'));
    
            //Caso não sejam equivalentes, envia mensagem de erro
            if (!$valid['status']) {
                return response()->json([
                    'mensagem' => $valid['message'],
                ], 400);
            }
    
            //Encontra o usuário com o email correspondete ao informado
            $u = Usuario::where('email', $r->input('email'))->first();
    
            //Caso não ache o usuário, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado!',
                ], 400);
            }
    
            //Envia mensagem de sucesso caso os códigos sejam iguais
            return response()->json([
                'mensagem' => 'Código válido!',
            ], 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de resetar a senha
    public function resetarSenha(Request $r, ValidarCodigoService $tokensReset) {
        try {//Testa exceção

            //Realiza a validação dos dados fornecidos
            $validator = Validator::make($r->all(), [
                'email' =>  [
                    'required',
                    'email',
                    new EmailValidacao()
                ],

                'codigo' => 'required|size:6|regex:/^\d+$/',

                'senha' => [
                    'required',
                    'string',
                    'confirmed',
                    'min:8',
                    'regex:/^\S*$/'
                ],
            ], [
                'codigo.required' => 'O campo código é obrigatório.',
                'codigo.size' => 'O código deve ter seis caracteres.',
                'codigo.regex' => 'O código só deve conter números.',
                'senha.regex' => 'A senha não pode conter espaços.'
            ]);
    
            //Envia mensagem de erro no caso de falha na validação
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
    
            //Testa se o código fornecido bate com o gerado
            $valid = $tokensReset->validarCodigo($r->input('email'), $r->input('codigo'));
    
            //Caso não sejam equivalentes, envia mensagem de erro
            if (!$valid['status']) {
                return response()->json([
                    'mensagem' => $valid['message'],
                ], 400);
            }
    
            //Encontra o usuário com o email correspondete ao informado
            $u = Usuario::where('email', $r->input('email'))->first();
    
            //Caso não ache o usuário, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado!',
                ], 400);
            }
    
            //Realiza a atualização da senha do usuário pela nova senha informada
            $u->update([
                'senha' => Hash::make($r->input('senha'))
            ]);
    
            //Encontra o registro de reset de senha do usuário informado no banco de dados
            $resetarSenha = DB::table('password_reset_tokens')->where('email', $r->input('email'));
    
            //Caso o registro exista, ele é apagado
            if ($resetarSenha->exists()) {
                $resetarSenha->delete();
            }
    
            return response()->json([//Envia mensagem de sucesso de reset de senha
                'status' => true,
                'mensagem' => 'Senha atualizada com sucesso!',
            ], 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Não foi possível alterar a senha.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de mostrar os dados do usuário por id
    public function dadosUsuario ($id) {
        try{//Testa se tem exceção

            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);
            }

            //Encontra o usuário informado pelo id
            $u = Usuario::find($id);

            //Utiliza o id_categoria para pegar os dados correspondentes
            switch ($u->id_categoria) {
                case 1:

                    //Instancia de Admin correspondente
                    $c = $u->admin;

                    //Pega id
                    if ($c) {

                        //Pegando os dados e enviando resposta de sucesso
                        $resposta = Usuario::where('id', $id)
          
                        ->with(['categoria:id,nome'])
                        ->with(['admin:id,id_usuario'])
          
                        ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'id_categoria')
                        ->get();

                        return response()->json($resposta);

                    } else {//Não achou
                        return response()->json([
                            'mensagem' => 'Falha ao mostrar dados.',
                        ], 404);
                    }

                case 2:

                    //Instancia de cliente correspondente
                    $c = $u->cliente;

                    //Pega id
                    if ($c) {

                        //Pegando os dados e enviando resposta de sucesso
                        $resposta = Usuario::where('id', $id)
          
                        ->with(['categoria:id,nome'])
                        ->with(['cliente:id,id_usuario,telefone'])
          
                        ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'id_categoria')
                        ->get();

                        return response()->json($resposta);

                    } else {//Não achou
                        return response()->json([
                            'mensagem' => 'Falha ao mostrar dados.',
                        ], 404);
                    }

                case 3:

                    //Instancia de vendedor correspondente
                    $c = $u->vendedor;

                    //Pega id
                    if ($c) {

                        //Pegando os dados e enviando resposta de sucesso
                        $resposta = Usuario::where('id', $id)
          
                        ->with(['categoria:id,nome'])
                        ->with(['vendedor' => function($query) {
                            $query->select('id','id_usuario', 'telefone', 'whatsapp', 'cnpj', 'descricao')
                                  ->with('endereco:id_vendedor,cep,logradouro,bairro,localidade,uf,numero');
                            }])
          
                        ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'id_categoria')
                        ->get();

                        return response()->json($resposta);

                    } else {//Não achou
                        return response()->json([
                            'mensagem' => 'Falha ao mostrar dados.',
                        ], 404);
                    }

                case 4:

                    //Instancia de entregador correspondente
                    $c = $u->entregador;

                    //Pega id
                    if ($c) {

                        //Pegando os dados e enviando resposta de sucesso
                        $resposta = Usuario::where('id', $id)
          
                        ->with(['categoria:id,nome'])

                        ->with(['entregador' => function($query) {
                            $query->select('id_usuario', 'telefone', 'placa', 'id_tipo_veiculo')
                                  ->with('tipoVeiculo:id,nome');
                        }])
          
                        ->select('id', 'nome', 'email', 'cpf', 'foto_login', 'id_categoria')
                        ->get();

                        return response()->json($resposta);

                    } else {//Não achou
                        return response()->json([
                            'mensagem' => 'Falha ao mostrar dados.',
                        ], 404);
                    }

                default://Caso não seja um id_categoria válido, envia mensagem de erro
                    return response()->json([
                        'mensagem' => 'Usuário inválido.'
                    ], 400);
            }


        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Não foi possível pegar os dados.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de excluir usuário por id
    public function excluirUsuario ($id) {

        try {//Testa exceção

            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);
            }
    
            //Encontra o usuário informado pelo id
            $u = Usuario::find($id);

            //Recebe os dados do usuário
            $idCategoria = $u->id_categoria;
            $fotoURL = $u->foto_login;

            //Se o usuário não for entregador ou vendedor, retorna mensagem de erro
            if (($idCategoria == 1)) {
                return response()->json([
                    'mensagem' => 'O usuário é admin e não pode ser excluído.'
                ], 403);
            }

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_usuarios/imagem_default_usuario.jpg';

            //Caso consiga deletar o usuário, irá entrar no if 
            if ($u->delete()) {

                //Verificar se a foto existe e é a default e, se não for, exclui ela do site
                if ($fotoURL && $fotoURL !== $defaultURL) {

                    $p = str_replace('storage/', '', $fotoURL);

                    if (Storage::disk('public')->exists($p)) {
                        Storage::disk('public')->delete($p);//Excluindo ela
                    }

                }
    
                $u->delete();//Deletando o usuário

                return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                    'mensagem' => 'Usuário excluído com sucesso.'
                ], 200);

            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao excluir usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }
}