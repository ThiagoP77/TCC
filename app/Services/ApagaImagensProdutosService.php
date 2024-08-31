<?php

//Namespace
namespace App\Services;

//Namespaces utilizados
use App\Models\Api\Produto;
use App\Models\Api\Vendedor;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

//Classe de consultar CEP pela API online ViaCEP
class ApagaImagensProdutosService {

    //Atributo com o caminho padrão da imagem
    protected $defaultURL = 'storage/imagens_produtos/imagem_default_produto.png';

    //Função de apagar as imagens dos produtos
    public function excluirImagens ($id) {
        try {//Testa se tem exceção

            //Obtém o vendedor
            $v = Vendedor::find($id);

            //Caso o vendedor não seja encontrado, envia mensagem de erro
            if (!$v) {
                return false;
            }

            //Encontra os produtos do vendedor
            $resposta = Produto::where('id_vendedor', $v->id)->get();
            
            //Apaga a imagem de cada produto no Storage
            foreach ($resposta as $produto) {

                $fotoURL = $produto->imagem_produto;
                $defaultURL = $this->defaultURL;

                //Verificar se a foto existe e é a default e, se não for, exclui ela do site
                if ($fotoURL && $fotoURL !== $defaultURL) {

                    $path = str_replace('storage/', '', $fotoURL);

                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);//Excluindo ela
                    }

                }
            }

            //Fornece a resposta de sucesso com os produtos
            return true;

        } catch (Exception $e) {//Captura erro e envia mensagem de erro
            return false;
        }
    }
}