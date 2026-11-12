<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Console;

use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Services\CertificadoService;
use Illuminate\Console\Command;

/**
 * Atribui codigo de verificacao aos certificados emitidos antes do codigo
 * existir (spec 8.6). Idempotente: so toca em quem ainda nao tem codigo e
 * nunca altera um codigo ja gravado.
 */
class GerarCodigosCertificadosCommand extends Command
{
    protected $signature = 'treinamento:certificados-codigo {--lote=200 : Certificados por lote}';

    protected $description = 'Atribui codigo de verificacao aos certificados que ainda nao tem (idempotente).';

    public function handle(CertificadoService $certificados): int
    {
        $total = 0;

        Certificado::query()
            ->whereNull('codigo_verificacao')
            ->chunkById((int) $this->option('lote'), function ($lote) use ($certificados, &$total) {
                foreach ($lote as $certificado) {
                    $certificados->garantirCodigo($certificado);
                    $total++;
                }
            });

        $this->info("Codigos atribuidos: {$total}");

        return self::SUCCESS;
    }
}
