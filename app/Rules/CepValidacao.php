<?php

//Namespace
namespace App\Rules;

//Namespaces utilizados
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

//Classe de validação de CEP
class CepValidacao implements ValidationRule
{

    protected $baseUrl = 'https://viacep.com.br/ws/';//Atributo que recebe parte da URL de acesso à API de consulta de CEP

    //Função de validação de CEP
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d{5}-\d{3}$/', $value)) {//Retorna uma falha caso não esteja no formato XXXXX-XXX
            $fail("CEP deve seguir o formato XXXXX-XXX! (Somente números)");
            return;
        }

        $value = preg_replace('/[^0-9]/', '', $value);//Deixa somente os números
        
        if (strlen($value) != 8) {//Retorna uma falha caso não possua 8 números
            $fail("CEP inserido não é válido!");
            return;
        }

        try {//Tenta acessar a API
            $response = Http::get($this->baseUrl . $value . '/json/');//URL completa de acesso à API e realiza a requisição
            $responseData = $response->json();//Recebe o json de resposta

            if (isset($responseData['erro']) && $responseData['erro']) {//Retorna uma falha caso a requisição retorne um erro
                $fail("CEP não encontrado!");
                return;
            }

            $requiredKeys = ['cep', 'logradouro', 'bairro', 'localidade', 'uf'];//Campos necessários no json recebido

            foreach ($requiredKeys as $key) {//Verifica se o json tem todos os campos necessários e, caso não tenha, envia mensagem de erro
                if (!isset($responseData[$key]) || empty($responseData[$key])) {
                    $fail("Resposta da API malformada!");
                    return;
                }
            }
        } catch (Exception $e) {//Retorna falha caso apareça alguma exceção
            $fail("Erro ao encontrar CEP: " . $e->getMessage());
            return;
        }
}
}

