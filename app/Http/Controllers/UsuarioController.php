<?php

namespace App\Http\Controllers;

use App\Rules\CpfValidacao;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    
    public function cadastro(Request $r){
        
        $r->validate([
            'id_categoria' => 'required|integer|in:2,3,4',
        ], [
            'id_categoria.in' => 'Tipo de usuário inválido.',
        ]);

        $r->validate([
            'nome' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^(?=.*\p{L})(?!.*  )[ \p{L}]+$/u'
            ],

            'email' => 'required|email|unique:usuarios,email',

            'senha' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^\S*$/'
            ],

            'cpf' => [
                'required',
                'unique:usuarios,cpf',
                new CpfValidacao
            ],

            'foto_login' => 'nullable|image|mimes:jpeg,png,bmp,gif|max:16384',
        ]);

        $id_categoria = $r->input('id_categoria');

        //Cliente
        if($id_categoria == 2) {
            $r->validate([
                'telefone' => 'required',
            ]);
        } 
        
        //Vendedor
        else if ($id_categoria == 3) {
            $r->validate([
                'telefone' => 'required',
                'whatsapp' => 'nullable',
                'endereco' => 'required',
                'cnpj' => 'nullable',
            ]);
        } 
        
        //Entregador
        else if ($id_categoria == 4) {
            $r->validate([
                'telefone' => 'required',
                'tipo_veiculo' => 'required',
                'placa' => 'required',
            ]);
        }
    }

    public function login(){
        
    }

    public function logout(){
        
    }

}
