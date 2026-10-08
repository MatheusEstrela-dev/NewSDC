<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Memoria de calculo do Anexo E da Resolucao GMG 83/2024 e criterios do item 8
 * do Anexo B. Tempos em segundos. TTE = maior valor entre TMD e TE (decisao da
 * CEDEC: o texto do item 5.1 prevalece sobre a formula de soma).
 */
final class CalculoEvacuacaoAnexoE
{
    public const LARGURA_MINIMA_ESTRANGULAMENTO = 1.2;

    private const EPSILON = 1e-9;
    private const DESCONTO_RUA = ['rua_mao_unica' => 2.90, 'rua_mao_dupla' => 5.80];
    private const DENSIDADE_MAXIMA_PONTO = 3.0;

    public function calcular(array $entrada): array
    {
        $setores = [];
        foreach ($entrada['setores'] as $setor) {
            $setores[$setor['id']] = $this->setor($setor);
        }

        $rotasPorId = array_column($entrada['rotas'], null, 'id');
        $acessos = [];
        $acessoDaRota = [];
        foreach ($entrada['acessos'] as $acesso) {
            $acessos[$acesso['id']] = $this->acesso($acesso, $rotasPorId, $setores);
            foreach ($acesso['rotas'] as $rotaId) {
                $acessoDaRota[$rotaId] = $acesso['id'];
            }
        }

        $rotas = [];
        foreach ($entrada['rotas'] as $rota) {
            $acessoId = $acessoDaRota[$rota['id']] ?? null;
            $rotas[$rota['id']] = $this->rota($rota, $setores, $acessoId, $acessoId === null ? null : $acessos[$acessoId]);
        }

        $pontos = array_map(fn (array $ponto): array => $this->ponto($ponto), $entrada['pontos_encontro']);

        $terfs = array_values(array_filter(array_column($rotas, 'terf_segundos'), fn ($t): bool => $t !== null));
        $tes = array_column($acessos, 'te_segundos');
        $tmd = $terfs === [] ? null : max($terfs);
        $te = $tes === [] ? null : max($tes);
        $tte = $tmd === null ? null : max($tmd, $te ?? 0.0);
        $declarado = $entrada['tte_declarado_segundos'] ?? null;

        return [
            'setores' => $setores,
            'rotas' => $rotas,
            'acessos' => $acessos,
            'pontos_encontro' => $pontos,
            'tmd_segundos' => $tmd,
            'te_segundos' => $te,
            'tte_segundos' => $tte,
            'criterio1_conforme' => $this->todos($pontos, 'conforme'),
            'criterio2_conforme' => $this->todos($rotas, 'conforme'),
            'possui_rota_invalida' => in_array(true, array_column($rotas, 'invalida'), true),
            'possui_setor_inviavel' => in_array(true, array_map(fn (array $s): bool => $s['situacao'] !== 'ok', $setores), true),
            'excede_declarado' => $declarado !== null && $tte !== null && $tte > $declarado,
        ];
    }

    private function setor(array $setor): array
    {
        // +30% em area comercial, arredondado para cima sem erro de ponto flutuante.
        $populacao = $setor['comercial'] ? intdiv($setor['populacao'] * 13 + 9, 10) : $setor['populacao'];
        $larguraUtil = $setor['via'] === 'calcada'
            ? $setor['largura'] * $setor['lados']
            : $setor['largura'] - self::DESCONTO_RUA[$setor['via']];

        if ($larguraUtil <= self::EPSILON) {
            return $this->setorSemTempo($populacao, null, 'via_insuficiente');
        }

        $area = $larguraUtil * $setor['distancia'];
        $densidade = $populacao / $area;
        $velocidade = TabelaVelocidadeAnexoE::velocidade($densidade, $setor['terreno']);
        if ($velocidade === null) {
            return ['densidade' => $densidade, 'area' => $area] + $this->setorSemTempo($populacao, $larguraUtil, 'densidade_inviavel');
        }

        return [
            'populacao_efetiva' => $populacao,
            'largura_util' => $larguraUtil,
            'area' => $area,
            'densidade' => $densidade,
            'velocidade' => $velocidade,
            'tempo_segundos' => $setor['distancia'] / $velocidade,
            'situacao' => 'ok',
        ];
    }

    private function setorSemTempo(int $populacao, ?float $larguraUtil, string $situacao): array
    {
        return [
            'populacao_efetiva' => $populacao,
            'largura_util' => $larguraUtil,
            'area' => null,
            'densidade' => null,
            'velocidade' => null,
            'tempo_segundos' => null,
            'situacao' => $situacao,
        ];
    }

    private function acesso(array $acesso, array $rotasPorId, array $setores): array
    {
        $setoresDoAcesso = [];
        foreach ($acesso['rotas'] as $rotaId) {
            foreach ($rotasPorId[$rotaId]['setores'] as $setorId) {
                $setoresDoAcesso[$setorId] = true;
            }
        }
        $n = 0;
        foreach (array_keys($setoresDoAcesso) as $setorId) {
            $n += $setores[$setorId]['populacao_efetiva'];
        }
        $divisor = $acesso['terreno'] === TabelaVelocidadeAnexoE::PLANO ? 100 : 79;

        return [
            'n' => $n,
            'te_segundos' => 1.2 * $n / ($divisor * $acesso['largura']) * 60,
            'invalido' => $acesso['largura'] < self::LARGURA_MINIMA_ESTRANGULAMENTO - self::EPSILON,
        ];
    }

    private function rota(array $rota, array $setores, ?string $acessoId, ?array $acesso): array
    {
        $tempos = array_map(fn (string $id): ?float => $setores[$id]['tempo_segundos'], $rota['setores']);
        $terf = in_array(null, $tempos, true) ? null : array_sum($tempos);
        $motivo = match (true) {
            $terf === null => 'setor_sem_tempo',
            $acesso !== null && $acesso['invalido'] => 'estrangulamento_abaixo_minimo',
            default => null,
        };
        $saida = $terf === null ? null : max($terf, $acesso['te_segundos'] ?? 0.0);

        return [
            'terf_segundos' => $terf,
            'acesso' => $acessoId,
            'saida_segundos' => $saida,
            'chegada_onda_segundos' => $rota['chegada_onda_segundos'],
            'nivel_emergencia' => $rota['nivel_emergencia'],
            'invalida' => $motivo !== null,
            'motivo' => $motivo,
            'conforme' => $motivo === null && $saida < $rota['chegada_onda_segundos'],
        ];
    }

    private function ponto(array $ponto): array
    {
        $densidade = $ponto['populacao'] / $ponto['area'];

        return ['densidade' => $densidade, 'conforme' => $densidade < self::DENSIDADE_MAXIMA_PONTO];
    }

    private function todos(array $itens, string $campo): bool
    {
        return $itens !== [] && ! in_array(false, array_column($itens, $campo), true);
    }
}
