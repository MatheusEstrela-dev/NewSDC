<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Enums\CategoriaAnexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaeFormAnexoRequest extends FormRequest
{
    /**
     * Extensoes aceitas: documentos do PAE e os formatos de mapa entregues
     * pelos empreendedores (KMZ/KML, shapefile e seus arquivos irmaos, ZIP).
     * A lista e fechada por extensao porque os formatos geograficos nao tem
     * assinatura de conteudo confiavel; em troca, so PDF e imagem abrem no
     * navegador (PaeFormularioService::viewAnexo), o resto sempre baixa.
     */
    public const EXTENSOES = [
        'pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'pptx', 'docx',
        'kmz', 'kml', 'zip', 'shp', 'shx', 'dbf', 'prj', 'cpg', 'qix', 'qmd',
    ];

    /** Mapas em A0 passam de 90 MB; o teto acompanha OCTANE_PACKAGE_MAX_LENGTH. */
    public const TAMANHO_MAXIMO_KB = 153600;

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
            'arquivo' => [
                'required',
                'file',
                'extensions:' . implode(',', self::EXTENSOES),
                'max:' . self::TAMANHO_MAXIMO_KB,
            ],
            'descricao' => ['nullable', 'string'],
            'categoria' => ['nullable', Rule::enum(CategoriaAnexo::class)],
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
            'arquivo.extensions' => 'Formato nao aceito. Envie PDF, imagem (JPG/PNG), XLSX, PPTX, DOCX ou mapa (KMZ, KML, shapefile, ZIP).',
            'arquivo.max' => 'O anexo deve ter no maximo 150 MB.',
            'descricao.string' => 'A descricao do anexo deve ser um texto.',
            'categoria' => 'Escolha uma categoria de anexo valida.',
        ];
    }
}
