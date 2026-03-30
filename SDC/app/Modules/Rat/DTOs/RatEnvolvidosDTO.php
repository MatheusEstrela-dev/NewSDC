<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use Illuminate\Support\Collection;

/**
 * RatEnvolvidosDTO - DTO para Envolvidos
 */
class RatEnvolvidosDTO
{
    public function __construct(
        public readonly string $nome_completo,
        public readonly ?string $cpf = null,
        public readonly ?\DateTime $data_nascimento = null,
        public readonly ?string $escolaridade = null,
        public readonly ?string $profissao = null,
        public readonly ?string $sexo = null,
        public readonly ?string $estado_civil = null,
        public readonly ?string $rg = null,
        public readonly ?string $fone = null,
        public readonly ?string $email = null,
        public readonly ?string $cnaes = null,
        public readonly string $endereco = '',
        public readonly ?string $numero = null,
        public readonly ?string $complemento = null,
        public readonly string $bairro = '',
        public readonly string $cidade = '',
        public readonly string $uf = '',
        public readonly ?string $cep = null,
        public readonly ?string $telefone_comercial = null,
        public readonly ?string $telefone_referencia = null,
        public readonly string $tipo_envolvimento = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nome_completo: $data['nome_completo'] ?? '',
            cpf: $data['cpf'] ?? null,
            data_nascimento: isset($data['data_nascimento']) ? new \DateTime($data['data_nascimento']) : null,
            escolaridade: $data['escolaridade'] ?? null,
            profissao: $data['profissao'] ?? null,
            sexo: $data['sexo'] ?? null,
            estado_civil: $data['estado_civil'] ?? null,
            rg: $data['rg'] ?? null,
            fone: $data['fone'] ?? null,
            email: $data['email'] ?? null,
            cnaes: $data['cnaes'] ?? null,
            endereco: $data['endereco'] ?? '',
            numero: $data['numero'] ?? null,
            complemento: $data['complemento'] ?? null,
            bairro: $data['bairro'] ?? '',
            cidade: $data['cidade'] ?? '',
            uf: $data['uf'] ?? '',
            cep: $data['cep'] ?? null,
            telefone_comercial: $data['telefone_comercial'] ?? null,
            telefone_referencia: $data['telefone_referencia'] ?? null,
            tipo_envolvimento: $data['tipo_envolvimento'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'nome_completo' => $this->nome_completo,
            'cpf' => $this->cpf,
            'data_nascimento' => $this->data_nascimento?->format('Y-m-d'),
            'escolaridade' => $this->escolaridade,
            'profissao' => $this->profissao,
            'sexo' => $this->sexo,
            'estado_civil' => $this->estado_civil,
            'rg' => $this->rg,
            'fone' => $this->fone,
            'email' => $this->email,
            'cnaes' => $this->cnaes,
            'endereco' => $this->endereco,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'uf' => $this->uf,
            'cep' => $this->cep,
            'telefone_comercial' => $this->telefone_comercial,
            'telefone_referencia' => $this->telefone_referencia,
            'tipo_envolvimento' => $this->tipo_envolvimento,
        ];
    }
}
