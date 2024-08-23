<!DOCTYPE html>

<!-- Design do email enviado ao vendedor ou entregador quando não aceitos no site -->

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LA Doceria - Recusado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #c1e1ec;
            margin: 0;
            padding: 0;
        }
        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eaeaea;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-content {
            padding: 20px;
            text-align: center;
        }
        .email-content h2 {
            font-size: 20px;
            color: #333333;
        }
        .email-content p {
            font-size: 16px;
            color: #666666;
        }
        .email-button {
            background-color: #333333;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 20px 0;
        }
        .email-footer {
            text-align: center;
            font-size: 12px;
            color: #888888;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        .email-footer p {
            margin: 0;
        }
        .email-footer a {
            color: #888888;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>LADoceria</h1>
        </div>
        <div class="email-content">
            <h2>Prezado(a) {{ $nome}},</h2>
            <p>Lamentamos muito. Enviamos esse email para avisar que seus serviços como {{$funcao}} não foram aceitos pela nossa empresa!</p>
            <p>Analisamos seus dados e eles não atendem as nossas exigências. Para mais detalhes, entre em contato pelo telefone da empresa.</p>
            <p>Ficamos a disposição, forte abraço!</p>
            <p>Saudações,<br>LADoceria.</p>
        </div>
        <div class="email-footer">
            <p>© 2024 LADoceria. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>