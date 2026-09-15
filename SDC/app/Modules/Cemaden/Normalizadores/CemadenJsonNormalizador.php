<?php

declare(strict_types=1);

namespace App\Modules\Cemaden\Normalizadores;

use App\Modules\Cemaden\DTOs\EstacaoCemadenDTO;
use App\Modules\Cemaden\DTOs\LeituraCemadenDTO;
use App\Modules\Medalhao\Contracts\NormalizadorSilver;
use App\Modules\Medalhao\DTOs\PayloadBruto;
use Carbon\Exceptions\InvalidFormatException;
use Generator;
use Illuminate\Support\Carbon;

final class CemadenJsonNormalizador implements NormalizadorSilver
{
    public function normalizar(PayloadBruto $bruto): iterable
    {
        if (trim($bruto->conteudo) === '') {
            return;
        }

        $dados = json_decode($bruto->conteudo, true);

        if (! is_array($dados)) {
            return;
        }

        $medidoEm = $this->instante($dados['atualizado'] ?? null);
        $estacoes = is_array($dados['estacoes'] ?? null) ? $dados['estacoes'] : [];

        // Dimensao antes do fato, mesma razao do INMET: a matview do mapa faz
        // join com a estacao, e fato sem dimensao nao aparece na tela.
        yield from $this->estacoes($estacoes);

        // Sem o instante global nao existe chave natural para a leitura, e
        // inventar now() gravaria a hora do worker como se fosse a hora da
        // medicao. Melhor entregar so a dimensao neste ciclo.
        if ($medidoEm !== null) {
            yield from $this->leituras($estacoes, $medidoEm);
        }
    }

    /**
     * @param array<int, mixed> $registros
     * @return Generator<EstacaoCemadenDTO>
     */
    private function estacoes(array $registros): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro)) {
                continue;
            }

            $dto = EstacaoCemadenDTO::fromFeedArray($registro);

            if ($dto !== null) {
                yield $dto;
            }
        }
    }

    /**
     * @param array<int, mixed> $registros
     * @return Generator<LeituraCemadenDTO>
     */
    private function leituras(array $registros, Carbon $medidoEm): Generator
    {
        foreach ($registros as $registro) {
            if (! is_array($registro)) {
                continue;
            }

            $dto = LeituraCemadenDTO::fromFeedArray($registro, $medidoEm);

            if ($dto !== null) {
                yield $dto;
            }
        }
    }

    /**
     * O feed publica "2026-09-02 19:26:40 UTC". O sufixo textual e explicito,
     * entao o parse fixa UTC em vez de confiar no timezone do processo — errar
     * isso deslocaria a serie inteira em 3 horas.
     */
    private function instante(mixed $valor): ?Carbon
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim(str_ireplace('UTC', '', $valor)), 'UTC');
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
