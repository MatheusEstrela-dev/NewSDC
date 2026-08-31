<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Services;

use App\Modules\Notificacoes\Models\Notificacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Move notificacoes da tabela quente para notifications_archive.
 *
 * Nada e apagado, apenas segregado: o inbox e o badge continuam rapidos e o
 * historico permanece consultavel. Mesma tratativa do webhooks:archive.
 *
 * Extraido do ArquivarNotificacoesCommand quando o botao "Limpar" do sino
 * passou a precisar do mesmo comportamento. Duas copias da rotina de
 * insert-e-delete-na-mesma-transacao sairiam de sincronia na primeira coluna
 * nova da tabela -- e uma copia errada aqui significa notificacao existindo nas
 * duas tabelas, ou em nenhuma.
 */
class ArquivadorDeNotificacoes
{
    /**
     * Arquiva tudo que o builder selecionar. Devolve quantas foram movidas.
     *
     * O builder e responsabilidade de quem chama: o comando agendado recorta
     * por idade, o botao do sino recorta pelo destinatario. Este servico nao
     * decide escopo -- so garante que a movimentacao e atomica.
     */
    public function arquivar(Builder $alvos, int $lote = 500): int
    {
        $lote = max(1, $lote);
        $movidas = 0;

        // chunkById e nao chunk: as linhas somem da consulta a cada lote, e a
        // paginacao por offset pularia registros.
        $alvos->orderBy('id')->chunkById($lote, function ($batch) use (&$movidas): void {
            $linhas = $batch->map(fn (Notificacao $n): array => [
                'id' => $n->id,
                'type' => $n->type,
                'notifiable_type' => $n->notifiable_type,
                'notifiable_id' => $n->notifiable_id,
                'data' => is_array($n->data) ? json_encode($n->data, JSON_UNESCAPED_UNICODE) : $n->data,
                'group_key' => $n->group_key,
                'group_bucket' => $n->group_bucket,
                'group_count' => $n->group_count,
                'last_event_at' => $n->last_event_at,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'updated_at' => $n->updated_at,
                'archived_at' => now(),
            ])->all();

            // Inserir e remover na mesma transacao: ou a linha esta no arquivo,
            // ou segue no inbox. Nunca nos dois, nunca em nenhum.
            DB::transaction(function () use ($batch, $linhas, &$movidas): void {
                DB::table('notifications_archive')->insertOrIgnore($linhas);
                Notificacao::query()->whereIn('id', $batch->pluck('id'))->delete();
                $movidas += count($linhas);
            });
        });

        return $movidas;
    }
}
