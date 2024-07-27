<?php

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjValidacao implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try{
            if (empty($value)) {
                $fail("O CNPJ não pode estar vazio.");
            }
    
            $validFormat = preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', $value);
            if (!$validFormat) {
                $fail("O CNPJ deve estar no formato XX.XXX.XXX/XXXX-XX.");
            }
    
            $numbers = $this->matchNumbers($value);
    
            if (count($numbers) !== 14) {
                $fail("O CNPJ deve conter exatamente 14 dígitos numéricos.");
            }
    
            $items = array_unique($numbers);
            if (count($items) === 1) {
                $fail("O CNPJ não pode ter todos os dígitos iguais.");
            }
    
            $digits = array_slice($numbers, 12);
            $digit0 = $this->validCalc(12, $numbers);
            
            if ($digit0 != $digits[0]) {
                $fail("Dígitos verificadores do CNPJ não conferem.");
            }
    
            $digit1 = $this->validCalc(13, $numbers);
    
            if ($digit1 != $digits[1]) {
                $fail("Dígitos verificadores do CNPJ não conferem.");
            }
        } catch (Exception $e) {
            $fail("CNPJ inválido inserido.");
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

    //Validação adaptada de: https://gist.github.com/alexbruno/6623b5afa847f891de9cb6f704d86d02

}

