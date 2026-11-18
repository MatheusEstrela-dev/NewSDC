<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Versoes;

use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\InscricaoService;
use App\Modules\Treinamento\Services\ReavaliacaoDeConclusaoService;
use App\Modules\Treinamento\Services\TrilhaService;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\ElegibilidadeDeAtualizacao;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Atualizacao de quem ja se formou em versao anterior do curso: inscreve na
 * vigente (sem a regra das 3 inscricoes) e ja entra com concluido o que nao
 * mudou. Ao terminar o que e novo, a conclusao e o certificado sao da nova versao.
 */
class AtualizacaoDeVersaoService
{
    public function __construct(
        private readonly InscricaoService $inscricoes,
        private readonly AproveitamentoDeItens $aproveitamento,
        private readonly TrilhaService $trilha,
        private readonly ReavaliacaoDeConclusaoService $reavaliacao,
    ) {}

    /**
     * Inscricao concluida de origem (a mais recente, em versao anterior do mesmo
     * curso) quando a pessoa pode se atualizar; null quando nao pode.
     */
    public function podeAtualizar(Treinamento $vigente, Model $inscrito): ?Inscricao
    {
        [$origem, $motivo] = $this->verificar($vigente, $inscrito);

        return $motivo === null ? $origem : null;
    }

    public function atualizar(Treinamento $vigente, Model $inscrito): Inscricao
    {
        $nova = ConexaoTreinamento::transacao(function () use ($vigente, $inscrito): Inscricao {
            // Trava a linha da vigente e confere de novo sob a trava: duas
            // requisicoes simultaneas nao passam juntas pelo "ja inscrito".
            $vigente = Treinamento::query()->lockForUpdate()->findOrFail($vigente->id);

            [$origem, $motivo] = $this->verificar($vigente, $inscrito);
            if ($motivo !== null) {
                throw new DomainException($motivo);
            }

            $nova = $this->inscricoes->inscreverParaAtualizacao($vigente, $inscrito, $origem, $origem->sexo);

            $this->gravarProgressoAproveitado($nova, $vigente, $origem);
            $this->trilha->atualizarPastas($nova);

            return $nova;
        });

        // Fora da transacao: o certificado e enfileirado e nao pode sair antes do commit.
        $this->reavaliacao->avaliar($nova->fresh());

        return $nova;
    }

    /** @return array{0: ?Inscricao, 1: ?string} origem e, se nao pode atualizar, o motivo */
    private function verificar(Treinamento $vigente, Model $inscrito): array
    {
        if ($vigente->curso_id === null || ! $vigente->eVigente()) {
            return [null, 'So a versao vigente do curso recebe atualizacao.'];
        }

        $doCurso = $this->inscricoesNoCurso($vigente, $inscrito);

        return [
            ElegibilidadeDeAtualizacao::origem($doCurso, $vigente),
            ElegibilidadeDeAtualizacao::motivo($vigente, $doCurso),
        ];
    }

    /** @return Collection<int, Inscricao> inscricoes da pessoa em qualquer versao EAD do curso da vigente */
    private function inscricoesNoCurso(Treinamento $vigente, Model $inscrito): Collection
    {
        return Inscricao::query()
            ->with('treinamento')
            ->where('inscrito_type', $inscrito::class)
            ->where('inscrito_id', $inscrito->getKey())
            ->whereIn('treinamento_id', Treinamento::query()
                ->where('curso_id', $vigente->curso_id)
                ->where('tipo', TipoTreinamento::EAD->value)
                ->select('id'))
            ->get();
    }

    /** Mesmos campos que ProgressoService::concluir grava, em lote. */
    private function gravarProgressoAproveitado(Inscricao $nova, Treinamento $vigente, Inscricao $origem): void
    {
        $ids = $this->aproveitamento->itensIguais($vigente, $origem);
        if ($ids === []) {
            return;
        }

        $agora = now();
        $linhas = ModuloItem::query()->with('video')->whereIn('id', $ids)->get()
            ->map(fn (ModuloItem $item) => [
                'inscricao_id' => $nova->id,
                'modulo_item_id' => $item->id,
                'status' => StatusProgresso::CONCLUIDO->value,
                'posicao' => $item->totalProgresso() ?? 0,
                'concluido_em' => $agora,
                'created_at' => $agora,
                'updated_at' => $agora,
            ])->all();

        ConexaoTreinamento::conexao()->table('treinamento.progresso_itens')->insert($linhas);
    }
}
