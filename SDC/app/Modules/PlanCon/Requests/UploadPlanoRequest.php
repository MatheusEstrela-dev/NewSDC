<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plancon.upload') ?? false;
    }

    /**
     * Os tipos acompanham a colecao de midia do COMPDEC
     * (CompdecPlanoContingencia::registerMediaCollections), que e onde o
     * arquivo vai parar: aceitar aqui um formato que a colecao recusa daria
     * erro so depois do upload. O legado tambem aceitava doc/docx/odt, nao so
     * PDF -- dos 619 planos migrados ha varios .docx.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) (config('compdec.upload_limits.plano_arquivo', 20 * 1024 * 1024) / 1024);

        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf,doc,docx,odt', "max:{$maxKb}"],
            'versao' => ['nullable', 'string', 'max:40'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            // So a conta estadual manda municipio_id; para usuario municipal o
            // servico ignora, porque o orgao vem do vinculo dele.
            'municipio_id' => ['nullable', 'integer', 'exists:municipios,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Selecione ao menos um arquivo.',
            'files.*.file' => 'O plano precisa ser um arquivo valido.',
            'files.*.mimes' => 'Formatos aceitos: PDF, DOC, DOCX e ODT.',
            'files.*.max' => 'Cada arquivo deve ter no maximo 20 MB.',
        ];
    }
}
