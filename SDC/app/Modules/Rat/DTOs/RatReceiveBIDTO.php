<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use Illuminate\Foundation\Http\FormRequest;

class RatReceiveBIDTO
{
    public function __construct(
        public readonly array $dadosGerais = [],
        public readonly array $comunicacao = [],
        public readonly array $local       = [],
        public readonly array $endereco    = [],
        public readonly array $recursos    = [],
        public readonly array $envolvidos  = [],
        public readonly array $vistoria    = [],
        public readonly bool  $finalize    = false,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            dadosGerais: $request->input('dados_gerais', []),
            comunicacao: $request->input('comunicacao', []),
            local:       $request->input('local', []),
            endereco:    $request->input('endereco', []),
            recursos:    $request->input('recursos', []),
            envolvidos:  $request->input('envolvidos', []),
            vistoria:    $request->input('vistoria', []),
            finalize:    (bool) $request->input('finalize', false),
        );
    }

    public function toArray(): array
    {
        return [
            'dadosGerais' => $this->dadosGerais,
            'comunicacao' => $this->comunicacao,
            'local'       => $this->local,
            'endereco'    => $this->endereco,
            'recursos'    => $this->recursos,
            'envolvidos'  => $this->envolvidos,
            'vistoria'    => $this->vistoria,
            'finalize'    => $this->finalize,
        ];
    }
}
