<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

use Illuminate\Support\Carbon;

final readonly class LeituraCemadenDTO
{
    use SafeCastingTrait;

    public function __construct(
        public string $codigoEstacao,
        public Carbon $medidoEm,
        public ?float $acumulado24h,
    ) {
    }

    /**
     * O horario NAO vem por estacao: e o campo "atualizado" do bloco, global
     * para o snapshot inteiro, e avanca de ~10 em 10 minutos. Por isso ele
     * chega por parametro em vez de sair do registro.
     *
     * @param array<string, mixed> $dados
     */
    public static function fromFeedArray(array $dados, Carbon $medidoEm): ?self
    {
        $codigo = trim((string) ($dados['codestacao'] ?? ''));

        if ($codigo === '') {
            return null;
        }

        $acumulado = $dados['acumulado'] ?? null;

        return new self(
            codigoEstacao: $codigo,
            medidoEm: $medidoEm,
            // null significa estacao que nao transmitiu neste snapshot, e nao
            // chuva zero. Manter a distincao e o que permite a tela dizer
            // "sem telemetria" em vez de "sem chuva".
            acumulado24h: self::asFloatOrNull($acumulado),
        );
    }
}
