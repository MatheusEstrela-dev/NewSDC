<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaeFormAnexoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pae.empreendimentos.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:51200'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $arquivo = $this->file('arquivo');

                $extension = mb_strtolower($arquivo?->getClientOriginalExtension() ?? '');

                if (! $arquivo || ! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                    return;
                }

                if (@getimagesize($arquivo->getRealPath()) === false) {
                    $validator->errors()->add('arquivo', 'A imagem enviada esta corrompida ou nao pode ser aberta.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.required' => 'Selecione um arquivo para anexar.',
            'arquivo.file' => 'O anexo precisa ser um arquivo valido.',
            'arquivo.mimes' => 'O anexo deve ser PDF, JPG, JPEG ou PNG.',
            'arquivo.max' => 'O anexo deve ter no maximo 50 MB.',
            'descricao.string' => 'A descricao do anexo deve ser um texto.',
        ];
    }
}
