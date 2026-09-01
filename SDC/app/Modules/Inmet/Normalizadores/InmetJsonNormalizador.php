<?php

declare(strict_types=1);

namespace App\Modules\Inmet\Normalizadores;

use App\Modules\Inmet\DTOs\EstacaoDTO;
use App\Modules\Inmet\DTOs\LeituraMeteorologicaDTO;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Generator;

final class InmetJsonNormalizador implements NormalizadorSilver
{
    /** Campos que, todos nulos, indicam hora que ainda nao foi medida. */
    private const MEDICOES = ['CHUVA', 'TEM_INS', 'UMD_INS', 'VEN_VEL', 'PRE_INS'];

    public function normalizar(PayloadBruto $bruto): iterable
    {
        if (trim($bruto->conteudo) === '') {
            return;
        }

        $dados = json_decode($bruto->conteudo, true);

        if (! is_array($dados)) {
            return;
        }

        // Estacao antes de leitura de proposito: a dimensao precisa existir
        // antes do fato, porque a matview do mapa faz join com ela.
        yield from $this->estacoes($dados['estacoes'] ?? []);
        yield from $this->leituras($dados['leituras'] ?? []);
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @return Generator<EstacaoDTO>
     */
    private function estacoes(array $registros): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro)) {
                continue;
            }

            $dto = EstacaoDTO::fromInventarioArray($registro);

            // null significa coordenada ausente ou zerada. Descartar e melhor
            // que plotar no Golfo da Guine.
            if ($dto !== null && $dto->codigo !== '') {
                yield $dto;
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     * @return Generator<LeituraMeteorologicaDTO>
     */
    private function leituras(array $registros): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro) || ! $this->temMedicao($registro)) {
                continue;
            }

            yield LeituraMeteorologicaDTO::fromInmetArray($registro);
        }
    }

    /**
     * A API devolve as 24 horas do dia, com todos os valores nulos nas horas
     * que ainda nao aconteceram. Medido na fixture de A521: 9 das 24. Sem esta
     * guarda, o Silver receberia 9 linhas vazias por estacao por ciclo.
     *
     * @param array<string, mixed> $registro
     */
    private function temMedicao(array $registro): bool
    {
        foreach (self::MEDICOES as $campo) {
            $valor = $registro[$campo] ?? null;

            if ($valor !== null && $valor !== '') {
                return true;
            }
        }

        return false;
    }
}
