<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Cliente;
use App\Models\Api\EnderecoCliente;
use App\Rules\CepValidacao;
use App\Services\ConsultaCEPService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


//Classe controladora de endereços do cliente
class EnderecoClienteController extends Controller
{

    protected $consultaCepService;//Atributo com o serviço de consulta de CEP

    //Construtor já com a criação do serviço
    public function __construct(ConsultaCEPService $consultaCepService)
    {
        $this->consultaCepService = $consultaCepService;
    }
    
    //Função de criar um novo endereço
    public function adicionarEndereco (Request $r) {
        try {

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'cep' => [
                    'required', 
                    'string', 
                    new CepValidacao
                ],

                'numero' => [
                    'required', 
                    'string', 
                    'regex:/^\d+$/'
                ]
            ], [

                'numero.regex' => 'O número deve conter apenas números.',
                'numero.required' => 'O campo número é obrigatório.',
                'numero.string' => 'O número deve ser uma string.',

            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Instancia do serviço de consultar CEP
            $consultaCepService = $this->consultaCepService;

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

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

            DB::beginTransaction();//Inicia a operação no banco

            //Recebe os dados do endereço
            $cepData = $resultado['data'];

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;

            //Criação do endereço do vendedor com os dados da requisição
            $enderecoC = new EnderecoCliente();
            $enderecoC->cep = $cepData['cep'];
            $enderecoC->logradouro = $cepData['logradouro'];
            $enderecoC->bairro = $cepData['bairro'];
            $enderecoC->localidade = $cepData['localidade'];
            $enderecoC->uf = $cepData['uf'];
            $enderecoC->numero = $dadosValidados['numero'];
            $enderecoC->id_cliente = $cliente->id;//Associando o endereço ao cliente
            $enderecoC->save();//Salvando endereço

            DB::commit();//Fazendo commit da operação

            //Envia mensagem de sucesso caso os códigos sejam iguais
            return response()->json([
                'mensagem' => 'Endereço cadastrado com sucesso!',
            ], 200);

        } catch (Exception $e) {

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao cadastrar endereço.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Função de excluir endereço
    public function excluirEndereco (Request $r, $id) {
        
        try {//Testa exceção

            //Verifica se o id informado é númerico e existe na tabela de endereços. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !EnderecoCliente::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Endereço não encontrado.'
                ], 404);
            }

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;

            //Verifica se o id informado é númerico e existe na tabela de endereços. Caso não existe, envia mensagem de erro
            if (!EnderecoCliente::where('id', $id)->where('id_cliente', $cliente->id)->exists()) {
                return response()->json([
                    'mensagem' => 'Endereço não pertence a esse cliente.'
                ], 401);
            }
    
            //Encontra o endereço informado pelo id
            $e = EnderecoCliente::find($id);

            //Caso consiga deletar o endereço, irá entrar no if 
            if ($e->delete()) {
    
                $e->delete();//Deletando o endereço

                return response()->json([//Envia mensagem de sucesso caso tudo tenha ocorrido de forma correta
                    'mensagem' => 'Endereço excluído com sucesso.'
                ], 200);

            } else {//Mensagem de erro caso não se encaixe em nenhum if

                return response()->json([
                    'mensagem' => 'Endereço não encontrado.'
                ], 404);

            }

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao excluir o endereço.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de listar todos os endereços do usuário logado
    public function listarEnderecos (Request $r) {
        try {

            //Obtém o usuário autenticado
            $u = $r->user(); 

            //Obtém o cliente
            $cliente = $u->cliente;

            //Caso o usuário ou cliente não sejam encontrados, envia mensagem de erro
            if (!$u || !$cliente) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.',
                ], 404);
            }

            //Encontra o endereços do cliente
            $resposta = EnderecoCliente::where('id_cliente', $cliente->id)
                            ->select('id', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero')
                            ->get();

            //Fornece a resposta de sucesso com os endereços
            return response()->json($resposta);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao listar endereços.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

    //Função de alteração de endereço
    public function alterarEndereco (Request $r, $id) {
        try {

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'cep' => [
                    'required', 
                    'string', 
                    new CepValidacao
                ],

                'numero' => [
                    'required', 
                    'string', 
                    'regex:/^\d+$/'
                ]
            ], [
                'numero.regex' => 'O número deve conter apenas números.',
                'numero.required' => 'O campo número é obrigatório.',
                'numero.string' => 'O número deve ser uma string.',
            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Verifica se o id informado é númerico e existe na tabela de endereços. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !EnderecoCliente::where('id', $id)->exists()) {
                return response()->json([
                    'mensagem' => 'Endereço não encontrado.'
                ], 404);
            }

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o cliente
            $cliente = $user->cliente;
            
            //Verifica se o id informado é númerico e existe na tabela de endereços. Caso não existe, envia mensagem de erro
            if (!EnderecoCliente::where('id', $id)->where('id_cliente', $cliente->id)->exists()) {
                return response()->json([
                    'mensagem' => 'Endereço não pertence a esse cliente.'
                ], 401);
            }

            //Instancia do serviço de consultar CEP
            $consultaCepService = $this->consultaCepService;

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

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

            DB::beginTransaction();//Inicia a operação no banco

            //Recebe os dados do endereço
            $cepData = $resultado['data'];

            //Encontra o usuário informado pelo id
            $enderecoC = EnderecoCliente::find($id);

            $enderecoC->cep = $cepData['cep'];
            $enderecoC->logradouro = $cepData['logradouro'];
            $enderecoC->bairro = $cepData['bairro'];
            $enderecoC->localidade = $cepData['localidade'];
            $enderecoC->uf = $cepData['uf'];
            $enderecoC->numero = $dadosValidados['numero'];
            $enderecoC->save();//Salvando endereço

            DB::commit();//Fazendo commit da operação

            //Envia mensagem de sucesso caso os códigos sejam iguais
            return response()->json([
                'mensagem' => 'Endereço alterado com sucesso!',
            ], 200);

        } catch (Exception $e) {

            DB::rollback();//Desfaz todas as operações realizadas no banco

                return response()->json([
                    'mensagem' => 'Erro ao alterar endereço.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Função de mostrar o endereço de id fornecido
    public function mostrarEndereco ($id) {
        try {

            //Verifica se o id informado é númerico e existe na tabela de usuários. Caso não existe, envia mensagem de erro
            if (!is_numeric($id) || !EnderecoCliente::where('id', $id)->exists()) {
                    return response()->json([
                        'mensagem' => 'Endereço não encontrado.'
                ], 404);
             }

            //Encontra o endereço de id fornecido
            $resposta = EnderecoCliente::where('id', $id)
                            ->select('id', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero')
                            ->get();

            //Resposta de sucesso com o endereço pedido
            return response()->json($resposta);

        } catch (Exception $e) {//Captura exceção e manda mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao exibir endereço.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }

}

