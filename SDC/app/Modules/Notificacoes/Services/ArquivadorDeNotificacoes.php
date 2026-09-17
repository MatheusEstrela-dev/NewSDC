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
 *
 * Arquivar MUDA o que o painel do sino mostra, entao o servico avisa quem
 * guarda estado derivado disso: o contador do badge e a versao do inbox. O
 * aviso fica aqui, e nao em quem chama, porque sao dois chamadores com escopos
 * diferentes (a poda diaria por idade e o botao Limpar por destinatario) e o
 * que ambos tem em comum e exatamente esta rotina. A poda agendada, em
 * particular, nao invalidava nada: o badge podia ficar velho, e com o ETag
 * servido a partir da versao o painel continuaria mostrando um card ja
 * arquivado ate a chave expirar.
 */
class ArquivadorDeNotificacoes
{
    public function __construct(
        private readonly ContadorNaoLidas $contador,
        private readonly VersaoDoInbox $versao,
    ) {}

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

        // Destinatarios tocados, acumulados lote a lote: sao eles que precisam
        // de contador e versao novos no final.
        $afetados = [];

        // chunkById e nao chunk: as linhas somem da consulta a cada lote, e a
        // paginacao por offset pularia registros.
        $alvos->orderBy('id')->chunkById($lote, function ($batch) use (&$movidas, &$afetados): void {
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

            foreach ($batch->pluck('notifiable_id')->unique() as $id) {
                $afetados[(string) $id] = $id;
            }
        });

        if ($afetados !== []) {
            $ids = array_values($afetados);
            $this->contador->invalidar($ids);
            $this->versao->invalidar($ids);
        }

        return $movidas;
    }
}
