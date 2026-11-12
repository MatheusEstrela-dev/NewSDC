<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaAlternativa;
use App\Modules\Treinamento\Models\ProvaQuestao;
use App\Modules\Treinamento\Models\ProvaResposta;
use App\Modules\Treinamento\Models\ProvaTentativa;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Prova do aluno (spec 7.1, 8.2, 8.3, 8.6, 8.7). Tudo e decidido aqui, nunca
 * no front: a tentativa e sorteada e congelada ao iniciar, a resposta so
 * entra enquanto a tentativa esta aberta, e o envio corrige 100% no backend
 * gravando nota, aprovada e correta uma unica vez (nunca recalculados).
 *
 * Escritas sob lock: a inscricao serializa o inicio (limite de tentativas) e
 * a tentativa serializa resposta e envio.
 */
class ProvaService
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ProgressoService $progresso,
        private readonly SituacaoProvaService $situacoes,
    ) {}

    /**
     * Prova do item; 404 se o item nao e PROVA ou nao tem configuracao.
     */
    public function daItem(ModuloItem $item): Prova
    {
        if ($item->tipo !== TipoItem::PROVA) {
            throw (new ModelNotFoundException)->setModel(Prova::class);
        }

        return Prova::query()->where('modulo_item_id', $item->id)->firstOrFail();
    }

    /**
     * Abre a tentativa da prova ou retoma a que ficou aberta (sem consumir
     * tentativa: so conta ao enviar). A ordem das questoes e das alternativas
     * e sorteada uma vez e persistida.
     */
    public function iniciar(Inscricao $inscricao, ModuloItem $item): ProvaTentativa
    {
        $prova = $this->daItem($item);
        if (! $this->trilha->itemLiberado($inscricao, $item)) {
            throw new AuthorizationException('Esta prova ainda esta bloqueada.');
        }

        return ConexaoTreinamento::transacao(function () use ($inscricao, $prova) {
            Inscricao::query()->whereKey($inscricao->id)->lockForUpdate()->firstOrFail();

            $aberta = ProvaTentativa::query()->abertas()
                ->where('prova_id', $prova->id)->where('inscricao_id', $inscricao->id)->first();
            if ($aberta !== null) {
                return $aberta;
            }

            $situacao = $this->situacoes->daProva($inscricao, $prova);
            if ($situacao->aprovada) {
                throw new \DomainException('Voce ja foi aprovado nesta prova.');
            }
            if ($situacao->tentativasRestantes() === 0) {
                throw new \DomainException('As tentativas desta prova se esgotaram. Procure o professor para liberar uma nova.');
            }

            $ordem = $this->sortear($prova);
            if ($ordem === []) {
                throw new \DomainException('Esta prova ainda nao tem questoes.');
            }

            $numero = (int) ProvaTentativa::query()
                ->where('prova_id', $prova->id)->where('inscricao_id', $inscricao->id)->max('numero');

            return ProvaTentativa::query()->create([
                'prova_id' => $prova->id,
                'inscricao_id' => $inscricao->id,
                'numero' => $numero + 1,
                'ordem' => $ordem,
                'iniciada_em' => now(),
            ]);
        });
    }

    /**
     * Autosave: grava (ou troca) a resposta de uma questao da tentativa. A
     * questao e a alternativa precisam fazer parte da ordem congelada da
     * tentativa; o banco ainda garante que a alternativa e da questao.
     */
    public function responder(Inscricao $inscricao, ModuloItem $item, int $tentativaId, int $questaoId, int $alternativaId): void
    {
        $prova = $this->daItem($item);

        ConexaoTreinamento::transacao(function () use ($inscricao, $prova, $tentativaId, $questaoId, $alternativaId) {
            $tentativa = $this->tentativaTravada($inscricao, $prova, $tentativaId);
            if (! $tentativa->estaAberta()) {
                throw new \DomainException('Esta tentativa ja foi enviada.');
            }

            $questao = collect($tentativa->ordem)->firstWhere('questao_id', $questaoId);
            if ($questao === null || ! in_array($alternativaId, $questao['alternativas'], true)) {
                throw new \DomainException('Resposta invalida para esta tentativa.');
            }

            $agora = now();
            ConexaoTreinamento::conexao()->statement(<<<'SQL'
                INSERT INTO treinamento.prova_respostas
                    (prova_tentativa_id, prova_questao_id, prova_alternativa_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT (prova_tentativa_id, prova_questao_id) DO UPDATE SET
                    prova_alternativa_id = EXCLUDED.prova_alternativa_id,
                    updated_at = EXCLUDED.updated_at
                SQL, [$tentativa->id, $questaoId, $alternativaId, $agora, $agora]);
        });
    }

    /**
     * Envia a tentativa: corrige pelo gabarito, grava nota/aprovada/correta e,
     * se aprovou, conclui o item da prova na trilha. Questao sem resposta
     * conta como errada. So um envio por tentativa.
     */
    public function enviar(Inscricao $inscricao, ModuloItem $item, int $tentativaId): ProvaTentativa
    {
        $prova = $this->daItem($item);

        return ConexaoTreinamento::transacao(function () use ($inscricao, $item, $prova, $tentativaId) {
            $tentativa = $this->tentativaTravada($inscricao, $prova, $tentativaId);
            if (! $tentativa->estaAberta()) {
                throw new \DomainException('Esta tentativa ja foi enviada.');
            }

            $respostas = ProvaResposta::query()->where('prova_tentativa_id', $tentativa->id)->get();
            $certas = ProvaAlternativa::withTrashed()
                ->whereIn('id', $respostas->pluck('prova_alternativa_id'))
                ->where('correta', true)
                ->pluck('id')
                ->all();

            $acertos = 0;
            foreach ($respostas as $resposta) {
                $acertou = in_array($resposta->prova_alternativa_id, $certas, true);
                $acertos += $acertou ? 1 : 0;
                ProvaResposta::query()->whereKey($resposta->id)->update(['correta' => $acertou]);
            }

            // Questao sem resposta nao tem linha: conta como errada.
            $total = count($tentativa->ordem);
            $nota = round(100 * $acertos / max(1, $total), 2);

            $tentativa->forceFill([
                'finalizada_em' => now(),
                'nota' => $nota,
                'aprovada' => $nota >= $prova->nota_minima,
            ])->save();

            if ($tentativa->aprovada) {
                $this->progresso->concluir($inscricao, $item);
            }

            return $tentativa;
        });
    }

    /**
     * Sorteia a ordem das questoes e das alternativas ativas agora.
     *
     * @return list<array{questao_id: int, alternativas: list<int>}>
     */
    private function sortear(Prova $prova): array
    {
        $questoes = ProvaQuestao::query()
            ->where('prova_id', $prova->id)
            ->with('alternativas:id,prova_questao_id')
            ->get(['id'])
            ->filter(fn (ProvaQuestao $q) => $q->alternativas->isNotEmpty());

        return $questoes->shuffle()->map(fn (ProvaQuestao $q) => [
            'questao_id' => $q->id,
            'alternativas' => $q->alternativas->pluck('id')->shuffle()->values()->all(),
        ])->values()->all();
    }

    /**
     * Tentativa da inscricao nesta prova, travada para escrita; 404 se for de
     * outra inscricao ou de outra prova (nao revela que existe).
     */
    private function tentativaTravada(Inscricao $inscricao, Prova $prova, int $tentativaId): ProvaTentativa
    {
        return ProvaTentativa::query()
            ->whereKey($tentativaId)
            ->where('inscricao_id', $inscricao->id)
            ->where('prova_id', $prova->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
