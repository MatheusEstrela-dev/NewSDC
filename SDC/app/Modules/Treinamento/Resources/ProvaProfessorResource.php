<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Prova como o professor a ve. Unico lugar em que o gabarito (`correta`) sai
 * do backend: o aluno nunca passa por aqui.
 *
 * @property-read \App\Modules\Treinamento\Models\Prova $resource
 */
class ProvaProfessorResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $prova = $this->resource;

        return [
            'id' => $prova->id,
            'item_id' => $prova->modulo_item_id,
            'titulo' => $prova->item?->titulo,
            'nota_minima' => $prova->nota_minima,
            'max_tentativas' => $prova->max_tentativas,
            'questoes' => $prova->questoes->map(fn (ProvaQuestao $q) => self::questao($q))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function questao(ProvaQuestao $questao): array
    {
        return [
            'id' => $questao->id,
            'enunciado' => $questao->enunciado,
            'ordem' => $questao->ordem,
            'alternativas' => $questao->alternativas->map(fn (ProvaAlternativa $a) => [
                'id' => $a->id,
                'texto' => $a->texto,
                'ordem' => $a->ordem,
                'correta' => $a->makeVisible('correta')->correta,
            ])->values()->all(),
        ];
    }
}
