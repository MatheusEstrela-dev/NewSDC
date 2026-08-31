<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Services\HistoricoService;

/**
 * Auditoria de negocio do cadastro de Prestador.
 *
 * HistoricoService::ENTITY_TYPE_MAP ja mapeava Prestador para a chave
 * 'prestador', mas nenhum observer estava registrado -- nada do cadastro
 * aparecia em /tdap/historicos. Como o Prestador e a raiz de todo o TDAP
 * (caminhao -> lote -> cronograma apontam para ele), quem entrou, quem saiu e
 * quem foi desativado precisa estar na trilha.
 *
 * O diff campo-a-campo continua sendo trabalho do LogsModelChanges; aqui ficam
 * apenas os eventos de negocio.
 */
class PrestadorObserver
{
    /**
     * Campos cuja alteracao vale um registro na trilha. Endereco e observacao
     * mudam por correcao de digitacao e so poluiriam o historico.
     *
     * @var array<int, string>
     */
    private const CAMPOS_RELEVANTES = ['cnpj', 'nome', 'representante', 'email', 'tel1', 'tel2', 'ativo'];

    public function __construct(
        private readonly HistoricoService $historicoService,
    ) {}

    public function created(Prestador $prestador): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'prestador.criado',
            entity: $prestador,
            obs: "Prestador {$prestador->nome} cadastrado.",
            payload: [
                'cnpj'  => $prestador->cnpj,
                'nome'  => $prestador->nome,
                'email' => $prestador->email,
                'ativo' => (bool) $prestador->ativo,
            ],
        );
    }

    public function updated(Prestador $prestador): void
    {
        // Ativacao/desativacao e evento proprio: e ela que decide se o
        // prestador aparece nos seletores de lote e cronograma.
        if ($prestador->wasChanged('ativo')) {
            $ativo = (bool) $prestador->ativo;

            $this->historicoService->registrar(
                tipoEvento: $ativo ? 'prestador.ativado' : 'prestador.desativado',
                entity: $prestador,
                obs: "Prestador {$prestador->nome} ".($ativo ? 'ativado.' : 'desativado.'),
                payload: ['ativo' => $ativo],
            );
        }

        $alterados = array_values(array_filter(
            self::CAMPOS_RELEVANTES,
            fn (string $campo): bool => $campo !== 'ativo' && $prestador->wasChanged($campo),
        ));

        if ($alterados === []) {
            return;
        }

        $this->historicoService->registrar(
            tipoEvento: 'prestador.atualizado',
            entity: $prestador,
            obs: "Prestador {$prestador->nome} atualizado: ".implode(', ', $alterados).'.',
            payload: [
                'campos_alterados' => $alterados,
                'anteriores'       => array_map(
                    fn (string $campo) => $prestador->getOriginal($campo),
                    array_combine($alterados, $alterados),
                ),
            ],
        );
    }

    public function deleted(Prestador $prestador): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'prestador.excluido',
            entity: $prestador,
            obs: "Prestador {$prestador->nome} excluido.",
            payload: ['cnpj' => $prestador->cnpj, 'nome' => $prestador->nome],
        );
    }
}
