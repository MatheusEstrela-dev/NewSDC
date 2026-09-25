<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Domain\Contracts\DemandaRepository;
use App\Modules\Demandas\Domain\Events\DemandaCriadaV1;
use App\Modules\Demandas\Domain\Services\ValidadorCamposDinamicos;
use App\Modules\Demandas\DTOs\AtualizarDemandaData;
use App\Modules\Demandas\DTOs\CriarDemandaData;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Enums\StatusDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAssunto;
use App\Modules\Demandas\Support\ContextoImportacao;
use Illuminate\Support\Facades\DB;

/**
 * Abrir e editar demanda. Publico para outros contextos: o Inventario abre
 * chamado de lote por aqui, nunca criando Demanda direto.
 */
final class DemandaWriteService
{
    /**
     * Mensagem de historico por campo editado, para o titulo nao aparecer como
     * "Descrição alterada." na aba Historico.
     */
    private const MENSAGENS_CAMPO_EDITADO = [
        'titulo' => 'Título alterado.',
        'descricao' => 'Descrição alterada.',
        'assunto_id' => 'Assunto alterado.',
    ];

    public function __construct(
        private readonly DemandaRepository $repository,
        private readonly ValidadorCamposDinamicos $validador,
        private readonly HistoricoDemanda $historico,
        private readonly SlaEngine $sla,
        private readonly ContextoImportacao $contexto,
    ) {}

    public function abrir(CriarDemandaData $dados): Demanda
    {
        $assunto = $dados->assuntoId !== null ? DemandaAssunto::find($dados->assuntoId) : null;
        $campos = $this->validador->normalizar($assunto, $dados->camposCustomizados);

        return DB::transaction(function () use ($dados, $campos): Demanda {
            $demanda = new Demanda($dados->toArray());
            $demanda->status = StatusDemanda::ABERTA;
            $demanda->campos_customizados = $campos;
            $this->repository->save($demanda);

            $this->historico->registrar(
                $demanda, $dados->criadoPorId, AcaoHistoricoDemanda::CRIADA,
                'Chamado aberto com status inicial: Em aberto.'
            );

            if (! $this->contexto->ativo()) {
                $this->sla->iniciarSlaParaDemanda($demanda);
                DB::afterCommit(static fn () => event(DemandaCriadaV1::create(
                    $demanda->id,
                    $demanda->protocolo,
                    $demanda->tipo->value,
                    (string) ($demanda->prioridade?->value ?? 3),
                    (int) $demanda->solicitante_id,
                )));
            }

            return $demanda;
        });
    }

    public function atualizar(Demanda $demanda, AtualizarDemandaData $dados, int $userId): Demanda
    {
        return DB::transaction(function () use ($demanda, $dados, $userId): Demanda {
            $antes = $demanda->only(['titulo', 'descricao', 'assunto_id']);
            $demanda->fill($dados->toArray());

            $trocouAssunto = $demanda->isDirty('assunto_id');
            if ($trocouAssunto || $dados->camposCustomizados !== null) {
                $assunto = $demanda->assunto_id !== null ? DemandaAssunto::find($demanda->assunto_id) : null;
                $demanda->campos_customizados = $this->validador->normalizar(
                    $assunto,
                    $dados->camposCustomizados ?? ($trocouAssunto ? [] : ($demanda->campos_customizados ?? [])),
                );
            }

            $this->repository->save($demanda);

            foreach ($antes as $campo => $valorAnterior) {
                if ((string) $valorAnterior === (string) $demanda->{$campo}) {
                    continue;
                }
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::EDITADA,
                    self::MENSAGENS_CAMPO_EDITADO[$campo] ?? 'Edição.',
                    campo: $campo, anterior: (string) $valorAnterior, novo: (string) $demanda->{$campo},
                );
            }

            return $demanda;
        });
    }
}
