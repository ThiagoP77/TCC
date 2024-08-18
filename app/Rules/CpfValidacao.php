<?php

//Namespace
namespace App\Rules;

//Namespaces utilizados
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

//Classe de validação de CPF
class CpfValidacao implements ValidationRule
{
    //Função de validação
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        //Adaptação do código disponível em (apenas adicionando os comandos de envio de falha e de tratamento de erro): https://gist.github.com/rafael-neri/ab3e58803a08cb4def059fce4e3c0e40

        try {
            if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $value)) {
                $fail ("CPF deve seguir o formato XXX.XXX.XXX-XX!");
                return;
            }
    
            $value = preg_replace( '/[^0-9]/is', '', $value );
            
            if (strlen($value) != 11) {
                $fail ("CPF inserido não é válido!");
                return;
            }
    
            if (preg_match('/(\d)\1{10}/', $value)) {
                $fail ("CPF inserido não é válido!");
                return;
            }
    
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $value[$c] * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($value[$c] != $d) {
                    $fail ("CPF inserido não é válido!");
                    return;
                }
            }
        } catch (Exception $e) {
            $fail("CPF inválido inserido.");
            return;
        }

    }
}
