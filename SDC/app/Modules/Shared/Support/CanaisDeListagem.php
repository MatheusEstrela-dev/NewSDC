<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

/**
 * Quem pode assinar o canal de tempo real de cada listagem.
 *
 * Fonte UNICA da autorizacao dos canais `listagem.*`. O `routes/channels.php`
 * nao carrega nome de permissao nenhum: pergunta aqui. Espalhar a regra por
 * canal levaria a canais que autorizam mais que a pagina que eles atualizam, e
 * sem nada que apontasse a divergencia.
 *
 * REGRA: o valor tem de ser a MESMA permissao do `middleware('can:...')` da
 * rota que serve a listagem. O canal so pode avisar quem ja poderia ver a
 * pagina -- nem mais, nem menos. Se algum dia a listagem passar a filtrar por
 * municipio no servidor, o recurso precisa migrar para ESCOPADOS: canal global
 * sobre listagem escopada e vazamento de escopo.
 *
 * RECUSA POR PADRAO: recurso sem entrada em MAPA devolve null, e quem autoriza
 * trata null como negativa. E isso que impede alguem de assinar
 * `listagem.qualquer-coisa` e receber aviso de mudanca que nao deveria ver.
 *
 * Nao e config nem binding de container: e lido no boot, por `channels.php`,
 * antes de a config estar quente.
 */
final class CanaisDeListagem
{
    /**
     * Recurso -> permissao da rota que serve a listagem.
     *
     * @var array<string, string>
     */
    private const MAPA = [
        // routes/modules/ajuda-humanitaria.php -> can:humanitaria.pedidos.view
        'pedidos-ah' => 'humanitaria.pedidos.view',

        // routes/modules/rat.php -> can:rat.protocolos.view
        //
        // DECLARADO, MAS AINDA NAO EMITE. O canal esta autorizado e correto; o
        // que falta e o dispatch e a assinatura na pagina. Nao foi fiado porque
        // `RatOcorrencia` e escrito de oito lugares em tres classes, e parte por
        // query builder (`where(...)->update()`, `->delete()`), onde observer do
        // Eloquent nao dispara -- qualquer atalho daria cobertura parcial, que e
        // pior que nenhuma numa tela cujo proposito e dizer se o dado esta
        // velho. Consolidar a superficie de escrita vem primeiro. Ver secao 8 do
        // spec.
        'rat' => 'rat.protocolos.view',

        // routes/modules/pmda.php -> can:pmda.analise.view
        'pmda-analises' => 'pmda.analise.view',
    ];

    /**
     * Recursos cuja listagem e recortada por municipio no servidor.
     *
     * So o PMDA: `PerfilPmda::aplicarEscopo()` sobrescreve `municipio_id` para
     * o COMPDEC. Pedidos e RAT listam o estado inteiro para quem tem a
     * permissao, entao canal global neles nao vaza nada.
     *
     * @var list<string>
     */
    private const ESCOPADOS = [
        'pmda-analises',
    ];

    /**
     * Permissao exigida pelo canal do recurso, ou null se o recurso nao existe.
     *
     * Null e negativa, nao "sem restricao".
     */
    public static function permissaoDe(string $recurso): ?string
    {
        return self::MAPA[$recurso] ?? null;
    }

    /**
     * O canal deste recurso exige escopo territorial no nome.
     *
     * Para recurso desconhecido devolve false, e isso nao e "false porque e
     * global": quem autoriza ja recusou antes, por permissaoDe().
     */
    public static function exigeEscopo(string $recurso): bool
    {
        return in_array($recurso, self::ESCOPADOS, true);
    }

    /** @return list<string> */
    public static function recursos(): array
    {
        return array_keys(self::MAPA);
    }
}
