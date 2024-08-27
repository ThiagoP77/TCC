<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Rules\CepValidacao;
use App\Services\ConsultaCEPService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

//Classe de controle com funções úteis para o site
class CoisasUteisController extends Controller
{
    
    protected $consultaCepService;//Atributo com o serviço de consulta de CEP

    //Construtor já com a criação do serviço
    public function __construct(ConsultaCEPService $consultaCepService)
    {
        $this->consultaCepService = $consultaCepService;
    }

    //Função de consultar um CEP
    public function procurarCEP (Request $r) {
        try {//Testa exceção

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'cep' => [
                    'required', 
                    'string', 
                    new CepValidacao
                ]
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

            //Recebe os dados do endereço
            $cepData = $resultado['data'];

            //Envia a mensagem de sucesso com os campos necessários
            return response()->json([
                'cep' => $cepData['cep'],
                'logradouro' => $cepData['logradouro'],
                'bairro' => $cepData['bairro'],
                'localidade' => $cepData['localidade'],
                'uf' => $cepData['uf']
            ], 200);

        } catch (Exception $e) {//Envia a exceção

                return response()->json([
                    'mensagem' => 'Erro ao consultar CEP.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

}
