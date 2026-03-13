<?php

declare(strict_types=1);

namespace App\Services\Rat;

use App\Models\Rat\RatOcorrencia;
use App\Models\Rat\RatOcorrenciaRelato;
use Illuminate\Support\Facades\DB;

/**
 * Gerenciamento dos relatos vinculados a uma ocorrência.
 *
 * Métodos públicos:
 *   manageRelatos()  — sincroniza relatos de uma ocorrência
 *   attachRelato()   — vincula um conteúdo polimórfico à ocorrência
 *   detachRelato()   — remove uma entrada da tabela pivô
 */
class RatRelatoService
{
    /**
     * Garante que a ocorrência possua exatamente os relatos informados.
     * Qualquer relato nào presente na lista será removido.
     *
     * @param array<array{conteudo_type: string, conteudo_id: int}> $relatos
     */
    public function manageRelatos(RatOcorrencia $ocorrencia, array $relatos): void
    {
        DB::transaction(function () use ($ocorrencia, $relatos) {
            $ocorrencia->relatos()->delete();

            foreach ($relatos as $relato) {
                $this->attachRelato($ocorrencia, $relato['conteudo_type'], $relato['conteudo_id']);
            }
        });
    }

    /**
     * Vincula um modelo conteúdo já persistido à ocorrência via tabela pivô.
     */
    public function attachRelato(RatOcorrencia $ocorrencia, string $conteudoType, int $conteudoId): RatOcorrenciaRelato
    {
        return RatOcorrenciaRelato::create([
            'ocorrencia_id' => $ocorrencia->id,
            'conteudo_type' => $conteudoType,
            'conteudo_id'   => $conteudoId,
            'created_by'    => auth()->id(),
        ]);
    }

    /**
     * Remove a entrada pivô pelo seu ID (não remove o conteúdo).
     */
    public function detachRelato(int $relatoId): void
    {
        RatOcorrenciaRelato::where('id', $relatoId)->delete();
    }
}
