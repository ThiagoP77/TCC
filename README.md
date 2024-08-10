## Requisitos

* PHP 8.2 ou superior
* Composer

## Como rodar o projeto baixado

Duplicar o arquivo ".env.example" e nomear para ".env" <br>
Alterar no arquivo ".env" as credenciais do banco de dados <br>
Criar banco de dados chamado "tcc" <br>

Instalar as dependências do PHP
```
composer install
```

Gerar a chave no arquivo ".env"
```
php artisan key:generate
```

Criação do link simbólico de armazenamento.
```
php artisan storage:link
```

Executar as migration
```
php artisan migrate
```

Executar as seeders
```
php artisan db:seed
```

Traduzido para português seguindo esses passos: https://github.com/lucascudo/laravel-pt-BR-localization

Iniciar o projeto criado com Laravel
```
php artisan serve
```

Para acessar a API, é recomendado utilizar o Insomnia para simular requisições à api.
```
http://127.0.0.1:8000/api/
```
