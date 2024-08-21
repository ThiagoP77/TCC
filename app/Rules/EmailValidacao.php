<?php

//Namespace
namespace App\Rules;

//Namespaces utilizados
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

//Classe para validação de domínio de email
class EmailValidacao implements ValidationRule
{

    //Função da rule
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        
        //Recebe o email
        $email = $value;

        //Em caso de sucesso, não envia mensagem de erro
        if ($this->validarDominio($email)) {
            
        } else {//Mensagem de erro
            $fail("Email apresenta domínio inválido.");
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
