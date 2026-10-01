<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Requests;

use App\Modules\Resgate\Enums\AcaoProposta;
use App\Modules\Resgate\Enums\TipoItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Proposta de mudanca no catalogo. Encerrar so pede codigo e justificativa;
 * criar e nova versao pedem o item completo.
 */
final class ProporItemCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('resgate.catalogo.propor');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $publica = $this->input('acao') !== AcaoProposta::Encerrar->value;
        $item = $publica ? 'required' : 'prohibited';

        return [
            'acao' => ['required', Rule::enum(AcaoProposta::class)],
            'codigo' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{3,40}$/'],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
            'dados' => [$item, 'array'],
            'dados.tipo' => [$item, Rule::enum(TipoItem::class)],
            'dados.titulo' => [$item, 'string', 'max:160'],
            'dados.descricao' => [$item, 'string', 'max:4000'],
            'dados.beneficiario' => [$item, Rule::in(['municipio', 'orgao'])],
            'dados.faixa_minima' => [$item, Rule::in(['bronze', 'prata', 'ouro', 'diamante'])],
            'dados.custo_pontos' => [$item, 'integer', 'min:0', 'max:1000000'],
            'dados.quantidade' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'dados.limite_por_ente_temporada' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'dados.prazo_reserva_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'dados.instrumento' => [$item, 'string', 'max:40'],
            'dados.base_normativa' => [$item, 'string', 'max:200'],
            'dados.unidade_responsavel' => [$item, 'string', 'max:160'],
            'dados.documentos_exigidos' => ['nullable', 'array', 'max:30'],
            'dados.documentos_exigidos.*' => ['string', 'max:200'],
            'dados.demonstracao' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'dados.titulo' => 'título',
            'dados.descricao' => 'descrição',
            'dados.faixa_minima' => 'faixa mínima',
            'dados.custo_pontos' => 'custo em pontos',
            'dados.base_normativa' => 'base normativa',
            'dados.unidade_responsavel' => 'unidade responsável',
        ];
    }
}
