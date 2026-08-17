<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\Coordenada;
use App\Modules\Cisterna\Domain\Etl\PonteMunicipio;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\CoberturaTelhado;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Enums\TipoMoradia;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaComunidade;
use App\Modules\Cisterna\Models\CisternaOrdemServico;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Refino de sinc_cisterna: 54 colunas varchar(150) para tipos reais.
 *
 * A tabela mais larga do legado guardava data, moeda, medida e booleano todos
 * como texto, porque o formulario gravava o valor cru do input. Aqui cada campo
 * volta ao tipo que sempre foi.
 */
class RefinaBeneficiarios implements Refinador
{
    public function __construct(
        private readonly PonteMunicipio $ponte,
    ) {}

    public function recurso(): string
    {
        return 'beneficiarios';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $cpf = NormalizaEntrada::cpf($doc['cpf'] ?? null);

        if ($cpf === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'CPF ausente ou sem 11 digitos: '.($doc['cpf'] ?? 'null'), $doc);

            return;
        }

        $municipioId = $this->ponte->resolver($doc['codmundv'] ?? null)
            ?? $this->ponte->resolverPorNome($doc['municipio'] ?? null);

        if ($municipioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Municipio sem correspondencia IBGE.', $doc);

            return;
        }

        $comunidadeId = $this->resolverComunidade($doc, $municipioId, $legacyId);
        $atributos = $this->mapear($doc, $legacyId, $cpf, $municipioId, $comunidadeId);

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria beneficiario CPF {$cpf}.");

            return;
        }

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId, $cpf): void {
                $existente = CisternaBeneficiario::withTrashed()->where('legacy_id', $legacyId)->first();

                // O legado marcou este cadastro como duplicado, e essa decisao e
                // de quem analisou -- nao se discute aqui. Duplicado tambem nao
                // disputa o lugar ativo do CPF: o unique e parcial e os exclui.
                if ($atributos['situacao_analise'] === SituacaoAnalise::DUPLICADO->value) {
                    $this->gravarERegistrar($existente, $atributos, $doc, $legacyId);

                    return;
                }

                // CPF ja usado por outro registro. O legado nao tinha UNIQUE; a
                // garantia era um count() em PHP antes do insert, que falhava
                // sob concorrencia -- e 492 CPFs acabaram repetidos em 1.003
                // linhas. Delas, o proprio legado ja resolveu a maioria
                // marcando um lado como duplicado; disputam o lugar ativo so as
                // que ninguem analisou.
                $concorrentes = CisternaBeneficiario::withTrashed()
                    ->where('cpf', $cpf)
                    ->when($existente !== null, fn ($q) => $q->whereKeyNot($existente->id))
                    ->get()
                    ->reject(fn (CisternaBeneficiario $i): bool => $i->situacao_analise === SituacaoAnalise::DUPLICADO);

                // Entre os concorrentes vence o cadastro mais antigo, o de menor
                // legacy_id. A pergunta e feita por registro -- "existe alguem
                // mais antigo disputando este CPF?" -- e por isso a decisao nao
                // depende da ordem de leitura nem de quantas vezes o refino
                // rodou. Marcar sempre o registro corrente, como antes, deixava
                // as duas pontas de cada par como duplicado e escondia 492
                // beneficiarios reais.
                $maisAntigo = $concorrentes->where('legacy_id', '<', $legacyId)->sortBy('legacy_id')->first();

                if ($maisAntigo !== null) {
                    $this->tratarConflitoDeCpf($maisAntigo, $existente, $atributos, $doc, $legacyId, $cpf);

                    return;
                }

                // Este e o mais antigo. Se um concorrente mais novo esta
                // ocupando o lugar ativo, ele cede.
                if (! $this->liberarLugarAtivo($concorrentes, $atributos['nome'], $legacyId, $cpf, $doc)) {
                    return;
                }

                $this->gravarERegistrar($existente, $atributos, $doc, $legacyId);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar: '.$e->getMessage(), $doc);
        }
    }

    /**
     * Decisao D25. Os CPFs que colidem entre registros ativos tem DUAS
     * naturezas, e tratar as duas igual seria errado (notas 5.1):
     *
     *  A) 22 casos: mesma pessoa, cadastro em duplicidade. Nome quase
     *     identico. Marca como duplicado, convencao que o legado ja usava.
     *
     *  B) 4 casos: CPF digitado errado, apontando para pessoas DIFERENTES.
     *     Ex.: 05924079659 esta em "DOUGLAS SOARES BARBOSA" e em "ISABEL
     *     ALVES SEPO". Marcar a segunda como duplicata apagaria uma
     *     beneficiaria real da lista ativa.
     *
     * O separador e a similaridade dos nomes normalizados.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<string, mixed>  $doc
     */
    private function tratarConflitoDeCpf(
        CisternaBeneficiario $conflito,
        ?CisternaBeneficiario $existente,
        array $atributos,
        array $doc,
        int $legacyId,
        string $cpf,
    ): void {
        if (! $this->pareceMesmaPessoa($conflito->nome, $atributos['nome'])) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                "CPF {$cpf} ja usado por #{$conflito->id} (\"{$conflito->nome}\"), mas este "
                ."registro e de \"{$atributos['nome']}\": nomes divergentes, provavel erro de "
                .'digitacao de CPF. NAO importado -- corrigir o CPF na origem e reprocessar.', $doc);

            return;
        }

        $atributos['situacao_analise'] = SituacaoAnalise::DUPLICADO->value;
        $atributos['situacao_analise_obs'] = "CPF coincide com o registro #{$conflito->id} "
            ."(legacy_id {$conflito->legacy_id}). Marcado automaticamente na migracao.";

        $registro = $this->gravar($existente, $atributos, $doc);

        RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
            "CPF {$cpf} colide com #{$conflito->id}: importado como Duplicado.", $registro->id);
    }

    /**
     * Rebaixa a duplicata que esteja ocupando o lugar ativo do CPF, para o
     * cadastro mais antigo do grupo poder assumi-lo.
     *
     * So acontece quando uma carga anterior gravou o grupo em outra ordem: o
     * unique parcial de CPF admite um unico registro fora de `duplicado`, e sem
     * isso o mais antigo esbarraria nele.
     *
     * Devolve false quando nao pode rebaixar -- nome divergente e outra pessoa,
     * e esconder um beneficiario real e pior que deixar o conflito visivel para
     * a area resolver.
     *
     * @param  Collection<int, CisternaBeneficiario>  $concorrentes
     * @param  array<string, mixed>  $doc
     */
    private function liberarLugarAtivo(
        Collection $concorrentes,
        ?string $nome,
        int $legacyId,
        string $cpf,
        array $doc,
    ): bool {
        foreach ($concorrentes as $irmao) {
            if (! $this->pareceMesmaPessoa($irmao->nome, $nome)) {
                RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                    "CPF {$cpf} ja usado por #{$irmao->id} (\"{$irmao->nome}\"), mas este "
                    ."registro e de \"{$nome}\": nomes divergentes, provavel erro de "
                    .'digitacao de CPF. NAO importado -- corrigir o CPF na origem e reprocessar.', $doc);

                return false;
            }

            $irmao->update([
                'situacao_analise' => SituacaoAnalise::DUPLICADO->value,
                'situacao_analise_obs' => "CPF coincide com o cadastro de legacy_id {$legacyId}, "
                    .'mais antigo do grupo. Marcado automaticamente na migracao.',
            ]);

            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), (int) $irmao->legacy_id,
                "CPF {$cpf}: cadastro mais recente rebaixado para Duplicado em favor do "
                ."legacy_id {$legacyId}.", $irmao->id);
        }

        return true;
    }

    /**
     * Grava e registra no log, distinguindo insert de update.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<string, mixed>  $doc
     */
    private function gravarERegistrar(
        ?CisternaBeneficiario $existente,
        array $atributos,
        array $doc,
        int $legacyId,
    ): void {
        $registro = $this->gravar($existente, $atributos, $doc);

        $existente === null
            ? RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $registro->id)
            : RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $registro->id);
    }

    /**
     * Unico ponto de gravacao do beneficiario, para os dois caminhos -- normal e
     * duplicado -- decidirem insert ou update do mesmo jeito.
     *
     * Existe porque separar as duas decisoes ja custou: o caminho do duplicado
     * chamava create() direto, e na segunda passada do refino os 501 registros
     * marcados como duplicado batiam no unique de legacy_id. Idempotencia nao
     * pode depender de qual ramo o registro seguiu.
     *
     * @param  array<string, mixed>  $atributos
     * @param  array<string, mixed>  $doc
     */
    private function gravar(
        ?CisternaBeneficiario $existente,
        array $atributos,
        array $doc,
    ): CisternaBeneficiario {
        if ($existente === null) {
            $registro = CisternaBeneficiario::create($atributos);
        } else {
            $existente->update($atributos);
            $registro = $existente;
        }

        $this->sincronizarAtendimentos($registro, $doc);

        return $registro;
    }

    /**
     * @param  array<string, mixed>  $doc
     * @return array<string, mixed>
     */
    private function mapear(array $doc, int $legacyId, string $cpf, int $municipioId, ?int $comunidadeId): array
    {
        return [
            'cpf' => $cpf,
            'nome' => $this->texto($doc['nome'] ?? null, 150) ?? 'Beneficiario '.$legacyId,
            'telefone' => $this->texto($doc['tel'] ?? null, 15),
            'data_nascimento' => $this->data($doc['dtNasc'] ?? null),
            'cadastro_unico' => $this->texto($doc['cadUnico'] ?? null, 12),

            'municipio_id' => $municipioId,
            'comunidade_id' => $comunidadeId,
            'endereco' => $this->texto($doc['endereco'] ?? null, 150),
            // Coordenada tem parser proprio: a coluna do legado era texto
            // livre com 21 formatos, e o valor sem separador decimal
            // estourava numeric(10,7) derrubando o cadastro inteiro.
            'latitude' => Coordenada::latitude($doc['latitude'] ?? null),
            'longitude' => Coordenada::longitude($doc['longitude'] ?? null),
            'ordem_servico_id' => $this->resolverOrdemServico($doc),

            // Os dois eixos, ortogonais: analise do cadastro e andamento da obra.
            'situacao_analise' => SituacaoAnalise::doLegado($doc['aprovado'] ?? null)->value,
            'situacao_analise_obs' => $this->texto($doc['aprovado_obs'] ?? null, 255),
            'situacao_obra' => SituacaoObra::doLegado($doc['estado'] ?? null)->value,
            'ranqueamento_ordem' => $this->inteiro($doc['ranqueamento_ordem'] ?? null),

            'qtd_pessoas' => $this->inteiro($doc['qtdPessoa'] ?? null),
            'renda' => NormalizaEntrada::moeda($doc['renda'] ?? null),
            'renda_per_capita' => NormalizaEntrada::moeda($doc['rendaPerCapita'] ?? null),

            'possui_deficiencia' => NormalizaEntrada::booleanoSimNao($doc['possui_deficiencia'] ?? null),
            'possui_crianca' => NormalizaEntrada::booleanoSimNao($doc['possui_crianca'] ?? null),
            'data_nascimento_crianca' => $this->data($doc['dtNasc_crianca'] ?? null),
            'possui_idoso' => NormalizaEntrada::booleanoSimNao($doc['possui_idoso'] ?? null),
            'chefiada_mulher' => NormalizaEntrada::booleanoSimNao($doc['chefiada_mulher'] ?? null),

            'tipo_moradia' => TipoMoradia::doLegado($doc['moradia'] ?? null)?->value,
            'tipo_moradia_outro' => $this->texto($doc['outroMoradia'] ?? null, 50),
            'comprimento_telhado' => NormalizaEntrada::decimal($doc['compTelhado'] ?? null),
            'largura_telhado' => NormalizaEntrada::decimal($doc['larguracompTelhado'] ?? null),
            'area_telhado' => NormalizaEntrada::decimal($doc['areaTotalTelhado'] ?? null),
            'comprimento_testada' => NormalizaEntrada::decimal($doc['compTestada'] ?? null),
            'num_caidas_telhado' => $this->inteiro($doc['numCaidaTelhado'] ?? null),
            'cobertura_telhado' => CoberturaTelhado::doLegado($doc['coberturaTelhado'] ?? null)?->value,
            'cobertura_outro' => $this->texto($doc['coberturaOutros'] ?? null, 150),
            'possui_fogao_lenha' => NormalizaEntrada::booleanoSimNao($doc['existeFogaoLenha'] ?? null),
            'medida_telhado_area_fogao' => NormalizaEntrada::decimal($doc['medidaTelhadoAreaFogao'] ?? null),
            'testada_disp_parte_fogao' => NormalizaEntrada::decimal($doc['testadaDispParteFogao'] ?? null),
            'atendido_por_pipa' => NormalizaEntrada::booleanoSimNao($doc['atendPipa'] ?? null),

            'agente_nome' => $this->texto($doc['nomeAgente'] ?? null, 70),
            'agente_cpf' => NormalizaEntrada::cpf($doc['cpfAgente'] ?? null),
            'engenheiro_nome' => $this->texto($doc['nomeEng'] ?? null, 150),
            'engenheiro_crea' => $this->texto($doc['creaEng'] ?? null, 20),

            // Legado: outrObs. `obs1` em algumas versoes do schema.
            'observacoes' => $this->texto($doc['outrObs'] ?? $doc['obs1'] ?? null, 1000),

            // created_by fica NULO de proposito. O user_id do legado NAO mapeia
            // para users do NewSDC: os 43 usuarios que cadastraram sao contas
            // COMPDEC municipais, e o cruzamento por CPF e por email deu ZERO
            // correspondencia. Usar o id cru seria pior que nulo -- id acima de
            // 55 quebra a FK, e id de 1 a 55 atribui o cadastro a outra pessoa,
            // fazendo a trilha do sino avisar quem nao tem nada com aquilo.
            //
            // A informacao nao se perde: user_id continua no
            // cisterna_legado_raw.doc, e legacy_id liga os dois. Quando as
            // contas COMPDEC existirem no NewSDC, um comando de reconciliacao
            // preenche created_by sem reimportar. Ver notas secao 5.7.
            'created_by' => null,

            'legacy_id' => $legacyId,
        ];
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function resolverComunidade(array $doc, int $municipioId, int $legacyId): ?int
    {
        $nome = trim((string) ($doc['comunidade'] ?? ''));

        if ($nome === '') {
            return null;
        }

        // Par (municipio, nome): e o que corrige o defeito C18 do legado, que
        // casava comunidade so pelo nome e misturava municipios.
        $id = CisternaComunidade::where('municipio_id', $municipioId)
            ->where('nome', $nome)
            ->value('id');

        if ($id === null) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "Comunidade \"{$nome}\" nao encontrada no municipio {$municipioId}: FK deixada nula.");

            return null;
        }

        return (int) $id;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function resolverOrdemServico(array $doc): ?int
    {
        $osLegacyId = $doc['os_id'] ?? null;

        if ($osLegacyId === null || $osLegacyId === '' || (int) $osLegacyId === 0) {
            return null;
        }

        $id = CisternaOrdemServico::where('legacy_id', (int) $osLegacyId)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Explode as cinco colunas respAt* em linhas. Substitui, nao acumula, para
     * o refino poder rodar de novo sem multiplicar atendimento.
     *
     * @param  array<string, mixed>  $doc
     */
    private function sincronizarAtendimentos(CisternaBeneficiario $beneficiario, array $doc): void
    {
        $beneficiario->atendimentosPipa()->delete();

        foreach (ResponsavelPipa::cases() as $responsavel) {
            $marcado = NormalizaEntrada::booleanoSimNao($doc[$responsavel->colunaLegado()] ?? null);

            if ($marcado !== true) {
                continue;
            }

            $beneficiario->atendimentosPipa()->create([
                'responsavel' => $responsavel->value,
                'descricao' => $responsavel === ResponsavelPipa::OUTROS
                    ? $this->texto($doc['outroAtendPipa'] ?? null, 255)
                    : null,
            ]);
        }
    }

    /**
     * Dois nomes designam a mesma pessoa? Usado para separar duplicidade de
     * cadastro (nome quase igual) de erro de digitacao de CPF (nomes de pessoas
     * diferentes) -- decisao D25.
     *
     * Limiar de 80% calibrado sobre os 26 casos reais de producao: separa os 22
     * de duplicidade dos 4 de CPF errado. E heuristica, nao verdade -- os casos
     * limitrofes vao para revisao da area (notas 5.1).
     */
    private function pareceMesmaPessoa(?string $a, ?string $b): bool
    {
        $primeiro = NormalizaEntrada::chaveTexto($a);
        $segundo = NormalizaEntrada::chaveTexto($b);

        if ($primeiro === null || $segundo === null) {
            // Sem nome para comparar, nao afirma que sao a mesma pessoa.
            return false;
        }

        if ($primeiro === $segundo) {
            return true;
        }

        similar_text($primeiro, $segundo, $percentual);

        return $percentual >= 80.0;
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiro(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', (string) $valor) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    /**
     * O legado guardava data em varchar(150): ha '0000-00-00', formato
     * brasileiro e string vazia.
     */
    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
