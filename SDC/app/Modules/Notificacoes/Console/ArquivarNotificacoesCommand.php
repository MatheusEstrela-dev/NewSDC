<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Console;

use App\Modules\Notificacoes\Models\Notificacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Move notificacoes com mais de N dias para notifications_archive.
 *
 * Mesma tratativa do webhooks:archive: a tabela operacional fica pequena (e o
 * inbox e o badge continuam rapidos) sem que o historico seja destruido. Nada e
 * apagado, apenas segregado.
 *
 * O corte usa created_at, e nao read_at, para que notificacao nunca lida tambem
 * saia da tabela quente: um aviso de 90 dias que ninguem abriu perdeu utilidade
 * operacional, mas continua valendo como registro.
 *
 * Uso: php artisan notificacoes:arquivar --days=90 --chunk=500
 */
class ArquivarNotificacoesCommand extends Command
{
    protected $signature = 'notificacoes:arquivar
                            {--days= : idade minima em dias (padrao: config notificacoes.retencao.dias_para_arquivar)}
                            {--chunk=500 : tamanho do lote}
                            {--dry-run : apenas conta o que seria arquivado}';

    protected $description = 'Move notificacoes antigas para notifications_archive, preservando o historico';

    public function handle(): int
    {
        $dias = (int) ($this->option('days') ?: config('notificacoes.retencao.dias_para_arquivar', 90));
        $lote = max(1, (int) $this->option('chunk'));
        $corte = now()->subDays($dias);

        $alvos = Notificacao::query()->where('created_at', '<', $corte);

        if ($this->option('dry-run')) {
            $this->info(sprintf(
                'Seriam arquivadas %d notificacao(oes) criadas antes de %s.',
                $alvos->count(),
                $corte->toDateTimeString()
            ));

            return self::SUCCESS;
        }

        $this->info("Arquivando notificacoes criadas antes de {$corte->toDateTimeString()}");

        $movidas = 0;

        // chunkById em vez de chunk: as linhas somem da consulta a cada lote, e a
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

            // Inserir e remover na mesma transacao: ou a linha esta no arquivo, ou
            // segue no inbox. Nunca nos dois, nunca em nenhum.
            DB::transaction(function () use ($batch, $linhas, &$movidas): void {
                DB::table('notifications_archive')->insertOrIgnore($linhas);
                Notificacao::query()->whereIn('id', $batch->pluck('id'))->delete();
                $movidas += count($linhas);
            });
        });

        $this->info("Arquivadas: {$movidas}");

        return self::SUCCESS;
    }
}
