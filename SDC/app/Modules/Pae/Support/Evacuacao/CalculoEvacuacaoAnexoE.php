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

    public const SITUACAO_OK = 'ok';
    public const SITUACAO_VIA_INSUFICIENTE = 'via_insuficiente';
    public const SITUACAO_DENSIDADE_INVIAVEL = 'densidade_inviavel';
    public const MOTIVO_SETOR_SEM_TEMPO = 'setor_sem_tempo';
    public const MOTIVO_ESTRANGULAMENTO_ABAIXO_MINIMO = 'estrangulamento_abaixo_minimo';

    /** Coeficiente de estrangulamento aplicado ao numero de pessoas (Anexo E, item 4); distinto da largura minima de 1,2 m. */
    private const COEFICIENTE_ESTRANGULAMENTO = 1.2;
    private const DIVISOR_PLANO = 100;
    private const DIVISOR_RAMPA_ESCADA = 79;
    private const SEGUNDOS_POR_MINUTO = 60;
    /** Acrescimo de 30% em area comercial, em decimos: populacao * 13 / 10 arredondado para cima via (p * 13 + 9) div 10. */
    private const ACRESCIMO_COMERCIAL_NUMERADOR = 13;
    private const ACRESCIMO_COMERCIAL_DENOMINADOR = 10;

    private const EPSILON = 1e-6;
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

        $terfs = array_column($rotas, 'terf_segundos');
        $tes = array_column($acessos, 'te_segundos');
        // Rota sem tempo calculavel torna o tempo da area indeterminado, nunca subestimado.
        $tmd = $terfs === [] || in_array(null, $terfs, true) ? null : max($terfs);
        $te = $tes === [] ? null : max($tes);
        $tte = $tmd === null ? null : max($tmd, $te ?? 0.0);
        $declarado = $entrada['tte_declarado_segundos'] ?? null;

        $sinais = [
            'criterio1_conforme' => $this->todos($pontos, 'conforme'),
            'criterio2_conforme' => $this->todos($rotas, 'conforme'),
            'possui_rota_invalida' => in_array(true, array_column($rotas, 'invalida'), true),
            'possui_setor_inviavel' => in_array(true, array_map(fn (array $s): bool => $s['situacao'] !== self::SITUACAO_OK, $setores), true),
            'excede_declarado' => $declarado !== null && $tte !== null && $this->estritamenteMaior($tte, (float) $declarado),
        ];

        return [
            'setores' => $setores,
            'rotas' => $rotas,
            'acessos' => $acessos,
            'pontos_encontro' => $pontos,
            'tmd_segundos' => $tmd,
            'te_segundos' => $te,
            'tte_segundos' => $tte,
        ] + $sinais + ['conforme' => self::conforme($sinais)];
    }

    /**
     * Fonte unica da regra de conformidade: os dois criterios atendidos e nenhum sinal de alerta.
     */
    public static function conforme(array $sinais): bool
    {
        return (bool) $sinais['criterio1_conforme'] && (bool) $sinais['criterio2_conforme']
            && ! $sinais['possui_rota_invalida'] && ! $sinais['possui_setor_inviavel'] && ! $sinais['excede_declarado'];
    }

    private function setor(array $setor): array
    {
        // +30% em area comercial, arredondado para cima sem erro de ponto flutuante.
        $populacao = $setor['comercial'] ? intdiv($setor['populacao'] * self::ACRESCIMO_COMERCIAL_NUMERADOR + self::ACRESCIMO_COMERCIAL_DENOMINADOR - 1, self::ACRESCIMO_COMERCIAL_DENOMINADOR) : $setor['populacao'];
        $larguraUtil = $setor['via'] === 'calcada'
            ? $setor['largura'] * $setor['lados']
            : $setor['largura'] - self::DESCONTO_RUA[$setor['via']];

        if ($larguraUtil <= self::EPSILON) {
            return $this->setorSemTempo($populacao, null, self::SITUACAO_VIA_INSUFICIENTE);
        }

        $area = $larguraUtil * $setor['distancia'];
        $densidade = $populacao / $area;
        $velocidade = TabelaVelocidadeAnexoE::velocidade($densidade, $setor['terreno']);
        if ($velocidade === null) {
            return ['densidade' => $densidade, 'area' => $area] + $this->setorSemTempo($populacao, $larguraUtil, self::SITUACAO_DENSIDADE_INVIAVEL);
        }

        return [
            'populacao_efetiva' => $populacao,
            'largura_util' => $larguraUtil,
            'area' => $area,
            'densidade' => $densidade,
            'velocidade' => $velocidade,
            'tempo_segundos' => $setor['distancia'] / $velocidade,
            'situacao' => self::SITUACAO_OK,
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
        $divisor = $acesso['terreno'] === TabelaVelocidadeAnexoE::PLANO ? self::DIVISOR_PLANO : self::DIVISOR_RAMPA_ESCADA;

        return [
            'n' => $n,
            'te_segundos' => self::COEFICIENTE_ESTRANGULAMENTO * $n / ($divisor * $acesso['largura']) * self::SEGUNDOS_POR_MINUTO,
            'invalido' => $acesso['largura'] < self::LARGURA_MINIMA_ESTRANGULAMENTO - self::EPSILON,
        ];
    }

    private function rota(array $rota, array $setores, ?string $acessoId, ?array $acesso): array
    {
        $tempos = array_map(fn (string $id): ?float => $setores[$id]['tempo_segundos'], $rota['setores']);
        $terf = in_array(null, $tempos, true) ? null : array_sum($tempos);
        $motivo = match (true) {
            $terf === null => self::MOTIVO_SETOR_SEM_TEMPO,
            $acesso !== null && $acesso['invalido'] => self::MOTIVO_ESTRANGULAMENTO_ABAIXO_MINIMO,
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
            'conforme' => $motivo === null && $this->estritamenteMenor($saida, (float) $rota['chegada_onda_segundos']),
        ];
    }

    private function ponto(array $ponto): array
    {
        $densidade = $ponto['populacao'] / $ponto['area'];

        return ['densidade' => $densidade, 'conforme' => $this->estritamenteMenor($densidade, self::DENSIDADE_MAXIMA_PONTO)];
    }

    /** Menor que o limite legal; valores a menos de EPSILON do limite contam como iguais. */
    private function estritamenteMenor(float $valor, float $limite): bool
    {
        return $valor < $limite - self::EPSILON;
    }

    /** Maior que o limite legal; valores a menos de EPSILON do limite contam como iguais. */
    private function estritamenteMaior(float $valor, float $limite): bool
    {
        return $valor > $limite + self::EPSILON;
    }

    private function todos(array $itens, string $campo): bool
    {
        return $itens !== [] && ! in_array(false, array_column($itens, $campo), true);
    }
}
