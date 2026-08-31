<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Requests;

use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Emissao de parecer tecnico (RN-10).
 */
class StoreParecerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('humanitaria.pedidos.parecer') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data_parecer' => ['required', 'date'],
            'parecer'      => ['required', 'string', 'max:5000'],
            'situacao'     => ['required', Rule::enum(SituacaoParecer::class)],
            'etapa'        => ['required', Rule::enum(EtapaParecer::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parecer.required'  => 'Escreva o parecer.',
            'situacao.required' => 'Informe se o parecer é favorável ou contrário.',
            'etapa.required'    => 'Informe a etapa a que o parecer pertence.',
        ];
    }
}
