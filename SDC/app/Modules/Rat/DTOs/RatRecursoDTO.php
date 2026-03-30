<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

/**
 * DTO para dados de recursos (viaturas/pessoal) em uma ocorrência RAT.
 */
readonly class RatRecursoDTO
{
    public function __construct(
        public ?int    $id = null,
        public ?int    $seq = null,
        public ?string $recursoTipo = null,
        public ?bool   $recursoProblemas = null,
        public ?string $recursoDescricao = null,
        public ?string $viaturaTipo = null,
        public ?string $viaturaPlaca = null,
        public ?string $viaturaPrefixo = null,
        public ?string $viaturaPadrao = null,
        public ?string $viaturaOrgao = null,
        public ?string $viaturaDescricao = null,
        public ?string $viaturaSaida = null,
        public ?string $viaturaChegada = null,
        public ?string $viaturaKm = null,
        public ?string $viaturaLocalOrigem = null,
        public ?string $viaturaLocalDestino = null,
        public ?int    $viaturaQuantidade = null,
        public ?int    $viaturaCapacidade = null,
        public ?string $viaturaCondicao = null,
        public ?string $viaturaOperador = null,
        public ?string $operadorMasp = null,
        public ?bool   $operadorIsCondutor = null,
        public ?string $viaturaContato = null,
        
        /** @var RatAgenteDTO[]|null */
        public ?array  $agentes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:                  $data['id']                   ?? null,
            seq:                 $data['seq']                  ?? null,
            recursoTipo:         $data['recurso_tipo']         ?? null,
            recursoProblemas:    isset($data['recurso_problemas']) ? (bool) $data['recurso_problemas'] : null,
            recursoDescricao:    $data['recurso_descricao']    ?? null,
            viaturaTipo:         $data['viatura_tipo']         ?? null,
            viaturaPlaca:        $data['viatura_placa']        ?? null,
            viaturaPrefixo:      $data['viatura_prefixo']      ?? null,
            viaturaPadrao:       $data['viatura_padrao']       ?? null,
            viaturaOrgao:        $data['viatura_orgao']        ?? null,
            viaturaDescricao:    $data['viatura_descricao']    ?? null,
            viaturaSaida:        $data['viatura_saida']        ?? null,
            viaturaChegada:      $data['viatura_chegada']      ?? null,
            viaturaKm:           $data['viatura_km']           ?? null,
            viaturaLocalOrigem:  $data['viatura_local_origem']  ?? null,
            viaturaLocalDestino: $data['viatura_local_destino'] ?? null,
            viaturaQuantidade:   isset($data['viatura_quantidade']) ? (int) $data['viatura_quantidade'] : null,
            viaturaCapacidade:   isset($data['viatura_capacidade']) ? (int) $data['viatura_capacidade'] : null,
            viaturaCondicao:     $data['viatura_condicao']     ?? null,
            viaturaOperador:     $data['viatura_operador']     ?? null,
            operadorMasp:        $data['operador_masp']        ?? null,
            operadorIsCondutor:  isset($data['operador_is_condutor']) ? (bool) $data['operador_is_condutor'] : null,
            viaturaContato:      $data['viatura_contato']      ?? null,
            agentes:             isset($data['agentes']) 
                                    ? array_map(fn($a) => RatAgenteDTO::fromArray($a), $data['agentes']) 
                                    : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'seq'                  => $this->seq,
            'recurso_tipo'         => $this->recursoTipo,
            'recurso_problemas'    => $this->recursoProblemas,
            'recurso_descricao'    => $this->recursoDescricao,
            'viatura_tipo'         => $this->viaturaTipo,
            'viatura_placa'        => $this->viaturaPlaca,
            'viatura_prefixo'      => $this->viaturaPrefixo,
            'viatura_padrao'       => $this->viaturaPadrao,
            'viatura_orgao'        => $this->viaturaOrgao,
            'viatura_descricao'    => $this->viaturaDescricao,
            'viatura_saida'        => $this->viaturaSaida,
            'viatura_chegada'      => $this->viaturaChegada,
            'viatura_km'           => $this->viaturaKm,
            'viatura_local_origem'  => $this->viaturaLocalOrigem,
            'viatura_local_destino' => $this->viaturaLocalDestino,
            'viatura_quantidade'   => $this->viaturaQuantidade,
            'viatura_capacidade'   => $this->viaturaCapacidade,
            'viatura_condicao'     => $this->viaturaCondicao,
            'viatura_operador'     => $this->viaturaOperador,
            'operador_masp'        => $this->operadorMasp,
            'operador_is_condutor' => $this->operadorIsCondutor,
            'viatura_contato'      => $this->viaturaContato,
        ], fn ($v) => $v !== null);
    }
}
