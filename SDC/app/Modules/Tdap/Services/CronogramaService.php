<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Modules\Tdap\Domain\Events\CronogramaAtivadoV1;
use App\Modules\Tdap\DTOs\CronogramaDTO;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Enums\FaseCronograma;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronogramaTramite;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Support\PoliticaPontoCaptacao;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CronogramaService
{
    public function __construct(
        private readonly OutboxDispatcher $outbox,
        private readonly HistoricoService $historico,
        private readonly PoliticaPontoCaptacao $politicaPontos,
    ) {}


    /**
     * @param  array<string, mixed>  $filtros
     * @param  ?int  $municipioEscopo  EscopoDeLeitura de quem consulta (null =
     *     estado inteiro). Soma-se ao filtro da tela, nunca o substitui: o
     *     `municipio_id` da querystring so estreita dentro do escopo.
     */
    public function listar(int $perPage = 15, array $filtros = [], ?int $municipioEscopo = null): LengthAwarePaginator
    {
        return Cronograma::query()
            ->when($municipioEscopo !== null, fn ($q) => $q->doMunicipio($municipioEscopo))
            ->with([
                'ata:id,numero',
                'lote:id,numero,nome',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
            ])
            ->withCount('caminhoes')
            // volume_contratado/volume_entregue somam colunas dos caminhoes
            // alocados; sem o withSum o accessor faria um SELECT por linha.
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->comExecucaoDeViagens()
            ->when(
                ($filtros['estado'] ?? null) === 'arquivado',
                fn ($q) => $q->arquivado(),
                fn ($q) => $q->naoArquivado(),
            )
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->noEstadoOuFase((string) $estado))
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->where('ata_id', (int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->where('prestador_id', (int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->orderByDesc('dt_inicio')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cronogramas do municipio cujo ponto foi autorizado por este PMDA.
     *
     * O vinculo e o pivo `tdap_cronograma_ponto.pmda_plano_id`, o mesmo que a
     * ficha mostra no selo do ponto. A consulta olha o pivô direto para o
     * cronograma continuar correspondente mesmo se o ponto tiver sido excluido.
     *
     * @return list<array{id: int, numero: string, estado: string, fase_tramite: ?array, arquivado: bool, dt_inicio: ?string, dt_final: ?string}>
     */
    public function vinculadosAoPmda(int $pmdaPlanoId, int $municipioId): array
    {
        return Cronograma::query()
            ->where('municipio_id', $municipioId)
            ->whereExists(function ($query) use ($pmdaPlanoId): void {
                $query->selectRaw('1')
                    ->from('tdap_cronograma_ponto as cp')
                    ->whereColumn('cp.cronograma_id', 'tdap_cronogramas.id')
                    ->where('cp.pmda_plano_id', $pmdaPlanoId);
            })
            ->orderByDesc('dt_inicio')
            ->orderByDesc('id')
            ->get(['id', 'numero', 'ativo', 'encerrado_em', 'arquivado_em', 'dt_inicio', 'dt_final', 'fase_tramite'])
            ->map(fn (Cronograma $cronograma) => [
                'id'         => $cronograma->id,
                'numero'     => $cronograma->numero,
                'estado'     => $cronograma->estado,
                'fase_tramite' => $cronograma->fase_tramite?->toArray(),
                'arquivado'  => $cronograma->arquivado_em !== null,
                'dt_inicio'  => $cronograma->dt_inicio?->toDateString(),
                'dt_final'   => $cronograma->dt_final?->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * Linhas planas para exportacao CSV (respeita os filtros da listagem).
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array<string, mixed>>
     */
    public function exportar(array $filtros = [], ?int $municipioEscopo = null): array
    {
        $rows = Cronograma::query()
            ->when($municipioEscopo !== null, fn ($q) => $q->doMunicipio($municipioEscopo))
            ->with([
                'ata:id,numero',
                'lote:id,numero',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj',
                'pontosCaptacao',
            ])
            ->withCount('caminhoes')
            // volume_contratado/volume_entregue somam colunas dos caminhoes
            // alocados; sem o withSum o accessor faria um SELECT por linha.
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->comExecucaoDeViagens()
            ->when(
                ($filtros['estado'] ?? null) === 'arquivado',
                fn ($q) => $q->arquivado(),
                fn ($q) => $q->naoArquivado(),
            )
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->noEstadoOuFase((string) $estado))
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->where('ata_id', (int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->where('prestador_id', (int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $q->buscar((string) $termo))
            ->when($filtros['data_inicio'] ?? null, fn ($q, $d) => $q->whereDate('dt_inicio', '>=', (string) $d))
            ->when($filtros['data_fim'] ?? null, fn ($q, $d) => $q->whereDate('dt_inicio', '<=', (string) $d))
            ->orderByDesc('dt_inicio')
            ->get();

        return $rows->map(fn (Cronograma $c) => [
            'Numero'                 => $c->numero,
            'Estado'                 => $c->estado,
            'Vigencia Inicio'        => $c->dt_inicio?->format('d/m/Y'),
            'Vigencia Fim'           => $c->dt_final?->format('d/m/Y'),
            'Ata'                    => $c->ata?->numero,
            'Lote'                   => $c->lote?->numero,
            'Municipio'              => $c->municipio?->nome,
            'UF'                     => $c->municipio?->uf,
            'Prestador'              => $c->prestador?->nome,
            'CNPJ'                   => $c->prestador?->cnpj,
            'Pontos de Captacao'     => $c->pontosCaptacao->pluck('nome')->implode(' | '),
            // Contratado = soma da agua prevista dos caminhoes alocados. Esta
            // coluna trazia `fator` (<= 0,60 em toda a base) e passava a
            // impressao de que o cronograma nao movia agua nenhuma.
            'Volume Contratado (m3)' => number_format($c->volume_contratado, 2, ',', '.'),
            'Volume Entregue (m3)'   => number_format($c->volume_entregue, 2, ',', '.'),
            'Execucao (%)'           => number_format($c->percentual_entregue, 2, ',', '.'),
            'Viagens Previstas'      => $c->viagens_previstas,
            'Viagens Realizadas'     => $c->viagens_realizadas,
            'Execucao Viagens (%)'   => number_format($c->percentual_viagens, 2, ',', '.'),
            'Caminhoes'              => (int) $c->caminhoes_count,
            'Consumo Diario'         => number_format((float) $c->consumo_diario, 2, ',', '.'),
            'Dias'                   => (int) $c->dias,
            'Fator'                  => number_format((float) $c->fator, 2, ',', '.'),
            'Arquivado'              => $c->arquivado_em ? 'Sim' : 'Nao',
        ])->all();
    }

    public function obter(int $id): Cronograma
    {
        return Cronograma::query()
            ->with([
                'ata:id,numero,dt_inicio,dt_final',
                'lote:id,numero,nome',
                'municipio:id,nome,uf',
                'prestador:id,nome,cnpj,email',
                'user:id,name,email',
                // Sem lista de colunas: no BelongsToMany o `id` sem tabela
                // colide com o `id` do pivot.
                'pontosCaptacao',
                'caminhoes.caminhao:id,placa,marca,modelo,capacidade_m3',
                'comprovantes',
            ])
            ->withCount(['caminhoes'])
            ->withSum('caminhoes', 'agua_prevista')
            ->withSum('caminhoes', 'agua_entregue')
            ->findOrFail($id);
    }

    public function criar(CronogramaDTO $dto): Cronograma
    {
        return DB::transaction(function () use ($dto): Cronograma {
            $data = $dto->toArray();
            $data['user_id'] = Auth::id();
            $data['ativo'] = false;

            // fase_tramite nao e fillable: atribuida explicitamente.
            $cronograma = new Cronograma($data);
            $cronograma->fase_tramite = FaseCronograma::Elaboracao;
            $cronograma->save();
            $this->sincronizarPontos($cronograma, $dto->ponto_captacao_ids);

            return $cronograma;
        });
    }

    public function atualizar(int $id, CronogramaDTO $dto): Cronograma
    {
        return DB::transaction(function () use ($id, $dto): Cronograma {
            // Trava a linha antes de olhar a fase: sem ela a edicao corria com
            // `tramitar` e gravava cabecalho/pontos num cronograma que acabara
            // de entrar em analise (a CEDEC aprovaria uma versao que nao viu).
            // Os pontos (sincronizarPontos) gravam nesta mesma transacao.
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);
            if ($cronograma->ativo) {
                throw new \DomainException('Cronograma ativo nao pode ter cabecalho editado. Encerre ou crie novo.');
            }
            if ($cronograma->tramitacao_travada) {
                throw new \DomainException('Cronograma em analise ou aprovado nao pode ser editado. Devolva-o para a elaboracao no Acompanhamento.');
            }

            // Trocar a empresa (ou o lote, que traz a empresa e o preco do m3)
            // com caminhao alocado deixava no cronograma caminhoes de outra
            // empresa -- a regra de alocar() so vale na hora de alocar.
            // Coluna nula (acervo legado) nao conta como troca: preencher o que
            // faltava nao muda a empresa de ninguem.
            $trocaContrato = ($cronograma->prestador_id !== null && (int) $cronograma->prestador_id !== $dto->prestador_id)
                || ($cronograma->lote_id !== null && (int) $cronograma->lote_id !== $dto->lote_id);
            if ($trocaContrato && $cronograma->caminhoes()->exists()) {
                throw new \DomainException('Cronograma com caminhao alocado nao pode trocar de prestador ou de lote. Remova os caminhoes antes.');
            }

            $cronograma->update($dto->toArray());

            $mudancas = $this->sincronizarPontos($cronograma, $dto->ponto_captacao_ids);
            if ($mudancas['attached'] !== [] || $mudancas['detached'] !== []) {
                $this->historico->registrar(
                    'cronograma.pontos_alterados',
                    $cronograma,
                    'Pontos de captacao alterados.',
                    ['incluidos' => $mudancas['attached'], 'removidos' => $mudancas['detached']],
                );
            }

            return $cronograma->fresh();
        });
    }

    /**
     * Grava os pontos no pivot com o PMDA de origem de cada um.
     *
     * O ponto que ja estava vinculado mantem a origem gravada quando nao consta
     * mais no PMDA vigente: o vinculo nasceu valido e a origem e historico, nao
     * estado atual.
     *
     * @param  list<int>  $pontoIds
     * @return array{attached: list<int>, detached: list<int>, updated: list<int>}
     */
    private function sincronizarPontos(Cronograma $cronograma, array $pontoIds): array
    {
        $origensAtuais = $cronograma->pontosCaptacao()
            ->pluck('tdap_cronograma_ponto.pmda_plano_id', 'pip_pmda_ponto.id')
            ->all();
        $origens = $this->politicaPontos->origens((int) $cronograma->municipio_id, $pontoIds);

        $vinculos = [];
        foreach ($pontoIds as $pontoId) {
            $vinculos[$pontoId] = ['pmda_plano_id' => $origens[$pontoId] ?? $origensAtuais[$pontoId] ?? null];
        }

        return $cronograma->pontosCaptacao()->sync($vinculos);
    }

    public function deletar(int $id): bool
    {
        // Trava a linha: sem ela, `deletar` corre com `ativar`/`tramitar` e
        // apaga um cronograma que acabou de ser ativado ou enviado.
        return DB::transaction(function () use ($id): bool {
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);
            if ($cronograma->ativo) {
                throw new \DomainException('Cronograma ativo nao pode ser excluido. Encerre antes.');
            }

            if ($cronograma->tramitacao_travada) {
                throw new \DomainException('Cronograma em analise ou aprovado nao pode ser excluido. Devolva-o para a elaboracao no Acompanhamento.');
            }

            // Excluir tirava a agua ja aprovada dos totais: as viagens ficam na
            // tabela, mas toda contagem passa pelo cronograma (CronoViagem::vinculada).
            // Arquivar esconde sem apagar a execucao.
            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado e registro historico. Arquive em vez de excluir.');
            }

            if ($cronograma->viagens()->where('validado', CronoViagem::STATUS_APROVADA)->exists()) {
                throw new \DomainException('Cronograma com viagem aprovada nao pode ser excluido. Arquive em vez de excluir.');
            }

            return (bool) $cronograma->delete();
        });
    }

    /**
     * Arquiva o cronograma (estado distinto do soft delete).
     * Idempotente: arquivar um cronograma ja arquivado nao gera novo evento.
     */
    public function arquivar(int $id): Cronograma
    {
        return DB::transaction(function () use ($id): Cronograma {
            // Com lock, como ativar/encerrar: arquivar nao pode correr com a
            // ativacao e esconder um cronograma que acabou de entrar em execucao.
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);

            // Ativo em execucao some da listagem e do painel quando arquivado,
            // mas segue recebendo viagens. Encerre antes.
            if ($cronograma->ativo && $cronograma->encerrado_em === null) {
                throw new \DomainException('Cronograma ativo nao pode ser arquivado. Encerre-o antes.');
            }

            if (! $cronograma->arquivado_em) {
                $cronograma->arquivado_em = now();
                $cronograma->save();
                $this->historico->registrar('cronograma.arquivado', $cronograma, 'Cronograma arquivado.');
            }

            return $cronograma->fresh();
        });
    }

    public function desarquivar(int $id): Cronograma
    {
        return DB::transaction(function () use ($id): Cronograma {
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);
            if ($cronograma->arquivado_em) {
                $cronograma->arquivado_em = null;
                $cronograma->save();
                $this->historico->registrar('cronograma.desarquivado', $cronograma, 'Cronograma desarquivado.');
            }

            return $cronograma->fresh();
        });
    }

    /**
     * Verifica se cronograma pode ser ativado.
     * Regra Fase 4 (vistoria vigente) entra com flag aqui.
     *
     * @return array{bool, ?string}
     */
    public function podeAtivar(Cronograma $cronograma): array
    {
        if ($cronograma->ativo) {
            return [false, 'Cronograma ja esta ativo.'];
        }

        if ($cronograma->encerrado_em) {
            return [false, 'Cronograma encerrado nao pode ser reativado.'];
        }

        // Arquivado esta fora de circulacao: so a tramitacao bloqueava, e a
        // ficha ativava mesmo assim.
        if ($cronograma->arquivado_em !== null) {
            return [false, 'Cronograma arquivado nao pode ser ativado. Desarquive antes.'];
        }

        // Acompanhamento: a ativacao e o ultimo passo da tramitacao. Sem esta
        // guarda o botao "Ativar" da ficha pularia a analise da CEDEC.
        if (config('tdap.cronogramas.exige_tramite', true) && $cronograma->fase_tramite !== FaseCronograma::Aprovado) {
            return [false, 'Cronograma precisa ser aprovado na analise da CEDEC antes da ativacao (fase atual: '
                .$cronograma->fase_tramite->label().').'];
        }

        $pendencia = $this->pendenciaParaAtivar($cronograma);

        return $pendencia === null ? [true, null] : [false, $pendencia];
    }

    /**
     * O que impede a ativacao alem do estado e da fase: frota, empresa,
     * vistoria, pontos e o "1 cronograma ativo por municipio".
     *
     * Separado de podeAtivar() para a tramitacao avisar ja na aprovacao o que a
     * ativacao vai recusar (TramitacaoCronogramaService::acoesDisponiveis) --
     * la a fase ainda e "em analise" e a regra de fase recusaria sempre.
     */
    public function pendenciaParaAtivar(Cronograma $cronograma): ?string
    {
        // 1 TDAP por municipio (#56): um cronograma em execucao por vez. A
        // demanda maior se resolve com mais caminhoes no mesmo cronograma
        // (locacao de frota), nao com um segundo cronograma em paralelo.
        $outroAtivo = Cronograma::query()
            ->where('municipio_id', $cronograma->municipio_id)
            ->whereKeyNot($cronograma->id)
            ->where('ativo', true)
            ->whereNull('encerrado_em')
            ->value('numero');
        if ($outroAtivo !== null) {
            return "O municipio ja tem o cronograma {$outroAtivo} em execucao. Encerre-o antes de ativar outro.";
        }

        $cronograma->loadCount('caminhoes');
        if ((int) $cronograma->caminhoes_count === 0) {
            return 'Cronograma exige ao menos 1 caminhao alocado.';
        }

        // Mesma regra de CronoCaminhaoService::alocar, conferida de novo aqui:
        // alocacao antiga (ou feita antes de uma troca de empresa) nao passava
        // por ela, e a viagem seria paga pelo lote do cronograma.
        // Por consulta, nao pela relacao carregada: a ficha (obter()) carrega
        // `caminhoes.caminhao` sem `prestador_id`, e loadMissing nao recarrega
        // -- todo caminhao parecia de outra empresa e a ativacao travava.
        if ($cronograma->prestador_id !== null) {
            $deOutraEmpresa = $cronograma->caminhoes()
                ->whereHas('caminhao', fn ($q) => $q->where('prestador_id', '<>', $cronograma->prestador_id))
                ->with('caminhao:id,placa')
                ->first();

            if ($deOutraEmpresa !== null) {
                return "Caminhao {$deOutraEmpresa->caminhao->placa} nao pertence a empresa contratada no cronograma. Remova-o antes de ativar.";
            }
        }

        foreach ($this->caminhoesSemVistoriaAte($cronograma, $cronograma->dt_final_efetiva) as $erro) {
            return $erro;
        }

        // Ponto aceito no rascunho pode ter saido do PMDA (ou ser do acervo
        // legado): a ativacao confere de novo contra o PMDA vigente, sem a
        // excecao de "ja vinculado" que o formulario aplica.
        if ($this->politicaPontos->exigePmda()) {
            $pontoIds = $cronograma->pontosCaptacao()->pluck('pip_pmda_ponto.id')->map(fn ($id) => (int) $id)->all();
            foreach ($this->politicaPontos->recusas((int) $cronograma->municipio_id, $pontoIds) as $recusa) {
                return $recusa.' Ajuste os pontos de captacao antes de ativar.';
            }
        }

        return null;
    }

    /**
     * Por que a vistoria do caminhao nao cobre $dataLimite; null = cobre.
     *
     * Regra unica de ativacao, prorrogacao e alocacao em cronograma ativo
     * (CronoCaminhaoService::alocar).
     */
    public function motivoVistoriaNaoCobre(?Caminhao $caminhao, string $placa, ?Carbon $dataLimite): ?string
    {
        $vistoria = $caminhao?->ultimaVistoriaAprovada;

        if ($vistoria === null) {
            return "Caminhao {$placa} nao tem vistoria aprovada. Registre/aprove vistoria antes.";
        }

        // Cronograma sem data final: nao ha periodo a cobrir, vigente hoje basta.
        if ($dataLimite === null) {
            return $vistoria->esta_vigente
                ? null
                : "Caminhao {$placa} nao tem vistoria aprovada vigente ("
                    .Vistoria::VIGENCIA_MESES.' meses). Registre/aprove vistoria antes.';
        }

        if (! $vistoria->cobre($dataLimite)) {
            return sprintf(
                'Caminhao %s tem vistoria valida ate %s, mas o periodo vai ate %s. Renove a vistoria antes.',
                $placa,
                $vistoria->valido_ate?->format('d/m/Y') ?? 'data desconhecida',
                $dataLimite->format('d/m/Y'),
            );
        }

        return null;
    }

    /**
     * Caminhoes alocados cuja vistoria nao cobre a data informada.
     *
     * GUARD Vistoria - a vistoria tem que COBRIR O CRONOGRAMA INTEIRO.
     *
     * Antes o guard exigia `vistoriaVigente`, que significa "vigente HOJE". Um
     * cronograma de jan a dez ativado em janeiro passava com uma vistoria que
     * vencia em marco, e nada avisava: o caminhao rodava nove meses sem inspecao
     * valida. A pergunta certa nao e "esta vigente agora" e sim "cobre ate
     * dt_final_efetiva".
     *
     * Usa ultimaVistoriaAprovada (nao vistoriaVigente): interessa a vistoria
     * aprovada mais nova, vigente ou nao -- e ela que define ate quando o
     * veiculo esta coberto, e a mensagem precisa dizer ate quando.
     *
     * Ativacao e prorrogacao compartilham este metodo de proposito: eram dois
     * caminhos para a mesma decisao, e foi assim que a validacao mais fraca da
     * prorrogacao deixou encurtar prazo no passado.
     *
     * @return \Generator<int, string> Mensagens de erro, uma por caminhao reprovado
     */
    private function caminhoesSemVistoriaAte(Cronograma $cronograma, ?Carbon $dataLimite): \Generator
    {
        $cronograma->load(['caminhoes.caminhao.ultimaVistoriaAprovada']);

        foreach ($cronograma->caminhoes as $cc) {
            $erro = $this->motivoVistoriaNaoCobre($cc->caminhao, $cc->caminhao?->placa ?? "id={$cc->caminhao_id}", $dataLimite);
            if ($erro !== null) {
                yield $erro;
            }
        }
    }

    public function ativar(int $id, ?string $observacao = null): Cronograma
    {
        return DB::transaction(function () use ($id, $observacao): Cronograma {
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);

            // "1 cronograma ativo por municipio": o lock acima e da LINHA, entao
            // dois cronogramas do mesmo municipio ativados juntos passavam os
            // dois pela checagem de `outroAtivo`. A trava por municipio
            // serializa as ativacoes (solta no fim da transacao). Nao ha
            // indice unico: o acervo ja tem municipio com mais de um ativo.
            if (DB::getDriverName() === 'pgsql') {
                DB::select('select pg_advisory_xact_lock(?, ?)', [7101, (int) $cronograma->municipio_id]);
            }

            [$ok, $erro] = $this->podeAtivar($cronograma);
            if (! $ok) {
                throw new \DomainException($erro ?? 'Cronograma nao pode ser ativado.');
            }

            $cronograma->ativo = true;
            $cronograma->ativado_em = now();
            $cronograma->stored_prestador = $cronograma->prestador?->only(['id', 'nome', 'cnpj', 'email']);
            $cronograma->stored_municipio = $cronograma->municipio?->only(['id', 'nome', 'uf']);
            $cronograma->stored_caminhoes = $cronograma->caminhoes()
                ->with('caminhao:id,placa,marca,modelo,capacidade_m3')
                ->get()
                ->map(fn ($cc) => [
                    'id'             => $cc->id,
                    'caminhao_id'    => $cc->caminhao_id,
                    'placa'          => $cc->caminhao?->placa,
                    'capacidade_m3'  => (float) ($cc->caminhao?->capacidade_m3 ?? 0),
                    'agua_prevista'  => (float) $cc->agua_prevista,
                    'num_viagens'    => (int) $cc->num_viagens,
                ])->toArray();
            $cronograma->stored_pmda_ponto = $this->snapshotDosPontos($cronograma);

            // Ativar e o passo final da tramitacao, venha da ficha ou do
            // Acompanhamento: o trâmite fica aqui para os dois caminhos.
            $faseAnterior = $cronograma->fase_tramite;
            $cronograma->fase_tramite = FaseCronograma::EmExecucao;
            $cronograma->save();

            CronogramaTramite::create([
                'cronograma_id' => $cronograma->id,
                'fase_anterior' => $faseAnterior,
                'fase_nova'     => FaseCronograma::EmExecucao,
                'acao'          => AcaoTramite::Ativar,
                'user_id'       => Auth::id(),
                'observacao'    => $observacao,
            ]);

            $fresh = $cronograma->fresh(['prestador', 'municipio', 'ata', 'lote']);

            // Emite CronogramaAtivadoV1 no outbox (mesma transacao do save).
            // Listener idempotente EnviarEmailCronogramaListener consome
            // o evento e envia o e-mail de forma assincrona/retryable.
            $this->outbox->persist(new CronogramaAtivadoV1(
                eventId:       DomainEvent::newId(),
                aggregateType: 'cronograma',
                aggregateId:   (string) $fresh->id,
                occurredAt:    new \DateTimeImmutable(),
                metadata: [
                    'cronograma_id' => $fresh->id,
                    'numero'        => $fresh->numero,
                    'prestador_id'  => $fresh->prestador_id,
                    'municipio_id'  => $fresh->municipio_id,
                      'ativado_em'    => $fresh->ativado_em?->toIso8601String(),
                      'actor_user_id' => \Illuminate\Support\Facades\Auth::id(),
                ],
            ));

            return $fresh;
        });
    }

    /**
     * Pontos como estavam na ativacao, com o PMDA que os autorizou (o gravado
     * no vinculo, nao o vigente hoje: e a origem que justificou o ponto).
     *
     * @return list<array<string, mixed>>
     */
    private function snapshotDosPontos(Cronograma $cronograma): array
    {
        return $this->politicaPontos->apresentarVinculados($cronograma->pontosCaptacao()->get());
    }

    public function encerrar(int $id, ?string $obs = null): Cronograma
    {
        return DB::transaction(function () use ($id, $obs): Cronograma {
            // Com lock, como o ativar: dois cliques/abas gravavam dois
            // tramites `encerrar` e sobrescreviam `encerrado_em`.
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);
            if (! $cronograma->ativo) {
                throw new \DomainException('Apenas cronogramas ativos podem ser encerrados.');
            }

            $cronograma->ativo = false;
            $cronograma->encerrado_em = now();
            if ($obs) {
                $cronograma->observacao = trim(($cronograma->observacao ?? '') . "\n[ENCERRAMENTO] " . $obs);
            }

            // Encerrado = Entregues no Acompanhamento.
            $faseAnterior = $cronograma->fase_tramite;
            $cronograma->fase_tramite = FaseCronograma::Entregue;
            $cronograma->save();

            CronogramaTramite::create([
                'cronograma_id' => $cronograma->id,
                'fase_anterior' => $faseAnterior,
                'fase_nova'     => FaseCronograma::Entregue,
                'acao'          => AcaoTramite::Encerrar,
                'user_id'       => Auth::id(),
                'observacao'    => $obs,
            ]);

            return $cronograma->fresh();
        });
    }

    /**
     * Estende a vigencia sem criar novo cronograma.
     *
     * A regra `dt_inicio_prorrogacao >= dt_final` ja existia no
     * AbstractCronogramaRequest, mas a rota de prorrogacao tem validacao
     * propria e mais fraca: dava para "prorrogar" para uma janela ANTERIOR ao
     * fim da vigencia original, encurtando o prazo em vez de estende-lo (as
     * datas efetivas dao precedencia a prorrogacao).
     */
    public function prorrogar(int $id, string $dtInicio, string $dtFinal, ?string $justificativa = null): Cronograma
    {
        return DB::transaction(function () use ($id, $dtInicio, $dtFinal, $justificativa): Cronograma {
            // Com lock, como encerrar: prorrogar e encerrar juntos gravavam
            // prorrogacao num cronograma ja encerrado.
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($id);
            if (! $cronograma->ativo) {
                throw new \DomainException('Apenas cronogramas ativos podem ser prorrogados.');
            }
            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao pode ser prorrogado.');
            }

            $inicioProrrogacao = Carbon::parse($dtInicio)->startOfDay();
            $finalProrrogacao = Carbon::parse($dtFinal)->startOfDay();

            if ($finalProrrogacao->lt($inicioProrrogacao)) {
                throw new \DomainException('A data final da prorrogacao nao pode ser anterior a inicial.');
            }

            /*
             * A janela nova emenda no fim EFETIVO (ja com prorrogacao anterior):
             * comeca no dia seguinte a ele, sem sobrepor e sem lacuna.
             *
             * Comparar com `dt_final` deixava uma segunda prorrogacao comecar
             * dentro da primeira e terminar antes dela -- as datas efetivas dao
             * precedencia a prorrogacao, entao isso ENCURTAVA o prazo. E um
             * inicio solto depois do fim deixava dias sem contrato no meio.
             */
            $fimAtual = $cronograma->dt_final_efetiva?->copy()->startOfDay();
            if ($fimAtual !== null) {
                $diaSeguinte = $fimAtual->copy()->addDay();

                if (! $inicioProrrogacao->equalTo($diaSeguinte)) {
                    throw new \DomainException(sprintf(
                        'A prorrogacao deve comecar no dia seguinte ao fim da vigencia atual (%s), ou seja, em %s.',
                        $fimAtual->format('d/m/Y'),
                        $diaSeguinte->format('d/m/Y'),
                    ));
                }

                if (! $finalProrrogacao->gt($fimAtual)) {
                    throw new \DomainException(
                        'A data final da prorrogacao deve ser posterior ao fim da vigencia atual ('.$fimAtual->format('d/m/Y').').'
                    );
                }
            }

            /*
             * A prorrogacao passa pelo mesmo guard de vistoria da ativacao.
             *
             * Sem isto o guard novo seria contornavel por construcao: bastava
             * ativar com uma janela curta que a vistoria cobre e, no dia
             * seguinte, prorrogar para um ano a frente sem revistoriar nada.
             */
            foreach ($this->caminhoesSemVistoriaAte($cronograma, $finalProrrogacao) as $erro) {
                throw new \DomainException($erro);
            }

            // Segunda prorrogacao ESTENDE a primeira: o inicio gravado continua
            // o da primeira janela. Sobrescrever com o inicio novo fazia
            // dt_inicio_efetiva pular e as viagens da 1a prorrogacao apareciam
            // como fora da vigencia.
            $cronograma->update([
                'dt_inicio_prorrogacao' => $cronograma->dt_inicio_prorrogacao ?? $dtInicio,
                'dt_final_prorrogacao'  => $dtFinal,
                'justificativa'         => $justificativa
                    ? trim(($cronograma->justificativa ?? '') . "\n[PRORROGACAO] " . $justificativa)
                    : $cronograma->justificativa,
            ]);

            return $cronograma->fresh();
        });
    }

    /**
     * Municipios para o filtro da listagem: so os vinculados a algum lote
     * (tdap_lote_municipios), cada um com as atas desses lotes.
     *
     * Cronograma so e emitido para municipio do lote (StoreCronogramaRequest),
     * entao oferecer os 853 do catalogo era oferecer filtros que sempre voltam
     * vazios. `ata_ids` deixa a tela restringir a lista quando uma Ata ja esta
     * escolhida. Lote inativo entra: cronograma antigo continua filtravel.
     *
     * @return list<array{id: int, nome: string, uf: ?string, ata_ids: list<int>}>
     */
    public function municipiosDosLotes(?int $municipioEscopo = null): array
    {
        return DB::table('tdap_lote_municipios as lm')
            ->join('tdap_lotes as l', 'l.id', '=', 'lm.lote_id')
            ->join('municipios as m', 'm.id', '=', 'lm.municipio_id')
            ->whereNull('l.deleted_at')
            ->when($municipioEscopo !== null, fn ($q) => $q->where('lm.municipio_id', $municipioEscopo))
            ->distinct()
            ->orderBy('m.nome')
            ->get(['m.id', 'm.nome', 'm.uf', 'l.ata_id'])
            ->groupBy('id')
            ->map(fn ($linhas) => [
                'id'      => (int) $linhas->first()->id,
                'nome'    => (string) $linhas->first()->nome,
                'uf'      => $linhas->first()->uf,
                'ata_ids' => $linhas->pluck('ata_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Cards da listagem de Cronogramas -- e os do dashboard, que chamam este
     * mesmo metodo para os dois mostrarem o mesmo numero.
     *
     * Sem os arquivados: a tabela abaixo dos cards tambem os esconde por padrao.
     * `$municipioId` recorta pelo municipio (null = estado inteiro).
     *
     * @return array<string, int|float>
     */
    public function obterEstatisticas(?int $municipioId = null): array
    {
        $row = Cronograma::query()
            ->naoArquivado()
            ->when($municipioId !== null, fn ($q) => $q->doMunicipio($municipioId))
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE ativo = TRUE AND encerrado_em IS NULL) AS ativos,
                COUNT(*) FILTER (WHERE ativo = FALSE AND encerrado_em IS NULL) AS rascunhos,
                COUNT(*) FILTER (WHERE encerrado_em IS NOT NULL) AS encerrados
            ')
            ->first();

        // Volume ativo = agua prevista dos caminhoes dos cronogramas ativos.
        // Antes era SUM(fator), que somava grandezas de no maximo 0,60 e
        // mostrava "0,6 m3 ativos" para a operacao inteira. A soma vem da
        // tabela dos caminhoes porque e la que o volume por rota e definido.
        $volumes = DB::table('tdap_crono_caminhoes as cc')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->whereNull('cc.deleted_at')
            ->whereNull('c.deleted_at')
            ->where('c.ativo', true)
            ->whereNull('c.encerrado_em')
            ->whereNull('c.arquivado_em')
            ->when($municipioId !== null, fn ($q) => $q->where('c.municipio_id', $municipioId))
            ->selectRaw('
                COALESCE(SUM(cc.agua_prevista), 0) AS previsto,
                COALESCE(SUM(cc.agua_entregue), 0) AS entregue
            ')
            ->first();

        $previsto = (float) ($volumes->previsto ?? 0);
        $entregue = (float) ($volumes->entregue ?? 0);

        return [
            'total'             => (int) ($row->total ?? 0),
            'ativos'            => (int) ($row->ativos ?? 0),
            'rascunhos'         => (int) ($row->rascunhos ?? 0),
            'encerrados'        => (int) ($row->encerrados ?? 0),
            'volume_ativo_m3'   => round($previsto, 2),
            'volume_entregue_m3' => round($entregue, 2),
            'execucao_percentual' => $previsto > 0 ? round(($entregue / $previsto) * 100, 2) : 0.0,
        ];
    }
}
