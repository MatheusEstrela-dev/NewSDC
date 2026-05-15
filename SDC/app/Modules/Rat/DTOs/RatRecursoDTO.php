<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Rat\DTOs\RatAgenteDTO;

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
            recursoTipo:         $data['recurso_tipo']         ?? $data['tipo_recurso'] ?? null,
            recursoProblemas:    isset($data['recurso_problemas']) ? (bool) $data['recurso_problemas'] : null,
            recursoDescricao:    $data['recurso_descricao']    ?? $data['descricao'] ?? null,
            viaturaTipo:         $data['viatura_tipo']         ?? $data['categoria'] ?? null,
            viaturaPlaca:        $data['viatura_placa']        ?? $data['identificacao'] ?? null,
            viaturaPrefixo:      $data['viatura_prefixo']      ?? null,
            viaturaPadrao:       $data['viatura_padrao']       ?? null,
            viaturaOrgao:        $data['viatura_orgao']        ?? $data['orgao_responsavel'] ?? null,
            viaturaDescricao:    $data['viatura_descricao']    ?? $data['observacoes'] ?? null,
            viaturaSaida:        $data['viatura_saida']        ?? $data['data_saida'] ?? $data['data_hora_saida'] ?? null,
            viaturaChegada:      $data['viatura_chegada']      ?? $data['data_chegada'] ?? $data['data_hora_chegada'] ?? null,
            viaturaKm:           $data['viatura_km']           ?? $data['km_percorrido'] ?? null,
            viaturaLocalOrigem:  $data['viatura_local_origem']  ?? $data['local_origem'] ?? null,
            viaturaLocalDestino: $data['viatura_local_destino'] ?? $data['local_destino'] ?? null,
            viaturaQuantidade:   self::intOrNull($data['viatura_quantidade'] ?? $data['quantidade'] ?? null),
            viaturaCapacidade:   self::intOrNull($data['viatura_capacidade'] ?? $data['capacidade'] ?? null),
            viaturaCondicao:     $data['viatura_condicao']     ?? $data['condicao'] ?? null,
            viaturaOperador:     $data['viatura_operador']     ?? $data['condutor'] ?? $data['operador'] ?? null,
            operadorMasp:        $data['operador_masp']        ?? null,
            operadorIsCondutor:  isset($data['operador_is_condutor']) ? (bool) $data['operador_is_condutor'] : null,
            viaturaContato:      $data['viatura_contato']      ?? $data['contato_emergencia'] ?? null,
            agentes:             isset($data['agentes']) 
                                    ? array_map(fn($a) => RatAgenteDTO::fromArray($a), $data['agentes']) 
                                    : null,
        );
    }

    private static function intOrNull(mixed $v): ?int
    {
        return ($v !== null && $v !== '') ? (int) $v : null;
    }

    public function toArray(): array
    {
        // Mapeamento de valores do frontend para o banco de dados (ENUMs)
        $tipoMap = [
            'pessoal'    => 'pe',
            'viatura'    => 'viatura',
            'aeronave'   => 'aereo',
            'aereo'      => 'aereo',
            'embarcacao' => 'aquatico',
            'aquatico'   => 'aquatico',
            'equipamento'=> 'outro',
            'material'   => 'outro',
            'outro'      => 'outro',
        ];

        $condicaoMap = [
            'operacional'     => 'boa',
            'manutencao'      => 'ruim',
            'inoperante'      => 'inoperante',
            'otima'           => 'otima',
            'boa'             => 'boa',
            'regular'         => 'regular',
            'ruim'            => 'ruim'
        ];

        $data = [
            'seq'                   => $this->seq ?? 1, // Default sequence
            'recurso_tipo'          => $tipoMap[$this->recursoTipo] ?? $this->recursoTipo,
            'recurso_problemas'     => $this->recursoProblemas,
            'recurso_descricao'     => $this->recursoDescricao,
            'viatura_tipo'          => $this->viaturaTipo,
            'viatura_placa'         => $this->viaturaPlaca,
            'viatura_prefixo'       => $this->viaturaPrefixo,
            'viatura_padrao'        => $this->viaturaPadrao,
            'viatura_orgao'         => $this->viaturaOrgao,
            'viatura_descricao'     => $this->viaturaDescricao,
            'viatura_saida'         => $this->viaturaSaida,
            'viatura_chegada'       => $this->viaturaChegada,
            'viatura_km'            => $this->viaturaKm,
            'viatura_local_origem'  => $this->viaturaLocalOrigem,
            'viatura_local_destino' => $this->viaturaLocalDestino,
            'viatura_quantidade'    => $this->viaturaQuantidade,
            'viatura_capacidade'    => $this->viaturaCapacidade,
            'viatura_condicao'      => $condicaoMap[$this->viaturaCondicao] ?? $this->viaturaCondicao,
            'viatura_operador'      => $this->viaturaOperador,
            'operador_masp'         => $this->operadorMasp,
            'operador_is_condutor'  => $this->operadorIsCondutor,
            'viatura_contato'       => $this->viaturaContato,
        ];

        return array_filter($data, fn ($v) => $v !== null && $v !== '');
    }
}
