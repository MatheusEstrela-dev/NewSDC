<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Enums;

/**
 * Os 13 itens conferidos na instalacao. No legado apareciam como colunas
 * booleanas, de quantidade e de foto repetidas nas tres tabelas de
 * relatorio, com nomes divergentes entre elas (calha_metros no COMPDEC,
 * qtd_calha no fornecedor, calha_opcao tambem no fornecedor).
 */
enum ItemInstalacao: string
{
    case CISTERNA_LOGO = 'cisterna_logo';
    case SUCAO = 'sucao';
    case BOMBA = 'bomba';
    case PLACA = 'placa';
    case CALHA = 'calha';
    case TUBULACAO = 'tubulacao';
    case FIXACAO = 'fixacao';
    case FILTRO = 'filtro';
    case BLOCO = 'bloco';
    case TE_PVC = 'te_pvc';
    case JOELHO_PVC = 'joelho_pvc';
    case LUVA_PVC = 'luva_pvc';
    case CAP_PVC = 'cap_pvc';

    public function label(): string
    {
        return match ($this) {
            self::CISTERNA_LOGO => 'Cisterna com logo',
            self::SUCAO => 'Sucao',
            self::BOMBA => 'Bomba',
            self::PLACA => 'Placa',
            self::CALHA => 'Calha',
            self::TUBULACAO => 'Tubulacao',
            self::FIXACAO => 'Fixacao',
            self::FILTRO => 'Filtro',
            self::BLOCO => 'Bloco',
            self::TE_PVC => 'Te PVC',
            self::JOELHO_PVC => 'Joelho PVC',
            self::LUVA_PVC => 'Luva PVC',
            self::CAP_PVC => 'Cap PVC',
        };
    }

    /**
     * Calha e tubulacao sao medidas em metros; as pecas de PVC em unidades;
     * os demais itens sao apenas conferidos, sem quantidade.
     */
    public function unidadePadrao(): ?UnidadeItem
    {
        return match ($this) {
            self::CALHA, self::TUBULACAO => UnidadeItem::M,
            self::TE_PVC, self::JOELHO_PVC, self::LUVA_PVC, self::CAP_PVC => UnidadeItem::UN,
            default => null,
        };
    }

    /**
     * Somente fixacao tem subquantidades (abracadeira, bucha, parafuso), que
     * vao na coluna `detalhes jsonb` — ver spec secao 4.6.
     */
    public function aceitaDetalhes(): bool
    {
        return $this === self::FIXACAO;
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $c): string => $c->value, self::cases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    /**
     * @return array<int, array{value: string, label: string, unidade: ?string, unidade_rotulo: ?string, aceita_detalhes: bool}>
     */
    public static function options(): array
    {
        // `unidade` e `aceita_detalhes` saem daqui, e nao de uma tabela paralela
        // no Vue: a tela do checklist precisa saber quais dos 13 itens tem
        // quantidade (so calha e tubulacao em metro, e as quatro pecas de PVC em
        // unidade) e qual aceita subquantidade (so fixacao: abracadeira, bucha e
        // parafuso). Duplicar isso no front faria as duas listas divergirem na
        // primeira mudanca, e o resultado seria campo de quantidade em item que
        // nao tem -- ou campo faltando onde tem.
        return array_map(
            fn (self $c): array => [
                'value' => $c->value,
                'label' => $c->label(),
                'unidade' => $c->unidadePadrao()?->value,
                'unidade_rotulo' => $c->unidadePadrao()?->label(),
                'aceita_detalhes' => $c->aceitaDetalhes(),
            ],
            self::cases(),
        );
    }
}
