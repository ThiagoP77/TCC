<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Events\AceitoAEvent;
use App\Events\EmailValidaEvent;
use App\Events\RecusadoAEvent;
use App\Events\RegistroCustomizadoEvent;
use App\Jobs\AceitoAJob;
use App\Jobs\RecuperarSenhaJob;
use App\Jobs\RecusadoAJob;
use App\Jobs\RegistroCustomizadoJob;
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
use App\Services\ApagaImagensProdutosService;
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

                RegistroCustomizadoJob::dispatch($usuario);//Enviando email de verificação

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

                RegistroCustomizadoJob::dispatch($usuario);//Envia email de verificação

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

                RegistroCustomizadoJob::dispatch($usuario);//Envia email de verificação

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
                    AceitoAJob::dispatch($email, $nome, $funcao);
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    AceitoAJob::dispatch($email, $nome, $funcao);
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
                    RecusadoAJob::dispatch($email, $nome, $funcao);
                } elseif ($u->id_categoria == 4) {
                    $funcao = "entregador";
                    RecusadoAJob::dispatch($email, $nome, $funcao);
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

            //Caso usuário tenha sido desativado
            if ($u->status == 'desativado') {
                return response()->json([
                    'mensagem' => 'Login não permitido. Seu usuário foi desativado por um de nossos admins. Para mais detalhes, entre em contato por esse número: +55 27 99533-4529!',
                ], 401);
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

            //Caso usuário tenha sido desativado
            if ($u->status == 'desativado') {
                return response()->json([
                    'mensagem' => 'Seu usuário foi desativado por um de nossos admins. Para mais detalhes, entre em contato por esse número: +55 27 99533-4529!',
                ], 401);
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
                    RecuperarSenhaJob::dispatch($u, $codigo, $data, $tempo);
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

        //Função de mostrar os dados do usuário por token
        public function exibirPerfil (Request $r) {
            try{//Testa se tem exceção
    
                //Obtém o usuário autenticado
                $u = $r->user(); 

                //Caso o usuário, cliente ou loja não sejam encontrados, envia mensagem de erro
                if (!$u) {
                    return response()->json([
                        'mensagem' => 'Usuário não encontrado.',
                    ], 404);
                }

                //Pegando id
                $id = $u->id;
    
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
    public function mudarStatus ($id) {

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
            $status = $u->status;

            //Se o usuário não for entregador ou vendedor, retorna mensagem de erro
            if (($idCategoria == 1)) {
                return response()->json([
                    'mensagem' => 'O usuário é admin e seu status não pode ser alterado.'
                ], 403);
            }

            //Verifica qual o status atual do usuário para alterá-lo e envia mensagem de sucesso
            if($status == 'ativo') {

                $u->status = 'desativado';
                $u->save();

                //Remove todos os tokens associados ao usuário
                $u->tokens()->delete();

                return response()->json([
                    'mensagem' => 'Usuário desativado com sucesso.'
                ], 200);

            } else if ($status == 'desativado') {

                $u->status = 'ativo';
                $u->save();

                return response()->json([
                    'mensagem' => 'Usuário ativado com sucesso.'
                ], 200);
                

            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Usuário não encontrado ou status inválido.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao alterar status do usuário.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de mostrar a foto do usuário por id
    public function fotoUsuario ($id) {
        try{//Testa se tem exceção
    
            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !Usuario::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.'
                ], 404);
            }
    
            //Encontra o usuário informado pelo id
            $u = Usuario::find($id);

            //Envia o caminho da imagem
            return response()->json([
                'url' => $u->foto_login
            ], 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Não foi possível mostrar a imagem.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de excluir imagem de perfil
    public function excluirFoto (Request $r) {
        try {//Testa se tem exceção

            //Recupera o usuário logado pelo token
            $u = $r->user();

            //Caso o usuário não seja encontrado, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.',
                ], 404);
            }

            //Adm não pode alterar seus dados
            if ($u->id_categoria == 1) {
                return response()->json([
                    'mensagem' => 'O administrador não pode alterar seus dados.',
                ], 400);
            }

            //Pega a URL da imagem do usuário
            $fotoURL = $u->foto_login;

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_usuarios/imagem_default_usuario.jpg';

            //Verificar se a foto existe e é a default e, se não for, exclui ela do site
            if ($fotoURL && $fotoURL !== $defaultURL) {

                $p = str_replace('storage/', '', $fotoURL);

                if (Storage::disk('public')->exists($p)) {
                    Storage::disk('public')->delete($p);//Excluindo ela
                }

            } else {
                return response()->json([//Envia mensagem de erro caso a imagem seja a default
                    'mensagem' => 'Nenhuma imagem para ser excluída.'
                ], 400);
            }

            //Define a imagem do usuário como a default e salva
            $u->foto_login = 'storage/imagens_usuarios/imagem_default_usuario.jpg';
            $u->save();

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Imagem de usuário excluída com sucesso.'
            ], 200);

        } catch (Exception $e){//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Não foi possível excluir a imagem.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de alterar foto do perfil
    public function alterarFoto (Request $r): JsonResponse {
        try {//Testa se tem exceção

            //Recupera usuário logado pelo token
            $u = $r->user();

            //Caso o usuário não seja encontrado, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.',
                ], 404);
            }

            //Adm não pode alterar seus dados
            if ($u->id_categoria == 1) {
                return response()->json([
                    'mensagem' => 'O administrador não pode alterar seus dados.',
                ], 400);
            }

            //Realiza as validações fornecidas para a imagem de usuário
            $validator = Validator::make($r->all(), [
                'imagem' => 'required|image|mimes:jpeg,png,jpg,gif|max:16384'
            ]);

            //Caso tenha erro na validação, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }

            //Captura a URL da imagem do usuário
            $fotoURL = $u->foto_login;

            //URL da imagem default do site
            $defaultURL = 'storage/imagens_usuarios/imagem_default_usuario.jpg';

            //Verificar se a foto existe e é a default e, se não for, exclui ela do site
            if ($fotoURL && $fotoURL !== $defaultURL) {

                $p = str_replace('storage/', '', $fotoURL);

                if (Storage::disk('public')->exists($p)) {
                    Storage::disk('public')->delete($p);//Excluindo ela
                }

            } 

            //Verifica se a imagem foi adicionada ou é a default e, caso não seja, adiciona ela no diretório público
            if ($r->hasFile('imagem') && $r->file('imagem')->isValid()) {
                $path = $r->file('imagem')->store('imagens_usuarios', 'public');//Salva a imagem no diretório
                $u->foto_login = 'storage/'.$path;
            } 

            $u->save();//Salvando o usuário

            return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                'mensagem' => 'Imagem de usuário alterada com sucesso.'
            ], 200);
            

        } catch (Exception $e){//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Não foi possível alterar a imagem.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de alterar dados do usuário
    public function alterarUsuario (Request $r) {
        try {//Testa se tem exceção

            //Pega o usuário logado pelo token
            $u = $r->user();

            //Caso o usuário não seja encontrado, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.',
                ], 404);
            }

            //Adm não pode alterar seus dados
            if ($u->id_categoria == 1) {
                return response()->json([
                    'mensagem' => 'O administrador não pode alterar seus dados.',
                ], 400);
            }

            //Realiza as validações fornecidas para os campos gerais de usuário
            $validator = Validator::make($r->all(), [
                'nome' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
                ],
        
                'email' => [
                    'required',
                    'email',
                    new EmailValidacao()
                ],
        
            ], [//Mensagens de erro personalizadas
                'nome.regex' => 'Nome não pode conter caracteres especiais.',
            ]);
    
            //Caso haja falhas no primeiro validator, envia json de erro
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422); 
            }
    
            //Recebe os dados validados
            $dadosValidadosU = $validator->validated();

            //Recebe a categoria de usuário
            $id_categoria = $u->id_categoria;

            //Variável booleana para verificar se o email foi alterado
            $teste_email = false;
    
            //Cliente caso o id_categoria seja 2
            if($id_categoria == 2) {
    
                //Validação dos dados específicos de cliente
                $validator = Validator::make($r->all(), [
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
    
                    $u->nome = $dadosValidados['nome'];//Recebe nome

                    //Verifica se o email foi alterado
                    if ($dadosValidados['email'] != $u->email) {

                        //Usa Eloquent para verificar a existência do e-mail
                        $emailExiste = Usuario::where('email', $dadosValidados['email'])->exists();
        
                        //Se existir, retorna mensagem de erro
                        if ($emailExiste) {
        
                            return response()->json([
                                'mensagem' => 'Email já registrado no sistema.',
                            ], 400);
        
                        } else {//Caso não exista, usuário recebe o email e ele precisa ser verificado
                            $u->email = $dadosValidados['email'];
                            $u->email_verified_at = null;
                            $teste_email = true;
                        }
                    } else {//Usuário continua com seu email
                        $u->email = $dadosValidados['email'];
                    }

                    $u->save();//Salvando o usuário
    
                    //Cliente relacionado ao usuário
                    $cliente = $u->cliente;

                    //Caso não encontre, envia mensagem de erro
                    if (!$cliente) {
                        return response()->json([
                            'mensagem' => 'Usuário não encontrado.',
                        ], 404);
                    }

                    $cliente->telefone = $dadosValidados['telefone'];//Recebe o telefone
                    $cliente->save();//Salvando cliente
    
                    DB::commit();//Fazendo commit da operação
    
                    if ($teste_email) {//Sucesso e email alterado
                        RegistroCustomizadoJob::dispatch($u);

                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso. Certifique-se de verificar seu novo email antes de realizar login!'
                        ], 200);

                    } else {//Sucesso
                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso.'
                    ], 200);
                    }
    
                } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                    DB::rollback();//Desfaz todas as operações realizadas no banco
    
                    return response()->json([
                        'mensagem' => 'Erro ao alterar dados.',
                        'erro' => $e->getMessage()
                    ], 400);
                }
            } 
            
            //Vendedor caso o id_categoria seja 3
            else if ($id_categoria == 3) {
    
                //Validação dos dados específicos de vendedor
                $validator = Validator::make($r->all(), [
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
                    $u->nome = $dadosValidados['nome'];
                    
                    //Verifica se o email foi alterado
                    if ($dadosValidados['email'] != $u->email) {

                        // Usa Eloquent para verificar a existência do e-mail
                        $emailExiste = Usuario::where('email', $dadosValidados['email'])->exists();
        
                        //Se existir, retorna mensagem de erro
                        if ($emailExiste) {
        
                            return response()->json([
                                'mensagem' => 'Email já registrado no sistema.',
                            ], 400);
        
                        } else {//Caso não exista, recebe o email e precisa de verificação
                            $u->email = $dadosValidados['email'];
                            $u->email_verified_at = null;
                            $teste_email = true;
                        }
                    } else {//Continua com o email antigo
                        $u->email = $dadosValidados['email'];
                    }

                    $u->save();//Salvando o usuário
    
                    //Vendedor associado ao usuário
                    $vendedor = $u->vendedor;
                    
                    //Caso não encontre, retorna mensagem de erro
                    if (!$vendedor) {
                        return response()->json([
                            'mensagem' => 'Usuário não encontrado.',
                        ], 404);
                    }

                    //Recebendo dados
                    $vendedor->telefone = $dadosValidados['telefone'];
                    $vendedor->whatsapp = $dadosValidados['whatsapp'];
                    
                    //Verifica se o CNPJ esta preenchido e é único
                    if (!empty($dadosValidados['cnpj']) && $dadosValidados['cnpj'] != $vendedor->cnpj) {

                        //Usa Eloquent para verificar a existência do cnpj
                        $cnpjExiste = Vendedor::where('cnpj', $dadosValidados['cnpj'])->exists();
        
                        //Se existir, envia mensagem de erro
                        if ($cnpjExiste) {
        
                            return response()->json([
                                'mensagem' => 'CNPJ já registrado no sistema.',
                            ], 400);
        
                        } else {//Caso não exista, recebe ele
                            $vendedor->cnpj = $dadosValidados['cnpj'];
                        }

                    } else if (empty($dadosValidados['cnpj'])) {//Se for vazio, recebe null
                        $vendedor->cnpj = $dadosValidados['cnpj'];
                    }

                    $vendedor->descricao = $dadosValidados['descricao'];
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
    
                    //Endereço associado ao vendedor recebe os dados
                    $enderecoV = $vendedor->endereco;
                    $enderecoV->cep = $cepData['cep'];
                    $enderecoV->logradouro = $cepData['logradouro'];
                    $enderecoV->bairro = $cepData['bairro'];
                    $enderecoV->localidade = $cepData['localidade'];
                    $enderecoV->uf = $cepData['uf'];
                    $enderecoV->numero = $dadosValidados['numero'];
                    $enderecoV->save();//Salvando endereço
    
                    DB::commit();//Fazendo commit da operação
    
                    //Mensagens de sucesso em caso de alteração ou não do email
                    if ($teste_email) {
                        RegistroCustomizadoJob::dispatch($u);

                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso. Certifique-se de verificar seu novo email antes de realizar login!'
                        ], 200);
                    } else {
                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso.'
                    ], 200);
                    }

                } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                    DB::rollback();//Desfaz todas as operações realizadas no banco
    
                    return response()->json([
                        'mensagem' => 'Erro ao alterar dados.',
                        'erro' => $e->getMessage()
                    ], 400);
                }
            } 
            
            //Entregador caso o id_categoria seja 4
            else if ($id_categoria == 4) {
    
                //Validação dos dados específicos de entregador
                $validator = Validator::make($r->all(), [
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
                    $u->nome = $dadosValidados['nome'];
                    
                    //Verifica se o email foi alterado
                    if ($dadosValidados['email'] != $u->email) {
                        //Usa Eloquent para verificar a existência do e-mail
                        $emailExiste = Usuario::where('email', $dadosValidados['email'])->exists();
        
                        //Se existir, envia mensagem de erro
                        if ($emailExiste) {
        
                            return response()->json([
                                'mensagem' => 'Email já registrado no sistema.',
                            ], 400);
        
                        } else {//Caso não exista, recebe e precisa verificar
                            $u->email = $dadosValidados['email'];
                            $u->email_verified_at = null;
                            $teste_email = true;
                        }
                    } else {//Continua com o email antigo
                        $u->email = $dadosValidados['email'];
                    }
    
                    $u->save();//Salvando usuário
    
                    //Entregador associado ao usuário recebe os dados
                    $entregador = $u->entregador;
                    $entregador->telefone = $dadosValidados['telefone'];
                    $entregador->id_tipo_veiculo = $dadosValidados['id_tipo_veiculo'];
                    
                    //Verifica se a placa já existe
                    if ($dadosValidados['placa'] != $entregador->placa) {
                        //Usa Eloquent para verificar a existência da placa
                        $placaExiste = Entregador::where('placa', $dadosValidados['placa'])->exists();
        
                        //Caso exista, envia mensagem de erro
                        if ($placaExiste) {
        
                            return response()->json([
                                'mensagem' => 'Placa já registrada no sistema.',
                            ], 400);
        
                        } else {//Recebe caso não exista
                            $entregador->placa = $dadosValidados['placa'];
                        }
                    }

                    $entregador->save();//Salvando entregador
    
                    DB::commit();//Fazendo commit da operação
    
                    //Verifica se email foi alterado e envia mensagem de sucesso
                    if ($teste_email) {
                        RegistroCustomizadoJob::dispatch($u);

                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso. Certifique-se de verificar seu novo email antes de realizar login'
                        ], 200);
                    } else {
                        return response()->json([
                            'mensagem' => 'Dados alterados com sucesso.'
                    ], 200);
                    }

                } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                    DB::rollback();//Desfaz todas as operações realizadas no banco
    
                    return response()->json([
                        'mensagem' => 'Erro ao alterar dados.',
                        'erro' => $e->getMessage()
                    ], 400);
    
                }
    
            } else {//Envia mensagem de erro caso não se encaixe em nenhum if
    
                return response()->json([
                    'mensagem' => 'Erro ao alterar dados.'
                ], 400);
            }
    
        } catch (Exception $e) {
            return response()->json([
                'mensagem' => 'Erro ao alterar dados.',
                'erro' => $e->getMessage()
            ], 400);
        }
        }
    
    //Rota de confirmar algo por senha
    public function confirmarPorSenha (Request $r) {
        try {//Testa exceção

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
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

            //Recebe o usuário logado
            $u = $r->user();

            //Caso não encontre o usuário
            if (!$u) {
                return response()->json([
                    'mensagem' => 'Usuário não encontrado.',
                ], 404);
            }
    
             //Verifica se a senha fornecida é a mesma que a do usuário
            if (Hash::check($r->input('senha'), $u->senha)) {

                //Envia mensagem de sucesso 
                return response()->json([
                    'status' => true,
                    'mensagem' => 'Operação confirmada.'
                ], 200);

            } else {//Envia mensagem de erro
                return response()->json([
                    'status' => false,
                    'mensagem' => 'Senha incorreta.'
                ], 400);
            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Erro ao confirmar senha.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }
}