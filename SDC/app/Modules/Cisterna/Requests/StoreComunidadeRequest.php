<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Models\CisternaComunidade;
use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaComunidade::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            // 70 e o limite da coluna, herdado de sinc_cisterna_com.comunidade.
            'nome' => ['required', 'string', 'max:70'],
            'ativa' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'municipio_id.required' => 'Selecione o municipio da comunidade.',
            'nome.required' => 'O nome da comunidade e obrigatorio.',
            'nome.max' => 'O nome da comunidade deve ter no maximo 70 caracteres.',
        ];
    }
}
