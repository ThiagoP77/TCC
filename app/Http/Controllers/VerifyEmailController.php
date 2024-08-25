<?php

//Namespace
namespace App\Http\Controllers;

//Namespaces utilizados
use App\Models\Api\Usuario;
use App\Rules\EmailValidacao;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

//Classe de verificação de email
class VerifyEmailController extends Controller
{

    //Função de verificação do email
    public function verify($id, $hash)
    {
        try {//Testa se tem exceção

            //Encontra o usuário pelo id
            $user = Usuario::find($id);

            //Aborta a operação caso tenha algum erro
            abort_if(!$user, 403);
            abort_if(!hash_equals($hash, sha1($user->getEmailForVerification())), 403);
    
            //Verifica o email caso ele não esteja verificado
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                event(new Verified($user));
            }

            //Mostra view de sucesso
            return view('verified-account');

        } catch (Exception $e) {//Tratamento de erro

            //Mostra view de erro
            return view('error-verified-account');
            
        }
  
    }
 
    //Função de reenviar verificação de email
    public function resendNotification(Request $r) {
        try {//Testa exceção

            //Realiza validação dos campos com os parâmetros informados
            $validator = Validator::make($r->all(), [
                'email' => [
                    'required',
                    'email',
                    new EmailValidacao()
                ],
            ]);

            //Se a validação der alguma falha, envia mensagem de erro
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            //Procura pelo usuário correspondente ao email recebido
            $u = Usuario::where('email', $r->input('email'))->first();

            //Caso o usuário não seja encontrado, envia mensagem de erro
            if (!$u) {
                return response()->json([
                    'error' => 'Email não registrado no sistema.',
                ], 404);
            }

            //Caso o usuário já possua o email verificado, envia mensagem de erro
            if($u->email_verified_at != null) {

                return response()->json([
                    'mensagem' => 'Seu endereço de email já foi verificado!',
                ], 400);

            }
            
            //Reenvia o email de verificação
            $u->sendEmailVerificationNotification();
 
            //Envia mensagem de sucesso
            return response()->json([
                'message' => 'Email de verificação enviado!',
            ], 200);

        } catch (Exception $e) {//Captura erro e envia mensagem de erro

            return response()->json([
                'mensagem' => 'Falha ao enviar email.',
                'erro' => $e->getMessage()
            ], 400);

        }
    }
}
