<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Support;

use Illuminate\Support\Facades\DB;

/**
 * Lado sdc do exactly-once (tabela integracao_aplicacoes, conexao padrao).
 * Chamado dentro da transacao do efeito: reservar() trava a mensagem pela
 * PK; se a linha ja existia, o efeito ja foi feito e so o resultado volta.
 */
final class RegistroDeAplicacoes
{
    private const TABELA = 'integracao_aplicacoes';

    /** true = primeira aplicacao (linha criada nesta transacao). */
    public function reservar(string $mensagemId, string $operacao): bool
    {
        return DB::table(self::TABELA)->insertOrIgnore([
            'mensagem_id' => $mensagemId,
            'operacao'    => $operacao,
            'aplicado_em' => now(),
        ]) === 1;
    }

    /** @return array<string, mixed> */
    public function resultado(string $mensagemId): array
    {
        $json = DB::table(self::TABELA)->where('mensagem_id', $mensagemId)->value('resultado');
        $resultado = is_string($json) ? json_decode($json, true) : null;

        return is_array($resultado) ? $resultado : [];
    }

    /**
     * Resultado e o recurso afetado; o aplicador pode devolver recurso_tipo e
     * recurso_id (ou so id).
     *
     * @param  array<string, mixed>  $resultado
     */
    public function concluir(string $mensagemId, array $resultado): void
    {
        $recursoId = $resultado['recurso_id'] ?? $resultado['id'] ?? null;
        $recursoTipo = $resultado['recurso_tipo'] ?? null;

        DB::table(self::TABELA)->where('mensagem_id', $mensagemId)->update([
            'recurso_tipo' => is_scalar($recursoTipo) ? (string) $recursoTipo : null,
            'recurso_id'   => is_scalar($recursoId) ? (string) $recursoId : null,
            'resultado'    => JsonCanonico::codificar((object) $resultado),
        ]);
    }
}
