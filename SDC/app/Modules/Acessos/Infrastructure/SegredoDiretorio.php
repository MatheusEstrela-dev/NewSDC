<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use Illuminate\Support\Env;

/**
 * Segredos do diretorio (senha de bind e chave da entrega de senha), lidos no
 * momento do uso e nunca em config/acessos.php: assim `config:cache` nao os
 * grava em bootstrap/cache/config.php. Ordem: `<NOME>_FILE` (segredo do
 * Swarm) e depois `<NOME>`. Arquivo informado mas ilegivel ou vazio e erro
 * de configuracao, sem cair para a env.
 */
final class SegredoDiretorio
{
    private const SENHA_BIND = 'DIRETORIO_SENHA';

    private const CHAVE_ENTREGA = 'DIRETORIO_CHAVE_ENTREGA';

    /** Senha da conta de servico usada no bind simples. */
    public function senhaBind(): string
    {
        return $this->exigir(self::SENHA_BIND);
    }

    /** Chave `base64:` da entrega de senha, igual no web e no worker. */
    public function chaveEntrega(): string
    {
        return $this->exigir(self::CHAVE_ENTREGA);
    }

    private function exigir(string $nome): string
    {
        return $this->ler($nome) ?? throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
    }

    private function ler(string $nome): ?string
    {
        $arquivo = $this->env($nome.'_FILE');
        if ($arquivo === null) {
            return $this->env($nome);
        }

        $conteudo = is_file($arquivo) && is_readable($arquivo) ? file_get_contents($arquivo) : false;
        $conteudo = $conteudo === false ? '' : trim($conteudo);
        if ($conteudo === '') {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
        }

        return $conteudo;
    }

    private function env(string $nome): ?string
    {
        $valor = Env::get($nome);

        return is_string($valor) && trim($valor) !== '' ? $valor : null;
    }
}
