<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\DTOs;

use App\Modules\AjudaHumanitaria\Enums\EtapaParecer;
use App\Modules\AjudaHumanitaria\Enums\SituacaoParecer;

/**
 * Parecer tecnico (RN-10).
 */
final readonly class ParecerDTO
{
    public function __construct(
        public string $dataParecer,
        public string $parecer,
        public SituacaoParecer $situacao,
        public EtapaParecer $etapa,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            dataParecer: (string) ($data['data_parecer'] ?? ''),
            parecer:     (string) ($data['parecer'] ?? ''),
            situacao:    SituacaoParecer::from((string) ($data['situacao'] ?? '')),
            etapa:       EtapaParecer::from((string) ($data['etapa'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data_parecer' => $this->dataParecer,
            'parecer'      => $this->parecer,
            'situacao'     => $this->situacao->value,
            'etapa'        => $this->etapa->value,
        ];
    }
}
