<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Auth\Access\AuthorizationException;

class ProgressoService
{
    public function __construct(private readonly TrilhaService $trilha) {}

    /**
     * Registra ate onde o aluno chegou (pagina no PDF, segundo no video). A
     * posicao nunca regride nem passa do total do item; chegar ao total
     * conclui o item.
     */
    public function registrarPosicao(Inscricao $inscricao, ModuloItem $item, int $posicao): ProgressoItem
    {
        $teto = $item->totalProgresso();
        if ($teto === null) {
            throw new \DomainException('Este item nao registra progresso por posicao.');
        }
        if (! $this->trilha->itemLiberado($inscricao, $item)) {
            throw new AuthorizationException('Este conteudo ainda esta bloqueado.');
        }

        $total = max(1, $teto);
        $alvo = max(1, min($posicao, $total));

        return $this->gravar($inscricao, $item, $alvo, $alvo >= $total);
    }

    /**
     * Marca o item como concluido (aprovacao de projeto pelo professor, ou
     * leitura completa). Nao checa liberacao: quem chama ja validou.
     */
    public function concluir(Inscricao $inscricao, ModuloItem $item): ProgressoItem
    {
        $posicao = $item->totalProgresso() ?? 0;

        return $this->gravar($inscricao, $item, $posicao, true);
    }

    /**
     * Upsert na chave unica (inscricao, item): dois POSTs concorrentes caem
     * na mesma linha, a posicao so cresce e CONCLUIDO nunca volta atras.
     */
    private function gravar(Inscricao $inscricao, ModuloItem $item, int $posicao, bool $concluido): ProgressoItem
    {
        $agora = now();
        $status = $concluido ? StatusProgresso::CONCLUIDO : StatusProgresso::EM_ANDAMENTO;

        return ConexaoTreinamento::transacao(function () use ($inscricao, $item, $posicao, $concluido, $agora, $status) {
            ConexaoTreinamento::conexao()->statement(<<<'SQL'
                INSERT INTO treinamento.progresso_itens
                    (inscricao_id, modulo_item_id, status, posicao, concluido_em, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT (inscricao_id, modulo_item_id) DO UPDATE SET
                    posicao = GREATEST(treinamento.progresso_itens.posicao, EXCLUDED.posicao),
                    status = CASE
                        WHEN treinamento.progresso_itens.status = 'CONCLUIDO' OR EXCLUDED.status = 'CONCLUIDO'
                            THEN 'CONCLUIDO' ELSE 'EM_ANDAMENTO' END,
                    concluido_em = COALESCE(treinamento.progresso_itens.concluido_em, EXCLUDED.concluido_em),
                    updated_at = EXCLUDED.updated_at
                SQL, [
                $inscricao->id,
                $item->id,
                $status->value,
                $posicao,
                $concluido ? $agora : null,
                $agora,
                $agora,
            ]);

            $this->trilha->atualizarPastas($inscricao);

            return ProgressoItem::query()
                ->where('inscricao_id', $inscricao->id)
                ->where('modulo_item_id', $item->id)
                ->firstOrFail();
        });
    }
}
