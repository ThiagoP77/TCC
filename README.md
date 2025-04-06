## Descrição da API

API de um sistema de e-commerce de doces, desenvolvida como TCC de curso técnico em informática.

## Descrição de Rotas da API

Disponível em:
```
https://docs.google.com/document/d/17Zx_dkY4cznlRPmKFjBSh4Ial_KhKQsLubPIg4MIjs0/edit?usp=sharing
```

## Requisitos

* PHP 8.2 ou superior <br>
* Composer <br><br>
* Redis-Cli 6.0.16
```
Utilizar o WSL como demonstrado no tutorial: https://www.youtube.com/watch?v=5VZpzwJeMDo
```

## Preparando o ambiente para rodar o projeto baixado

Duplicar o arquivo ".env.example" e nomear para ".env" <br>
Alterar no arquivo ".env" as credenciais do banco de dados <br>
Criar banco de dados chamado "tcc" <br>

Instalar as dependências do PHP
```
composer install --ignore-platform-reqs
```

Gerar a chave no arquivo ".env"
```
php artisan key:generate
```

Criação do link simbólico de armazenamento
```
php artisan storage:link
```

Executar as migration / criar o banco de dados (pela primeira vez)
```
php artisan migrate
```

Executar as seeders / preencher o banco de dados
```
php artisan db:seed
```

Executar as migration de novo / recriar o banco de dados (terá que preencher de novo)
```
php artisan migrate:fresh
```

## Comandos para rodar o projeto baixado

Em um terminal, iniciar o projeto criado com Laravel
```
php artisan serve
```

Em outro terminal, ativar as tarefas agendadas 
```
php artisan schedule:work
```

Em outro terminal, ativar a fila do jobs
```
php artisan queue:work
```

## Como acessar a API

Para acessar a API, utilize o caminho abaixo
```
http://127.0.0.1:8000/api/
```
