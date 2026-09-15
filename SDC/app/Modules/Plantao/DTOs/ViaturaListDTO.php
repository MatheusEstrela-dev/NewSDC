<?php

declare(strict_types=1);

namespace App\Modules\Plantao\DTOs;

use App\Modules\Plantao\Models\Viatura;

class ViaturaListDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $prefixo,
        public readonly string $placa,
        public readonly string $modelo,
        public readonly ?string $marca,
        public readonly string $localizacao,
        public readonly string $localizacao_valor,
        public readonly bool $exclusiva_sobreaviso,
        public readonly string $status,
        public readonly string $status_valor,
        public readonly ?int $hodometro,
        public readonly ?string $combustivel_label,
        public readonly ?string $combustivel_valor,
        public readonly int $combustivel_percentual,
        public readonly ?string $ultimo_condutor_nome,
        public readonly bool $ativo,
        public readonly ?string $observacoes,
        public readonly ?int $movimentacao_aberta_id,
        // Estado de EXIBICAO. Difere de `status_valor` (o estado fisico gravado
        // no banco) num caso: viatura DISPONIVEL com reserva agendada aparece
        // como RESERVADA. A tela da frota nao pode oferece-la como livre.
        public readonly string $status_exibicao,
        public readonly string $status_exibicao_valor,
        public readonly bool $reservada,
        public readonly ?int $reserva_id,
        public readonly ?string $reserva_agente_nome,
        public readonly ?string $reserva_inicio,
        public readonly ?string $reserva_fim,
    ) {
    }

    public static function fromModel(Viatura $viatura): self
    {
        $reserva = $viatura->reservaAgendada;

        // Reserva agendada esconde o DISPONIVEL, e apenas ele: viatura em
        // manutencao ou em transito continua mostrando o estado fisico, que e
        // mais forte que a intencao de uso.
        $reservada = $reserva !== null
            && $viatura->status === \App\Modules\Plantao\Enums\StatusViatura::DISPONIVEL;

        return new self(
            id: $viatura->id,
            prefixo: $viatura->prefixo,
            placa: $viatura->placa,
            modelo: $viatura->modelo,
            marca: $viatura->marca,
            localizacao: $viatura->localizacao?->label() ?? '',
            localizacao_valor: $viatura->localizacao?->value ?? '',
            exclusiva_sobreaviso: (bool) $viatura->exclusiva_sobreaviso,
            status: $viatura->status?->label() ?? '',
            status_valor: $viatura->status?->value ?? '',
            hodometro: $viatura->hodometro_atual,
            combustivel_label: $viatura->nivel_combustivel?->label(),
            combustivel_valor: $viatura->nivel_combustivel?->value,
            combustivel_percentual: $viatura->nivel_combustivel?->percentual() ?? 0,
            ultimo_condutor_nome: $viatura->ultimo_condutor_nome,
            ativo: (bool) $viatura->ativo,
            observacoes: $viatura->observacoes,
            movimentacao_aberta_id: $viatura->movimentacaoAberta?->id,
            status_exibicao: $reservada ? 'Reservada' : ($viatura->status?->label() ?? ''),
            status_exibicao_valor: $reservada ? 'RESERVADA' : ($viatura->status?->value ?? ''),
            reservada: $reservada,
            reserva_id: $reserva?->id,
            reserva_agente_nome: $reserva?->agente_nome,
            reserva_inicio: $reserva?->inicio_previsto?->toIso8601String(),
            reserva_fim: $reserva?->fim_previsto?->toIso8601String(),
        );
    }

    public static function collection(iterable $items): array
    {
        return array_map(
            fn(Viatura $item) => self::fromModel($item),
            is_array($items) ? $items : iterator_to_array($items)
        );
    }
}
