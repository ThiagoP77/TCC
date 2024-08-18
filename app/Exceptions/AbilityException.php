<?php

//Namespace
namespace App\Exceptions;

//Namespaces utilizados
use Exception;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

//Classe de exceção própria criada para indicar falta de abilities em um token de requisição
class AbilityException extends Exception
{
    //
}
