<?php

declare(strict_types=1);

namespace App\Support\Database;

/**
 * Base dos models do sdc_powerbi. Alem da conexao separada, recusa instanciar
 * com o recurso desligado: ConexaoSeparada cairia na conexao default (sdc), e
 * model do powerbi nunca pode gravar la.
 */
trait UsaConexaoPowerBi
{
    use UsaConexaoSeparada;

    protected static function chaveDeConexao(): string
    {
        return ConexaoPowerBi::CHAVE;
    }

    public function initializeUsaConexaoPowerBi(): void
    {
        ConexaoPowerBi::exigirAtiva();
    }
}
