<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatDadosGeraisDTO;
use App\Modules\Rat\DTOs\RatEnvolvidoDTO;
use App\Modules\Rat\DTOs\RatRecursoDTO;
use App\Modules\Rat\DTOs\RatAgenteDTO;
use App\Modules\Rat\DTOs\RatVistoriaDTO;
use App\Modules\Rat\DTOs\RatHistoricoDTO;
use App\Modules\Rat\Enums\Status;
use App\Modules\Rat\Models\Rat;
use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\RatOcorrenciaRelato;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Models\Relatos\RatRelatoEnvolvidos;
use App\Modules\Rat\Models\Relatos\RatRelatoRecurso;
use App\Models\Rat\Recursos\RatRecursosEmpregado;
use App\Modules\Rat\Models\Recursos\RatRecursosComponentesGuarnicao;
use App\Modules\Rat\Models\Relatos\RatRelatoVistoria;
use Illuminate\Support\Facades\DB;

/**
 * Persiste alterações em RATs — criação, atualização, rascunho e finalização.
 * Depende de RatRepositoryInterface e RatProtocoloService (abstrações).
 *
 * 4 métodos púблicos:
 *   create()    — cria RAT em branco com protocolo único (transação)
 *   update()    — persiste dados com status EM_ANDAMENTO
 *   saveDraft() — persiste dados mantendo status RASCUNHO
 *   finalize()  — muda status para FINALIZADO (com guard clauses)
 */
class RatWriteService
{
    public function __construct(
        private readonly RatRepositoryInterface $repository,
        private readonly RatProtocoloService    $protocoloService,
    ) {}

    /** Cria um RAT em branco com protocolo sequencial único dentro de transação atômica. */
    public function create(): Rat
    {
        return DB::transaction(function () {
            $protocolo = $this->protocoloService->generate();
            return $this->repository->create($this->buildInitialData($protocolo));
        });
    }

    /** Cria um RAT com dados do formulário em uma única transação. */
    public function createWithData(array $data): Rat
    {
        return DB::transaction(function () use ($data) {
            $protocolo = $this->protocoloService->generate();
            $ocorrencia = RatOcorrencia::create([
                'numero_bos'  => $protocolo,
                'status'      => 0,
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);

            $id = (string)$ocorrencia->id;

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

            if (isset($data['finalize']) && $data['finalize']) {
                $ocorrencia->update(['status' => 1]);
            }

            return $this->repository->findById($id);
        });
    }

    /** Persiste todos os campos editáveis e avança o status para EM_ANDAMENTO. */
    public function update(string $id, array $data): Rat
    {
        return $this->repository->update($id, array_merge($data, [
            'status' => Status::EM_ANDAMENTO->value,
        ]));
    }

    /** Persiste os dados mantendo o status RASCUNHO. */
    public function saveDraft(string $id, array $data): Rat
    {
        return $this->repository->update($id, array_merge($data, [
            'status' => Status::RASCUNHO->value,
        ]));
    }

    /** Finaliza o RAT — aborta se já finalizado. */
    public function finalize(string $id): Rat
    {
        $rat = $this->repository->findById($id);
        abort_if(is_null($rat), 404, 'RAT não encontrado.');
        abort_if($rat->status === Status::FINALIZADO->value, 422, 'RAT já está finalizado.');

        $this->repository->updateStatus($id, Status::FINALIZADO->value);
        return $rat->fresh();
    }

    /** Salva ou atualiza os Dados Gerais de um RAT. */
    public function saveDadosGerais(string $ocorrenciaId, RatDadosGeraisDTO $dto): RatRelatoDadosGerais
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            /** @var \Illuminate\Contracts\Auth\Guard $auth */
            $auth = auth();
            $relatoId = $this->getRelatoId($ocorrenciaId, RatRelatoDadosGerais::class);
            
            $dadosGerais = RatRelatoDadosGerais::updateOrCreate(
                ['id' => $relatoId],
                array_merge($dto->toArray(), [
                    'ocorrencia_id' => $ocorrenciaId,
                    'usuario_id'    => $auth->id(),
                    'created_by'    => $auth->id(),
                ])
            );

            $this->ensureRelatoLink($ocorrenciaId, $dadosGerais);
            return $dadosGerais;
        });
    }

    /** Salva um envolvido (cria ou atualiza). */
    public function saveEnvolvido(string $ocorrenciaId, RatEnvolvidoDTO $dto): RatRelatoEnvolvidos
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            /** @var \Illuminate\Contracts\Auth\Guard $auth */
            $auth = auth();
            $envolvido = RatRelatoEnvolvidos::updateOrCreate(
                ['id' => $dto->id],
                array_merge($dto->toArray(), [
                    'ocorrencia_id' => $ocorrenciaId,
                    'usuario_id'    => $auth->id(),
                    'created_by'    => $auth->id()
                ])
            );

            $this->ensureRelatoLink($ocorrenciaId, $envolvido);
            return $envolvido;
        });
    }

    /** Salva um recurso e seus agentes. */
    public function saveRecurso(string $ocorrenciaId, RatRecursoDTO $dto): RatRelatoRecurso
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            /** @var \Illuminate\Contracts\Auth\Guard $auth */
            $auth = auth();
            // 1. Salva o relato de recurso principal
            $recurso = RatRelatoRecurso::updateOrCreate(
                ['id' => $dto->id],
                array_merge($dto->toArray(), [
                    'ocorrencia_id' => $ocorrenciaId,
                    'usuario_id'    => $auth->id(),
                    'created_by'    => $auth->id()
                ])
            );

            $this->ensureRelatoLink($ocorrenciaId, $recurso);

            // 2. Salva agentes (guarnição) se fornecidos
            if ($dto->agentes !== null) {
                // Remove agentes antigos para evitar duplicatas se for um update completo
                // Ou apenas faz updateOrCreate individualmente
                foreach ($dto->agentes as $agenteDto) {
                    RatRecursosComponentesGuarnicao::updateOrCreate(
                        ['id' => $agenteDto->id],
                        array_merge($agenteDto->toArray(), [
                            'relato_recurso_id' => $recurso->id,
                            'created_by' => $auth->id()
                        ])
                    );
                }
            }

            return $recurso;
        });
    }

    /** Salva o histórico/descrição. */
    public function saveHistorico(string $ocorrenciaId, RatHistoricoDTO $dto): void
    {
        // O histórico narrativo deve ser salvo na coluna 'descricao' dos Dados Gerais
        DB::transaction(function () use ($ocorrenciaId, $dto) {
            $dadosGerais = RatRelatoDadosGerais::where('ocorrencia_id', $ocorrenciaId)->first();
            if (!$dadosGerais) {
                // Cria rascunho de dados gerais se não existir
                /** @var \Illuminate\Contracts\Auth\Guard $auth */
                $auth = auth();
                $dadosGerais = RatRelatoDadosGerais::create([
                    'ocorrencia_id' => $ocorrenciaId,
                    'usuario_id'    => $auth->id(),
                    'created_by'    => $auth->id(),
                    'descricao'     => $dto->historico,
                    'nat_codigo'    => 'NI', // Não informado inicialmente
                    'data_fato'     => now(),
                    'local_origem'  => 'NI',
                ]);
                $this->ensureRelatoLink($ocorrenciaId, $dadosGerais);
            } else {
                $dadosGerais->update(['descricao' => $dto->historico]);
            }
            
            // Também salva na coluna historico da RatOcorrencia para compatibilidade frontend
            RatOcorrencia::where('id', $ocorrenciaId)->update(['historico' => $dto->historico]);
        });
    }

    /** Salva ou atualiza uma Vistoria. */
    public function saveVistoria(string $ocorrenciaId, RatVistoriaDTO $dto): RatRelatoVistoria
    {
        return DB::transaction(function () use ($ocorrenciaId, $dto) {
            /** @var \Illuminate\Contracts\Auth\Guard $auth */
            $auth = auth();
            $vistoria = RatRelatoVistoria::updateOrCreate(
                ['id' => $this->getRelatoId($ocorrenciaId, RatRelatoVistoria::class)],
                array_merge($dto->toArray(), ['created_by' => $auth->id()])
            );

            $this->ensureRelatoLink($ocorrenciaId, $vistoria);
            return $vistoria;
        });
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** Garante que existe um link na tabela pivô para este relato. */
    private function ensureRelatoLink(string $ocorrenciaId, $model): void
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        RatOcorrenciaRelato::firstOrCreate([
            'ocorrencia_id' => $ocorrenciaId,
            'conteudo_id'   => $model->id,
            'conteudo_type' => get_class($model),
        ], [
            'created_by' => $auth->id(),
        ]);
    }

    /** Obtém o ID do relato existente para uma ocorrência e tipo. */
    private function getRelatoId(string $ocorrenciaId, string $type): ?int
    {
        return RatOcorrenciaRelato::where('ocorrencia_id', $ocorrenciaId)
            ->where('conteudo_type', $type)
            ->value('conteudo_id');
    }

    private function buildInitialData(string $protocolo): array
    {
        return [
            'protocolo'    => $protocolo,
            'status'       => Status::RASCUNHO->value,
            'dados_gerais' => [],
            'local'        => [],
            'endereco'     => [],
            'comunicacao'  => [],
            'recursos'     => [],
            'envolvidos'   => [],
            'vistoria'     => [],
            'historico'    => [],
            'anexos'       => [],
        ];
    }
}

