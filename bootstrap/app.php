<?php

use App\Exceptions\AbilityException;
use App\Http\Middleware\ChecaAbilities;
use App\Http\Middleware\ChecaAbility;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => ChecaAbilities::class,
            'ability' => ChecaAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        $exceptions->render(function(AuthenticationException $e){
            return response()->json([
                'mensagem' => 'Token inválido, faça login para acessar essa rota.',
                'erro' => $e->getMessage()
            ], 401);
        });

        $exceptions->render(function(AbilityException $e){
            return response()->json([
                'mensagem' => 'Você não tem permissão para acessar essa rota.',
            ], 401);
        });
    })->create();
