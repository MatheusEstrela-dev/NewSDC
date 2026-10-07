<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SalvarFichaAnexoBRequest extends FormRequest
{
    private const CONTAGEM_EDIFICACOES = ['nullable', 'integer', 'min:0', 'max:2147483647'];

    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'base_versao' => ['required', 'integer', 'min:0'],
            'nome_barragem' => ['nullable', 'string', 'max:255'],
            'nome_mina' => ['nullable', 'string', 'max:255'],
            'metodo_construtivo' => ['nullable', 'string', 'max:100'],
            'volume_reservatorio' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'municipio_sede_id' => ['nullable', 'integer', 'exists:municipios,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'regex:/^-?\d{1,3}(\.\d{1,7})?$/'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'regex:/^-?\d{1,3}(\.\d{1,7})?$/'],
            'tipo_rejeito' => ['nullable', 'string', 'max:5000'],
            'toxicidade' => ['nullable', 'string', 'max:255'],
            'extensao_zas_km' => ['nullable', 'numeric', 'min:0', 'regex:/^\d{1,7}(\.\d{1,3})?$/'],
            'populacao_zas' => ['nullable', 'integer', 'min:0'],
            'populacao_zas_mobilidade_reduzida' => ['nullable', 'integer', 'min:0'],
            'populacao_zss' => ['nullable', 'integer', 'min:0'],
            'cursos_agua' => ['present', 'nullable', 'array', 'max:100'],
            'cursos_agua.*' => ['string', 'max:255'],
            'edificacoes_hospitalares' => self::CONTAGEM_EDIFICACOES,
            'edificacoes_escolares' => self::CONTAGEM_EDIFICACOES,
            'edificacoes_prisionais' => self::CONTAGEM_EDIFICACOES,
            'edificacoes_outras' => self::CONTAGEM_EDIFICACOES,
            'estruturas_associadas' => ['present', 'nullable', 'array', 'max:100'],
            'estruturas_associadas.*' => ['string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $total = $this->input('populacao_zas');
            $mobilidade = $this->input('populacao_zas_mobilidade_reduzida');
            if (is_numeric($total) && is_numeric($mobilidade) && (int) $mobilidade > (int) $total) {
                $validator->errors()->add(
                    'populacao_zas_mobilidade_reduzida',
                    'A população com dificuldade de locomoção não pode exceder a população total da ZAS.',
                );
            }
        });
    }
}
