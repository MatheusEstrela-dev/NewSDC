<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

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

            // Extrair sufixo e incrementar
            $nextSuffix = 0;
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

            if (!empty($data['finalize'])) {
                $ocorrencia->update([
                    'status'     => 1,
                    'updated_by' => $userId,
                ]);
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
        ])->find($id);
    }

    public function finalize(string $id): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        abort_if($ocorrencia->status === 1, 422, 'RAT já está finalizado.');

        $ocorrencia->update([
            'status'     => 1,
            'updated_by' => Auth::id(),
        ]);

        return $ocorrencia->fresh();
    }

    public function saveDraft(string $id, array $data): RatOcorrencia
    {
        return DB::transaction(function () use ($id, $data) {
            $userId = Auth::id();

            RatOcorrencia::where('id', $id)->update([
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

            // Gera o número do BOS no formato {ano}-{seq 9 dígitos}-{cod 3 dígitos}
            // quando todos os campos identificadores do BO estiverem preenchidos.
            if ($dto->uniBoAno && $dto->uniBoSequencial && $dto->uniBoCodUnidade) {
                $ano      = str_pad((string) $dto->uniBoAno, 4, '0', STR_PAD_LEFT);
                $seq      = str_pad((string) $dto->uniBoSequencial, 9, '0', STR_PAD_LEFT);
                $cod      = str_pad((string) $dto->uniBoCodUnidade, 3, '0', STR_PAD_LEFT);
                $numeroBos = "{$ano}-{$seq}-{$cod}";
                RatOcorrencia::where('id', $ocorrenciaId)->update([
                    'numero_bos'     => $numeroBos,
                    'sequencial_ano' => (int) $dto->uniBoAno,
                ]);
            }

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
