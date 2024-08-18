<?php

//Namespaces utilizados
use App\Exceptions\AbilityException;
use App\Http\Middleware\ChecaAbilities;
use App\Http\Middleware\ChecaAbility;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

//Classe com configurações mais internas
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(//Configurações de arquivos para lidarem com rotas
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {//Configurações dos middlewares
        $middleware->alias([
            'abilities' => ChecaAbilities::class,//Definindo uma classe própria para lidar com abilities do Sanctum
            'ability' => ChecaAbility::class,//Definindo uma classe própria para lidar com abilities do Sanctum
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {//Configurações personalizadas de tratamento de exceções
        
        $exceptions->render(function(AuthenticationException $e){//Retorno fixo de mensagem de erro para caso de Token inválido
            return response()->json([
                'mensagem' => 'Token inválido, faça login para acessar essa rota.',
                'erro' => $e->getMessage()
            ], 401);
        });

        $exceptions->render(function(AbilityException $e){//Retorno fixo de mensagem de erro para caso de abilitie não presente no Token
            return response()->json([
                'mensagem' => 'Você não tem permissão para acessar essa rota.',
            ], 401);
        });

        $exceptions->render(function(QueryException $e){//Retorno fixo de mensagem de erro para caso de problema de conexão com o banco
            return response()->json([
                'mensagem' => 'Erro ao conectar com o banco de dados.',
            ], 500);
        });
    })->create();
