<?php

declare(strict_types=1);

namespace App\Modules\Pae\Requests;

use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\Simulado\SimuladoAnexoC;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegistrarSimuladoRequest extends FormRequest
{
    private const DATA = 'date_format:Y-m-d';

    public function authorize(): bool
    {
        return $this->user()?->can('pae.protocolos.validar') ?? false;
    }

    public function rules(): array
    {
        return self::regras($this->all());
    }

    public function messages(): array
    {
        return self::mensagens();
    }

    /**
     * Fonte unica das regras, reutilizada pelo PaeSimuladoService fora do HTTP.
     * `$dados` so alimenta as regras condicionais (integrado e justificativa).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function regras(array $dados = []): array
    {
        return [
            'dt_realizacao' => ['required', self::DATA, 'before_or_equal:today'],
            'nivel_emergencia' => ['required', 'integer', 'in:2,3'],
            'dt_apresentacao' => ['required', self::DATA, 'before_or_equal:today', 'after_or_equal:dt_realizacao'],
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'integrado' => ['required', 'boolean'],
            'barragens_integradas' => [
                'nullable', 'string', 'max:2000',
                Rule::requiredIf(fn (): bool => filter_var($dados['integrado'] ?? false, FILTER_VALIDATE_BOOLEAN)),
            ],
            'aviso_cedec_em' => ['nullable', self::DATA, 'before_or_equal:dt_realizacao'],
            'chave_idempotencia' => ['required', 'uuid'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            ...self::regrasCriterios($dados),
            ...self::regrasTempos(),
            ...self::regrasAlarme(),
            ...self::regrasInformativos(),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasCriterios(array $dados = []): array
    {
        $regras = ['criterios' => ['required', 'array:'.implode(',', SimuladoAnexoC::numeros())]];
        foreach (SimuladoAnexoC::numeros() as $numero) {
            $atende = data_get($dados, "criterios.{$numero}.atende");
            $regras["criterios.{$numero}"] = ['required', 'array'];
            $regras["criterios.{$numero}.atende"] = ['required', 'boolean'];
            $regras["criterios.{$numero}.justificativa"] = [
                'nullable', 'string', 'max:2000',
                Rule::requiredIf(fn (): bool => SimuladoAnexoC::exigeJustificativa($numero, $atende)),
            ];
        }

        return $regras;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasTempos(): array
    {
        $regras = ['tempos' => ['nullable', 'array:'.implode(',', SimuladoAnexoC::CATEGORIAS)]];
        foreach (SimuladoAnexoC::CATEGORIAS as $categoria) {
            $regras["tempos.{$categoria}"] = ['nullable', 'array', 'max:50'];
            $regras["tempos.{$categoria}.*.nome"] = ['required', 'string', 'max:255'];
            $regras["tempos.{$categoria}.*.populacao"] = ['nullable', 'integer', 'min:0', 'max:10000000'];
            $regras["tempos.{$categoria}.*.chegada_onda"] = ['required', 'string', TempoAnexoE::REGRA_MM_SS];
            $regras["tempos.{$categoria}.*.saida"] = ['required', 'string', TempoAnexoE::REGRA_MM_SS];
            $regras["tempos.{$categoria}.*.houve_problemas"] = ['required', 'boolean'];
            $regras["tempos.{$categoria}.*.ponto_valido"] = ['required', 'boolean'];
            $regras["tempos.{$categoria}.*.estimativa"] = ['required', 'boolean'];
        }
        $regras['tempos.hospitalares_prisionais.*.nivel_emergencia'] = ['required', 'integer', 'in:2,3'];

        return $regras;
    }

    /** @return array<string, array<int, mixed>> */
    public static function regrasAlarme(): array
    {
        return [
            'alarme' => ['required', 'array'],
            'alarme.audivel_todos' => ['required', 'boolean'],
            'alarme.morador_nome' => ['nullable', 'string', 'max:255'],
            'alarme.morador_localizacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private static function regrasInformativos(): array
    {
        $contagem = ['nullable', 'integer', 'min:0', 'max:100000000'];

        return [
            'informativos.participacao' => ['nullable', 'array'],
            'informativos.participacao.populacao_zas' => $contagem,
            'informativos.participacao.participantes' => $contagem,
            'informativos.participacao.cadastrados_pae' => $contagem,
            'informativos.participacao.anos_anteriores' => ['nullable', 'array', 'max:20'],
            'informativos.participacao.anos_anteriores.*.ano' => ['required', 'integer', 'between:2000,'.CarbonImmutable::today()->year],
            'informativos.participacao.anos_anteriores.*.participantes' => ['required', 'integer', 'min:0', 'max:100000000'],
            'informativos.ensino_observacoes' => ['nullable', 'string', 'max:5000'],
            'informativos.recursos_observacoes' => ['nullable', 'string', 'max:5000'],
            'informativos.conclusao_compdec' => ['nullable', Rule::in(['sim', 'nao'])],
        ];
    }

    /** @return array<string, string> */
    public static function mensagens(): array
    {
        return [
            'dt_apresentacao.after_or_equal' => 'A data de apresentação não pode ser anterior à data de realização.',
            'aviso_cedec_em.before_or_equal' => 'O aviso à CEDEC não pode ser posterior à realização do simulado.',
            'barragens_integradas.required' => 'Informe as barragens do simulado integrado.',
            'criterios.*.justificativa.required' => 'Justifique o critério que não atende.',
        ];
    }
}
