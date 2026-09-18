<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Observers;

use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Services\HistoricoService;

/**
 * Auditoria de negocio do cadastro de Caminhao.
 *
 * Mesmo buraco que o PrestadorObserver foi criado para tapar:
 * HistoricoService::ENTITY_TYPE_MAP ja mapeava Caminhao para 'caminhao', mas
 * nenhum observer estava registrado -- nada do cadastro da frota aparecia em
 * /tdap/historicos. E aqui pesa mais que no prestador: o caminhao e o que
 * efetivamente roda, a placa e a identidade dele nos oficios, e trocar a placa
 * ou desativar o veiculo muda quem pode ser alocado em cronograma.
 *
 * Eventos de negocio apenas -- diff campo-a-campo nao e trabalho da trilha.
 */
class CaminhaoObserver
{
    /**
     * Campos cuja alteracao vale um registro.
     *
     * `observacoes` fica de fora pelo mesmo motivo que endereco ficou no
     * prestador: muda por correcao de digitacao e so poluiria o historico.
     * `prestador_id` entra porque e troca de dono -- o caminhao passa a contar
     * na frota de outra empresa.
     *
     * @var array<int, string>
     */
    private const CAMPOS_RELEVANTES = ['placa', 'prestador_id', 'capacidade_m3', 'marca', 'modelo', 'ano', 'ativo'];

    public function __construct(
        private readonly HistoricoService $historicoService,
    ) {}

    public function created(Caminhao $caminhao): void
    {
        $this->historicoService->registrar(
            tipoEvento: 'caminhao.criado',
            entity: $caminhao,
            obs: "Caminhao {$caminhao->placa} cadastrado.",
            payload: [
                'placa'         => $caminhao->placa,
                'prestador_id'  => $caminhao->prestador_id,
                'capacidade_m3' => (float) $caminhao->capacidade_m3,
                'ativo'         => (bool) $caminhao->ativo,
            ],
        );
    }

    public function updated(Caminhao $caminhao): void
    {
        // Ativacao/desativacao e evento proprio: e ela que decide se o veiculo
        // aparece no seletor de alocacao e no de nova vistoria.
        if ($caminhao->wasChanged('ativo')) {
            $ativo = (bool) $caminhao->ativo;

            $this->historicoService->registrar(
                tipoEvento: $ativo ? 'caminhao.ativado' : 'caminhao.desativado',
                entity: $caminhao,
                obs: "Caminhao {$caminhao->placa} ".($ativo ? 'ativado.' : 'desativado.'),
                payload: ['ativo' => $ativo],
            );
        }

        $alterados = array_values(array_filter(
            self::CAMPOS_RELEVANTES,
            fn (string $campo): bool => $campo !== 'ativo' && $caminhao->wasChanged($campo),
        ));

        if ($alterados === []) {
            return;
        }

        $this->historicoService->registrar(
            tipoEvento: 'caminhao.atualizado',
            entity: $caminhao,
            obs: "Caminhao {$caminhao->placa} atualizado: ".implode(', ', $alterados).'.',
            payload: [
                'campos_alterados' => $alterados,
                'anteriores'       => array_map(
                    fn (string $campo) => $caminhao->getOriginal($campo),
                    array_combine($alterados, $alterados),
                ),
            ],
        );
    }

    public function deleted(Caminhao $caminhao): void
    {
        // Force delete repete o evento `deleted`; o registro ja saiu no soft.
        if ($caminhao->isForceDeleting()) {
            return;
        }

        $this->historicoService->registrar(
            tipoEvento: 'caminhao.excluido',
            entity: $caminhao,
            obs: "Caminhao {$caminhao->placa} excluido.",
            payload: [
                'placa'        => $caminhao->placa,
                'prestador_id' => $caminhao->prestador_id,
            ],
        );
    }
}
