<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Enums;

/**
 * Situacao do ciclo de vida de um curso EAD, ortogonal a publicacao e ao
 * StatusTreinamento (que e do fluxo de eventos). Pausar e desativar nao mexem
 * em publicado_em: ao voltar para ATIVO o curso reaparece como estava.
 *
 * ATIVO       funcionamento normal.
 * PAUSADO     suspensao temporaria: sala fechada e sem novas inscricoes, mas
 *             o curso segue no catalogo, marcado como pausado.
 * DESATIVADO  encerrado: some do catalogo e a sala fecha; certificados seguem
 *             validos.
 */
enum SituacaoCurso: string
{
    case ATIVO = 'ATIVO';
    case PAUSADO = 'PAUSADO';
    case DESATIVADO = 'DESATIVADO';

    public function getLabel(): string
    {
        return match ($this) {
            self::ATIVO => 'Ativo',
            self::PAUSADO => 'Pausado',
            self::DESATIVADO => 'Desativado',
        };
    }

    /** Variante do atomo Badge. */
    public function getVariant(): string
    {
        return match ($this) {
            self::ATIVO => 'success',
            self::PAUSADO => 'warning',
            self::DESATIVADO => 'neutral',
        };
    }

    /** Ponto unico: o aluno pode entrar na sala e usar o conteudo. */
    public function permiteAcessoDoAluno(): bool
    {
        return $this === self::ATIVO;
    }

    public function recebeInscricao(): bool
    {
        return $this === self::ATIVO;
    }

    /** Curso listado no catalogo (interno e portal). */
    public function apareceNoCatalogo(): bool
    {
        return $this !== self::DESATIVADO;
    }

    /** Conclusao e emissao de certificado so acontecem com o curso ativo. */
    public function permiteConclusao(): bool
    {
        return $this === self::ATIVO;
    }

    /** Mensagem para o aluno barrado; null quando o acesso esta liberado. */
    public function mensagemDeBloqueio(): ?string
    {
        return match ($this) {
            self::ATIVO => null,
            self::PAUSADO => 'Curso pausado pelo professor.',
            self::DESATIVADO => 'Curso encerrado.',
        };
    }

    /**
     * @return list<string> valores que aparecem no catalogo
     */
    public static function valoresNoCatalogo(): array
    {
        return array_map(
            fn (self $s) => $s->value,
            array_values(array_filter(self::cases(), fn (self $s) => $s->apareceNoCatalogo())),
        );
    }
}
