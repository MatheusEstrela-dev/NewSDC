<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Conclusao;

use App\Modules\Treinamento\Contracts\CriterioConclusao;
use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Services\SituacaoProvaService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Services\Versoes\AproveitamentoDeItens;

/**
 * Curso EAD: inscricao aprovada + todas as pastas concluidas (spec 8.4).
 * Aproveitamento = media das notas finais das provas do curso (na atualizacao,
 * as provas aproveitadas entram com a nota da inscricao de origem).
 *
 * Quem ja concluiu fica congelado (spec 8.7): com concluido_em gravado, o
 * resultado e o gravado, e a trilha atual nao e consultada.
 */
final class ConclusaoPorTrilha implements CriterioConclusao
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly SituacaoProvaService $provas,
        private readonly AproveitamentoDeItens $itens,
    ) {}

    public function avaliar(Inscricao $inscricao): ?ResultadoConclusao
    {
        if ($inscricao->status !== StatusInscricao::APROVADA) {
            return null;
        }

        if ($inscricao->concluido_em !== null) {
            return new ResultadoConclusao($inscricao->concluido_em, $inscricao->aproveitamento);
        }

        if (! $this->trilha->concluida($inscricao)) {
            return null;
        }

        return new ResultadoConclusao(now(), $this->aproveitamento($inscricao));
    }

    /**
     * Media das notas das provas: as feitas nesta inscricao e, na atualizacao,
     * a nota aprovada da inscricao de origem nas provas aproveitadas.
     */
    private function aproveitamento(Inscricao $inscricao): ?float
    {
        $proprias = $this->provas->daInscricao($inscricao);

        $notas = [];
        foreach ($proprias as $situacao) {
            if ($situacao->notaFinal !== null) {
                $notas[$situacao->moduloItemId] = $situacao->notaFinal;
            }
        }

        foreach ($this->notasHerdadas($inscricao) as $itemId => $nota) {
            if (isset($proprias[$itemId]) && ! isset($notas[$itemId])) {
                $notas[$itemId] = $nota;
            }
        }

        return $notas === [] ? null : round(array_sum($notas) / count($notas), 2);
    }

    /**
     * Nota final da origem nas provas que a atualizacao aproveitou, por item
     * da versao desta inscricao. Segue a cadeia quando a origem e ela mesma
     * uma atualizacao (a prova herdada la nao tem tentativa propria).
     *
     * @return array<int, float>
     */
    private function notasHerdadas(Inscricao $inscricao): array
    {
        if (! $inscricao->eAtualizacao()) {
            return [];
        }

        $origem = Inscricao::query()->with('treinamento')->find($inscricao->atualizacao_de_inscricao_id);
        if ($origem === null) {
            return [];
        }

        $notasDaOrigem = [];
        foreach ($this->provas->daInscricao($origem) as $situacao) {
            if ($situacao->notaFinal !== null) {
                $notasDaOrigem[$situacao->moduloItemId] = $situacao->notaFinal;
            }
        }
        $notasDaOrigem += $this->notasHerdadas($origem);

        $herdadas = [];
        foreach ($this->itens->mapaDeIguais($inscricao->treinamento, $origem) as $itemNovo => $itemOrigem) {
            if (isset($notasDaOrigem[$itemOrigem])) {
                $herdadas[$itemNovo] = $notasDaOrigem[$itemOrigem];
            }
        }

        return $herdadas;
    }
}
