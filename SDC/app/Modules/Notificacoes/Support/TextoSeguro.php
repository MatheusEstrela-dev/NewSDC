<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Support;

/**
 * Sanitiza o texto que entra no payload de notificacao.
 *
 * Titulo e mensagem carregam conteudo de origem humana (nome de quem comentou,
 * titulo de demanda, justificativa de reprovacao). O papiro aponta a ausencia de
 * sanitizacao como gap critico do sistema, e este payload viaja para tres
 * superficies diferentes: o painel Vue, o corpo Markdown do Telegram e o
 * broadcast via websocket.
 *
 * Notificacao nao precisa de HTML nenhum, entao a regra e a mais restritiva
 * possivel: remover marcacao por completo em vez de tentar filtrar tags
 * permitidas. Isso dispensa uma dependencia como HTMLPurifier e elimina a classe
 * inteira de bypass que listas de permissao costumam ter.
 */
final class TextoSeguro
{
    private const LIMITE_TITULO = 120;

    private const LIMITE_MENSAGEM = 500;

    public static function titulo(string $texto): string
    {
        return self::limpar($texto, self::LIMITE_TITULO);
    }

    public static function mensagem(string $texto): string
    {
        return self::limpar($texto, self::LIMITE_MENSAGEM);
    }

    /**
     * URL de acao: aceita apenas caminho interno ou http(s). Bloqueia esquemas
     * executaveis como javascript: e data:, que transformariam o botao do card
     * em vetor de execucao.
     */
    public static function url(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($esquema, ['http', 'https'], true) ? $url : null;
    }

    private static function limpar(string $texto, int $limite): string
    {
        // Decodificar antes de remover as tags impede que uma entidade escapada
        // (&lt;script&gt;) sobreviva ao strip_tags e volte a virar marcacao no
        // momento em que algum consumidor decodificar o texto.
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = strip_tags($texto);

        // Colapsa quebras e espacos repetidos: o card tem duas linhas de altura.
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? '';

        return mb_substr(trim($texto), 0, $limite);
    }
}
