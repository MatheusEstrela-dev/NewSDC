<?php

declare(strict_types=1);

namespace App\Modules\Pmda\Services;

use App\Modules\Compdec\Enums\StatusOrgao;
use App\Modules\Compdec\Enums\TipoOrgao;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Pmda\Enums\PmdaStatus;
use App\Modules\Pmda\Enums\SolicitacaoComunidadeStatus;
use App\Modules\Pmda\Models\Comunidade;
use App\Modules\Pmda\Models\ComunidadeSolicitacao;
use App\Modules\Pmda\Models\PmdaComunidade;
use App\Modules\Pmda\Models\PmdaCompdecMembro;
use App\Modules\Pmda\Models\PmdaPlano;
use App\Modules\Pmda\Models\PmdaPonto;
use App\Modules\Pmda\Models\PmdaRepresentante;
use App\Modules\Shared\BaseService;
use App\Support\Cache\CachedRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PmdaPlanoService extends BaseService
{
    /**
     * Status que consideram o municipio com PMDA "pendente" (em aberto),
     * impedindo abrir outro. Espelha o legado (gestaocedec verificaCriarPmda:
     * status IN 0,2) ampliado com COMPLETO, que e parte do ciclo de edicao.
     *
     * @return list<string>
     */
    public static function statusPendente(): array
    {
        return [
            PmdaStatus::RASCUNHO->value,
            PmdaStatus::COMPLETO->value,
            PmdaStatus::EM_ANALISE->value,
        ];
    }

    public function criar(int $municipioId, int $userId, array $data): PmdaPlano
    {
        $pendente = PmdaPlano::query()
            ->where('municipio_id', $municipioId)
            ->whereIn('status', self::statusPendente())
            ->first();

        if ($pendente !== null) {
            throw new \DomainException(
                'Este município já possui um PMDA em aberto ('.$pendente->status->getLabel().
                ', protocolo '.($pendente->protocolo ?? '—').'). Conclua, cancele ou edite o existente antes de criar outro.'
            );
        }

        return PmdaPlano::create(array_merge($data, [
            'municipio_id' => $municipioId,
            'status'       => PmdaStatus::RASCUNHO,
            'created_by'   => $userId,
        ]));
    }

    public function atualizar(PmdaPlano $plano, array $data, int $userId): PmdaPlano
    {
        $plano->update(array_merge($data, [
            'updated_by'          => $userId,
            'dt_ultima_alteracao' => now(),
        ]));

        return $plano->refresh();
    }

    /**
     * Exclui o plano (e dependencias em cascata). Regra de negocio: CEDEC (manager) so
     * exclui PMDA ATENDIDO; admin/super-admin ($bypassStatus=true) excluem qualquer status.
     */
    public function excluir(PmdaPlano $plano, bool $bypassStatus = false): void
    {
        if (! $bypassStatus && $plano->status !== PmdaStatus::ATENDIDO) {
            throw new \DomainException('Só é possível excluir um PMDA com status "Atendido".');
        }

        $plano->delete();
    }

    public function listar(array $filtros = [], int $perPage = 15, ?int $page = null): LengthAwarePaginator
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        if (! empty($filtros['buscar'])) {
            $termo = $filtros['buscar'];
            $query->where(function ($q) use ($termo) {
                $q->where('protocolo', 'ilike', "%{$termo}%")
                    ->orWhereHas('municipio', fn ($m) => $m->where('nome', 'ilike', "%{$termo}%"));
            });
        }
        if (! empty($filtros['data_inicio'])) {
            $query->whereDate('data', '>=', $filtros['data_inicio']);
        }
        if (! empty($filtros['data_fim'])) {
            $query->whereDate('data', '<=', $filtros['data_fim']);
        }

        return $this->paginate($query, $perPage, $page);
    }

    /**
     * Contagens dos cards do indice de planos. Publico e auto-contido de
     * proposito: e resolvido via app() dentro de uma task do
     * Concurrency::tasks(), que pode executar em outro processo.
     *
     * @return array<string, int>
     */
    public function statisticsIndex(?int $municipioId = null): array
    {
        // Ao contrario de listar()/exportar(), que recebem o recorte dentro de
        // $filtros, aqui ele vem explicito: nao existe array de filtros para
        // carregar. Null = perfil estadual, sem recorte.
        //
        // UM GROUP BY, nao 4 SELECT count(*). Este metodo roda dentro de um task
        // worker do Swoole e o worker HTTP fica BLOQUEADO esperando o resultado
        // (Concurrency wait_ms = 5s), entao cada round-trip a menos sai do caminho
        // quente de todo mundo. `toBase()` aplica os scopes (SoftDeletes incluso) e
        // devolve o query builder cru: sem ele o cast de `status` para PmdaStatus
        // viraria chave de array e o pluck quebraria.
        $porStatus = PmdaPlano::query()
            ->when($municipioId !== null, fn ($q) => $q->where('municipio_id', $municipioId))
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total'     => (int) $porStatus->sum(),
            'emEdicao'  => (int) ($porStatus[PmdaStatus::RASCUNHO->value] ?? 0),
            'emAnalise' => (int) ($porStatus[PmdaStatus::EM_ANALISE->value] ?? 0),
            'aprovados' => (int) ($porStatus[PmdaStatus::APROVADO->value] ?? 0),
        ];
    }

    /** Linhas para exportacao CSV (respeita filtros). */
    public function exportar(array $filtros = []): array
    {
        $query = PmdaPlano::query()->with('municipio')->latest('data');
        $query = $this->applyFilters($query, $filtros, ['municipio_id', 'status']);

        return $query->get()->map(fn (PmdaPlano $p) => [
            'Protocolo' => $p->protocolo,
            'Municipio' => $p->municipio?->nome,
            'Situacao'  => $p->status->getLabel(),
            'Criacao'   => $p->data?->format('d/m/Y'),
        ])->all();
    }

    /**
     * Recalcula RASCUNHO <-> COMPLETO conforme comunidades e representantes.
     * Nao mexe em planos ja submetidos (EM_ANALISE/APROVADO/ATENDIDO e terminais).
     */
    public const REPRESENTANTES_POR_COMUNIDADE = 3;

    public function recalcularStatus(PmdaPlano $plano): PmdaPlano
    {
        $intocaveis = [
            PmdaStatus::EM_ANALISE, PmdaStatus::APROVADO, PmdaStatus::ATENDIDO,
            PmdaStatus::ARQUIVADO, PmdaStatus::ANULADO, PmdaStatus::CANCELADO, PmdaStatus::ENCERRADO,
        ];
        if (in_array($plano->status, $intocaveis, true)) {
            return $plano;
        }

        $totComunidades = $plano->comunidades()->count();
        $todasComRepresentantes = $totComunidades > 0
            && $plano->comunidades()
                ->withCount('representantes')
                ->get()
                ->every(fn ($c) => $c->representantes_count >= self::REPRESENTANTES_POR_COMUNIDADE);

        $novo = $todasComRepresentantes ? PmdaStatus::COMPLETO : PmdaStatus::RASCUNHO;
        if ($plano->status !== $novo) {
            $plano->update(['status' => $novo]);
        }

        return $plano->refresh();
    }

    /**
     * Envia o PMDA para analise da CEDEC (Etapa 7). Exige os anexos obrigatorios
     * (Termo de Compromisso + Oficio) e que o plano ainda esteja em edicao.
     */
    public function enviar(PmdaPlano $plano, int $userId): PmdaPlano
    {
        if (! in_array($plano->status, [PmdaStatus::RASCUNHO, PmdaStatus::COMPLETO], true)) {
            throw new \DomainException('Este PMDA já foi enviado ou não está mais em edição.');
        }

        // Alinhado ao legado (gestaocedec): so envia quando COMPLETO — cada comunidade
        // precisa de REPRESENTANTES_POR_COMUNIDADE representantes. Recalcula para garantir
        // o status atual antes de barrar.
        $plano = $this->recalcularStatus($plano);
        if ($plano->status !== PmdaStatus::COMPLETO) {
            throw new \DomainException(
                'Complete o PMDA antes de enviar: é preciso ao menos 1 comunidade e '.
                self::REPRESENTANTES_POR_COMUNIDADE.' representantes por comunidade.'
            );
        }

        if ($plano->getMedia(PmdaPlano::MEDIA_TERMO)->isEmpty() || $plano->getMedia(PmdaPlano::MEDIA_OFICIO)->isEmpty()) {
            throw new \DomainException('Anexe o Termo de Compromisso e o Ofício de Solicitação (PDF) antes de enviar.');
        }

        $plano->update([
            'status'              => PmdaStatus::EM_ANALISE,
            'dt_analise'          => now(),
            'resp_homolog'        => \App\Models\User::find($userId)?->name, // quem enviou (municipio), como no legado
            'dt_ultima_alteracao' => now(),
            'updated_by'          => $userId,
        ]);

        return $plano->refresh();
    }

    /** Fila de analise CEDEC: planos EM_ANALISE (mais antigos primeiro). */
    public function pendentesAnalise(array $filtros = [], int $perPage = 15, ?int $page = null): LengthAwarePaginator
    {
        $query = PmdaPlano::query()
            ->with('municipio')
            ->where('status', PmdaStatus::EM_ANALISE->value)
            ->oldest('dt_analise');
        $query = $this->applyFilters($query, $filtros, ['municipio_id']);

        return $this->paginate($query, $perPage, $page);
    }

    /** Aprova o PMDA (EM_ANALISE -> APROVADO). */
    public function aprovar(PmdaPlano $plano, int $userId, ?string $resp = null): PmdaPlano
    {
        $this->garantirEmAnalise($plano);

        $plano->update([
            'status'              => PmdaStatus::APROVADO,
            'data_aprov'          => now(),
            'dt_estado'           => now(),
            'resp_estado'         => $resp ?: (\App\Models\User::find($userId)?->name),
            'dt_ultima_alteracao' => now(),
            'updated_by'          => $userId,
        ]);

        return $plano->refresh();
    }

    /** Arquiva/rejeita o PMDA (EM_ANALISE -> ARQUIVADO) com motivo. */
    public function arquivar(PmdaPlano $plano, string $motivo, int $userId): PmdaPlano
    {
        $this->garantirEmAnalise($plano);

        $plano->update([
            'status'              => PmdaStatus::ARQUIVADO,
            'motivo_analise'      => $motivo,
            'dt_estado'           => now(),
            'resp_estado'         => \App\Models\User::find($userId)?->name,
            'dt_ultima_alteracao' => now(),
            'updated_by'          => $userId,
        ]);

        return $plano->refresh();
    }

    /** Devolve o PMDA ao municipio para correcao (EM_ANALISE -> RASCUNHO) com motivo. */
    public function pedirAlteracao(PmdaPlano $plano, string $motivo, int $userId): PmdaPlano
    {
        $this->garantirEmAnalise($plano);

        $plano->update([
            'status'              => PmdaStatus::RASCUNHO,
            'pedido_altera'       => true,
            'alterar_com'         => true, // legado: libera edicao de comunidades apos devolucao
            'motivo_analise'      => $motivo,
            'resp_estado'         => \App\Models\User::find($userId)?->name, // quem devolveu (CEDEC)
            'dt_estado'           => now(),
            'dt_ultima_alteracao' => now(),
            'updated_by'          => $userId,
        ]);

        return $plano->refresh();
    }

    /** Garante que o plano esta EM_ANALISE (unico estado analisavel pela CEDEC). */
    private function garantirEmAnalise(PmdaPlano $plano): void
    {
        if ($plano->status !== PmdaStatus::EM_ANALISE) {
            throw new \DomainException('Este PMDA não está em análise (status atual: '.$plano->status->getLabel().').');
        }
    }
}

class PmdaCopiaService
{
    private const DATA_MINIMA_COPIA = '2021-04-03';

    public function copiar(PmdaPlano $origem, int $userId): PmdaPlano
    {
        if ($origem->data->lte(Carbon::parse(self::DATA_MINIMA_COPIA))) {
            throw new \DomainException('PMDA anterior a 03/04/2021 não pode ser copiado.');
        }

        if (! $origem->status->permiteCopia()) {
            throw new \DomainException('Status atual não permite cópia.');
        }

        $copia = $origem->replicate(['protocolo', 'status', 'data', 'data_aprov', 'dt_analise']);
        $copia->status     = PmdaStatus::RASCUNHO;
        $copia->data       = now();
        $copia->protocolo  = null; // regerado pelo Observer
        $copia->created_by = $userId;
        $copia->save();

        $this->duplicarComunidades($origem, $copia);

        return $copia->refresh();
    }

    private function duplicarComunidades(PmdaPlano $origem, PmdaPlano $copia): void
    {
        foreach ($origem->comunidades()->with('representantes')->get() as $comunidade) {
            $novaComunidade = $comunidade->replicate(['pmda_plano_id']);
            $novaComunidade->pmda_plano_id = $copia->id;
            $novaComunidade->save();

            foreach ($comunidade->representantes as $representante) {
                $novoRepresentante = $representante->replicate(['pmda_comunidade_id']);
                $novoRepresentante->pmda_comunidade_id = $novaComunidade->id;
                $novoRepresentante->save();
            }
        }
    }
}

class ComunidadeService
{
    /**
     * Status em que uma comunidade e considerada "em uso" e nao pode estar em outro plano.
     * Metodo (e nao const) porque enum->value em constant expression exige PHP 8.2+.
     *
     * @return list<string>
     */
    private static function statusAtivos(): array
    {
        return [
            PmdaStatus::RASCUNHO->value,
            PmdaStatus::COMPLETO->value,
            PmdaStatus::EM_ANALISE->value,
            PmdaStatus::APROVADO->value,
            PmdaStatus::ATENDIDO->value,
        ];
    }

    public function __construct(private readonly PmdaPlanoService $planos) {}

    public function adicionar(PmdaPlano $plano, array $data): PmdaComunidade
    {
        $comunidadeId = $data['comunidade_id'] ?? null;

        // Vinculo de comunidade ja cadastrada: dados-mestre (nome, coordenadas)
        // sao autoritativos; o municipio so informa populacao/distancia do plano.
        if ($comunidadeId !== null) {
            $mestre = Comunidade::where('municipio_id', $plano->municipio_id)
                ->where('ativo', true)
                ->find((int) $comunidadeId);

            if ($mestre === null) {
                throw new \DomainException('Comunidade não encontrada para este município.');
            }
            if ($this->jaEmPlanoAtivo((int) $comunidadeId, $plano->id)) {
                throw new \DomainException('Esta comunidade já está vinculada a outro PMDA ativo.');
            }

            $data['nome']      = $mestre->nome;
            $data['latitude']  = $data['latitude'] ?? $mestre->latitude;
            $data['longitude'] = $data['longitude'] ?? $mestre->longitude;

            // Ultima referencia conhecida da comunidade, so como ponto de
            // partida: o que o municipio informar no plano sempre vence. E o
            // destino dos campos que o legado guardava em pip_comunidade
            // (trecho_pav/trecho_n_pav/pop_atendida) e que aqui pertencem ao
            // vinculo, nao ao catalogo - ver ComunidadeLegadoService.
            //
            // array_key_exists e nao ??: o middleware ConvertEmptyStringsToNull
            // transforma campo apagado de proposito em null, e com ?? o valor
            // do catalogo voltaria por cima justamente de quem quis zerar. So
            // pre-preenche o que o formulario nao mandou.
            foreach (['trecho_pav', 'trecho_n_pav', 'pop_atendida'] as $campo) {
                if (! array_key_exists($campo, $data)) {
                    $data[$campo] = $mestre->{$campo};
                }
            }
        }

        $data['municipio_id'] = $plano->municipio_id;

        $comunidade = $plano->comunidades()->create($data);
        $this->planos->recalcularStatus($plano);

        return $comunidade;
    }

    public function remover(PmdaComunidade $comunidade): void
    {
        $plano = $comunidade->plano;
        $comunidade->delete();
        if ($plano) {
            $this->planos->recalcularStatus($plano);
        }
    }

    /**
     * Comunidades-mestre ativas do municipio do plano ainda nao vinculadas a ele.
     * Alimenta o seletor "Adicionar Comunidade" da aba de distribuicao.
     */
    public function disponiveis(PmdaPlano $plano): Collection
    {
        $jaVinculadas = $plano->comunidades()
            ->whereNotNull('comunidade_id')
            ->pluck('comunidade_id')
            ->all();

        return Comunidade::query()
            ->where('municipio_id', $plano->municipio_id)
            ->where('ativo', true)
            ->when($jaVinculadas, fn ($q) => $q->whereNotIn('id', $jaVinculadas))
            ->orderBy('nome')
            ->get();
    }

    private function jaEmPlanoAtivo(int $comunidadeId, int $planoIdAtual): bool
    {
        return PmdaComunidade::query()
            ->where('comunidade_id', $comunidadeId)
            ->where('pmda_plano_id', '!=', $planoIdAtual)
            ->whereHas('plano', fn ($q) => $q->whereIn('status', self::statusAtivos()))
            ->exists();
    }
}

/**
 * Fluxo de solicitacao de inclusao de comunidade (municipio) e analise (CEDEC).
 */
class ComunidadeSolicitacaoService
{
    /** Registra uma solicitacao pendente vinda do municipio (aba de distribuicao). */
    public function criar(PmdaPlano $plano, array $data, int $userId): ComunidadeSolicitacao
    {
        $nome = trim($data['nome']);

        $duplicada = ComunidadeSolicitacao::query()
            ->where('municipio_id', $plano->municipio_id)
            ->where('status', SolicitacaoComunidadeStatus::PENDENTE->value)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->exists();

        if ($duplicada) {
            throw new \DomainException('Já existe uma solicitação pendente com este nome para o município.');
        }

        $jaCadastrada = Comunidade::query()
            ->where('municipio_id', $plano->municipio_id)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->exists();

        if ($jaCadastrada) {
            throw new \DomainException('Esta comunidade já está cadastrada. Use "Adicionar Comunidade".');
        }

        return ComunidadeSolicitacao::create([
            'municipio_id'   => $plano->municipio_id,
            'pmda_plano_id'  => $plano->id,
            'nome'           => $nome,
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'status'         => SolicitacaoComunidadeStatus::PENDENTE,
            'solicitado_por' => $userId,
        ]);
    }

    /** Historico de solicitacoes do municipio (exibido na aba de distribuicao). */
    public function historicoDoMunicipio(int $municipioId): Collection
    {
        return ComunidadeSolicitacao::query()
            ->where('municipio_id', $municipioId)
            ->latest()
            ->get();
    }

    /** Fila de pendencias para a CEDEC (com filtro opcional por municipio). */
    public function pendentes(array $filtros = [], int $perPage = 15, ?int $page = null): LengthAwarePaginator
    {
        return ComunidadeSolicitacao::query()
            // plano e solicitadoPor alimentam o detalhamento que a CEDEC le antes
            // de decidir; carregados aqui para nao gerar N+1 na fila paginada.
            ->with(['municipio', 'plano', 'solicitadoPor'])
            ->where('status', SolicitacaoComunidadeStatus::PENDENTE->value)
            ->when($filtros['municipio_id'] ?? null, fn ($q, $m) => $q->where('municipio_id', $m))
            ->oldest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Aprova a solicitacao: promove para o registro mestre (comunidades) e
     * marca a solicitacao como APROVADA. Idempotente no nome (municipio, nome).
     */
    public function aprovar(ComunidadeSolicitacao $solicitacao, int $userId): Comunidade
    {
        if ($solicitacao->status !== SolicitacaoComunidadeStatus::PENDENTE) {
            throw new \DomainException('Esta solicitação já foi analisada.');
        }

        return DB::transaction(function () use ($solicitacao, $userId) {
            $comunidade = Comunidade::firstOrCreate(
                [
                    'municipio_id' => $solicitacao->municipio_id,
                    'nome'         => $solicitacao->nome,
                ],
                [
                    'latitude'   => $solicitacao->latitude,
                    'longitude'  => $solicitacao->longitude,
                    'ativo'      => true,
                    'created_by' => $userId,
                ]
            );

            $solicitacao->update([
                'status'        => SolicitacaoComunidadeStatus::APROVADA,
                'comunidade_id' => $comunidade->id,
                'analisado_por' => $userId,
                'analisado_em'  => now(),
            ]);

            return $comunidade;
        });
    }

    /** Rejeita a solicitacao com motivo (visivel ao municipio no historico). */
    public function rejeitar(ComunidadeSolicitacao $solicitacao, string $motivo, int $userId): void
    {
        if ($solicitacao->status !== SolicitacaoComunidadeStatus::PENDENTE) {
            throw new \DomainException('Esta solicitação já foi analisada.');
        }

        $solicitacao->update([
            'status'          => SolicitacaoComunidadeStatus::REJEITADA,
            'motivo_rejeicao' => $motivo,
            'analisado_por'   => $userId,
            'analisado_em'    => now(),
        ]);
    }
}

class RepresentanteService
{
    public function __construct(private readonly PmdaPlanoService $planos) {}

    public function adicionar(PmdaComunidade $comunidade, array $data): PmdaRepresentante
    {
        $representante = $comunidade->representantes()->create($data);
        $this->recalcular($comunidade);

        return $representante;
    }

    public function atualizar(PmdaRepresentante $representante, array $data): PmdaRepresentante
    {
        $representante->update($data);

        return $representante->refresh();
    }

    public function remover(PmdaRepresentante $representante): void
    {
        $comunidade = $representante->comunidade;
        $representante->delete();
        if ($comunidade) {
            $this->recalcular($comunidade);
        }
    }

    private function recalcular(PmdaComunidade $comunidade): void
    {
        $plano = $comunidade->plano;
        if ($plano) {
            $this->planos->recalcularStatus($plano);
        }
    }
}

class PlanoPontoService
{
    public function vincular(PmdaPlano $plano, int $pontoId, string $situacao = 'ATIVO'): void
    {
        // syncWithoutDetaching evita duplicar o vinculo (unique no pivot).
        $plano->pontos()->syncWithoutDetaching([$pontoId => ['situacao' => $situacao]]);
    }

    /** Cria um ponto de captacao do municipio do plano e ja o vincula (Etapa 4). */
    public function criarEVincular(PmdaPlano $plano, array $data): PmdaPonto
    {
        $ponto = PmdaPonto::create([
            'municipio_id' => $plano->municipio_id,
            'nome'         => $data['nome'],
            'tipo'         => (int) ($data['tipo'] ?? 1),
            'ativo'        => true,
        ]);

        $plano->pontos()->attach($ponto->id, ['situacao' => $data['situacao'] ?? 'ATIVO']);

        return $ponto;
    }

    public function desvincular(PmdaPlano $plano, int $pontoId): void
    {
        $plano->pontos()->detach($pontoId);
    }

    /** Pontos ativos do municipio do plano disponiveis para vinculo. */
    public function disponiveis(PmdaPlano $plano): Collection
    {
        return PmdaPonto::query()
            ->where('municipio_id', $plano->municipio_id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();
    }
}

class CompdecMembroService
{
    public function adicionar(PmdaPlano $plano, array $data): PmdaCompdecMembro
    {
        return $plano->compdecMembros()->create($data);
    }

    public function remover(PmdaCompdecMembro $membro): void
    {
        $membro->delete();
    }
}

/**
 * Ficha cadastral do COMPDEC acessada de dentro do PMDA. Reaproveita o
 * registro mestre do municipio (Compdec\Orgao tipo COMPDEC): o PMDA le como
 * fallback e grava no proprio orgao, mantendo o cadastro unico e autoritativo.
 */
class CompdecFichaService
{
    /** Colunas reais de compdec_orgaos editaveis pela ficha (exclui tem_plano_contingencia, gerido por observer). */
    private const COLUNAS = [
        'status', 'lei_criacao_numero', 'lei_criacao_data', 'decreto_numero', 'decreto_data',
        'portaria_numero', 'portaria_data', 'email', 'email_secundario', 'email_terciario',
        'telefone', 'telefone_secundario', 'fax', 'endereco', 'qtd_efetivo', 'qtd_nupdec',
        'tem_sede_propria', 'tem_viatura', 'tem_mapeamento_risco', 'tem_simulado', 'tem_cartao_pdc',
    ];

    /** Campos sem coluna propria: guardados em metadata.capacidades (sem migracao). */
    private const META = [
        'tem_computador', 'tem_curso_gestao', 'data_curso_gestao', 'tem_curso_sco', 'data_curso_sco',
        'tem_workshop_pdc', 'data_workshop_pdc', 'tem_experiencia', 'tempo_experiencia_anos',
        'capacitacao_nupdec', 'possui_capacitacao_pdc', 'data_capacitacao_pdc',
        'possui_compdec', 'possui_nupdec', 'possui_efetivo', 'nao_possui_lei', 'nao_possui_decreto',
        'nao_possui_portaria', 'associacao',
    ];

    /** Orgao COMPDEC do municipio do plano (ou null se ainda nao cadastrado). */
    public function orgaoDoMunicipio(PmdaPlano $plano): ?Orgao
    {
        return Orgao::query()
            ->where('municipio_id', $plano->municipio_id)
            ->where('tipo', TipoOrgao::COMPDEC->value)
            ->first();
    }

    /** Localiza ou cria (em memoria) o orgao COMPDEC do municipio do plano. */
    public function orgaoOuNovo(PmdaPlano $plano): Orgao
    {
        return $this->orgaoDoMunicipio($plano) ?? new Orgao([
            'tipo'         => TipoOrgao::COMPDEC->value,
            'status'       => StatusOrgao::ATIVO->value,
            'municipio_id' => $plano->municipio_id,
            'nome'         => $plano->municipio?->nome ?? ('COMPDEC '.$plano->municipio_id),
            'codigo'       => 'COMPDEC-'.$plano->municipio_id,
        ]);
    }

    /** Garante o orgao persistido (para anexar arquivos/foto). */
    public function garantirOrgao(PmdaPlano $plano): Orgao
    {
        $orgao = $this->orgaoOuNovo($plano);
        if (! $orgao->exists) {
            $orgao->save();
            (new CachedRepository('orgaos', ttlSeconds: 3600))->flush();
        }

        return $orgao;
    }

    /** Dados de fallback para preencher a ficha (vazios com default quando inexistente). */
    public function fichaDoPlano(PmdaPlano $plano): array
    {
        $orgao = $this->orgaoDoMunicipio($plano);
        $cap = $orgao?->metadata['capacidades'] ?? [];
        $plano->loadMissing('municipio');

        return [
            'existe'               => $orgao !== null,
            'orgao_nome'           => $orgao?->nome,
            'municipio_nome'       => $plano->municipio?->nome,
            'municipio_regiao'     => $plano->municipio?->regiao,
            'municipio_mesorregiao' => $plano->municipio?->mesorregiao,
            // Prefeitura/municipio (read-only na ficha; editavel na aba ISS).
            'prefeito_nome'        => $plano->nome_prefeito,
            'prefeitura_email'     => $plano->email_prefeitura,
            'prefeitura_telefone'  => $plano->tel_prefeitura,
            'prefeito_telefone'    => $plano->tel_prefeito,
            'prefeito_celular'     => $plano->cel_prefeito,
            'prefeitura_endereco'  => $plano->endereco,
            'prefeitura_bairro'    => $plano->bairro,
            'prefeitura_cep'       => $plano->cep,
            'foto_coordenador_url' => $orgao?->foto_coordenador_url,
            'status'               => $orgao?->status?->value ?? StatusOrgao::ATIVO->value,
            'possui_compdec'       => (bool) ($cap['possui_compdec'] ?? ($orgao !== null)),
            // Atos legais
            'lei_criacao_numero'   => $orgao?->lei_criacao_numero,
            'lei_criacao_data'     => $orgao?->lei_criacao_data?->toDateString(),
            'decreto_numero'       => $orgao?->decreto_numero,
            'decreto_data'         => $orgao?->decreto_data?->toDateString(),
            'portaria_numero'      => $orgao?->portaria_numero,
            'portaria_data'        => $orgao?->portaria_data?->toDateString(),
            // Contato
            'email'                => $orgao?->email,
            'email_secundario'     => $orgao?->email_secundario,
            'email_terciario'      => $orgao?->email_terciario,
            'telefone'             => $orgao?->telefone,
            'telefone_secundario'  => $orgao?->telefone_secundario,
            'fax'                  => $orgao?->fax,
            'endereco'             => $orgao?->endereco,
            // Quantitativos
            'qtd_efetivo'          => $orgao?->qtd_efetivo ?? 0,
            'qtd_nupdec'           => $orgao?->qtd_nupdec ?? 0,
            // Estrutura / capacidades (colunas)
            'tem_sede_propria'     => (bool) ($orgao?->tem_sede_propria ?? false),
            'tem_viatura'          => (bool) ($orgao?->tem_viatura ?? false),
            'tem_mapeamento_risco' => (bool) ($orgao?->tem_mapeamento_risco ?? false),
            'tem_simulado'         => (bool) ($orgao?->tem_simulado ?? false),
            'tem_cartao_pdc'       => (bool) ($orgao?->tem_cartao_pdc ?? false),
            'tem_plano_contingencia' => (bool) ($orgao?->tem_plano_contingencia ?? false), // read-only
            // Capacidades / capacitacao (metadata)
            'tem_computador'        => (bool) ($cap['tem_computador'] ?? false),
            'tem_curso_gestao'      => (bool) ($cap['tem_curso_gestao'] ?? false),
            'data_curso_gestao'     => $cap['data_curso_gestao'] ?? null,
            'tem_curso_sco'         => (bool) ($cap['tem_curso_sco'] ?? false),
            'data_curso_sco'        => $cap['data_curso_sco'] ?? null,
            'tem_workshop_pdc'      => (bool) ($cap['tem_workshop_pdc'] ?? false),
            'data_workshop_pdc'     => $cap['data_workshop_pdc'] ?? null,
            'tem_experiencia'       => (bool) ($cap['tem_experiencia'] ?? false),
            'tempo_experiencia_anos' => $cap['tempo_experiencia_anos'] ?? null,
            'capacitacao_nupdec'    => $cap['capacitacao_nupdec'] ?? null,
            'possui_capacitacao_pdc' => (bool) ($cap['possui_capacitacao_pdc'] ?? false),
            'data_capacitacao_pdc'  => $cap['data_capacitacao_pdc'] ?? null,
            'possui_nupdec'         => (bool) ($cap['possui_nupdec'] ?? false),
            'possui_efetivo'        => (bool) ($cap['possui_efetivo'] ?? false),
            'nao_possui_lei'        => (bool) ($cap['nao_possui_lei'] ?? false),
            'nao_possui_decreto'    => (bool) ($cap['nao_possui_decreto'] ?? false),
            'nao_possui_portaria'   => (bool) ($cap['nao_possui_portaria'] ?? false),
            'associacao'            => $cap['associacao'] ?? null,
        ];
    }

    /** Localiza ou cria o orgao COMPDEC do municipio e grava a ficha (colunas + metadata). */
    public function salvar(PmdaPlano $plano, array $data): Orgao
    {
        $orgao = $this->orgaoOuNovo($plano);

        $orgao->fill(collect($data)->only(self::COLUNAS)->toArray());

        // Merge das capacidades em metadata sem perder chaves nao editadas.
        $metadata = $orgao->metadata ?? [];
        $metadata['capacidades'] = array_replace(
            $metadata['capacidades'] ?? [],
            collect($data)->only(self::META)->toArray()
        );
        $orgao->metadata = $metadata;

        $orgao->save();

        (new CachedRepository('orgaos', ttlSeconds: 3600))->flush();

        return $orgao;
    }

    /** Sobe/atualiza a foto do coordenador do orgao COMPDEC do municipio. */
    public function uploadFoto(PmdaPlano $plano, UploadedFile $arquivo): Media
    {
        $orgao = $this->garantirOrgao($plano);
        $disk = (string) config('compdec.disk', 'compdec');

        return $orgao
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Orgao::MEDIA_FOTO_COORDENADOR, $disk);
    }

    public function removerFoto(PmdaPlano $plano): void
    {
        $orgao = $this->orgaoDoMunicipio($plano);
        $orgao?->clearMediaCollection(Orgao::MEDIA_FOTO_COORDENADOR);
    }
}

