<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Http;

use App\Modules\PowerBi\Support\DefinicaoTabela;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;

/** Entrada da API de leitura: valida antes de reservar uma conexao de BI. */
final class ParametrosConsultaBi
{
    private const MAX_CURSOR_BYTES = 8192;

    /**
     * @param list<string>|null $apos
     * @param array<string, string> $filtros
     */
    private function __construct(
        public readonly ?string $desde,
        public readonly ?array $apos,
        public readonly int $limite,
        public readonly array $filtros,
    ) {
    }

    /** @param array<string, mixed> $entrada */
    public static function de(array $entrada, DefinicaoTabela $d): self
    {
        $desde = $entrada['desde'] ?? null;
        $desde = $desde === null || $desde === '' ? null : self::data($desde, 'desde');
        $apos = self::cursor($entrada['cursor'] ?? null, count($d->pk));

        $limite = $entrada['limite'] ?? config('powerbi.api.limite_padrao', 1000);
        if ((! is_string($limite) && ! is_int($limite))
            || preg_match('/^[0-9]+$/D', (string) $limite) !== 1
            || trim((string) $limite, '0') === '') {
            throw ValidationException::withMessages(['limite' => 'Informe um numero inteiro positivo.']);
        }
        $limite = min((int) $limite, max(1, (int) config('powerbi.api.limite_maximo', 10000)));

        $filtros = [];
        foreach ($d->filtros as $coluna) {
            $valor = $entrada[$coluna] ?? null;
            if ($valor !== null && $valor !== '') {
                $filtros[$coluna] = self::escalar($valor, $coluna);
            }
        }

        return new self($desde, $apos, $limite, $filtros);
    }

    /** @return list<string>|null */
    private static function cursor(mixed $cursor, int $partesPk): ?array
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }
        if (! is_string($cursor) || strlen($cursor) > self::MAX_CURSOR_BYTES
            || preg_match('/^[A-Za-z0-9_+\/-]+={0,2}$/D', $cursor) !== 1) {
            throw ValidationException::withMessages(['cursor' => 'Cursor invalido.']);
        }

        try {
            $json = base64_decode(strtr($cursor, '-_', '+/'), true);
            $partes = $json === false ? null : json_decode($json, true, 4, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            $partes = null;
        }
        if (! is_array($partes) || ! array_is_list($partes) || count($partes) !== $partesPk + 1) {
            throw ValidationException::withMessages(['cursor' => 'Cursor invalido.']);
        }

        $data = self::data(array_shift($partes), 'cursor', true);

        return [$data, ...array_map(fn (mixed $p): string => self::escalar($p, 'cursor'), $partes)];
    }

    private static function escalar(mixed $valor, string $campo): string
    {
        if ((! is_string($valor) && ! is_int($valor)) || str_contains((string) $valor, "\0")
            || ! mb_check_encoding((string) $valor, 'UTF-8')) {
            throw ValidationException::withMessages([$campo => 'Informe um valor textual ou inteiro valido.']);
        }

        return (string) $valor;
    }

    private static function data(mixed $valor, string $campo, bool $exigirHora = false): string
    {
        $formato = '/^(\d{4})-(\d{2})-(\d{2})(?:[T ]([01]\d|2[0-3]):([0-5]\d):([0-5]\d)(?:\.\d{1,6})?(?:Z|[+-](?:0\d|1[0-5])(?::?[0-5]\d)?)?)?$/D';
        if (! is_string($valor) || preg_match($formato, $valor, $partes) !== 1
            || ! checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])
            || ($exigirHora && ! isset($partes[4]))) {
            throw ValidationException::withMessages([$campo => 'Informe uma data ISO valida, sem expressoes relativas.']);
        }

        try {
            // String evita o binding DateTime do Laravel, que remove fracao e fuso.
            return Carbon::parse($valor)->format('Y-m-d H:i:s.uP');
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([$campo => 'Informe uma data valida.']);
        }
    }
}
