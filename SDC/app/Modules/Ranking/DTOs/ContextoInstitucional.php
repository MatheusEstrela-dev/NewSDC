<?php

declare(strict_types=1);

namespace App\Modules\Ranking\DTOs;

use InvalidArgumentException;

/**
 * Vinculo institucional do usuario NO INSTANTE do fato.
 *
 * Nao e o vinculo atual: e o que valia quando a acao aconteceu. Quem credita
 * pontos precisa saber a que orgao e a que municipio o usuario pertencia
 * naquele momento, e com que qualidade de prova esse vinculo foi reconstruido.
 *
 * TRES SITUACOES QUE NAO PODEM SER CONFUNDIDAS
 *
 * 1. `municipioId` nulo com evidencia `comprovado`: usuario de orgao estadual
 *    ou regional, sem municipio de origem aplicavel. O vinculo esta provado;
 *    simplesmente nao existe dimensao municipal para ele. Continua elegivel ao
 *    placar de usuario e de orgao.
 * 2. Evidencia `em_apuracao` com `orgaoId` nulo: nao foi possivel reconstruir
 *    vinculo algum na janela. Sem crédito presumido, e jamais preenchido com o
 *    orgao atual do usuario.
 * 3. Evidencia `inferido`: existe vinculo na janela, mas a data ou a origem do
 *    dado nao tem prova documental. Serve para auditoria e nao autoriza
 *    credito competitivo - so `comprovado()` autoriza.
 *
 * Sem orgao nao ha vinculo a afirmar: por isso `orgaoId` nulo so e aceito com
 * evidencia `em_apuracao`, e municipio sem orgao e sempre incoerente.
 */
final readonly class ContextoInstitucional
{
    public const EVIDENCIA_COMPROVADO = 'comprovado';

    public const EVIDENCIA_INFERIDO = 'inferido';

    public const EVIDENCIA_EM_APURACAO = 'em_apuracao';

    /** Mesmos valores aceitos pela coluna ranking.vinculos.evidencia. */
    public const EVIDENCIAS = [
        self::EVIDENCIA_COMPROVADO,
        self::EVIDENCIA_INFERIDO,
        self::EVIDENCIA_EM_APURACAO,
    ];

    public function __construct(
        public ?int $orgaoId,
        public ?int $municipioId,
        public string $evidencia,
    ) {
        if (! in_array($evidencia, self::EVIDENCIAS, true)) {
            throw new InvalidArgumentException(
                "Evidencia de vinculo desconhecida: '{$evidencia}'."
            );
        }

        if ($orgaoId !== null && $orgaoId <= 0) {
            throw new InvalidArgumentException('Identificador de orgao invalido.');
        }

        if ($municipioId !== null && $municipioId <= 0) {
            throw new InvalidArgumentException('Identificador de municipio invalido.');
        }

        // Afirmar vinculo comprovado ou inferido sem dizer a que orgao seria
        // uma prova sem objeto. A ausencia de orgao so tem uma leitura valida:
        // o vinculo nao foi reconstruido.
        if ($orgaoId === null && $evidencia !== self::EVIDENCIA_EM_APURACAO) {
            throw new InvalidArgumentException(
                'Contexto sem orgao so e valido com evidencia em apuracao.'
            );
        }

        // Municipio e dimensao derivada do orgao do vinculo. Sem orgao ele nao
        // tem de onde vir e creditaria um municipio sem lastro institucional.
        if ($municipioId !== null && $orgaoId === null) {
            throw new InvalidArgumentException(
                'Contexto com municipio exige o orgao do vinculo.'
            );
        }
    }

    /**
     * Nenhum vinculo reconstruivel na janela do fato.
     *
     * Construtor nomeado justamente para que o chamador nao seja tentado a
     * preencher orgao "provisorio" enquanto apura.
     */
    public static function emApuracao(): self
    {
        return new self(null, null, self::EVIDENCIA_EM_APURACAO);
    }

    /**
     * Contexto reconstruido a partir de um vinculo encontrado na janela.
     * A evidencia vem do vinculo e nunca e promovida pelo chamador.
     */
    public static function deVinculo(int $orgaoId, ?int $municipioId, string $evidencia): self
    {
        return new self($orgaoId, $municipioId, $evidencia);
    }

    /**
     * Unico estado que autoriza credito competitivo.
     *
     * Municipio nulo NAO derruba a comprovacao: ver situacao 1 no docblock.
     */
    public function comprovado(): bool
    {
        return $this->evidencia === self::EVIDENCIA_COMPROVADO;
    }

    /**
     * Fica fora da classificacao, mas o fato continua registrado no livro.
     */
    public function emApuracaoPendente(): bool
    {
        return $this->evidencia === self::EVIDENCIA_EM_APURACAO;
    }

    /**
     * Ha dimensao municipal a creditar neste contexto.
     */
    public function temMunicipio(): bool
    {
        return $this->municipioId !== null;
    }
}
