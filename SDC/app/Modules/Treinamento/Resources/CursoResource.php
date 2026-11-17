<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CursoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'sigla' => $this->sigla,
            'descricao' => $this->descricao,
            'ativo' => (bool) $this->ativo,
            'turmas_count' => (int) ($this->turmas_count ?? 0),
        ];
    }
}
