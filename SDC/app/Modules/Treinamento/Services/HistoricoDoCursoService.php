<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Models\CursoEvento;
use Illuminate\Support\Facades\Auth;

/**
 * Historico do fluxo do curso (treinamento.curso_eventos).
 *
 * Append-only e sem estado: o service e singleton sob Octane e nao guarda nada
 * entre requisicoes. O autor sai do usuario autenticado da requisicao atual
 * quando nao e informado - em job e comando nao ha usuario e a linha fica com
 * autor_id NULL (acao de sistema).
 *
 * NAO ha try/catch em volta do INSERT: em Postgres um erro dentro de uma
 * transacao aborta a transacao inteira, entao engolir a falha aqui so trocaria
 * uma mensagem clara por um "current transaction is aborted" mais adiante.
 */
class HistoricoDoCursoService
{
    /**
     * @param  array<string, mixed>  $dados  contexto livre (valores antes/depois)
     */
    public function registrar(
        int $treinamentoId,
        TipoEventoCurso $tipo,
        string $descricao,
        array $dados = [],
        ?int $autorId = null,
    ): CursoEvento {
        return CursoEvento::create([
            'treinamento_id' => $treinamentoId,
            'tipo' => $tipo->value,
            'descricao' => $descricao,
            'dados' => $dados,
            'autor_id' => $autorId ?? self::autorAtual(),
        ]);
    }

    /** users.id do servidor logado, ou null em job/comando. */
    private static function autorAtual(): ?int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : null;
    }
}
