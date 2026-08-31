<?php

declare(strict_types=1);

namespace App\Modules\Tdap\DTOs;

use App\Modules\Tdap\Support\Documento;

/**
 * Payload de escrita do Prestador.
 *
 * CONTRATO: cnpj, tel1, tel2 e cep saem daqui como DIGITOS PUROS -- a mascara e
 * assunto da exibicao ({@see Documento}). O acervo legado guardava valores
 * mascarados e truncados, o que impedia buscar prestador por telefone.
 */
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
        $uf = self::nullable($data['uf'] ?? null);

        return new self(
            cnpj:          self::cleanDigits((string) ($data['cnpj'] ?? '')),
            nome:          trim((string) ($data['nome'] ?? '')),
            representante: self::nullable($data['representante'] ?? null),
            email:         mb_strtolower(trim((string) ($data['email'] ?? ''))),
            tel1:          Documento::digitos(self::nullable($data['tel1'] ?? null)),
            tel2:          Documento::digitos(self::nullable($data['tel2'] ?? null)),
            endereco:      self::nullable($data['endereco'] ?? null),
            bairro:        self::nullable($data['bairro'] ?? null),
            cidade:        self::nullable($data['cidade'] ?? null),
            // `uf` vazia virava string '' e o filtro por UF nunca casava.
            uf:            $uf === null ? null : mb_strtoupper($uf),
            cep:           Documento::digitos(self::nullable($data['cep'] ?? null)),
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
        return Documento::digitos($value) ?? '';
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
