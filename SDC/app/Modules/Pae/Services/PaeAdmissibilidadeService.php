<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Domain\ContextoTransicao;
use App\Modules\Pae\Domain\Workflows\PaeProtocoloWorkflow;
use App\Modules\Pae\Models\PaeAdmissibilidadeDecisao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\PaeAnexoJ;
use App\Modules\Pae\Support\PaeFundamentosSumarios;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as LaravelValidator;

final class PaeAdmissibilidadeService
{
    public function __construct(private readonly PaeProtocoloWorkflow $workflow)
    {
    }

    public static function regrasTriagem(): array
    {
        return [
            'municipios' => ['present', 'array'],
            'municipios.*.municipio_id' => ['required', 'integer', 'distinct', 'exists:municipios,id'],
            'municipios.*.na_zas' => ['required', 'boolean'],
            'municipios.*.na_zss' => ['required', 'boolean'],
            'itens' => ['present', 'array', 'max:9'],
            'itens.*.chave' => ['required', 'string', 'distinct', Rule::in(PaeAnexoJ::CHAVES)],
            'itens.*.resultado' => ['required', Rule::in(['sim', 'nao', 'nao_aplicavel'])],
            'itens.*.justificativa' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public static function validarComplementos(LaravelValidator $validator, array $municipios, array $itens): void
    {
        $validator->after(static function (LaravelValidator $validator) use ($municipios, $itens): void {
            foreach ($municipios as $indice => $municipio) {
                if (! ($municipio['na_zas'] ?? false) && ! ($municipio['na_zss'] ?? false)) {
                    $validator->errors()->add("municipios.{$indice}.na_zas", 'Informe ZAS, ZSS ou ambas.');
                }
            }

            foreach ($itens as $indice => $item) {
                if (in_array($item['resultado'] ?? null, ['nao', 'nao_aplicavel'], true)
                    && trim((string) ($item['justificativa'] ?? '')) === '') {
                    $validator->errors()->add("itens.{$indice}.justificativa", 'Justifique a ausência ou não aplicabilidade.');
                }
            }
        });
    }

    public function salvarTriagem(PaeProtocolo $protocolo, array $municipios, array $itens, User $user): void
    {
        $validator = Validator::make(['municipios' => $municipios, 'itens' => $itens], self::regrasTriagem());
        self::validarComplementos($validator, $municipios, $itens);
        $validator->validate();

        DB::transaction(function () use ($protocolo, $municipios, $itens, $user): void {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [PaeProtocoloStatus::NOVO, PaeProtocoloStatus::ENTRADA_PROCESSO], true)) {
                throw ValidationException::withMessages(['protocolo' => 'A triagem só pode ser alterada na entrada do processo.']);
            }

            $municipioIds = [];
            foreach ($municipios as $municipio) {
                $municipioIds[] = (int) $municipio['municipio_id'];
                $locked->municipiosImpactados()->updateOrCreate(
                    ['municipio_id' => $municipio['municipio_id']],
                    [
                        'na_zas' => (bool) $municipio['na_zas'],
                        'na_zss' => (bool) $municipio['na_zss'],
                        'confirmado_por' => $user->id,
                        'confirmado_em' => now(),
                    ],
                );
            }
            $this->removerAusentes($locked->municipiosImpactados(), 'municipio_id', $municipioIds);

            $chaves = [];
            foreach ($itens as $item) {
                $chaves[] = $item['chave'];
                $locked->itensAdmissibilidade()->updateOrCreate(
                    ['chave' => $item['chave']],
                    [
                        'resultado' => $item['resultado'],
                        'justificativa' => trim((string) ($item['justificativa'] ?? '')) ?: null,
                        'updated_by' => $user->id,
                    ],
                );
            }
            $this->removerAusentes($locked->itensAdmissibilidade(), 'chave', $chaves);

            $locked->increment('admissibilidade_triagem_versao');
            TimelinePae::registrar($locked, 'admissibilidade_triagem', 'Municípios ZAS/ZSS e checklist do Anexo J atualizados.', $user);
        });
    }

    private function removerAusentes($relacao, string $coluna, array $chaves): void
    {
        if ($chaves === []) {
            $relacao->delete();

            return;
        }

        $relacao->whereNotIn($coluna, $chaves)->delete();
    }

    public static function regrasDecisao(): array
    {
        return [
            'tipo' => ['required', Rule::in(['admitido', 'correcao_solicitada', 'reprovado_sumariamente'])],
            'fundamentacao' => ['required', 'string', 'min:10', 'max:10000'],
            'chave_idempotencia' => ['required', 'uuid'],
            'fundamentos' => ['nullable', 'array'],
            'fundamentos.*' => ['integer', 'distinct', Rule::in(PaeFundamentosSumarios::ARTIGOS)],
            'transitorio_confirmado' => ['nullable', 'boolean'],
            'submetido_em' => ['nullable', 'date', 'before_or_equal:today'],
            'notificado_em' => ['nullable', 'date', 'before_or_equal:today'],
            'num_sei' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function validarDecisaoComplementar(LaravelValidator $validator, array $dados): void
    {
        $validator->after(static function (LaravelValidator $validator) use ($dados): void {
            if (($dados['tipo'] ?? null) === 'reprovado_sumariamente'
                && empty($dados['fundamentos'])) {
                $validator->errors()->add('fundamentos', 'Indique ao menos um fundamento do art. 131.');
            }

            if (($dados['tipo'] ?? null) !== 'correcao_solicitada') {
                return;
            }

            foreach (['submetido_em', 'notificado_em', 'num_sei'] as $campo) {
                if (! is_string($dados[$campo] ?? null) || trim($dados[$campo]) === '') {
                    $validator->errors()->add($campo, 'Campo obrigatório para a correção transitória.');
                }
            }
            if (! filter_var($dados['transitorio_confirmado'] ?? false, FILTER_VALIDATE_BOOL)) {
                $validator->errors()->add('transitorio_confirmado', 'A CEDEC deve confirmar a submissão anterior à publicação da resolução.');
            }
            if (is_string($dados['submetido_em'] ?? null) && is_string($dados['notificado_em'] ?? null)
                && strtotime($dados['submetido_em']) !== false
                && strtotime($dados['notificado_em']) !== false
                && strtotime($dados['submetido_em']) > strtotime($dados['notificado_em'])) {
                $validator->errors()->add('submetido_em', 'A submissão deve anteceder a notificação.');
            }
        });
    }

    public function decidir(PaeProtocolo $protocolo, array $dados, User $user): PaeAdmissibilidadeDecisao
    {
        $validator = Validator::make($dados, self::regrasDecisao());
        self::validarDecisaoComplementar($validator, $dados);
        $validados = $validator->validate();

        return DB::transaction(function () use ($protocolo, $validados, $user): PaeAdmissibilidadeDecisao {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            $repetida = $locked->decisoesAdmissibilidade()
                ->where('chave_idempotencia', $validados['chave_idempotencia'])->first();
            if ($repetida !== null) {
                return $repetida;
            }

            if ($locked->status !== PaeProtocoloStatus::ENTRADA_PROCESSO) {
                throw ValidationException::withMessages(['protocolo' => 'A decisão só pode ser registrada na entrada do processo.']);
            }

            $itens = $locked->itensAdmissibilidade()->get()->keyBy('chave');
            if ($itens->count() !== count(PaeAnexoJ::CHAVES)
                || $locked->municipiosImpactados()->count() === 0
                || $locked->municipiosImpactados()->whereNull('confirmado_em')->exists()) {
                throw ValidationException::withMessages(['triagem' => 'Complete o checklist e confirme os municípios ZAS/ZSS antes da decisão.']);
            }

            $snapshot = [];
            foreach (PaeAnexoJ::CHAVES as $chave) {
                $item = $itens->get($chave);
                if ($item === null) {
                    throw ValidationException::withMessages(['triagem' => 'O checklist do Anexo J está incompleto.']);
                }
                $snapshot[] = [
                    'chave' => $chave,
                    'resultado' => $item->resultado,
                    'justificativa' => $item->justificativa,
                ];
            }

            $fundamentos = array_map('intval', $validados['fundamentos'] ?? []);
            $ultima = $locked->decisoesAdmissibilidade()->first();
            if ($ultima !== null
                && $ultima->tipo === $validados['tipo']
                && $ultima->fundamentacao === $validados['fundamentacao']
                && ($ultima->fundamentos ?? []) === $fundamentos
                && $ultima->checklist_snapshot === $snapshot
                && $ultima->triagem_versao === $locked->admissibilidade_triagem_versao
                && $ultima->submetido_em?->toDateString() === ($validados['submetido_em'] ?? null)
                && $ultima->notificado_em?->toDateString() === ($validados['notificado_em'] ?? null)
                && $ultima->num_sei === ($validados['num_sei'] ?? null)) {
                return $ultima;
            }

            $notificadoEm = $validados['tipo'] === 'correcao_solicitada'
                ? CarbonImmutable::parse($validados['notificado_em']) : null;
            $decisao = PaeAdmissibilidadeDecisao::create([
                'protocolo_id' => $locked->id,
                'tipo' => $validados['tipo'],
                'chave_idempotencia' => $validados['chave_idempotencia'],
                'fundamentacao' => $validados['fundamentacao'],
                'fundamentos' => $fundamentos ?: null,
                'checklist_snapshot' => $snapshot,
                'triagem_versao' => $locked->admissibilidade_triagem_versao,
                'submetido_em' => $validados['submetido_em'] ?? null,
                'transitorio_confirmado' => $validados['tipo'] === 'correcao_solicitada',
                'notificado_em' => $notificadoEm?->toDateString(),
                'prazo_correcao_em' => $notificadoEm?->addDays(30)->toDateString(),
                'num_sei' => $validados['num_sei'] ?? null,
                'decidido_por' => $user->id,
                'decidido_em' => now(),
            ]);

            TimelinePae::registrar($locked, 'admissibilidade_decisao',
                "Decisão de admissibilidade: {$decisao->tipo}. {$decisao->fundamentacao}", $user);

            if ($decisao->tipo === 'reprovado_sumariamente') {
                $this->workflow->transitar($locked, PaeProtocoloStatus::REPROVADO_SUMARIAMENTE, $user,
                    $decisao->fundamentacao, ContextoTransicao::decisaoAdmissibilidade());
            }

            return $decisao;
        });
    }

    public function resumo(PaeProtocolo $protocolo): array
    {
        $protocolo->load(['municipiosImpactados.municipio', 'itensAdmissibilidade', 'decisoesAdmissibilidade.decisor']);
        $registrados = $protocolo->itensAdmissibilidade->keyBy('chave');

        $itens = [];
        foreach (PaeAnexoJ::CHAVES as $chave) {
            $item = $registrados->get($chave);
            $itens[] = [
                'chave' => $chave,
                'rotulo' => PaeAnexoJ::ROTULOS[$chave],
                'resultado' => $item?->resultado,
                'justificativa' => $item?->justificativa,
            ];
        }

        $usuario = auth()->user();

        return [
            'municipios' => $protocolo->municipiosImpactados->map(fn ($municipio) => [
                'municipio_id' => $municipio->municipio_id,
                'nome' => $municipio->municipio?->nome,
                'uf' => $municipio->municipio?->uf,
                'na_zas' => $municipio->na_zas,
                'na_zss' => $municipio->na_zss,
            ])->values()->all(),
            'itens' => $itens,
            'decisao_vigente' => $protocolo->decisoesAdmissibilidade->first(),
            'decisoes' => $protocolo->decisoesAdmissibilidade->values()->all(),
            'fundamentos_disponiveis' => PaeFundamentosSumarios::ROTULOS,
            'legado_sem_triagem' => $protocolo->admissibilidade_legada_sem_triagem,
            'pode_editar' => $usuario?->can('pae.protocolos.edit')
                && in_array($protocolo->status, [PaeProtocoloStatus::NOVO, PaeProtocoloStatus::ENTRADA_PROCESSO], true),
            'pode_decidir' => $usuario?->can('pae.protocolos.validar')
                && $protocolo->status === PaeProtocoloStatus::ENTRADA_PROCESSO,
        ];
    }
}
