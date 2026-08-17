<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Escrita no cisterna_etl_log. Registra as quatro acoes, nao apenas as
 * falhas: `skipped` por idempotencia e `updated` por reprocesso sao o que
 * permite auditar uma carga de milhares de linhas.
 */
final class RegistroEtl
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function inserido(string $recurso, string $tabela, int $legacyId, int $newId, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'inserted', $newId, null, $payload);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function atualizado(string $recurso, string $tabela, int $legacyId, int $newId, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'updated', $newId, null, $payload);
    }

    public static function ignorado(string $recurso, string $tabela, int $legacyId, string $motivo, ?int $newId = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'skipped', $newId, $motivo, null);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public static function erro(string $recurso, string $tabela, int $legacyId, string $motivo, ?array $payload = null): void
    {
        self::gravar($recurso, $tabela, $legacyId, 'error', null, $motivo, $payload);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private static function gravar(
        string $recurso,
        string $tabela,
        int $legacyId,
        string $acao,
        ?int $newId,
        ?string $motivo,
        ?array $payload,
    ): void {
        DB::table('cisterna_etl_log')->insert([
            'recurso' => $recurso,
            'tabela' => $tabela,
            'pk_legado' => (string) $legacyId,
            'new_id' => $newId,
            'acao' => $acao,
            'motivo' => $motivo,
            'payload_legado' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, int> acao => quantidade
     */
    public static function resumo(?string $recurso = null): array
    {
        return DB::table('cisterna_etl_log')
            ->when($recurso !== null, fn ($q) => $q->where('recurso', $recurso))
            ->selectRaw('acao, COUNT(*) AS total')
            ->groupBy('acao')
            ->pluck('total', 'acao')
            ->map(fn ($t): int => (int) $t)
            ->all();
    }
}
