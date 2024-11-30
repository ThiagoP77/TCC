<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utillizados

use App\Jobs\AceitoPedidoEntregadorJob;
use App\Jobs\AceitoPedidoLojaJob;
use App\Jobs\EntregueJob;
use App\Jobs\RecusadoPedidoLojaJob;
use App\Models\Api\Cliente;
use App\Models\Api\ItemPedido;
use App\Models\Api\Pedido;
use App\Models\Api\Produto;
use App\Models\Api\Usuario;
use App\Models\Api\Vendedor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//Classe controladora de pedido
class PedidoController extends Controller
{
    
    //Rota de listar pedidos para a loja
    public function pedidosLoja (Request $r, $tipo) {

        try {//Testa se tem exceção
    
            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Obtém o vendedor
            $vendedor = $user->vendedor;
    
            //Caso o usuário ou vendedor não sejam encontrados
            if (!$user || !$vendedor) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário ou a loja.'
                ], 404);
            }

            //Verifica se o tipo é válido
            if (!is_numeric($tipo) || $tipo > 5 || $tipo < 1) {
                return response()->json(['mensagem' => 'Tipo de pedido inválido.'], 400);
            }

            //Da um valor diferente para o status dependendo do valor passado no parâmetro da url
            switch ($tipo) {

                case 1:
                    $status = "Pendente.";
                    break;
                case 2:
                    $status = "Aceito pela loja.";
                    break;
                case 3:
                    $status = "Aceito para entrega.";
                    break;
                case 4:
                    $status = "Entregue.";
                    break;
                case 5:
                    $status = "Recusado.";
                    break;
                case 6:
                    $status = "Cancelado.";
                    break;
            }
    
            //Caso já tenha entregador estabelecido
            if ($tipo == 3 || $tipo == 4) {

                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where('id_vendedor', $vendedor->id)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(relations: ['entregador' => function($query) {
                    $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['tipoVeiculo' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            } else {//Caso não tenha

                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where('id_vendedor', $vendedor->id)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_vendedor', 'id_pagamento', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
            
            }
            
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
    
        }
    }

    //Rota de listar pedidos para o vendedor
    public function pedidosLojaT (Request $r) {

        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Obtém o vendedor
            $vendedor = $user->vendedor;
    
            //Caso o usuário ou vendedor não sejam encontrados
            if (!$user || !$vendedor) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário ou a loja.'
                ], 404);
            }
    
            //Recupera os pedidos e demais dados importantes 
            $pedidosQuery = Pedido::where('id_vendedor', $vendedor->id)
            ->with(['cliente' => function($query) {
                $query->select('id', 'telefone', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['vendedor' => function($query) {
                $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['endereco' => function ($subQuery) {
                    $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                }]);
            }])
            ->with(['metodoPagamento' => function($query) {
                $query->select('id', 'nome');
            }])
            ->with(['itens' => function($query) {
                $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                ->with(['produto' => function ($subQuery) {
                    $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                }]);;
            }])
            ->orderBy('updated_at', 'desc');

            //Recupera dados importantes
            $pedidos = $pedidosQuery->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            //Adiciona a relação 'entregador' se o status for "Aceito para entrega." ou "Entregue."
            foreach ($pedidos as $pedido) {
                if ($pedido->status == "Aceito para entrega." || $pedido->status == "Entregue.") {
                    $pedido->load(['entregador' => function($query) {
                        $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                            ->with(['usuario' => function ($subQuery) {
                                $subQuery->select('id', 'nome');
                            }])
                            ->with(['tipoVeiculo' => function ($subQuery) {
                                $subQuery->select('id', 'nome');
                            }]);
                    }]);
                } else {//Caso não seja, retira o id_entregador da respota
                    $pedido->makeHidden('id_entregador');
                }
            }
     
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
        
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
        
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
        
        }
    }

    //Aceitar pedido por id
    public function aceitarPedidoLoja (Request $r, $id) {
        try {//Testa se tem exceção
    
            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Obtém o cliente
            $vendedor = $user->vendedor;
    
            //Caso o usuário ou vendedor não sejam encontrados
            if (!$user || !$vendedor) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário ou a loja.'
                ], 404);
            }
    
            //Recupera os pedidos pendentes e demais dados importantes 
            $pedido = Pedido::find($id);
    
            //Caso não encontre o pedido, envia mensagem de erro
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }

            //Caso o pedido não seja do vendedor logado, envia mensagem de erro
            if ($pedido->id_vendedor !== $vendedor->id) {
                return response()->json([
                    'mensagem' => 'Você não tem permissão para aceitar este pedido.'
                ], 403);
            } 

            //Caso o pedido não esteja pendente, envia mensagem de erro
            if($pedido->status != "Pendente.") {
                return response()->json([
                    'mensagem' => 'Esse pedido não apresenta status de pendente e não pode ser aceito.'
                ], 400);
            } 

            //Muda o status de pedido e salva
            $pedido->status = "Aceito pela loja.";
            $pedido->save();

            //Recebe o nome da loja
            $nomeLoja = $user->nome;

            //Procura o cliente
            $cliente = Cliente::find($pedido->id_cliente);

            //Verifica se ele existe mesmo e envia email avisando que o pedido foi aceito
            if ($cliente) {
                $userCliente = $cliente->usuario;//Pega o usuário associado ao cliente

                $nomeCliente = $userCliente->nome;//Nome do cliente
                $emailCliente = $userCliente->email;//Email do cliente

                //Método construtor com os parâmetros necessários
                AceitoPedidoLojaJob::dispatch($emailCliente, $nomeLoja, $nomeCliente);
            }

            //Envia mensagem de sucesso
            return response()->json([
                'mensagem' => 'Pedido aceito com sucesso.'
            ], 200);
            
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao aceitar pedido.',
                    'erro' => $e->getMessage()
                ], 400);
        }
    }

    //Recusar pedido por id
    public function recusarPedidoLoja (Request $r, $id) {
        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 
        
            //Obtém o cliente
            $vendedor = $user->vendedor;
        
            //Caso o usuário ou vendedor não sejam encontrados
            if (!$user || !$vendedor) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário ou a loja.'
                ], 404);
            }
        
            //Recupera os pedidos pendentes e demais dados importantes 
            $pedido = Pedido::find($id);
        
            //Caso não encontre o pedido, envia mensagem de erro
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }
    
            //Caso o pedido não seja do vendedor logado, envia mensagem de erro
            if ($pedido->id_vendedor !== $vendedor->id) {
                return response()->json([
                    'mensagem' => 'Você não tem permissão para recusar este pedido.'
                ], 403);
            } 
    
            //Caso o pedido não esteja pendente, envia mensagem de erro
            if($pedido->status != "Pendente.") {
                return response()->json([
                    'mensagem' => 'Esse pedido não apresenta status de pendente e não pode ser recusado.'
                ], 400);
            } 
    
            //Recupera os itens associados ao pedido
            $itens = ItemPedido::where("id_pedido", $pedido->id)->get();

            //Inicia uma transação no banco de dados
            DB::beginTransaction();

            //Percorre os itens do pedido, voltando ao estoque 
            foreach ($itens as $item) {
                    
                //Obtém o produto associado ao item
                $produto = Produto::find($item->id_produto);

                //Produto recupera os produtos em seu estoque
                if ($produto) {
                    $produto->qtde_estoque += $item->qtde;
                    $produto->save();
                }

            }

            //Muda o status do pedido
            $pedido->status = "Recusado.";
            $pedido->save();

            //Dá commit nas allterações no banco
            DB::commit();

            $nomeLoja = $user->nome;//Nome da loja

            $telefoneLoja = $vendedor->telefone;//Telefone da loja

            $cliente = Cliente::find($pedido->id_cliente);//Procura o cliente

            //Verifica se o cliente existe e envia emai informando que o pedido foi recusado
            if ($cliente) {
                $userCliente = $cliente->usuario;//Pega o usuário associado ao cliente

                $nomeCliente = $userCliente->nome;//Nome do cliente
                $emailCliente = $userCliente->email;//Email do cliente

                //Método construtor do email com os parâmetros necessários
                RecusadoPedidoLojaJob::dispatch($emailCliente, $nomeLoja, $nomeCliente, $telefoneLoja);
            }
    
            //Envia mensagem de sucesso
            return response()->json([
                'mensagem' => 'Pedido recusado com sucesso.'
            ], 200);
                
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            DB::rollBack();//Reverte as alterações no banco
        
            return response()->json([
                'mensagem' => 'Erro ao recusar pedido.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Rota de listar pedidos pendentes para a loja
    public function pedidosCliente (Request $r, $tipo) {

        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 
        
            //Obtém o cliente
            $cliente = $user->cliente;
        
            //Caso o usuário ou cliente não sejam encontrados
            if (!$user || !$cliente) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
    
            //Verifica se o tipo é válido e, se não for, envia mensagem de erro
            if (!is_numeric($tipo) || $tipo > 6 || $tipo < 1) {
                return response()->json(['mensagem' => 'Tipo de pedido inválido.'], 400);
            }
    
            //Da um valor diferente para o status dependendo do valor passado no parâmetro da url
            switch ($tipo) {
    
                case 1:
                    $status = "Pendente.";
                    break;
                case 2:
                    $status = "Aceito pela loja.";
                    break;
                case 3:
                    $status = "Aceito para entrega.";
                    break;
                case 4:
                    $status = "Entregue.";
                    break;
                case 5:
                    $status = "Recusado.";
                    break;
                case 6:
                    $status = "Cancelado.";
                    break;
            }
        
            //Caso já tenha entregador estabelecido
            if ($tipo == 3 || $tipo == 4) {
    
                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where('id_cliente', $cliente->id)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(relations: ['entregador' => function($query) {
                    $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['tipoVeiculo' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
    
            } else {//Caso não tenha
    
                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where('id_cliente', $cliente->id)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_vendedor', 'id_pagamento', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
                
            }
                
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
        
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
        
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
        
        }
    }

    //Rota de listar pedidos para o cliente
    public function pedidosClienteT (Request $r) {

        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 
        
            //Obtém o cliente
            $cliente = $user->cliente;
        
            //Caso o usuário ou cliente não sejam encontrados
            if (!$user || !$cliente) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
    
            //Recupera os pedidos e demais dados importantes 
            $pedidosQuery = Pedido::where('id_cliente', $cliente->id)
            ->with(['cliente' => function($query) {
                $query->select('id', 'telefone', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['vendedor' => function($query) {
                $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['endereco' => function ($subQuery) {
                    $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                }]);
            }])
            ->with(['metodoPagamento' => function($query) {
                $query->select('id', 'nome');
            }])
            ->with(['itens' => function($query) {
                $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                ->with(['produto' => function ($subQuery) {
                    $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                }]);;
            }])
            ->orderBy('updated_at', 'desc');

            //Recupera dados importantes
            $pedidos = $pedidosQuery->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            //Adiciona a relação 'entregador' se o status for "Aceito para entrega." ou "Entregue."
            foreach ($pedidos as $pedido) {
                if ($pedido->status == "Aceito para entrega." || $pedido->status == "Entregue.") {
                    $pedido->load(['entregador' => function($query) {
                        $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                            ->with(['usuario' => function ($subQuery) {
                                $subQuery->select('id', 'nome');
                            }])
                            ->with(['tipoVeiculo' => function ($subQuery) {
                                $subQuery->select('id', 'nome');
                            }]);
                    }]);
                } else {//Caso não seja, retira o id_entregador da respota
                    $pedido->makeHidden('id_entregador');
                }
            }
     
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
        
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
        
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
        
        }
    }

    //Cancelar pedido por id
    public function cancelarPedido (Request $r, $id) {
        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 
        
            //Obtém o cliente
            $cliente = $user->cliente;
        
            //Caso o usuário ou cliente não sejam encontrados
            if (!$user || !$cliente) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
        
            //Recupera o pedido com o ID fornecido
            $pedido = Pedido::find($id);
        
            //Caso não encontre o pedido, envia mensagem de erro
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }
    
            //Caso o pedido não seja do cliente logado, envia mensagem de erro
            if ($pedido->id_cliente !== $cliente->id) {
                return response()->json([
                    'mensagem' => 'Você não tem permissão para cancelar esse pedido.'
                ], 403);
            } 
    
            //Caso o pedido não esteja pendente, envia mensagem de erro
            if($pedido->status != "Pendente.") {
                return response()->json([
                    'mensagem' => 'Esse pedido não pode mais ser cancelado!'
                ], 400);
            } 
    
            //Recupera os itens associados ao pedido
            $itens = ItemPedido::where("id_pedido", $pedido->id)->get();

            //Inicia uma transação no banco de dados
            DB::beginTransaction();

            //Percorre os itens do pedido, voltando ao estoque 
            foreach ($itens as $item) {
                    
                //Obtém o produto associado ao item
                $produto = Produto::find($item->id_produto);

                //Produto recupera os produtos em seu estoque
                if ($produto) {
                    $produto->qtde_estoque += $item->qtde;
                    $produto->save();
                }

            }

            //Muda o status do pedido
            $pedido->status = "Cancelado.";
            $pedido->save();

            //Dá commit nas alterações no banco
            DB::commit();
    
            //Envia mensagem de sucesso
            return response()->json([
                'mensagem' => 'Pedido cancelado com sucesso.'
            ], 200);
                
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            DB::rollBack();//Reverte as alterações no banco
        
            return response()->json([
                'mensagem' => 'Erro ao cancelar pedido.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Rota de listar pedidos aceitos pelas lojas
    public function aceitosGeral () {

        try {//Testa se tem exceção
    
            //Recupera os pedidos e demais dados importantes 
            $pedidos = Pedido::where('status', 'Aceito pela loja.')
            ->with(['cliente' => function($query) {
                $query->select('id', 'telefone', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['vendedor' => function($query) {
                $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['endereco' => function ($subQuery) {
                    $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                }]);
            }])
            ->with(['metodoPagamento' => function($query) {
                $query->select('id', 'nome');
            }])
            ->with(['itens' => function($query) {
                $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                ->with(['produto' => function ($subQuery) {
                    $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                }]);;
            }])
            ->orderBy('updated_at', 'asc')
            ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
            
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
    
        }
    }

    //Aceitar pedido por id
    public function aceitarPedidoEntregador (Request $r, $id) {
        try {//Testa se tem exceção
    
            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Obtém o entregador
            $entregador = $user->entregador;
    
            //Caso o usuário ou entregador não sejam encontrados
            if (!$user || !$entregador) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
    
            //Recupera o pedido pelo ID
            $pedido = Pedido::find($id);
    
            //Caso não encontre o pedido, envia mensagem de erro
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }

            //Caso o pedido não tenha sido aceito pela loja, envia mensagem de erro
            if($pedido->status != "Aceito pela loja.") {
                return response()->json([
                    'mensagem' => 'Esse pedido não pode mais ser aceito.'
                ], 400);
            } 

            //Caso o pedido seja de outro entregador, envia mensagem de erro
            if(!is_null($pedido->id_entregador)) {
                return response()->json([
                    'mensagem' => 'Esse pedido já foi aceito por um entregador.'
                ], 400);
            } 

            //Muda o status de pedido e salva
            $pedido->id_entregador = $entregador->id;
            $pedido->status = "Aceito para entrega.";
            $pedido->save();

            //Recebe o nome da loja
            $nomeEntregador = $user->nome;
            $telefoneEntregador = $entregador->telefone;

            //Procura o cliente
            $cliente = Cliente::find($pedido->id_cliente);

            //Procura a loja
            $loja = Vendedor::find($pedido->id_vendedor);

            //Verifica se cliente e loja existem e envia um email para o cliente
            if ($cliente && $loja) {
                $userCliente = $cliente->usuario;//Pega o usuário associado ao cliente

                $nomeCliente = $userCliente->nome;//Nome do cliente
                $emailCliente = $userCliente->email;//Email do cliente

                $nomeLoja = $loja->usuario->nome;

                //Método construtor com os parâmetros necessários
                AceitoPedidoEntregadorJob::dispatch($emailCliente, $nomeEntregador, $telefoneEntregador, $nomeCliente, $nomeLoja);
            }

            //Envia mensagem de sucesso
            return response()->json([
                'mensagem' => 'Pedido aceito com sucesso.'
            ], 200);
            
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao aceitar pedido.',
                    'erro' => $e->getMessage()
                ], 400);
        }
    }

    //Rota de listar pedidos para o entregador
    public function pedidosEntregador (Request $r, $tipo) {

        try {//Testa se tem exceção

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o entregador
            $entregador = $user->entregador;

            //Caso o usuário ou entregador não sejam encontrados
            if (!$user || !$entregador) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }

            //Verifica se o tipo é válido
            if (!is_numeric($tipo) || $tipo > 2 || $tipo < 1) {
                return response()->json(['mensagem' => 'Tipo de pedido inválido.'], 400);
            }

            //Da um valor diferente para o status dependendo do valor passado no parâmetro da url
            switch ($tipo) {

                case 1:
                    $status = "Aceito para entrega.";
                    break;
                case 2:
                    $status = "Entregue.";
                    break;

            }

            //Recupera os pedidos e demais dados importantes 
            $pedidos = Pedido::where('id_entregador', $entregador->id)
            ->where('status', $status)
            ->with(['cliente' => function($query) {
                $query->select('id', 'telefone', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['vendedor' => function($query) {
                $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['endereco' => function ($subQuery) {
                    $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                }]);
            }])
            ->with(relations: ['entregador' => function($query) {
                $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['tipoVeiculo' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['metodoPagamento' => function($query) {
                $query->select('id', 'nome');
            }])
            ->with(['itens' => function($query) {
                $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                ->with(['produto' => function ($subQuery) {
                    $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                }]);
            }])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);

        }
    }

    //Rota de listar pedidos para o entregador
    public function pedidosEntregadorT (Request $r) {

        try {//Testa se tem exceção
        
            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o entregador
            $entregador = $user->entregador;

            //Caso o usuário ou entregador não sejam encontrados
            if (!$user || !$entregador) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
    
            //Recupera os pedidos e demais dados importantes 
            $pedidos = Pedido::where('id_entregador', $entregador->id)
            ->with(['cliente' => function($query) {
                $query->select('id', 'telefone', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['vendedor' => function($query) {
                $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['endereco' => function ($subQuery) {
                    $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                }]);
            }])
            ->with(relations: ['entregador' => function($query) {
                $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                ->with(['usuario' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }])
                ->with(['tipoVeiculo' => function ($subQuery) {
                    $subQuery->select('id', 'nome');
                }]);
            }])
            ->with(['metodoPagamento' => function($query) {
                $query->select('id', 'nome');
            }])
            ->with(['itens' => function($query) {
                $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                ->with(['produto' => function ($subQuery) {
                    $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                }]);;
            }])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
     
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
        
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
        
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
        
        }
    }

    //Aceitar pedido por id
    public function marcarEntregue (Request $r, $id) {
        try {//Testa se tem exceção
    
            //Obtém o usuário autenticado
            $user = $r->user(); 
    
            //Obtém o entregador
            $entregador = $user->entregador;
    
            //Caso o usuário ou entregador não sejam encontrados
            if (!$user || !$entregador) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar seu usuário.'
                ], 404);
            }
    
            //Recupera o pedido pelo id
            $pedido = Pedido::find($id);
    
            //Caso não encontre o pedido, envia mensagem de erro
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }

            //Caso o pedido não tenha sido aceito para entregue, envia mensagem de erro
            if($pedido->status != "Aceito para entrega.") {
                return response()->json([
                    'mensagem' => 'Esse pedido não pode ser marcado como entregue.'
                ], 400);
            } 

            //Caso o pedido não seja do entregador, envia mensagem de erro
            if($pedido->id_entregador != $entregador->id) {
                return response()->json([
                    'mensagem' => 'Você não tem permissão para marcar esse pedido como entregue.'
                ], 400);
            } 

            //Muda o status de pedido e salva
            $pedido->status = "Entregue.";
            $pedido->save();

            //Procura o cliente
            $cliente = Cliente::find($pedido->id_cliente);

            //Procura a loja
            $loja = Vendedor::find($pedido->id_vendedor);

            //Verifica se cliente e loja existem e envia um email para o cliente
            if ($cliente && $loja) {
                $userCliente = $cliente->usuario;//Pega o usuário associado ao cliente

                $nomeCliente = $userCliente->nome;//Nome do cliente
                $emailCliente = $userCliente->email;//Email do cliente

                $nomeLoja = $loja->usuario->nome;//Nome da loja
                $telefoneLoja = $loja->telefone;//Telefone da loja

                //Método construtor com os parâmetros necessários
                EntregueJob::dispatch($emailCliente, $nomeCliente, $nomeLoja, $telefoneLoja);
            }

            //Envia mensagem de sucesso
            return response()->json([
                'mensagem' => 'Pedido marcado como entregue com sucesso.'
            ], 200);
            
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao marcar pedido como entregue.',
                    'erro' => $e->getMessage()
                ], 400);
        }
    }

    
    //Rota de listar pedidos para a loja
    public function pedidosAdm ($id, $tipo) {

        try {//Testa se tem exceção
    
            //Obtém o usuário autenticado
            $user = Usuario::find($id); 

            //Caso o usuário ou vendedor não sejam encontrados
            if (!$user) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o usuário.'
                ], 404);
            }
    
            //Obtém o vendedor
            $categoria = $user->id_categoria;

    
            //Caso o usuário ou vendedor não sejam encontrados
            if ($categoria < 2 || $categoria > 4) {
                return response()->json([
                    'mensagem' => 'Categoria inexistente ou o usuário é administrador.'
                ], 404);
            }

            //Da um valor diferente para o status dependendo do valor passado no parâmetro da url
            switch ($categoria) {

                case 2:
                    $coluna = "id_cliente";
                    $id_c = $user->cliente->id;
                    break;
                case 3:
                    $coluna = "id_vendedor";
                    $id_c = $user->vendedor->id;
                    break;
                case 4:
                    $coluna = "id_entregador";
                    $id_c = $user->entregador->id;
                    break;

            }

            //Verifica se o tipo é válido
            if (!is_numeric($tipo) || $tipo > 6 || $tipo < 1) {
                return response()->json(['mensagem' => 'Tipo de pedido inválido.'], 400);
            }

            //Da um valor diferente para o status dependendo do valor passado no parâmetro da url
            switch ($tipo) {

                case 1:
                    $status = "Pendente.";
                    break;
                case 2:
                    $status = "Aceito pela loja.";
                    break;
                case 3:
                    $status = "Aceito para entrega.";
                    break;
                case 4:
                    $status = "Entregue.";
                    break;
                case 5:
                    $status = "Recusado.";
                    break;
                case 6:
                    $status = "Cancelado.";
                    break;

            }

            //Verifica se apresenta um status apropriado quando o usuário é entregador
            if ($categoria == 4) {
                if ($tipo != 3 && $tipo != 4) {
                    return response()->json(['mensagem' => 'Esse tipo de pedido não apresenta entregador.'], 400);
                }
            }
    
            //Caso já tenha entregador estabelecido
            if ($tipo == 3 || $tipo == 4) {

                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where($coluna, $id_c)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(relations: ['entregador' => function($query) {
                    $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['tipoVeiculo' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            } else {//Caso não tenha

                //Recupera os pedidos e demais dados importantes 
                $pedidos = Pedido::where($coluna, $id_c)
                ->where('status', $status)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_vendedor', 'id_pagamento', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
            
            }
            
            //Envia mensagem de sucesso
            return response()->json($pedidos, 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao listar pedidos.',
                    'erro' => $e->getMessage()
                ], 400);
    
        }
    }

    //Rota de mostrar dados de um pedido a partir do ID
    public function dadosPedido ($id) {

        try {//Testa se tem exceção
    
            //Obtém o pedido informado
            $pedido = Pedido::find($id); 

            //Caso o pedido não seja encontrado
            if (!$pedido) {
                return response()->json([
                    'mensagem' => 'Falha ao encontrar o pedido.'
                ], 404);
            }

            //Obtem o status do pedido
            $status = $pedido->status;
    
            //Caso já tenha entregador estabelecido
            if ($status == "Aceito para entrega." || $status == "Entregue.") {

                //Recupera os dados do pedido e informações importantes
                $pedidoI = Pedido::where('id', $id)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(relations: ['entregador' => function($query) {
                    $query->select('id', 'telefone', 'placa', 'id_tipo_veiculo', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['tipoVeiculo' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_pagamento', 'id_vendedor', 'id_entregador', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);

            } else {//Caso não tenha

                //Recupera os dados do pedido e informações importantes 
                $pedidoI = Pedido::where('id', $id)
                ->with(['cliente' => function($query) {
                    $query->select('id', 'telefone', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }]);
                }])
                ->with(['vendedor' => function($query) {
                    $query->select('id', 'telefone', 'whatsapp', 'id_usuario')
                    ->with(['usuario' => function ($subQuery) {
                        $subQuery->select('id', 'nome');
                    }])
                    ->with(['endereco' => function ($subQuery) {
                        $subQuery->select('id', 'id_vendedor', 'cep', 'logradouro', 'bairro', 'localidade', 'uf', 'numero');
                    }]);
                }])
                ->with(['metodoPagamento' => function($query) {
                    $query->select('id', 'nome');
                }])
                ->with(['itens' => function($query) {
                    $query->select('id_pedido', 'id_produto', 'qtde', 'preco', 'desconto')
                    ->with(['produto' => function ($subQuery) {
                        $subQuery->select('id', 'nome', 'descricao', 'imagem_produto');
                    }]);;
                }])
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'id_cliente', 'id_vendedor', 'id_pagamento', 'status', 'precisa_troco', 'troco', 'total', 'lucro_loja', 'lucro_adm', 'lucro_entregador', 'endereco_cliente']);
            
            }
            
            //Envia mensagem de sucesso
            return response()->json($pedidoI, 200);
    
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
    
                return response()->json([
                    'mensagem' => 'Erro ao mostrar dados do pedido.',
                    'erro' => $e->getMessage()
                ], 400);
    
        }
    }

}
