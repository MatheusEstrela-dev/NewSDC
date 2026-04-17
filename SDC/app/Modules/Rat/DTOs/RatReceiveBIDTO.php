<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Rat\Http\Requests\ReceiveRatBIRequest;

readonly class RatReceiveBIDTO
{
    public function __construct(
        public array $dadosGerais,
        public array $comunicacao,
        public array $local,
        public array $endereco,
        public array $recursos,
        public array $envolvidos,
        public array $vistoria,
        public bool  $finalize,
    ) {}

    public static function fromRequest(ReceiveRatBIRequest $request): self
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

    public function toModelArray(): array
    {
        return [
            'dados_gerais' => $this->dadosGerais,
            'comunicacao'  => $this->comunicacao,
            'local'        => $this->local,
            'endereco'     => $this->endereco,
            'recursos'     => $this->recursos,
            'envolvidos'   => $this->envolvidos,
            'vistoria'     => $this->vistoria,
            'tem_vistoria' => !empty($this->vistoria),
        ];
    }
}
