<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Support\NormalizaEntrada;

final readonly class VistoriaDTO
{
    /**
     * @param  array<int, ItemConferidoDTO>  $itens
     */
    public function __construct(
        public int $beneficiarioId,
        public EtapaVistoria $etapa,
        public ?int $numeroInstalacao = null,
        public ?string $engenheiroNome = null,
        public ?string $engenheiroCrea = null,
        public ?string $engenheiroArt = null,
        public ?string $dataRelatorio = null,
        public ?string $localRelatorio = null,
        public ?string $processoSei = null,
        public ?string $contrato = null,
        public ?string $empenho = null,
        public ?int $placaObras = null,
        public ?string $endereco = null,
        public ?string $bairro = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
        private array $itens = [],
    ) {}

    /**
     * @param  array<string, mixed>  $d
     */
    public static function deValidados(array $d): self
    {
        $etapa = EtapaVistoria::from((string) $d['etapa']);

        return new self(
            beneficiarioId: (int) $d['beneficiario_id'],
            etapa: $etapa,
            numeroInstalacao: isset($d['numero_instalacao']) && $d['numero_instalacao'] !== null
                ? (int) $d['numero_instalacao']
                : null,
            engenheiroNome: $d['engenheiro_nome'] ?? null,
            engenheiroCrea: $d['engenheiro_crea'] ?? null,
            // Somente a etapa CEDEC tem ART.
            engenheiroArt: $etapa->exigeDadosAdministrativos() ? ($d['engenheiro_art'] ?? null) : null,
            dataRelatorio: $d['data_relatorio'] ?? null,
            localRelatorio: $d['local_relatorio'] ?? null,
            processoSei: $etapa->exigeDadosAdministrativos() ? ($d['processo_sei'] ?? null) : null,
            contrato: $etapa->exigeDadosAdministrativos() ? ($d['contrato'] ?? null) : null,
            empenho: $etapa->exigeDadosAdministrativos() ? ($d['empenho'] ?? null) : null,
            placaObras: $etapa->exigeDadosAdministrativos() && isset($d['placa_obras'])
                ? (int) $d['placa_obras']
                : null,
            endereco: $d['endereco'] ?? null,
            bairro: $d['bairro'] ?? null,
            latitude: NormalizaEntrada::decimal($d['latitude'] ?? null),
            longitude: NormalizaEntrada::decimal($d['longitude'] ?? null),
            observacoes: $d['observacoes'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
            itens: self::extrairItens($d['itens'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'beneficiario_id' => $this->beneficiarioId,
            'etapa' => $this->etapa->value,
            'numero_instalacao' => $this->numeroInstalacao,
            'engenheiro_nome' => $this->engenheiroNome,
            'engenheiro_crea' => $this->engenheiroCrea,
            'engenheiro_art' => $this->engenheiroArt,
            'data_relatorio' => $this->dataRelatorio,
            'local_relatorio' => $this->localRelatorio,
            'processo_sei' => $this->processoSei,
            'contrato' => $this->contrato,
            'empenho' => $this->empenho,
            'placa_obras' => $this->placaObras,
            'endereco' => $this->endereco,
            'bairro' => $this->bairro,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * @return array<int, ItemConferidoDTO>
     */
    public function itens(): array
    {
        return $this->itens;
    }

    /**
     * @param  array<string, array<string, mixed>>  $itens
     * @return array<int, ItemConferidoDTO>
     */
    private static function extrairItens(array $itens): array
    {
        $lista = [];

        foreach ($itens as $chave => $dados) {
            if (! is_array($dados)) {
                continue;
            }

            $lista[] = ItemConferidoDTO::deValidados((string) $chave, $dados);
        }

        return $lista;
    }
}
