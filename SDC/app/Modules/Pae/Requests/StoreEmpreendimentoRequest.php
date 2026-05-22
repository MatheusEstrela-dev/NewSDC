<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpreendimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:OPERACAO,DESATIVADA,CONSTRUCAO,DESCOMISSIONAMENTO'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'pae_empdor_id' => ['required', 'integer', 'exists:pae_empdors,id'],
            'pae_coordenador_id' => ['nullable', 'integer'],
            'regiao_id' => ['nullable', 'integer'],
            'm_construcao' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'finalidade' => ['nullable', 'string', 'max:255'],
            'volume' => ['nullable', 'numeric', 'min:0'],
            'pop_zas' => ['nullable', 'integer', 'min:0'],
            'orgao_fisc' => ['nullable', 'string', 'max:255'],
            'coordenador' => ['nullable', 'string', 'max:255'],
            'tel_coordenador' => ['nullable', 'string', 'max:50'],
            'email_coord' => ['nullable', 'email', 'max:255'],
            'mina' => ['nullable', 'string', 'max:255'],
            'coordenador_sub' => ['nullable', 'string', 'max:255'],
            'tel_coordenador_sub' => ['nullable', 'string', 'max:50'],
            'email_coord_sub' => ['nullable', 'email', 'max:255'],
        ];
    }
}
