<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Console;

use App\Modules\Treinamento\Services\PreenchimentoMunicipioInscritoService;
use Illuminate\Console\Command;

class PreencherMunicipioInscritoCommand extends Command
{
    protected $signature = 'treinamento:preencher-municipio-inscrito {--dry-run : Mostra o que seria preenchido sem gravar}';

    protected $description = 'Preenche o municipio da pessoa nas inscricoes antigas (servidor pelo orgao atual, cidadao pelo cadastro)';

    public function handle(PreenchimentoMunicipioInscritoService $servico): int
    {
        $simular = (bool) $this->option('dry-run');
        $relatorio = $servico->preencher($simular);

        $this->info($simular ? 'Simulacao (nada foi gravado).' : 'Preenchimento concluido.');
        $this->line("Inscricoes analisadas: {$relatorio['analisadas']}");
        $this->line("Preenchidas: {$relatorio['preenchidas']}");
        $this->line("Sem municipio: {$relatorio['sem_municipio']}");

        return self::SUCCESS;
    }
}
