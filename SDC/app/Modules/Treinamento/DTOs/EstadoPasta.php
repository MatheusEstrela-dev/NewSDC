<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Models\Modulo;

final readonly class EstadoPasta
{
    /**
     * @param  list<EstadoItem>  $itens
     */
    public function __construct(
        public Modulo $modulo,
        public bool $liberada,
        public bool $concluida,
        public array $itens,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->modulo->id,
            'titulo' => $this->modulo->titulo,
            'descricao' => $this->modulo->descricao,
            'ordem' => $this->modulo->ordem,
            'liberada' => $this->liberada,
            'concluida' => $this->concluida,
            'itens' => array_map(fn (EstadoItem $i) => $i->toArray(), $this->itens),
        ];
    }
}
