<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Support;

use App\Support\Database\ConexaoPowerBi;
use Throwable;

/** Trilha em controle.execucoes (fora da transacao de gravacao, para sobreviver a falha). */
final class RegistroDeExecucoes
{
    public function iniciar(string $modulo, string $tipo, ?string $tabela = null): int
    {
        return (int) ConexaoPowerBi::tabela('controle.execucoes')->insertGetId([
            'modulo' => $modulo,
            'tabela' => $tabela,
            'tipo' => $tipo,
            'status' => 'executando',
        ]);
    }

    public function concluir(int $id, ResultadoExecucao $r): void
    {
        ConexaoPowerBi::tabela('controle.execucoes')->where('id', $id)->update([
            'status' => 'sucesso',
            'finalizada_em' => now(),
            'linhas_lidas' => $r->lidas(),
            'linhas_gravadas' => $r->gravadas(),
            'detalhe' => json_encode($r->porTabela(), JSON_THROW_ON_ERROR),
        ]);
    }

    public function falhar(int $id, Throwable $e): void
    {
        ConexaoPowerBi::tabela('controle.execucoes')->where('id', $id)->update([
            'status' => 'falhou',
            'finalizada_em' => now(),
            'erro' => mb_substr($e->getMessage(), 0, 2000),
        ]);
    }
}
