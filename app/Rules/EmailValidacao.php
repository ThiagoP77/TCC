<?php

//Namespace
namespace App\Rules;

//Namespaces utilizados
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

//Classe para validação de domínio de email
class EmailValidacao implements ValidationRule
{

    //Função da rule
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
           
        //Recebe o email
        $email = $value;

        // Verifica se o email contém o caractere '@'
        if (strpos($email, '@') !== false) {

            // Em caso de sucesso, não envia mensagem de erro
            if ($this->validarDominio($email)) {

            } else {
                //Mensagem de erro
                $fail("Email apresenta domínio inválido ou a verificação não foi possível (problema de rede).");
                return;
            }
            
        } else {
            //Erro (a outra validação já envia o erro)
            return;
        }
            
    }

    //Função que realiza a validação de domínio e retorna um boolean
    private function validarDominio($email)
    {
        $d = substr(strrchr($email, "@"), 1);
        return checkdnsrr($d, 'MX');
    }
}
