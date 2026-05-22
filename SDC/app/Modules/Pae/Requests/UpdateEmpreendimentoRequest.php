<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpreendimentoRequest extends FormRequest
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
            'nome' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:OPERACAO,DESATIVADA,CONSTRUCAO,DESCOMISSIONAMENTO'],
            'municipio_id' => ['sometimes', 'integer', 'exists:municipios,id'],
            'pae_empdor_id' => ['sometimes', 'integer', 'exists:pae_empdors,id'],
            'pae_coordenador_id' => ['sometimes', 'nullable', 'integer'],
            'regiao_id' => ['sometimes', 'nullable', 'integer'],
            'm_construcao' => ['sometimes', 'nullable', 'string', 'max:255'],
            'material' => ['sometimes', 'nullable', 'string', 'max:255'],
            'finalidade' => ['sometimes', 'nullable', 'string', 'max:255'],
            'volume' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pop_zas' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'orgao_fisc' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coordenador' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tel_coordenador' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_coord' => ['sometimes', 'nullable', 'email', 'max:255'],
            'mina' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coordenador_sub' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tel_coordenador_sub' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_coord_sub' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }
}
