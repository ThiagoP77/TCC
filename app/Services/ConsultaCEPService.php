<?php

//Namespace
namespace App\Services;

//Namespaces utilizados
use Exception;
use Illuminate\Support\Facades\Http;

//Classe de consultar CEP pela API online ViaCEP
class ConsultaCEPService
{
    protected $baseUrl = 'https://viacep.com.br/ws/';//Parte inicial da url  de acesso à API

    //Função de consultar a API
    public function consultarCep(string $cep): array
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);//Deixa somente os números no CEP

        try {//Testa se gera erro
            $response = Http::get($this->baseUrl . $cep . '/json/');//Cria a url completa de acessar a API e realiza a requisição
            $responseData = $response->json();//Recebe o json

            if (isset($responseData['erro']) && $responseData['erro']) {//Verifica se tem campo de erro e, caso tenha, envia mensagem de erro
                return [
                    'data' => ['mensagem' => 'CEP não encontrado.'],
                    'status' => 400,
                ];
            }

            $requiredKeys = ['cep', 'logradouro', 'bairro', 'localidade', 'uf'];//Campos necessários no json recebido

            foreach ($requiredKeys as $key) {//Verifica se o json tem todos os campos necessários e, caso não tenha, envia mensagem de erro
                if (!isset($responseData[$key]) || empty($responseData[$key])) {
                    return [
                        'data' => ['mensagem' => 'Resposta da API malformada.'],
                        'status' => 500,
                    ];
                }
            }

            return [//Mensagem de sucesso caso não tenha erros
                'data' => $responseData,
                'status' => 200,
            ];

        } catch (Exception $e) {//Captura o erro e envia mensagem de erro
            return [
                'data' => [
                    'mensagem' => 'Erro ao consultar o CEP.',
                    'erro' => $e->getMessage(),
                ],
                'status' => 500,
            ];
        }
    }
}