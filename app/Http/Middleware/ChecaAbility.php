<?php

//Namespace
namespace App\Http\Middleware;

//Namespaces utilizados
use App\Exceptions\AbilityException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\AuthenticationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;

//Classe que realiza a verificação das abilties de um token
class ChecaAbility
{
    
    //Obs.: Função feita pelo próprio Laravel, apenas substitui a Exceção lançada para realizar tratamento personalizado
    public function handle($request, $next, ...$abilities)
    {
        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException;
        }

        foreach ($abilities as $ability) {
            if ($request->user()->tokenCan($ability)) {
                return $next($request);
            }
        }

        throw new AbilityException();
    }
}
