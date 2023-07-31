<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'endereco.rua' => 'required',
            'endereco.numero' => 'numeric',
            'endereco.bairro' => 'required',
            'endereco.cidade' => 'required',
            'endereco.uf' => 'required',
            'endereco.cep' => 'required',
            'isbn' => 'required',
            'capa' => 'image|max:2048',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'endereco.rua.required' => 'Obrigatório.',
            'endereco.bairro.required' => 'Obrigatório.',
            'endereco.cidade.required' => 'Obrigatório.',
            'endereco.uf.required' => 'Obrigatório.',
            'endereco.cep.required' => 'Obrigatório.',
            'endereco.numero.numeric' => 'Somente números.',
            'capa.image' => 'O arquivo deve ser uma imagem.',
            'capa.max' => 'O arquivo deve ter no máximo 2MB.',
        ];
    }


}
