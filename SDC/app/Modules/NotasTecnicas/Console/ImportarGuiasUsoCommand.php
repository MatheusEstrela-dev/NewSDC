<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Console;

use App\Models\User;
use App\Modules\NotasTecnicas\Services\NotaTecnicaGuiasImportacaoService;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Importa os guias de uso do SDC MG (pasta Doc/ do repositorio) para a pasta
 * "Guias de Uso do SDC" de Notas Tecnicas. Doc/ fica fora de SDC/ e nao vai na
 * imagem: copie a pasta para o container e passe o caminho.
 */
class ImportarGuiasUsoCommand extends Command
{
    protected $signature = 'notas-tecnicas:importar-guias
        {diretorio : Pasta Doc/ com o guia completo e "Guias por modulo/PDF"}
        {--autor= : E-mail ou id do usuario que elabora e publica as notas}
        {--dry-run : So mostra o que seria criado, sem gravar}';

    protected $description = 'Importa os guias de uso por modulo como notas tecnicas publicadas (sem aviso no sino)';

    public function handle(NotaTecnicaGuiasImportacaoService $importacao): int
    {
        $autor = $this->autor();

        if ($autor === null) {
            $this->error('Informe --autor com o e-mail ou id de um usuario existente.');

            return self::FAILURE;
        }

        try {
            $relatorio = $importacao->importar((string) $this->argument('diretorio'), $autor, (bool) $this->option('dry-run'));
        } catch (InvalidArgumentException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        }

        $this->table(['Pasta', 'Guia', 'Codigo', 'Situacao'], array_map(
            static fn (array $linha): array => [$linha['pasta'], $linha['titulo'], $linha['codigo'] ?? '-', $linha['situacao']],
            $relatorio,
        ));

        $criadas = count(array_filter($relatorio, static fn (array $linha): bool => $linha['situacao'] === 'criada'));
        $this->info($this->option('dry-run')
            ? 'Simulacao: nada foi gravado.'
            : "{$criadas} guia(s) criado(s) e publicado(s). A extracao de texto foi para a fila low.");

        return self::SUCCESS;
    }

    private function autor(): ?User
    {
        $valor = trim((string) $this->option('autor'));

        if ($valor === '') {
            return null;
        }

        return ctype_digit($valor)
            ? User::query()->find((int) $valor)
            : User::query()->where('email', $valor)->first();
    }
}
