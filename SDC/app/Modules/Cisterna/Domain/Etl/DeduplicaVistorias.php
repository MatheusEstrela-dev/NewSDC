<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl;

use Illuminate\Support\Facades\DB;

/**
 * Escolhe uma linha por beneficiario nas tabelas de relatorio do legado.
 *
 * Producao tem 856 relatorios de fornecedor para 791 beneficiarios e 675 de
 * CEDEC para 658 — o excedente e reenvio do mesmo formulario, que o legado
 * nao prevenia (spec 4.6.6).
 *
 * Critério: a linha com mais campos preenchidos vence; `id` maior desempata,
 * por ser a submissao mais recente.
 */
final class DeduplicaVistorias
{
    /**
     * legacy_ids que devem ser refinados, por tabela.
     *
     * @return array<int, int>
     */
    public function vencedores(string $tabela, string $colunaBeneficiario): array
    {
        $linhas = DB::table('cisterna_legado_raw')
            ->where('tabela', $tabela)
            ->get(['pk_legado', 'doc']);

        $porBeneficiario = [];

        foreach ($linhas as $linha) {
            $doc = json_decode((string) $linha->doc, true);

            if (! is_array($doc)) {
                continue;
            }

            $beneficiario = (int) ($doc[$colunaBeneficiario] ?? 0);

            if ($beneficiario === 0) {
                // Sem beneficiario: deixa passar para o refinador registrar o erro.
                $porBeneficiario['orfao_'.$linha->pk_legado] = [
                    'legacy_id' => (int) $linha->pk_legado,
                    'peso' => 0,
                ];

                continue;
            }

            $peso = $this->completude($doc);
            $atual = $porBeneficiario[$beneficiario] ?? null;

            $vence = $atual === null
                || $peso > $atual['peso']
                || ($peso === $atual['peso'] && (int) $linha->pk_legado > $atual['legacy_id']);

            if ($vence) {
                $porBeneficiario[$beneficiario] = [
                    'legacy_id' => (int) $linha->pk_legado,
                    'peso' => $peso,
                ];
            }
        }

        return array_map(fn (array $v): int => $v['legacy_id'], array_values($porBeneficiario));
    }

    /**
     * Quantidade de campos com valor util. Trata '0' e '0000-00-00' como
     * vazios, porque o legado grava os dois no lugar de NULL.
     *
     * @param  array<string, mixed>  $doc
     */
    private function completude(array $doc): int
    {
        $peso = 0;

        foreach ($doc as $valor) {
            if ($valor === null) {
                continue;
            }

            $texto = trim((string) $valor);

            if ($texto === '' || $texto === '0' || str_starts_with($texto, '0000-00-00')) {
                continue;
            }

            $peso++;
        }

        return $peso;
    }
}
