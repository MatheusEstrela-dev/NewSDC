<?php

declare(strict_types=1);

namespace App\Modules\PlanCon\Services;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Compdec\DTOs\PlanoContingenciaDTO;
use App\Modules\Compdec\Models\CompdecPlanoContingencia;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Services\PlanoContingenciaService as CompdecPlanoService;
use App\Modules\PlanCon\DTOs\MunicipioDTO;
use App\Modules\PlanCon\DTOs\PlanConStatsDTO;
use App\Modules\PlanCon\Enums\SituacaoPlano;
use App\Modules\Shared\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Painel estadual de cobertura de Plano de Contingencia.
 *
 * Este modulo NAO tem tabela propria: o dono do dado e o COMPDEC
 * (compdec_planos_contingencia), onde o plano nasce versionado, e ativado e
 * aprovado. Aqui se le o plano ATIVO de cada orgao para responder a pergunta
 * estadual "quais municipios tem plano".
 *
 * Ha tambem o envio pelo proprio municipio (enviarPlanosDoUsuario): o orgao
 * sai do usuario logado, nunca do request, e a gravacao e DELEGADA ao servico
 * do COMPDEC. Assim existe uma porta a mais, mas nao uma segunda copia das
 * regras de versao e ativacao.
 */
class PlanoContingenciaService extends BaseService
{
    public function __construct(
        private readonly CompdecPlanoService $planoCompdec = new CompdecPlanoService(),
    ) {
    }

    /**
     * Envio de plano pelo proprio municipio: o orgao vem do usuario logado,
     * nunca do request, para ninguem enviar plano em nome de outro municipio.
     *
     * Cada envio cria uma VERSAO nova e a ativa -- a anterior continua no
     * historico do orgao. A gravacao e delegada ao servico do COMPDEC, dono do
     * dado, para nao existir uma segunda copia das regras de versao/ativacao.
     *
     * @param  UploadedFile[]  $arquivos
     * @return array{enviados: int, erros: array<string, string>}
     */
    public function enviarPlanosDoUsuario(
        User $usuario,
        array $arquivos,
        ?string $versao = null,
        ?string $observacoes = null,
        ?int $municipioId = null,
    ): array {
        $orgao = $this->resolverOrgaoDeEnvio($usuario, $municipioId);

        $resultado = ['enviados' => 0, 'erros' => []];

        foreach ($arquivos as $arquivo) {
            $nome = $arquivo->getClientOriginalName();

            try {
                $this->planoCompdec->criar(
                    $orgao->id,
                    new PlanoContingenciaDTO(
                        versao: $versao ?: $this->proximaVersao($orgao->id),
                        observacoes: $observacoes,
                        ativo: true,
                        tamanhoBytes: $arquivo->getSize(),
                    ),
                    $arquivo,
                );

                $resultado['enviados']++;
            } catch (Throwable $e) {
                $resultado['erros'][$nome] = 'Falha ao gravar o arquivo.';
                report($e);
            }
        }

        return $resultado;
    }

    /**
     * Decide em qual orgao o plano sera gravado.
     *
     * Usuario COM orgao (municipal) grava sempre no proprio, e o municipio_id
     * que venha no request e IGNORADO -- e o que impede um municipio de enviar
     * plano em nome de outro.
     *
     * Usuario SEM orgao (CEDEC/estadual) precisa escolher o municipio, porque
     * ele atua em nome de qualquer um -- caso real de plano que chega por
     * e-mail e a CEDEC sobe pelo municipio.
     */
    private function resolverOrgaoDeEnvio(User $usuario, ?int $municipioId): Orgao
    {
        $proprio = $this->resolverOrgaoDoUsuario($usuario);

        if ($proprio !== null) {
            return $proprio;
        }

        // Nao conseguir resolver o orgao NAO e o mesmo que ser estadual:
        // resolverOrgaoDoUsuario() tambem devolve null quando o usuario tem
        // 2+ orgaos e nenhum marcado como principal. Deixar cair no ramo de
        // baixo daria a um usuario MUNICIPAL o direito de enviar plano em nome
        // de qualquer cidade - exatamente o que este metodo existe para impedir.
        //
        // Orgao sem municipio (CEDEC e REDEC, os 7 do cadastro) nao conta:
        // esse e o estadual legitimo, que escolhe o municipio de proposito.
        if ($usuario->orgaos()->whereNotNull('municipio_id')->exists()) {
            throw new RuntimeException(
                'Sua conta esta vinculada a mais de um orgao e nenhum deles esta definido como principal. Peca ao gestor para definir o orgao principal antes de enviar o plano.'
            );
        }

        if ($municipioId === null) {
            throw new RuntimeException(
                'Escolha o municipio: sua conta nao esta vinculada a um orgao COMPDEC.'
            );
        }

        $orgao = Orgao::query()
            ->where('municipio_id', $municipioId)
            ->where('tipo', 'compdec')
            ->first();

        if ($orgao === null) {
            throw new RuntimeException('Nao ha COMPDEC cadastrado para o municipio escolhido.');
        }

        return $orgao;
    }

    /**
     * Municipios que a conta estadual pode escolher no envio: os que tem
     * COMPDEC cadastrado. Vazio para usuario municipal, que nao escolhe.
     *
     * @return array<int, array{id: int, nome: string}>
     */
    public function municipiosParaEnvio(User $usuario): array
    {
        if ($this->resolverOrgaoDoUsuario($usuario) !== null) {
            return [];
        }

        return DB::table('municipios as m')
            ->join('compdec_orgaos as o', function ($join): void {
                $join->on('o.municipio_id', '=', 'm.id')
                    ->where('o.tipo', '=', 'compdec')
                    ->whereNull('o.deleted_at');
            })
            ->distinct()
            ->orderBy('m.nome')
            ->get(['m.id', 'm.nome'])
            ->map(fn ($m) => ['id' => (int) $m->id, 'nome' => $m->nome])
            ->all();
    }

    /**
     * Orgao COMPDEC do usuario: o principal, senao o marcado como principal no
     * vinculo, senao o unico que ele tem. Com mais de um e sem principal
     * definido, devolve null em vez de escolher no escuro.
     */
    public function resolverOrgaoDoUsuario(User $usuario): ?Orgao
    {
        $usuario->loadMissing('orgaoPrincipal');

        $orgao = $usuario->orgaoPrincipal
            ?? $usuario->orgaos()->wherePivot('is_principal', true)->first()
            ?? ($usuario->orgaos()->count() === 1 ? $usuario->orgaos()->first() : null);

        return $orgao?->municipio_id === null ? null : $orgao;
    }

    /**
     * v1, v2, v3... contando o que o orgao ja tem. O legado gravava a versao
     * como texto livre digitado pelo usuario, o que gerou '1', '2' e vazio.
     */
    private function proximaVersao(int $orgaoId): string
    {
        $total = CompdecPlanoContingencia::query()->doOrgao($orgaoId)->count();

        return 'v'.($total + 1);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->findAll($filters, $perPage);
    }

    public function findAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $termo) => $this->aplicarBusca($q, $termo)
            )
            ->when(
                $filters['municipio_id'] ?? null,
                fn (Builder $q, $id) => $q->where('municipios.id', $id)
            )
            ->orderByRaw('planos.enviado_em DESC NULLS LAST')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Busca por nome, codigo IBGE ou codigo do municipio no legado.
     *
     * A tela mostra as tres colunas, entao o usuario digita qualquer uma das
     * tres. Antes so o nome casava e procurar por "3108602" devolvia lista
     * vazia, parecendo que o filtro estava quebrado.
     */
    private function aplicarBusca(Builder $query, string $termo, bool $comCodigoLegado = true): Builder
    {
        $termo = trim($termo);
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        return $query->where(function (Builder $q) use ($termo, $digitos, $comCodigoLegado): void {
            $q->where('municipios.nome', 'ilike', "%{$termo}%");

            if ($digitos !== '') {
                $q->orWhere('municipios.codigo_ibge', 'like', "{$digitos}%");

                // Codigo do legado (coluna "Cod. Mun."). So existe na listagem
                // de planos; na de municipios sem plano a coluna nem e exibida.
                //
                // Parametro e nao propriedade: como flag de instancia, uma
                // chamada a getMunicipiosSemPlano() a deixava em false para
                // sempre e, sob Octane (instancia viva entre requests), a busca
                // por codigo legado morria em todas as telas seguintes.
                if ($comCodigoLegado) {
                    $q->orWhereRaw('CAST(planos.legacy_municipio_id AS TEXT) = ?', [$digitos]);
                }
            }
        });
    }

    public function getStatistics(): array
    {
        return $this->calcularEstatisticas();
    }

    public function getStats(): PlanConStatsDTO
    {
        $stats = $this->calcularEstatisticas();

        return new PlanConStatsDTO(
            totalMunicipios: $stats['totalMunicipios'],
            municipiosComPlano: $stats['municipiosComPlano'],
            municipiosSemPlano: $stats['municipiosSemPlano'],
            percentualComPlano: $stats['percentualComPlano'],
            totalPlanos: $stats['totalPlanos'],
            planosRegulares: $stats['planosRegulares'],
            planosIrregulares: $stats['planosIrregulares'],
            percentualRegulares: $stats['percentualRegulares'],
        );
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function listMunicipiosComPlano(int $perPage = 15, array $filters = []): array
    {
        $paginator = $this->getMunicipiosComPlano($perPage, $filters);

        return $this->formatarPagina($paginator, temPlano: true);
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function listMunicipiosSemPlano(int $perPage = 15, array $filters = []): array
    {
        $paginator = $this->getMunicipiosSemPlano($perPage, $filters);

        return $this->formatarPagina($paginator, temPlano: false);
    }

    /**
     * Lista TODAS as versoes de plano, uma linha por plano, como a tela do
     * legado (`compdec/plano/listacomplano`): um municipio que enviou tres
     * versoes aparece em tres linhas. Ordena por municipio e, dentro dele, da
     * versao mais nova para a mais antiga.
     */
    public function getMunicipiosComPlano(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery(apenasAtivos: false)
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $termo) => $this->aplicarBusca($q, $termo)
            )
            ->orderBy('municipios.nome')
            ->orderByRaw('planos.enviado_em DESC NULLS LAST')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getMunicipiosSemPlano(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Esta consulta nao passa por baseQuery(): nao existe alias `planos`
        // aqui, entao a busca fica em nome + IBGE.
        return DB::table('municipios')
            ->select(['municipios.id', 'municipios.nome', 'municipios.codigo_ibge'])
            ->where('municipios.uf', 'MG')
            ->whereNotIn('municipios.id', $this->municipiosComPlanoSubquery())
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $termo) => $this->aplicarBusca($q, $termo, comCodigoLegado: false)
            )
            ->orderBy('municipios.nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Abre o plano no navegador (inline, nao download), de onde quer que ele
     * esteja:
     *
     *  1. media do MediaLibrary, quando o plano foi enviado pelo sistema novo
     *     ou ja teve os bytes importados;
     *  2. direto do disco do legado, pelo nome guardado em `legacy_arquivo`.
     *     Isso e o que permite validar os 619 planos migrados apenas com o
     *     bind mount da pasta do legado, sem rodar copia nenhuma.
     */
    public function downloadPlano(CompdecPlanoContingencia $plano): Response
    {
        $media = $plano->getFirstMedia(CompdecPlanoContingencia::MEDIA_ARQUIVO);

        if ($media !== null) {
            return $this->respostaInline(
                $media->getPath(),
                $media->file_name,
                $media->mime_type,
            );
        }

        $caminho = $this->planoCompdec->localizarArquivoLegado(
            (string) $plano->legacy_arquivo
        );

        abort_if(
            $caminho === null,
            404,
            'Arquivo nao encontrado. O registro veio do sistema legado e o arquivo ainda nao esta acessivel.'
        );

        return $this->respostaInline($caminho, basename($caminho));
    }

    /**
     * Content-Disposition inline: o navegador abre o PDF na aba em vez de
     * baixar. Extensoes que o navegador nao renderiza (doc/docx/odt) caem em
     * download pelo proprio navegador, sem precisar de tratamento aqui.
     */
    private function respostaInline(string $caminho, string $nome, ?string $mime = null): Response
    {
        $mime ??= match (mb_strtolower(pathinfo($nome, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'odt' => 'application/vnd.oasis.opendocument.text',
            default => 'application/octet-stream',
        };

        return new Response(file_get_contents($caminho) ?: '', 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($nome).'"',
        ]);
    }

    /**
     * Municipio + plano ATIVO do seu COMPDEC. Um municipio so aparece quando
     * tem orgao COMPDEC com plano ativo nao deletado.
     */
    private function baseQuery(bool $apenasAtivos = true): Builder
    {
        return DB::table('municipios')
            ->join('compdec_orgaos as orgaos', function ($join): void {
                $join->on('orgaos.municipio_id', '=', 'municipios.id')
                    ->where('orgaos.tipo', '=', 'compdec')
                    ->whereNull('orgaos.deleted_at');
            })
            ->join('compdec_planos_contingencia as planos', function ($join) use ($apenasAtivos): void {
                $join->on('planos.orgao_id', '=', 'orgaos.id')
                    ->whereNull('planos.deleted_at');

                if ($apenasAtivos) {
                    $join->where('planos.ativo', '=', true);
                }
            })
            // O nome do arquivo esta na media do Spatie; nos planos ainda sem
            // bytes copiados do legado, cai para legacy_arquivo, que e o nome
            // que o usuario reconhece na tela antiga.
            ->leftJoin('media', function ($join): void {
                $join->on('media.model_id', '=', 'planos.id')
                    ->where('media.model_type', '=', CompdecPlanoContingencia::class)
                    ->where('media.collection_name', '=', CompdecPlanoContingencia::MEDIA_ARQUIVO);
            })
            ->select([
                'municipios.id',
                'municipios.nome',
                'municipios.codigo_ibge',
                'planos.id as plano_id',
                'planos.versao',
                'planos.enviado_em as data_ultima_atualizacao',
                'planos.legacy_municipio_id as codigo_municipio',
                DB::raw('COALESCE(media.name, media.file_name, planos.legacy_arquivo) as arquivo'),
                // Distingue "tem nome do arquivo" de "tem o arquivo": os planos
                // migrados guardam o nome do legado, mas os bytes so existem
                // depois de copiados para a media.
                DB::raw('(media.id IS NOT NULL) as tem_arquivo'),
                DB::raw($this->expressaoSituacao().' as situacao_plano'),
            ]);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function municipiosComPlanoSubquery(): Builder
    {
        // Sem filtrar por `ativo`: "ter plano" e ter QUALQUER versao nao
        // apagada, que e o mesmo criterio de getMunicipiosComPlano()
        // (baseQuery com apenasAtivos: false). Exigir ativo aqui fazia um
        // municipio cujas versoes estao todas inativas aparecer nas DUAS
        // listas ao mesmo tempo.
        return DB::table('compdec_orgaos as orgaos')
            ->join('compdec_planos_contingencia as planos', function ($join): void {
                $join->on('planos.orgao_id', '=', 'orgaos.id')
                    ->whereNull('planos.deleted_at');
            })
            ->whereNotNull('orgaos.municipio_id')
            ->whereNull('orgaos.deleted_at')
            ->select('orgaos.municipio_id');
    }

    /**
     * Regular = plano ativo enviado ha menos de N anos.
     *
     * O legado nao tinha essa nocao: so um flag `com_comdec.plano_cont`, que
     * ja estava defasado (356 marcados contra 403 uploads reais). Plano de
     * contingencia exige revisao periodica, entao a idade do arquivo e o que
     * de fato diz se o municipio esta em dia.
     */
    private function expressaoSituacao(): string
    {
        $anos = (int) config('compdec.plano_validade_anos', 5);

        return sprintf(
            "CASE WHEN planos.enviado_em >= (NOW() - INTERVAL '%d years') THEN '%s' ELSE '%s' END",
            $anos,
            SituacaoPlano::REGULAR->value,
            SituacaoPlano::IRREGULAR->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calcularEstatisticas(): array
    {
        $totalMunicipios = Municipio::query()->where('uf', 'MG')->count();

        $cobertura = DB::query()
            ->fromSub($this->baseQuery(), 'cobertura')
            ->selectRaw(sprintf(
                'COUNT(DISTINCT id) AS municipios_com_plano,
                 COUNT(*) FILTER (WHERE situacao_plano = %s) AS regulares',
                DB::getPdo()->quote(SituacaoPlano::REGULAR->value)
            ))
            ->first();

        $municipiosComPlano = (int) ($cobertura->municipios_com_plano ?? 0);
        $planosRegulares = (int) ($cobertura->regulares ?? 0);

        // Total de planos conta o acervo inteiro (todas as versoes), nao so os
        // ativos: e o volume de documentos que o sistema guarda.
        $totalPlanos = CompdecPlanoContingencia::query()->count();
        $planosAtivos = CompdecPlanoContingencia::query()->where('ativo', true)->count();

        return [
            'totalMunicipios' => $totalMunicipios,
            'municipiosComPlano' => $municipiosComPlano,
            'municipiosSemPlano' => max($totalMunicipios - $municipiosComPlano, 0),
            'percentualComPlano' => $this->percentual($municipiosComPlano, $totalMunicipios),
            'totalPlanos' => $totalPlanos,
            'planosRegulares' => $planosRegulares,
            'planosIrregulares' => max($planosAtivos - $planosRegulares, 0),
            'percentualRegulares' => $this->percentual($planosRegulares, $planosAtivos),
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    private function formatarPagina(LengthAwarePaginator $paginator, bool $temPlano): array
    {
        $dtos = collect($paginator->items())->map(fn ($item) => MunicipioDTO::fromArray([
            'id' => $item->id,
            'nome' => $item->nome,
            'codigo_ibge' => $item->codigo_ibge,
            'tem_plano' => $temPlano,
            'situacao_plano' => $item->situacao_plano ?? null,
            'data_ultima_atualizacao' => $item->data_ultima_atualizacao ?? null,
            'codigo_municipio' => $item->codigo_municipio ?? null,
            'plano_id' => $item->plano_id ?? null,
            'versao' => $item->versao ?? null,
            'arquivo' => $item->arquivo ?? null,
            'tem_arquivo' => (bool) ($item->tem_arquivo ?? false),
        ])->toArray())->all();

        return [
            'data' => $dtos,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    private function percentual(int $parte, int $total): float
    {
        return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
    }
}
