<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Infrastructure\Persistence;

use App\Modules\AjudaHumanitaria\Domain\Repositories\SaldoMaterialRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RN-25: leitura do saldo de material por deposito na base legada.
 *
 * Somente leitura. Nenhuma escrita na base do sistema procedural.
 *
 * A base e a do gestaocedec, onde vivem aju_estoque, aju_deposito e
 * aju_unidade. O legado consultava esse saldo com a categoria CESTA BASICA
 * fixa em codigo; aqui o recorte e pelo codigo legado do material, que o
 * catalogo do modulo guarda em materiais_ah.codigo_legado.
 *
 * Toda falha de conexao vira lista vazia mais registro em log: a tela de
 * disponibilidade de material degrada, nao quebra. Enquanto as chaves
 * DB_LEGACY_* nao estiverem no .env, esse e o comportamento esperado.
 */
final class LegadoSaldoMaterialRepository implements SaldoMaterialRepositoryInterface
{
    /**
     * @return array<int, array{deposito: string, material: string, saldo: int}>
     */
    public function saldoPorDeposito(?string $codigoLegado = null): array
    {
        $chave = 'ajuda_humanitaria:saldo_legado:' . ($codigoLegado ?? 'todos');
        $ttl   = (int) config('ajuda-humanitaria.saldo_cache_ttl', 300);

        return Cache::remember($chave, $ttl, function () use ($codigoLegado): array {
            try {
                $consulta = DB::connection($this->conexao())
                    ->table('aju_estoque as e')
                    ->join('aju_deposito as d', 'e.id_deposito', '=', 'd.id_deposito')
                    ->join('aju_unidade as u', 'e.id_produto', '=', 'u.id_unidade')
                    ->where('e.saldo', '<>', 0)
                    ->groupBy('d.id_deposito', 'd.nome', 'u.singular')
                    ->orderBy('d.nome')
                    ->select([
                        'd.nome as deposito',
                        'u.singular as material',
                        DB::raw('SUM(e.saldo) as saldo'),
                    ]);

                if ($codigoLegado !== null) {
                    $consulta->where('u.id_unidade', $codigoLegado);
                }

                return $consulta->get()
                    ->map(static fn (object $linha): array => [
                        'deposito' => (string) $linha->deposito,
                        'material' => (string) $linha->material,
                        'saldo'    => (int) $linha->saldo,
                    ])
                    ->all();
            } catch (Throwable $erro) {
                Log::warning('Saldo de material legado indisponivel.', [
                    'conexao' => $this->conexao(),
                    'erro'    => $erro->getMessage(),
                ]);

                return [];
            }
        });
    }

    public function disponivel(): bool
    {
        try {
            DB::connection($this->conexao())->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function conexao(): string
    {
        return (string) config('ajuda-humanitaria.legacy_connection', 'legacy');
    }
}
