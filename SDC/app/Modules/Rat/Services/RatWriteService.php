<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Rat\Domain\Events\RegistroCompletoV1;
use App\Modules\Rat\Domain\Events\RelatorioFinalizadoV1;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\Models\RatOcorrencia;
use App\Modules\Rat\Models\RatOcorrenciaRelato;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use App\Modules\Rat\Models\Recursos\RatRecursosComponentesGuarnicao;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatWriteService
{
    public function __construct(
        private readonly RatProtocoloService $protocoloService,
        private readonly OutboxDispatcher $outbox,
    ) {}

    public function create(): RatOcorrencia
    {
        return DB::transaction(function () {
            $protocolo = $this->protocoloService->generate();
            $userId    = Auth::id();

            return RatOcorrencia::create([
                'numero_bos'     => $protocolo,
                'sequencial_ano' => now()->year,
                'status'         => 0,
                'prazo_edicao'   => now()->addHours(48),
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);
        });
    }

    public function createRelacionado(string $origemId): RatOcorrencia
    {
        return DB::transaction(function () use ($origemId) {
            $origem = RatOcorrencia::findOrFail($origemId);

            // Extrair sequência do RAT origem: YYYY-SEQUENCE-SUFFIX
            $parts = explode('-', $origem->numero_bos);
            $year = $parts[0];
            $sequence = $parts[1]; // ex: 000000063

            // Encontrar maior sufixo relacionado: YYYY-SEQUENCE-%
            $latestRelated = RatOcorrencia::where('numero_bos', 'like', "{$year}-{$sequence}-%")
                ->lockForUpdate()
                ->orderByDesc('numero_bos')
                ->value('numero_bos');

            // Extrair sufixo e incrementar (sequência sempre começa em 001)
            $nextSuffix = 1;
            if ($latestRelated) {
                $relatedParts = explode('-', $latestRelated);
                $nextSuffix = (int) $relatedParts[2] + 1;
            }

            $numeroBos = sprintf('%s-%s-%03d', $year, $sequence, $nextSuffix);
            $userId = Auth::id();

            return RatOcorrencia::create([
                'numero_bos'           => $numeroBos,
                'sequencial_ano'       => now()->year,
                'status'               => 0,
                'prazo_edicao'         => now()->addHours(48),
                'created_by'           => $userId,
                'updated_by'           => $userId,
                'ocorrencia_origem_id' => $origemId,
            ]);
        });
    }

    public function createWithData(array $data): RatOcorrencia
    {
        return DB::transaction(function () use ($data) {
            $protocolo = $this->protocoloService->generate();
            $userId    = Auth::id();

            $ocorrencia = RatOcorrencia::create([
                'numero_bos'     => $protocolo,
                'sequencial_ano' => now()->year,
                'status'         => 0,
                'prazo_edicao'   => now()->addHours(48),
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);

            $id = (string) $ocorrencia->id;

            if (isset($data['dadosGerais']) || isset($data['comunicacao']) || isset($data['local']) || isset($data['endereco'])) {
                $this->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($data));
            }

            if (isset($data['recursos']) && is_array($data['recursos'])) {
                foreach ($data['recursos'] as $recurso) {
                    $this->saveRecurso($id, RatRecursoDTO::fromArray($recurso));
                }
            }

            if (isset($data['envolvidos']) && is_array($data['envolvidos'])) {
                foreach ($data['envolvidos'] as $envolvido) {
                    $this->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($envolvido));
                }
            }

            if (isset($data['vistoria']) && is_array($data['vistoria']) && !empty($data['vistoria'])) {
                $this->saveVistoria($id, RatVistoriaDTO::fromArray($data['vistoria']));
            }

            if (isset($data['historico'])) {
                $this->saveHistorico($id, RatHistoricoDTO::fromArray(['historico' => $data['historico']]));
            }

            /*
             * Registro completo = criacao que ja chega com conteudo. O
             * esqueleto vazio de create() e o autosave de saveDraft() ficam de
             * fora: rascunho nao e entrega.
             */
            if ($this->temConteudoDeRegistro($data)) {
                $this->publicarRegistroCompleto($ocorrencia, $this->actorUserId());
            }

            if (!empty($data['finalize'])) {
                $ocorrencia->update([
                    'status'     => 1,
                    'updated_by' => $userId,
                ]);

                $this->publicarRelatorioFinalizado($ocorrencia, $this->actorUserId(), 'criacao');
            }

            return $ocorrencia->fresh();
        });
    }

    public function findById(string $id): ?RatOcorrencia
    {
        return RatOcorrencia::with([
            'creator',
            'updater',
            'historicos',
            'ratAnexos',
            'relatosMorph.conteudo', // Carrega vistoria, envolvidos, recursos, dados_gerais
            'ocorrenciaOrigem',
            'ocorrenciasFilhas',
        ])->find($id);
    }

    public function finalize(string $id): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        abort_if($ocorrencia->status === 1, 422, 'RAT já está finalizado.');

        // Finalizar manualmente e permitido a qualquer momento: e o autor
        // abrindo mao do restante da janela de edicao de 48h. O prazo_edicao
        // existe para BLOQUEAR edicao apos vencer (update()) e para o
        // fechamento automatico (rat:close-expired) — nao para impedir o
        // fechamento manual antecipado (com o bloqueio, ninguem finalizava:
        // antes das 48h a regra proibia e depois o cron ja tinha fechado).
        // Transacao: o RelatorioFinalizadoV1 tem de cair no outbox junto com a
        // virada de status, senao o marco existe no banco e nunca no ranking
        // (ou o contrario, se o outbox gravasse fora).
        return DB::transaction(function () use ($ocorrencia): RatOcorrencia {
            $userId = $this->actorUserId();

            $ocorrencia->update([
                'status'     => 1,
                'updated_by' => $userId,
            ]);

            $this->publicarRelatorioFinalizado($ocorrencia, $userId, 'manual');

            return $ocorrencia->fresh();
        });
    }

    public function saveDraft(string $id, array $data): RatOcorrencia
    {
        return DB::transaction(function () use ($id, $data) {
            $ocorrencia = RatOcorrencia::findOrFail($id);

            // Após o prazo de 48h, o RAT fica bloqueado para edição
            if ($ocorrencia->prazo_edicao && $ocorrencia->prazo_edicao->isPast()) {
                abort(422, 'O prazo de edição de 48h foi encerrado. Este RAT não pode mais ser alterado.');
            }

            $userId = Auth::id();

            // Via modelo, e nao `where(...)->update()`: observer do Eloquent nao
            // dispara para escrita em massa, e a listagem depende dele.
            RatOcorrencia::find($id)?->update([
                'status'     => 0,
                'updated_by' => $userId,
            ]);

            if (isset($data['dadosGerais']) || isset($data['comunicacao']) || isset($data['local']) || isset($data['endereco'])) {
                $this->saveDadosGerais($id, RatDadosGeraisDTO::fromArray($data));
            }

            if (isset($data['recursos']) && is_array($data['recursos'])) {
                RatRelatoRecurso::where('ocorrencia_id', $id)->delete();
                RatOcorrenciaRelato::where('ocorrencia_id', $id)
                    ->where('conteudo_type', RatRelatoRecurso::class)
                    ->forceDelete();

                foreach ($data['recursos'] as $index => $recursoData) {
                    unset($recursoData['id']);
                    $recursoData['seq'] = $recursoData['seq'] ?? ($index + 1);
                    $this->saveRecurso($id, RatRecursoDTO::fromArray($recursoData));
                }
            }

            if (isset($data['envolvidos']) && is_array($data['envolvidos'])) {
                RatRelatoEnvolvidos::where('ocorrencia_id', $id)->delete();
                RatOcorrenciaRelato::where('ocorrencia_id', $id)
                    ->where('conteudo_type', RatRelatoEnvolvidos::class)
                    ->forceDelete();

                foreach ($data['envolvidos'] as $index => $envolvidoData) {
                    unset($envolvidoData['id']);
                    $envolvidoData['seq'] = $envolvidoData['seq'] ?? ($index + 1);
                    $this->saveEnvolvido($id, RatEnvolvidoDTO::fromArray($envolvidoData));
                }
            }

            if (isset($data['vistoria']) && !empty($data['vistoria'])) {
                $this->saveVistoria($id, RatVistoriaDTO::fromArray($data['vistoria']));
            }

            if (isset($data['historico'])) {
                $this->saveHistorico($id, RatHistoricoDTO::fromArray(['historico' => $data['historico']]));
            }

            return RatOcorrencia::find($id);
        });
    }

    public function saveDadosGerais(string $ocorrenciaId, RatDadosGeraisDTO $dto): RatRelatoDadosGerais
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $dadosArray = $dto->toArray();

            $dadosGerais = RatRelatoDadosGerais::updateOrCreate(
                ['ocorrencia_id' => $ocorrenciaId],
                array_merge($dadosArray, [
                    'ocorrencia_id' => $ocorrenciaId,
                    'created_by'    => Auth::id(),
                ])
            );
            $this->ensureRelatoLink($ocorrenciaId, $dadosGerais);

            return $dadosGerais;
        });
    }

    public function saveEnvolvido(string $ocorrenciaId, RatEnvolvidoDTO $dto): RatRelatoEnvolvidos
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $data = array_merge($dto->toArray(), [
                'ocorrencia_id' => $ocorrenciaId,
                'created_by'    => Auth::id(),
            ]);

            if ($dto->id) {
                $envolvido = RatRelatoEnvolvidos::find($dto->id);
                $envolvido ? $envolvido->update($data) : $envolvido = RatRelatoEnvolvidos::create($data);
            } else {
                $envolvido = RatRelatoEnvolvidos::create($data);
            }

            $this->ensureRelatoLink($ocorrenciaId, $envolvido);
            return $envolvido;
        });
    }

    public function saveRecurso(string $ocorrenciaId, RatRecursoDTO $dto): RatRelatoRecurso
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $data = array_merge($dto->toArray(), [
                'ocorrencia_id' => $ocorrenciaId,
                'created_by'    => Auth::id(),
            ]);

            if ($dto->id) {
                $recurso = RatRelatoRecurso::find($dto->id);
                $recurso ? $recurso->update($data) : $recurso = RatRelatoRecurso::create($data);
            } else {
                $recurso = RatRelatoRecurso::create($data);
            }

            $this->ensureRelatoLink($ocorrenciaId, $recurso);

            if ($dto->agentes !== null) {
                foreach ($dto->agentes as $agenteDto) {
                    $agenteData = array_merge($agenteDto->toArray(), [
                        'relato_recurso_id' => $recurso->id,
                        'created_by'        => Auth::id(),
                    ]);
                    if ($agenteDto->id) {
                        $agente = RatRecursosComponentesGuarnicao::find($agenteDto->id);
                        $agente ? $agente->update($agenteData) : RatRecursosComponentesGuarnicao::create($agenteData);
                    } else {
                        RatRecursosComponentesGuarnicao::create($agenteData);
                    }
                }
            }

            return $recurso;
        });
    }

    public function saveHistorico(string $ocorrenciaId, RatHistoricoDTO $dto): void
    {
        DB::transaction(function () use ($ocorrenciaId, $dto) {
            $historico = $dto->historicoArray ?? ($dto->historico ? [$dto->historico] : []);

            // Enforça limite de 500 caracteres por entrada de texto
            $historico = array_map(function ($entry) {
                if (is_string($entry)) {
                    return mb_substr($entry, 0, 500);
                }
                if (is_array($entry)) {
                    foreach (['texto', 'descricao', 'text', 'content'] as $key) {
                        if (isset($entry[$key]) && is_string($entry[$key])) {
                            $entry[$key] = mb_substr($entry[$key], 0, 500);
                        }
                    }
                }
                return $entry;
            }, $historico);

            // where()->update() bypasses Eloquent casts — JSON-encode explicitly for jsonb column
            RatOcorrencia::where('id', $ocorrenciaId)->update([
                'historico' => json_encode($historico, JSON_UNESCAPED_UNICODE) ?: '[]',
            ]);
        });
    }

    public function saveVistoria(string $ocorrenciaId, RatVistoriaDTO $dto): RatRelatoVistoria
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $vistoriaArray = $dto->toArray();

            $vistoria = RatRelatoVistoria::updateOrCreate(
                ['ocorrencia_id' => $ocorrenciaId],
                array_merge($vistoriaArray, [
                    'ocorrencia_id' => $ocorrenciaId,
                    'created_by'    => Auth::id(),
                ])
            );
            $this->ensureRelatoLink($ocorrenciaId, $vistoria);
            return $vistoria;
        });
    }

    /**
     * Ha conteudo de RAT no payload, ou e so o esqueleto?
     */
    private function temConteudoDeRegistro(array $data): bool
    {
        foreach (['dadosGerais', 'comunicacao', 'local', 'endereco', 'recursos', 'envolvidos', 'vistoria', 'historico'] as $bloco) {
            if (!empty($data[$bloco])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Autor da acao, capturado AQUI, onde a request ainda existe.
     *
     * As colunas de autoria do RAT nao reconstroem isso depois:
     * `rat_ocorrencias.created_by`/`updated_by` sao string(191) e
     * `rat_relato_recursos.created_by` e unsignedBigInteger, nenhuma das duas
     * com FK para `users`. O evento carrega o id explicito ou o fato vai para
     * apuracao no ranking — jamais se deduz autor do texto de created_by.
     */
    private function actorUserId(): ?int
    {
        $id = Auth::id();

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    private function publicarRegistroCompleto(RatOcorrencia $ocorrencia, ?int $userId): void
    {
        $registradoEm = $ocorrencia->created_at ?? now();

        $this->outbox->persist(new RegistroCompletoV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'rat_ocorrencia',
            aggregateId:   (string) $ocorrencia->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'ocorrencia_id'  => (string) $ocorrencia->id,
                'numero_bos'     => $ocorrencia->numero_bos,
                'sequencial_ano' => $ocorrencia->sequencial_ano,
                'status'         => (int) $ocorrencia->status,
                'actor_user_id'  => $userId,
                'prazo_edicao'   => $ocorrencia->prazo_edicao?->toIso8601String(),
                'registrado_em'  => $registradoEm->toIso8601String(),
            ],
        ));
    }

    /**
     * `origem` distingue o fechamento manual da finalizacao embutida na
     * criacao. O fechamento automatico por prazo (rat:close-expired) NAO passa
     * por aqui: ninguem entregou nada, a janela de 48h so venceu.
     */
    private function publicarRelatorioFinalizado(RatOcorrencia $ocorrencia, ?int $userId, string $origem): void
    {
        $finalizadoEm = new \DateTimeImmutable();

        $this->outbox->persist(new RelatorioFinalizadoV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'rat_ocorrencia',
            aggregateId:   (string) $ocorrencia->id,
            occurredAt:    $finalizadoEm,
            metadata: [
                'ocorrencia_id'  => (string) $ocorrencia->id,
                'numero_bos'     => $ocorrencia->numero_bos,
                'sequencial_ano' => $ocorrencia->sequencial_ano,
                'status'         => 1,
                'actor_user_id'  => $userId,
                'prazo_edicao'   => $ocorrencia->prazo_edicao?->toIso8601String(),
                'finalizado_em'  => $finalizadoEm->format(\DATE_ATOM),
                'origem'         => $origem,
            ],
        ));
    }

    private function ensureRelatoLink(string $ocorrenciaId, object $model): void
    {
        RatOcorrenciaRelato::firstOrCreate([
            'ocorrencia_id' => $ocorrenciaId,
            'conteudo_id'   => $model->id,
            'conteudo_type' => get_class($model),
        ], [
            'created_by' => Auth::id(),
        ]);
    }
}
