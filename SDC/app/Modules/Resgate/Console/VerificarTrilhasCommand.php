<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Console;

use App\Modules\Resgate\Services\DocumentosDoPedido;
use App\Modules\Resgate\Support\HashEvento;
use App\Modules\Resgate\Support\TrilhaDoPedido;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Verificador diario da trilha do resgate (plano, secao 5).
 *
 * Recalcula a cadeia de hash de TODO pedido e o SHA-256 de TODO anexo no
 * disco. Adulteracao por escrita direta no banco ou troca de arquivo aparece
 * aqui mesmo que ninguem abra o pedido. Falha = log critico + codigo de saida
 * diferente de zero, para o agendador e o monitoramento acusarem.
 */
final class VerificarTrilhasCommand extends Command
{
    protected $signature = 'resgate:verificar-trilhas';

    protected $description = 'Recalcula a cadeia de hash dos pedidos e o hash dos anexos do resgate';

    public function handle(DocumentosDoPedido $documentos, TrilhaDoPedido $trilha): int
    {
        $db = DB::connection((string) config('resgate.conexao'));
        $quebrados = [];
        $pedidos = 0;

        foreach ($db->select('SELECT * FROM resgate.pedidos ORDER BY id') as $pedido) {
            $pedidos++;
            // Cadeia + retrato do pedido + fim da cadeia contra o status atual.
            $sequencia = HashEvento::verificar($trilha->eventos($db, (int) $pedido->id), $pedido);
            if ($sequencia !== null) {
                $quebrados[] = "pedido #{$pedido->id}: cadeia quebrada no evento {$sequencia}";
            }
        }

        $anexos = 0;
        foreach ($db->select('SELECT id, pedido_id FROM resgate.documentos ORDER BY id') as $doc) {
            $anexos++;
            $aberto = $documentos->abrir($db, (int) $doc->pedido_id, (int) $doc->id);
            if ($aberto === null || ! $aberto['integro']) {
                $quebrados[] = "documento #{$doc->id} do pedido #{$doc->pedido_id}: arquivo ausente ou hash divergente";
            }
        }

        if ($quebrados !== []) {
            Log::critical('resgate: trilha adulterada', ['ocorrencias' => $quebrados]);
            foreach ($quebrados as $linha) {
                $this->error($linha);
            }

            return self::FAILURE;
        }

        $this->info("Trilhas integras: {$pedidos} pedidos, {$anexos} anexos.");

        return self::SUCCESS;
    }
}
