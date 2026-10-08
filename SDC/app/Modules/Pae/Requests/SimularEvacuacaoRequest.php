<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimularEvacuacaoRequest extends FormRequest
{
    private const IDENTIFICADOR = ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9_-]+$/'];
    private const MM_SS = 'regex:/^\d{1,3}:[0-5]\d$/';
    private const TERRENOS = ['plano', 'inclinado'];

    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.edit') ?? false;
    }

    public function rules(): array
    {
        return self::regras();
    }

    /** Fonte unica das regras da entrada, reutilizada pelo PaeEvacuacaoService. */
    public static function regras(): array
    {
        return [
            'setores' => ['required', 'array', 'min:1', 'max:50'],
            'setores.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'setores.*.populacao' => ['required', 'integer', 'min:0', 'max:1000000'],
            'setores.*.comercial' => ['required', 'boolean'],
            'setores.*.via' => ['required', Rule::in(['calcada', 'rua_mao_unica', 'rua_mao_dupla'])],
            'setores.*.largura' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'setores.*.lados' => ['nullable', 'required_if:setores.*.via,calcada', 'integer', 'in:1,2'],
            'setores.*.distancia' => ['required', 'numeric', 'gt:0', 'max:100000', 'decimal:0,2'],
            'setores.*.terreno' => ['required', Rule::in(self::TERRENOS)],
            'rotas' => ['required', 'array', 'min:1', 'max:20'],
            'rotas.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'rotas.*.setores' => ['required', 'array', 'min:1', 'max:50'],
            'rotas.*.setores.*' => self::IDENTIFICADOR,
            'rotas.*.chegada_onda' => ['required', 'string', self::MM_SS],
            'rotas.*.nivel_emergencia' => ['required', 'integer', 'in:1,2,3'],
            'acessos' => ['nullable', 'array', 'max:20'],
            'acessos.*.id' => [...self::IDENTIFICADOR, 'distinct'],
            'acessos.*.largura' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'acessos.*.terreno' => ['required', Rule::in(self::TERRENOS)],
            'acessos.*.rotas' => ['required', 'array', 'min:1', 'max:20'],
            'acessos.*.rotas.*' => self::IDENTIFICADOR,
            'pontos_encontro' => ['required', 'array', 'min:1', 'max:50'],
            'pontos_encontro.*.nome' => ['required', 'string', 'max:255'],
            'pontos_encontro.*.endereco' => ['required', 'string', 'max:500'],
            'pontos_encontro.*.populacao' => ['required', 'integer', 'min:0', 'max:1000000'],
            'pontos_encontro.*.area' => ['required', 'numeric', 'gt:0', 'max:10000000', 'decimal:0,2'],
            'tte_declarado' => ['nullable', 'string', self::MM_SS],
        ];
    }
}
