<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Services;

use App\Modules\Ranking\DTOs\ContextoInstitucional;
use App\Modules\Ranking\Models\Vinculo;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Resolve o vinculo usuario -> orgao -> municipio VIGENTE NO INSTANTE do fato.
 *
 * Plano: docs/superpowers/plans/2026-09-21-ranqueamento-ipcm.md, secao 4.
 *
 * POR QUE ESTA CLASSE EXISTE
 * A pivot operacional (compdec_orgao_user) guarda o estado ATUAL e nao responde
 * "a que orgao Ana pertencia em marco". ranking.vinculos e a copia historica com
 * intervalo de validade, e so ela pode atribuir um credito ao passado.
 *
 * REGRA QUE NAO SE NEGOCIA
 * Copiar o orgao atual do usuario para tapar buraco historico nao prova vinculo
 * nenhum - inventa um. Ausencia de vinculo na janela vira `em_apuracao` com
 * orgao e municipio nulos, e o fato fica fora da classificacao ate a apuracao.
 * Mudanca de orgao DEPOIS do fato nao reescreve o fato: a janela do vinculo
 * antigo continua cobrindo aquele instante e e ela que responde.
 *
 * CUIDADO COM OCTANE - ESTE SERVICE E SINGLETON E SOBREVIVE ENTRE REQUESTS
 * O worker nao morre no fim do request, entao qualquer estado guardado aqui
 * atravessa para o proximo usuario atendido. Por isso:
 *
 *   - NAO ler Auth::user(), auth(), request(), sessao ou qualquer estado de
 *     request dentro deste service. O usuario do fato chega por argumento,
 *     porque o "usuario atual" do worker pode ser outro - ou o do request
 *     anterior. Em fila e replay de evento antigo nao existe usuario atual
 *     nenhum.
 *   - NAO guardar propriedade mutavel de instancia com dado de usuario. Um
 *     `private array $cache` indexado por user_id vazaria o contexto de um
 *     usuario na pontuacao do proximo, e o vazamento seria silencioso: pontos
 *     creditados ao orgao errado, sem erro nenhum no log.
 *   - Memoizacao, se um dia for necessaria, vai para cache EXTERNO com chave
 *     explicita (Cache::store(...), incluindo user_id e instante na chave),
 *     nunca para propriedade de objeto.
 *
 * As unicas propriedades desta classe sao readonly e de configuracao: o nome da
 * conexao. Nenhuma carrega dado de usuario.
 */
class InstitutionalContextResolver
{
    public function __construct(
        private readonly string $conexao = 'ranking',
    ) {}

    /**
     * Contexto institucional do usuario no instante informado.
     *
     * A janela do vinculo e o intervalo [valido_de, valido_ate):
     *   valido_de <= instante AND (valido_ate IS NULL OR instante < valido_ate)
     *
     * Inicio inclusivo e fim exclusivo pelo mesmo motivo que os periodos do
     * placar usam [inicio, fim): a troca de orgao acontece num unico instante,
     * e ele pertence ao vinculo que comeca, nao ao que termina. Sem isso, o
     * fato ocorrido exatamente na virada seria coberto por dois vinculos e
     * cairia em apuracao por uma ambiguidade puramente aritmetica.
     */
    public function resolver(int $userId, DateTimeImmutable $instante): ContextoInstitucional
    {
        if ($userId <= 0) {
            return ContextoInstitucional::emApuracao();
        }

        $vigentes = $this->vinculosVigentes($userId, $instante);

        // Sem vinculo na janela o contexto fica em apuracao. Aqui e exatamente
        // onde seria tentador consultar o orgao atual do usuario: nao se faz.
        if ($vigentes === []) {
            return ContextoInstitucional::emApuracao();
        }

        if (count($vigentes) === 1) {
            return $this->contextoDe($vigentes[0]);
        }

        // Usuario com dois vinculos abertos ao mesmo tempo (A/B) nao tem orgao
        // ativo deduzivel a partir da tabela. Escolher o primeiro, o mais
        // recente ou o de menor id seria arbitrar o credito - e credito
        // arbitrado e indistinguivel de credito correto depois de lancado.
        $comprovados = array_values(array_filter(
            $vigentes,
            fn (array $vinculo): bool => $vinculo['evidencia'] === ContextoInstitucional::EVIDENCIA_COMPROVADO,
        ));

        // Excecao unica: se apenas UM dos concorrentes tem prova documental, a
        // prova desempata. Dois comprovados continuam ambiguos.
        if (count($comprovados) === 1) {
            return $this->contextoDe($comprovados[0]);
        }

        return ContextoInstitucional::emApuracao();
    }

    /**
     * Vinculos do usuario cuja janela cobre o instante do fato.
     *
     * @return list<array{orgao_id: int, municipio_id: ?int, evidencia: string}>
     */
    private function vinculosVigentes(int $userId, DateTimeImmutable $instante): array
    {
        // Instante normalizado em UTC com offset explicito. A coluna e
        // timestamptz; enviar texto sem fuso deixaria a comparacao a cargo do
        // TimeZone da sessao do Postgres, e a fronteira exata da janela - que
        // e justamente o caso critico - dependeria de configuracao de servidor.
        $momento = $instante->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');

        $linhas = Vinculo::on($this->conexao)
            ->select(['orgao_id', 'municipio_id', 'evidencia'])
            ->where('user_id', $userId)
            ->where('valido_de', '<=', $momento)
            ->where(function ($query) use ($momento): void {
                // valido_ate nulo = vinculo aberto, vigente ate hoje.
                $query->whereNull('valido_ate')
                    ->orWhere('valido_ate', '>', $momento);
            })
            ->orderBy('valido_de')
            ->orderBy('id')
            ->get();

        return $linhas->map(fn (Vinculo $vinculo): array => [
            // Cast explicito: o driver pgsql devolve bigint como string quando
            // os casts do model nao estao ativos (query crua, teste isolado).
            'orgao_id' => (int) $vinculo->getAttribute('orgao_id'),
            'municipio_id' => $vinculo->getAttribute('municipio_id') === null
                ? null
                : (int) $vinculo->getAttribute('municipio_id'),
            'evidencia' => (string) $vinculo->getAttribute('evidencia'),
        ])->all();
    }

    /**
     * Traduz o vinculo encontrado em contexto, PROPAGANDO a evidencia da linha.
     *
     * Um vinculo `inferido` produz contexto `inferido`: encontrar a linha nao
     * melhora a qualidade da prova, e promover a `comprovado` aqui daria
     * credito competitivo a um vinculo que ninguem documentou.
     *
     * municipio_id nulo e propagado como nulo com a evidencia original: orgao
     * estadual sem municipio aplicavel continua comprovado. Municipio nulo nao
     * e o mesmo que contexto em apuracao.
     *
     * @param array{orgao_id: int, municipio_id: ?int, evidencia: string} $vinculo
     */
    private function contextoDe(array $vinculo): ContextoInstitucional
    {
        // Evidencia fora do dominio conhecido (a coluna e varchar livre, sem
        // CHECK no banco) nao vira credito: degrada para apuracao em vez de
        // estourar no meio da ingestao de um lote.
        if (! in_array($vinculo['evidencia'], ContextoInstitucional::EVIDENCIAS, true)) {
            return ContextoInstitucional::emApuracao();
        }

        // Linha gravada com evidencia 'em_apuracao': o vinculo existe na janela
        // mas nao sustenta credito. Orgao e municipio sao descartados para que
        // nenhum consumidor leia o orgao sem antes checar comprovado().
        if ($vinculo['evidencia'] === ContextoInstitucional::EVIDENCIA_EM_APURACAO) {
            return ContextoInstitucional::emApuracao();
        }

        return ContextoInstitucional::deVinculo(
            $vinculo['orgao_id'],
            $vinculo['municipio_id'],
            $vinculo['evidencia'],
        );
    }
}
