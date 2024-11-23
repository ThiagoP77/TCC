<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Vendedor;
use App\Models\Api\FraseVendedor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

//Classe controladora para a frase do vendedor
class FraseVendedorController extends Controller
{
    
    //Função de cadastrar uma nova frase
    public function novaFrase(Request $r) {
        try {//Verifica exceção

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o vendedor
            $vendedor = $user->vendedor;

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$vendedor) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Realiza a validação dos dados recebidos no request
            $validator = Validator::make($r->all(), [
                'frase' => [
                    'required', 
                    'string',
                ],
            ]);
    
            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Recebe os dados validados
            $dadosValidados = $validator->validated();

            DB::beginTransaction();//Inicia a operação no banco

            //Se o vendedor já tiver allguma frase cadastrada, altera ela
            if ($vendedor->fraseVendedor) {

                //Recebe os dados da frase
                $f = $vendedor->fraseVendedor;

                //Altera o conteúdo da frase
                $f->frase = $dadosValidados['frase'];
                $f->save();//Salvando

            } else {//Se não tiver, cria uma

                //Criando um novo registro na tabela de frases do vendedor
                $f = new FraseVendedor();

                //Preenchendo os dados
                $f->id_vendedor = $vendedor->id;
                $f->frase = $dadosValidados['frase'];
                $f->save();//Salvando

            }

            DB::commit();//Dá commit nas operações

            //Envia mensagem de sucesso caso o processo não gere erros
            return response()->json([
                'mensagem' => 'Nova frase cadastrada com sucesso!',
            ], 200);

        } catch (Exception $e) {//Captura exceção e envia mensagem de erro

            //Reverte a transação em caso de erro
            DB::rollBack();

            return response()->json([
                'mensagem' => 'Falha ao fornecer a nova frase.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de excluir a frase do vendedor
    public function excluirFrase(Request $r)  {
        try {//Verifica exceção

            //Obtém o usuário autenticado
            $user = $r->user(); 

            //Obtém o vendedor
            $vendedor = $user->vendedor;

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$vendedor) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Caso o vendedor tenha uma frase cadastrada, apaga ela
            if ($vendedor->fraseVendedor) {

                //Recebe os dados da frase
                $f = $vendedor->fraseVendedor;

                //Delleta o registro
                $f->delete();
                
                //Envia mensagem de sucesso caso não gere erros
                return response()->json([
                    'mensagem' => 'Frase excluída com sucesso!',
                ], 200);

            } else {//Caso não tenha, envia mensagem de erro

                return response()->json([
                    'mensagem' => 'Não há nenhuma frase registrada para ser excluída!',
                ], 404);

            }
            
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao excluir a frase.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }

    //Função de exibir a frase do vendedor indicado
    public function exibirFrase($id)  {
        try {//Verfiica exceção

            //Obtém o vendedor pelo id fornecido
            $vendedor = Vendedor::find($id);

            //Caso não ache o vendedor, envia mensagem de erro
            if (!$vendedor) {
                return response()->json([
                    'mensagem' => 'Vendedor não encontrado.'
                ], 404);
            }

            //Caso o vendedor tenha alguma frase, envia ela
            if ($vendedor->fraseVendedor) {

                //Encontra a frase e seu conteudo
                $resposta = FraseVendedor::where('id_vendedor', $vendedor->id)
                ->select('frase')
                ->get();

                //Fornece a resposta de sucesso com a frase
                return response()->json($resposta, 200);

            } else {//Envia uma frase vazia, mostrando que não há cadastro de frase

                return response()->json([
                    'frase' => '',
                ], 200);

            }
            
        } catch (Exception $e) {//Captura exceção e envia mensagem de erro
            return response()->json([
                'mensagem' => 'Falha ao exibir a frase.',
                'erro' => $e->getMessage()
            ], 400);
        }
    }
}
