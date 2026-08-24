<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Treinamento\Models\Cidadao;
use Illuminate\Console\Command;

/**
 * Descarta cadastros do Portal de Treinamentos que nunca confirmaram o e-mail.
 *
 * Cadastro pendente ocupa o unique de CPF e de e-mail no banco. O
 * Portal\RegisterController ja sobrescreve o pendente quando alguem tenta se
 * cadastrar com os mesmos dados, entao ninguem fica travado por causa disso -
 * este command e higiene: sem ele a tabela acumula tentativa abandonada e
 * cadastro de bot para sempre.
 */
class LimparCidadaosNaoVerificadosCommand extends Command
{
    protected $signature = 'treinamento:limpar-cidadaos-nao-verificados
        {--days=7 : Idade minima (em dias) para descartar cadastros nao confirmados}';

    protected $description = 'Remove cadastros do Portal de Treinamentos que nao confirmaram o e-mail ha mais de N dias.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // forceDelete porque o unique e no indice do banco, que ignora
        // deleted_at: soft delete nao liberaria o CPF nem o e-mail. Seguro
        // porque cadastro nao confirmado nunca autenticou, logo nao tem
        // inscricao, presenca ou certificado vinculados; os pedidos de
        // verificacao pendentes caem por cascade.
        $removidos = 0;

        Cidadao::withTrashed()
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $cutoff)
            ->chunkById(500, function ($cidadaos) use (&$removidos) {
                foreach ($cidadaos as $cidadao) {
                    $cidadao->forceDelete();
                    $removidos++;
                }
            });

        $this->info("Removidos {$removidos} cadastros nao confirmados com mais de {$days} dias.");

        return self::SUCCESS;
    }
}
