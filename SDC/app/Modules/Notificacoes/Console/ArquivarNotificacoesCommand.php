<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Console;

use App\Modules\Notificacoes\Models\Notificacao;
use App\Modules\Notificacoes\Services\ArquivadorDeNotificacoes;
use Illuminate\Console\Command;

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

        // A rotina de insert-e-delete vive no servico: o botao "Limpar" do sino
        // usa exatamente a mesma, recortando por destinatario em vez de idade.
        $movidas = app(ArquivadorDeNotificacoes::class)->arquivar($alvos, $lote);

        $this->info("Arquivadas: {$movidas}");

        return self::SUCCESS;
    }
}
