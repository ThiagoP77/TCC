<?php

//Namespace
namespace App\Rules;

//Namespaces utilizados
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

//Classe de validação de CNPJ
class CnpjValidacao implements ValidationRule
{
    
    //Função de validação
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        //Adaptação do código disponível em (apenas adicionando os comandos de envio de falha e de tratamento de erro): https://gist.github.com/alexbruno/6623b5afa847f891de9cb6f704d86d02

        try{
            if (empty($value)) {
                $fail("O CNPJ não pode estar vazio.");
                return;
            }
    
            $validFormat = preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', $value);
            if (!$validFormat) {
                $fail("O CNPJ deve estar no formato XX.XXX.XXX/XXXX-XX.");
                return;
            }
    
            $numbers = $this->matchNumbers($value);
    
            if (count($numbers) !== 14) {
                $fail("O CNPJ deve conter exatamente 14 dígitos numéricos.");
                return;
            }
    
            $items = array_unique($numbers);
            if (count($items) === 1) {
                $fail("O CNPJ não pode ter todos os dígitos iguais.");
                return;
            }
    
            $digits = array_slice($numbers, 12);
            $digit0 = $this->validCalc(12, $numbers);
            
            if ($digit0 != $digits[0]) {
                $fail("Dígitos verificadores do CNPJ não conferem.");
                return;
            }
    
            $digit1 = $this->validCalc(13, $numbers);
    
            if ($digit1 != $digits[1]) {
                $fail("Dígitos verificadores do CNPJ não conferem.");
                return;
            }
        } catch (Exception $e) {
            $fail("CNPJ inválido inserido.");
            return;
        }
        }
        

    private function matchNumbers(string $value): array
    {
        preg_match_all('/\d/', $value, $matches);
        return $matches[0];
    }

    // Função para calcular dígitos verificadores
    private function validCalc(int $x, array $numbers): int
    {
        $slice = array_slice($numbers, 0, $x);
        $factor = $x - 7;
        $sum = 0;

        for ($i = $x; $i >= 1; $i--) {
            $n = $slice[$x - $i];
            $sum += $n * $factor--;
            if ($factor < 2) {
                $factor = 9;
            }
        }

        $result = 11 - ($sum % 11);
        return $result > 9 ? 0 : $result;
    }

}

