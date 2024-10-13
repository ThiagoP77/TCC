<?php

namespace App\Services;

use App\Models\Api\Carrinho;
use App\Models\Api\Produto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LimparCarrinhosService
{

    //Função de excluir os tokens expirados
    public function __invoke() 
    {

        //Define a data limite para exclusão
        $dataLimite = Carbon::now();

        //Obtém os registros expirados do carrinho
        $carrinhosExpirados = Carrinho::where('expires_at', '<=', $dataLimite)->get();

        foreach ($carrinhosExpirados as $carrinho) {
            
            //Obtém o produto associado ao carrinho
            $produto = Produto::find($carrinho->id_produto);
            
            if ($produto) {

                //Adiciona a quantidade de volta ao estoque
                $produto->qtde_estoque += $carrinho->qtde; // Supondo que você tenha um campo `estoque`
                $produto->save(); // Salva as alterações no produto

            }

            //Exclui o registro do carrinho
            $carrinho->delete();
        }
 
    }

    //Limpar carrinhos expirados de clientes específicos
    public function limparCarrinhosExpiradosPorCliente($idCliente, $idLoja) 
    {
        {
            // Define a data limite para exclusão
            $dataLimite = Carbon::now();
    
            // Obtém os registros expirados do carrinho para o cliente e loja específicos
            $carrinhosExpirados = Carrinho::where('expires_at', '<=', $dataLimite)
                ->where('id_cliente', $idCliente)
                ->where('id_vendedor', $idLoja) // Considerando que o ID da loja é o id_vendedor
                ->get();
    
            foreach ($carrinhosExpirados as $carrinho) {
                // Obtém o produto associado ao carrinho
                $produto = Produto::find($carrinho->id_produto);
                
                if ($produto) {
                    // Adiciona a quantidade de volta ao estoque
                    $produto->qtde_estoque += $carrinho->qtde; // Supondo que você tenha um campo `estoque`
                    $produto->save(); // Salva as alterações no produto
                }
    
                // Exclui o registro do carrinho
                $carrinho->delete();
            }
        }
}
}
