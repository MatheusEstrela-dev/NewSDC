<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\TipoDecreto;

/**
 * Dados de abertura e edicao do pedido (RN-04, RN-05, RN-06).
 *
 * Nao carrega numero nem ano: sao atribuidos pelo NumeracaoPedidoService.
 * Nao carrega status: quem o altera e o TramitacaoService.
 */
final readonly class PedidoAhDTO
{
    public function __construct(
        public int $municipioId,
        public int $popAtendida,
        public string $esforcosRealizados,
        public ?int $cobradeId = null,
        public bool $decretoSeEcpVigente = false,
        public ?TipoDecreto $tipoDecreto = null,
        public ?string $numeroDecreto = null,
        public ?string $vigenciaDecreto = null,
        public ?string $nomeCoordenador = null,
        public ?string $telCoordenador = null,
        public ?string $celCoordenador = null,
        public ?string $emailCoordenador = null,
        public ?string $nomePrefeito = null,
        public ?string $telPrefeito = null,
        public ?string $celPrefeito = null,
        public ?string $emailPrefeito = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $tipo = self::texto($data['tipo_decreto'] ?? null);

        return new self(
            municipioId:         (int) ($data['municipio_id'] ?? 0),
            popAtendida:         (int) ($data['pop_atendida'] ?? 0),
            esforcosRealizados:  (string) ($data['esforcos_realizados'] ?? ''),
            cobradeId:           isset($data['cobrade_id']) ? (int) $data['cobrade_id'] : null,
            decretoSeEcpVigente: (bool) ($data['decreto_se_ecp_vig'] ?? false),
            tipoDecreto:         $tipo !== null ? TipoDecreto::from($tipo) : null,
            numeroDecreto:       self::texto($data['numero_decreto'] ?? null),
            vigenciaDecreto:     self::texto($data['vigencia_decreto'] ?? null),
            nomeCoordenador:     self::texto($data['nome_coordenador'] ?? null),
            telCoordenador:      self::texto($data['tel_coordenador'] ?? null),
            celCoordenador:      self::texto($data['cel_coordenador'] ?? null),
            emailCoordenador:    self::texto($data['email_coordenador'] ?? null),
            nomePrefeito:        self::texto($data['nome_prefeito'] ?? null),
            telPrefeito:         self::texto($data['tel_prefeito'] ?? null),
            celPrefeito:         self::texto($data['cel_prefeito'] ?? null),
            emailPrefeito:       self::texto($data['email_prefeito'] ?? null),
        );
    }

    /**
     * Colunas de pedidos_ah correspondentes. Numero, ano e status ficam a cargo
     * de quem cria ou tramita.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'municipio_id'         => $this->municipioId,
            'pop_atendida'         => $this->popAtendida,
            'esforcos_realizados'  => $this->esforcosRealizados,
            'cobrade_id'           => $this->cobradeId,
            'decreto_se_ecp_vig'   => $this->decretoSeEcpVigente,
            'tipo_decreto'         => $this->tipoDecreto?->value,
            'numero_decreto'       => $this->numeroDecreto,
            'vigencia_decreto'     => $this->vigenciaDecreto,
            'nome_coordenador'     => $this->nomeCoordenador,
            'tel_coordenador'      => $this->telCoordenador,
            'cel_coordenador'      => $this->celCoordenador,
            'email_coordenador'    => $this->emailCoordenador,
            'nome_prefeito'        => $this->nomePrefeito,
            'tel_prefeito'         => $this->telPrefeito,
            'cel_prefeito'         => $this->celPrefeito,
            'email_prefeito'       => $this->emailPrefeito,
        ];
    }

    private static function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
