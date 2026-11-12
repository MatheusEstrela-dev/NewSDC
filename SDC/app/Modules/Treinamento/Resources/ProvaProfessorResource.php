<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Resources;

use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\ProvaResposta;
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
        $comRespostas = array_flip($this->questoesComRespostas($prova->questoes->modelKeys()));

        return [
            'id' => $prova->id,
            'item_id' => $prova->modulo_item_id,
            'titulo' => $prova->item?->titulo,
            'escala_nota' => Prova::ESCALA_NOTA,
            'nota_minima' => $prova->nota_minima,
            'max_tentativas' => $prova->max_tentativas,
            'questoes' => $prova->questoes->map(fn (ProvaQuestao $q) => self::questao($q, $comRespostas))->values()->all(),
        ];
    }

    /**
     * @param  list<int>  $questaoIds
     * @return list<int>
     */
    private function questoesComRespostas(array $questaoIds): array
    {
        return $questaoIds === []
            ? []
            : ProvaResposta::query()->whereIn('prova_questao_id', $questaoIds)->distinct()->pluck('prova_questao_id')->all();
    }

    /**
     * `tem_respostas`: algum aluno ja respondeu esta questao (tentativa aberta
     * ou enviada); editar o enunciado ou o gabarito afeta o historico.
     *
     * @param  array<int, mixed>|null  $comRespostas  ids de questao como chaves, quando o chamador ja os consultou em lote
     * @return array<string, mixed>
     */
    public static function questao(ProvaQuestao $questao, ?array $comRespostas = null): array
    {
        $temRespostas = $comRespostas === null
            ? ProvaResposta::query()->where('prova_questao_id', $questao->id)->exists()
            : array_key_exists($questao->id, $comRespostas);

        return [
            'id' => $questao->id,
            'enunciado' => $questao->enunciado,
            'ordem' => $questao->ordem,
            'tem_respostas' => $temRespostas,
            'alternativas' => $questao->alternativas->map(fn (ProvaAlternativa $a) => [
                'id' => $a->id,
                'texto' => $a->texto,
                'ordem' => $a->ordem,
                'correta' => $a->makeVisible('correta')->correta,
            ])->values()->all(),
        ];
    }
}
