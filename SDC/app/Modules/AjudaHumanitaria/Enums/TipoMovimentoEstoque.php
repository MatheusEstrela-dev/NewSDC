<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Enums;

/**
 * Tipos de lancamento do ledger de estoque.
 *
 * O ledger guarda o tipo como texto, e nao como enum no cast do model, de
 * proposito: a tela de extrato precisa exibir lancamento antigo mesmo que o
 * vocabulario mude, e um cast estrito quebraria a listagem inteira por causa
 * de uma linha desconhecida. Este enum e a tabela de traducao, com fallback.
 *
 * TRANSF_SAIDA, TRANSF_ENTRADA e LIBERACAO estao declarados porque o dominio
 * ja os preve, mas nenhum e emitido ainda: as telas de transferencia e
 * liberacao seguem somente leitura.
 */
enum TipoMovimentoEstoque: string
{
    /** Saldo consolidado que veio do gestaocedec, um por par material/deposito. */
    case ABERTURA = 'ABERTURA';

    case ENTRADA = 'ENTRADA';

    case ESTORNO_ENTRADA = 'ESTORNO_ENTRADA';

    case TRANSF_SAIDA = 'TRANSF_SAIDA';

    case TRANSF_ENTRADA = 'TRANSF_ENTRADA';

    case LIBERACAO = 'LIBERACAO';

    public function label(): string
    {
        return match ($this) {
            self::ABERTURA        => 'Abertura',
            self::ENTRADA         => 'Entrada',
            self::ESTORNO_ENTRADA => 'Estorno de entrada',
            self::TRANSF_SAIDA    => 'Transferência (saída)',
            self::TRANSF_ENTRADA  => 'Transferência (entrada)',
            self::LIBERACAO       => 'Liberação',
        };
    }

    /**
     * Cor do Badge. Segue o sinal do movimento, nao o nome: quem le o extrato
     * quer distinguir o que entrou do que saiu antes de ler o rotulo.
     */
    public function cor(): string
    {
        return match ($this) {
            self::ABERTURA                        => 'info',
            self::ENTRADA, self::TRANSF_ENTRADA   => 'success',
            self::ESTORNO_ENTRADA, self::LIBERACAO, self::TRANSF_SAIDA => 'warning',
        };
    }

    /**
     * Rotulo de um tipo qualquer, inclusive o que nao esta neste enum.
     *
     * Lancamento com tipo desconhecido aparece com o texto cru em vez de
     * sumir da tela ou derrubar a listagem.
     */
    public static function rotuloDe(?string $tipo): string
    {
        if ($tipo === null || $tipo === '') {
            return '—';
        }

        return self::tryFrom($tipo)?->label() ?? $tipo;
    }

    public static function corDe(?string $tipo): string
    {
        return ($tipo !== null ? self::tryFrom($tipo)?->cor() : null) ?? 'neutral';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }
}
