<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * RN-25 servida pelo estoque nativo, sem depender da base procedural.
 *
 * Substitui LegadoSaldoMaterialRepository, que cruzava aju_estoque, aju_deposito
 * e aju_unidade em outro banco, por MySQL, e precisava de cache justamente por
 * isso. Aqui a leitura e local e barata, entao nao ha cache: o saldo sai sempre
 * atual, e some a janela de 5 minutos em que a tela mostrava numero velho.
 *
 * disponivel() e sempre true porque a fonte agora e o banco da propria
 * aplicacao. Se ele estiver fora, nada mais responde de qualquer forma, e nao
 * ha o que degradar.
 *
 * O contrato devolve saldo como int enquanto ajuda_h_estoque_saldos guarda
 * numeric(14,3). A conversao fica aqui para nao propagar mudanca de assinatura
 * para controllers e telas nesta fase. Os dados migrados do legado sao todos
 * inteiros, entao nada e truncado hoje; quando o dominio passar a lidar com
 * fracao, contrato e consumidores mudam juntos.
 */
final class PostgresSaldoMaterialRepository implements SaldoMaterialRepositoryInterface
{
    /**
     * @return array<int, array{deposito: string, material: string, saldo: int}>
     */
    public function saldoPorDeposito(?string $codigoLegado = null): array
    {
        $consulta = DB::table('ajuda_h_estoque_saldos as s')
            ->join('ajuda_h_depositos as d', 's.deposito_id', '=', 'd.id')
            ->join('materiais_ah as m', 's.material_ah_id', '=', 'm.id')
            ->where('s.saldo', '<>', 0)
            ->orderBy('d.nome')
            ->orderBy('m.nome')
            ->select([
                'd.nome as deposito',
                'm.nome as material',
                's.saldo as saldo',
            ]);

        if ($codigoLegado !== null) {
            $consulta->where('m.codigo_legado', $codigoLegado);
        }

        return $consulta->get()
            ->map(static fn (object $linha): array => [
                'deposito' => (string) $linha->deposito,
                'material' => (string) $linha->material,
                'saldo'    => (int) $linha->saldo,
            ])
            ->all();
    }

    public function disponivel(): bool
    {
        return true;
    }
}
