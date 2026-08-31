<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Requests;

use App\Modules\Cisterna\Models\CisternaOrdemServico;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CisternaOrdemServico::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lote_id' => ['required', 'integer', 'exists:cisterna_lotes,id'],
            'nome' => ['required', 'string', 'max:255'],
            'observacao' => ['nullable', 'string', 'max:1000'],
            // URL do processo no SEI. Legado: coluna link_doc, que guardava
            // endereco e nao arquivo -- ver migration 2026_08_14_100300.
            'documento_url' => ['nullable', 'url', 'max:500'],
            // Arquivo anexado, que o legado nao tinha.
            'documento_os' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lote_id.required' => 'Selecione o lote da ordem de servico.',
            'nome.required' => 'O nome da ordem de servico e obrigatorio.',
            'documento_url.url' => 'O documento deve ser uma URL valida, como o link do processo no SEI.',
        ];
    }
}
