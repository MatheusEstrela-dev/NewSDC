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
            $userId    = auth()->id();

            return RatOcorrencia::create([
                'numero_bos' => $protocolo,
                'status'     => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function createWithData(array $data): RatOcorrencia
    {
        return DB::transaction(function () use ($data) {
            $protocolo = $this->protocoloService->generate();
            $userId    = auth()->id();

            $ocorrencia = RatOcorrencia::create([
                'numero_bos' => $protocolo,
                'status'     => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
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
            'relatosMorph.conteudo',
            'historicos',
        ])->find($id);
    }

    public function finalize(string $id): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        abort_if($ocorrencia->status === 1, 422, 'RAT já está finalizado.');

        $ocorrencia->update([
            'status'     => 1,
            'updated_by' => auth()->id(),
        ]);

        return $ocorrencia->fresh();
    }

    public function saveDraft(string $id, array $data): RatOcorrencia
    {
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->update([
            'status'     => 0,
            'updated_by' => auth()->id(),
        ]);
        return $ocorrencia->fresh();
    }

    public function saveDadosGerais(string $ocorrenciaId, RatDadosGeraisDTO $dto): RatRelatoDadosGerais
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $dadosGerais = RatRelatoDadosGerais::updateOrCreate(
                ['ocorrencia_id' => $ocorrenciaId],
                array_merge($dto->toArray(), [
                    'ocorrencia_id' => $ocorrenciaId,
                    'created_by'    => auth()->id(),
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
                'created_by'    => auth()->id(),
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
                'created_by'    => auth()->id(),
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
                    RatRecursosComponentesGuarnicao::updateOrCreate(
                        ['id' => $agenteDto->id ?? null, 'relato_recurso_id' => $recurso->id],
                        array_merge($agenteDto->toArray(), [
                            'relato_recurso_id' => $recurso->id,
                            'created_by'        => auth()->id(),
                        ])
                    );
                }
            }

            return $recurso;
        });
    }

    public function saveHistorico(string $ocorrenciaId, RatHistoricoDTO $dto): void
    {
        DB::transaction(function () use ($ocorrenciaId, $dto) {
            $historico = $dto->historicoArray ?? ($dto->historico ? [$dto->historico] : []);
            RatOcorrencia::where('id', $ocorrenciaId)->update(['historico' => $historico]);
        });
    }

    public function saveVistoria(string $ocorrenciaId, RatVistoriaDTO $dto): RatRelatoVistoria
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            $vistoria = RatRelatoVistoria::updateOrCreate(
                ['ocorrencia_id' => $ocorrenciaId],
                array_merge($dto->toArray(), [
                    'ocorrencia_id' => $ocorrenciaId,
                    'created_by'    => auth()->id(),
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
            'created_by' => auth()->id(),
        ]);
    }
}
