<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Support;

use App\Modules\Compdec\Models\Prefeitura;

/**
 * Aba ISS do PMDA alimentada pelo cadastro da Prefeitura (compdec_prefeituras),
 * #56: "Trazer fallback de dados da Prefeitura" e "Campos nao editaveis".
 *
 * O que a Prefeitura tem preenchido e a fonte de verdade: vem pronto na aba e
 * fica travado -- na tela e no servidor (aplicar() sobrescreve o que chegar).
 * Para mudar, atualiza-se o cadastro da Prefeitura. Campo vazio no cadastro
 * continua editavel no PMDA, senao o municipio ficaria sem como preencher.
 */
final class IssDaPrefeitura
{
    /** campo do PMDA => coluna de compdec_prefeituras */
    private const MAPA = [
        'nome_prefeito'    => 'prefeito_nome',
        'tel_prefeito'     => 'prefeito_telefone',
        'cel_prefeito'     => 'prefeito_celular',
        'tel_prefeitura'   => 'tel_prefeitura',
        'email_prefeitura' => 'email_prefeitura',
        'endereco'         => 'endereco',
        'bairro'           => 'bairro',
        'cep'              => 'cep',
        'cobra_iss'        => 'inss_tem_cobranca',
        'aliquota_iss'     => 'inss_aliquota',
        'num_lei_iss'      => 'inss_lei_cobranca',
        'resp_cob_iss'     => 'inss_responsavel',
    ];

    /** Tamanho maximo de cada campo no PMDA (UpdatePmdaPlanoRequest). */
    private const LIMITES = [
        'nome_prefeito' => 110, 'tel_prefeito' => 20, 'cel_prefeito' => 20, 'tel_prefeitura' => 20,
        'email_prefeitura' => 110, 'endereco' => 150, 'bairro' => 60, 'cep' => 10,
        'num_lei_iss' => 30, 'resp_cob_iss' => 30,
    ];

    /**
     * Campos do PMDA que a Prefeitura do municipio fornece (so os preenchidos).
     *
     * @return array<string, mixed>
     */
    public static function dados(?int $municipioId): array
    {
        if ($municipioId === null) {
            return [];
        }

        $prefeitura = Prefeitura::query()->where('municipio_id', $municipioId)->latest('id')->first();
        if ($prefeitura === null) {
            return [];
        }

        $dados = [];
        foreach (self::MAPA as $campo => $coluna) {
            $valor = $prefeitura->{$coluna};
            if ($valor === null || $valor === '') {
                continue;
            }
            $dados[$campo] = match ($campo) {
                'cobra_iss'    => (bool) $valor,
                'aliquota_iss' => (float) $valor,
                // Cadastro da Prefeitura aceita textos maiores que as colunas
                // do PMDA: corta no limite da validacao do plano.
                default        => mb_substr((string) $valor, 0, self::LIMITES[$campo] ?? 255),
            };
        }

        return $dados;
    }

    /** @return list<string> */
    public static function travados(?int $municipioId): array
    {
        return array_keys(self::dados($municipioId));
    }

    /**
     * Sobrepoe aos dados recebidos os campos travados pela Prefeitura.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public static function aplicar(?int $municipioId, array $dados): array
    {
        return array_merge($dados, self::dados($municipioId));
    }
}
