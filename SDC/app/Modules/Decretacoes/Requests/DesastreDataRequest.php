<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request to validate disaster data submission.
 */
class DesastreDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'municipios'               => 'required|array',
            'municipios.*.id'          => 'required|integer|exists:cedec_municipio,id',
            'municipios.*.n_protocolo_fide' => 'nullable|string',
            'municipios.*.categorias'  => 'nullable|array',
            'municipios.*.categorias.*.id' => 'required|integer',
            'municipios.*.categorias.*.desastres' => 'nullable|array',
            'municipios.*.categorias.*.desastres.*.id' => 'required|integer',
            // `descricao` (areas/populacao afetada) foi removida do formulario
            // de Dados de Desastre e nao e mais aceita nem persistida.
            'municipios.*.categorias.*.desastres.*.items' => 'nullable|array',
            'municipios.*.categorias.*.desastres.*.items.*.id' => 'required|integer',
            'municipios.*.categorias.*.desastres.*.items.*.campos' => 'nullable|array',
            'municipios.*.categorias.*.desastres.*.items.*.campos.*.id' => 'required|integer',
            'municipios.*.categorias.*.desastres.*.items.*.campos.*.titulo' => 'required|string',
            'municipios.*.categorias.*.desastres.*.items.*.campos.*.valor' => 'nullable',
            'municipios.*.categorias.*.desastres.*.items.*.campos.*.tipo' => 'required|string|in:number,currency,text,radio,select,textarea',
        ];
    }
}
