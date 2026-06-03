<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

final readonly class PrestadorDTO
{
    public function __construct(
        public string $cnpj,
        public string $nome,
        public ?string $representante,
        public string $email,
        public ?string $tel1,
        public ?string $tel2,
        public ?string $endereco,
        public ?string $bairro,
        public ?string $cidade,
        public ?string $uf,
        public ?string $cep,
        public bool $ativo,
        public ?string $observacoes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            cnpj:          self::cleanDigits((string) ($data['cnpj'] ?? '')),
            nome:          (string) ($data['nome'] ?? ''),
            representante: self::nullable($data['representante'] ?? null),
            email:         mb_strtolower((string) ($data['email'] ?? '')),
            tel1:          self::cleanDigitsOrNull($data['tel1'] ?? null),
            tel2:          self::cleanDigitsOrNull($data['tel2'] ?? null),
            endereco:      self::nullable($data['endereco'] ?? null),
            bairro:        self::nullable($data['bairro'] ?? null),
            cidade:        self::nullable($data['cidade'] ?? null),
            uf:            isset($data['uf']) ? mb_strtoupper((string) $data['uf']) : null,
            cep:           self::cleanDigitsOrNull($data['cep'] ?? null),
            ativo:         (bool) ($data['ativo'] ?? true),
            observacoes:   self::nullable($data['observacoes'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cnpj'          => $this->cnpj,
            'nome'          => $this->nome,
            'representante' => $this->representante,
            'email'         => $this->email,
            'tel1'          => $this->tel1,
            'tel2'          => $this->tel2,
            'endereco'      => $this->endereco,
            'bairro'        => $this->bairro,
            'cidade'        => $this->cidade,
            'uf'            => $this->uf,
            'cep'           => $this->cep,
            'ativo'         => $this->ativo,
            'observacoes'   => $this->observacoes,
        ];
    }

    private static function cleanDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private static function cleanDigitsOrNull(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = self::cleanDigits($value);

        return $clean === '' ? null : $clean;
    }

    private static function nullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
