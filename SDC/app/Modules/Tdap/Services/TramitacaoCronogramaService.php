<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Models\User;
use App\Modules\Tdap\Domain\CronogramaWorkflow;
use App\Modules\Tdap\Enums\AcaoTramite;
use App\Modules\Tdap\Enums\FaseCronograma;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronogramaTramite;
use App\Modules\Tdap\Models\Historico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Acompanhamento de cronograma: o painel das 4 fases e a tramitacao entre
 * elas. Mesmo papel do TramitacaoService do AjudaHumanitaria.
 */
class TramitacaoCronogramaService
{
    /** Teto de cards por coluna: o painel e fila de trabalho, nao relatorio. */
    private const LIMITE_POR_FASE = 200;

    public function __construct(
        private readonly CronogramaWorkflow $workflow,
        private readonly CronogramaService $cronogramas,
        private readonly HistoricoService $historico,
    ) {}

    /**
     * Move o cronograma para a fase seguinte (ou a anterior, ao devolver).
     *
     * @throws \DomainException com a frase do bloqueio
     */
    public function tramitar(int $cronogramaId, AcaoTramite $acao, User $user, ?string $observacao = null): Cronograma
    {
        $observacao = $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null;

        if ($acao->exigeObservacao() && $observacao === null) {
            throw new \DomainException('Informe o motivo da devolução.');
        }

        return DB::transaction(function () use ($cronogramaId, $acao, $user, $observacao): Cronograma {
            $cronograma = Cronograma::query()->lockForUpdate()->findOrFail($cronogramaId);

            $motivo = $this->workflow->motivoParaNaoTramitar($cronograma, $acao, $user);
            if ($motivo !== null) {
                throw new \DomainException($motivo);
            }

            // Ativar passa pelas guardas e pelo snapshot de sempre, e o proprio
            // CronogramaService grava o tramite (a ficha tambem ativa por la).
            if ($acao === AcaoTramite::Ativar) {
                return $this->cronogramas->ativar($cronograma->id, $observacao);
            }

            $anterior = $cronograma->fase_tramite;
            $nova = $anterior->destino($acao);

            $cronograma->fase_tramite = $nova;
            $cronograma->save();

            CronogramaTramite::create([
                'cronograma_id' => $cronograma->id,
                'fase_anterior' => $anterior,
                'fase_nova'     => $nova,
                'acao'          => $acao,
                'user_id'       => $user->id,
                'observacao'    => $observacao,
            ]);

            if ($acao->tipoEvento() !== null) {
                $this->historico->registrar(
                    $acao->tipoEvento(),
                    $cronograma,
                    $observacao ?? "{$anterior->label()} → {$nova->label()}",
                    ['de' => $anterior->value, 'para' => $nova->value],
                    $user->id,
                );
            }

            return $cronograma->fresh();
        });
    }

    /**
     * Acoes que esta pessoa pode tomar agora, com o motivo quando bloqueada.
     * O front so desenha o que vem daqui, para a regra nao morar em dois lugares.
     *
     * @return list<array{value: string, label: string, exige_observacao: bool, bloqueio: ?string}>
     */
    public function acoesDisponiveis(Cronograma $cronograma, ?User $user): array
    {
        // Cada botao exige a permissao da propria acao (permissaoPara): sem ela
        // o botao nem aparece. `bloqueio` e para quem pode agir mas ainda nao
        // pode seguir (e precisa saber por que).
        if ($user === null) {
            return [];
        }

        $acoes = [];

        foreach ($cronograma->fase_tramite->acoesPermitidas() as $acao) {
            $permissao = $cronograma->fase_tramite->permissaoPara($acao);
            if ($permissao === null || ! $user->can($permissao)) {
                continue;
            }

            $bloqueio = $this->workflow->motivoParaNaoTramitar($cronograma, $acao, $user, false);
            if ($bloqueio === null && $acao === AcaoTramite::Ativar) {
                [, $bloqueio] = $this->cronogramas->podeAtivar($cronograma);
            }

            $acoes[] = [
                'value'            => $acao->value,
                'label'            => $acao->label(),
                'exige_observacao' => $acao->exigeObservacao(),
                'bloqueio'         => $bloqueio,
            ];
        }

        return $acoes;
    }

    /**
     * Ficha da tramitacao: fase atual, trilha e acoes de quem consulta.
     *
     * @return array<string, mixed>
     */
    public function detalhe(Cronograma $cronograma, ?User $user): array
    {
        $cronograma->load(['tramites.user:id,name', 'user:id,name'])
            ->loadSum('caminhoes', 'agua_prevista')
            ->loadSum('caminhoes', 'agua_entregue')
            ->loadSum('caminhoes', 'num_viagens');

        return [
            'id'       => $cronograma->id,
            'numero'   => $cronograma->numero,
            'fase'     => $cronograma->fase_tramite->toArray(),
            'estado'   => $cronograma->estado,
            'arquivado' => $cronograma->arquivado_em !== null,
            'fases'    => array_map(fn (FaseCronograma $f) => $f->toArray(), FaseCronograma::cases()),
            'acoes'    => $this->acoesDisponiveis($cronograma, $user),
            'resumo'   => [
                'viagens_previstas'  => $cronograma->viagens_previstas,
                'viagens_realizadas' => $cronograma->viagens_realizadas,
                'volume_contratado'  => $cronograma->volume_contratado,
                'volume_entregue'    => $cronograma->volume_entregue,
                'ativado_em'         => $cronograma->ativado_em?->toIso8601String(),
                'encerrado_em'       => $cronograma->encerrado_em?->toIso8601String(),
            ],
            'tramites' => $this->linhaDoTempo($cronograma),
        ];
    }

    /**
     * Todo o fluxo do cronograma, so dele: os tramites do Acompanhamento e os
     * eventos do Historico (criacao, ativacao, prorrogacao, encerramento...).
     *
     * O cronograma anterior ao Acompanhamento nao tem tramite; sem o
     * Historico a fase Entregues aparecia vazia. E o acervo importado nem
     * sempre tem Historico, entao criacao/ativacao/encerramento sem evento
     * gravado saem das proprias colunas do cronograma.
     *
     * @return list<array<string, mixed>>
     */
    private function linhaDoTempo(Cronograma $cronograma): array
    {
        $tramites = $cronograma->tramites->toBase()->map(fn (CronogramaTramite $t) => [
            'id'            => 't'.$t->id,
            'acao'          => $t->acao->value,
            'acao_label'    => $t->acao->label(),
            'fase_anterior' => $t->fase_anterior->label(),
            'fase_nova'     => $t->fase_nova->label(),
            'observacao'    => $t->observacao,
            'usuario'       => $t->user?->name,
            'data'          => $t->created_at?->toIso8601String(),
        ]);

        $acoesComTramite = $cronograma->tramites->map(fn (CronogramaTramite $t) => $t->acao)->unique();

        // Evento do Historico que o tramite ja conta (o tramite e mais rico:
        // tem fase de origem e destino). Recalculo de entregas e ruido tecnico.
        $repetidos = ['cronograma.enviado_analise', 'cronograma.aprovado', 'cronograma.devolvido', 'cronograma.entregas_recalculadas'];
        if ($acoesComTramite->contains(AcaoTramite::Ativar)) {
            $repetidos[] = 'cronograma.ativado';
        }
        if ($acoesComTramite->contains(AcaoTramite::Encerrar)) {
            $repetidos[] = 'cronograma.encerrado';
        }

        $eventos = Historico::query()
            ->with('user:id,name')
            ->doEntity('cronograma', $cronograma->id)
            ->whereNotIn('tipo_evento', $repetidos)
            ->get()
            ->toBase()
            ->map(fn (Historico $h) => [
                'id'            => 'h'.$h->id,
                'acao'          => self::EVENTOS[$h->tipo_evento][0] ?? 'edicao',
                'acao_label'    => self::EVENTOS[$h->tipo_evento][1] ?? ucfirst(str_replace(['cronograma.', '_'], ['', ' '], $h->tipo_evento)),
                'fase_anterior' => null,
                'fase_nova'     => null,
                'observacao'    => $h->obs,
                'usuario'       => $h->user?->name,
                'data'          => $h->data_evento?->toIso8601String(),
            ]);

        $tipos = $eventos->pluck('acao')->merge($tramites->pluck('acao'));
        $marcos = collect([
            ['criacao',  'Cronograma criado',     $cronograma->created_at],
            ['ativar',   'Cronograma ativado',    $cronograma->ativado_em],
            ['encerrar', 'Cronograma encerrado',  $cronograma->encerrado_em],
        ])
            ->filter(fn ($m) => $m[2] !== null && ! $tipos->contains($m[0]))
            ->map(fn ($m) => [
                'id'            => 'm'.$m[0],
                'acao'          => $m[0],
                'acao_label'    => $m[1],
                'fase_anterior' => null,
                'fase_nova'     => null,
                'observacao'    => 'Registrado no cadastro do cronograma.',
                'usuario'       => $m[0] === 'criacao' ? $cronograma->user?->name : null,
                'data'          => $m[2]->toIso8601String(),
            ]);

        return $tramites->merge($eventos)->merge($marcos)
            ->sortByDesc('data')
            ->values()
            ->all();
    }

    /** tipo_evento do Historico => [estilo no painel, titulo]. */
    private const EVENTOS = [
        'cronograma.criado'            => ['criacao', 'Cronograma criado'],
        'cronograma.ativado'           => ['ativar', 'Cronograma ativado'],
        'cronograma.encerrado'         => ['encerrar', 'Cronograma encerrado'],
        'cronograma.prorrogado'        => ['prorrogar', 'Cronograma prorrogado'],
        'cronograma.pontos_alterados'  => ['edicao', 'Pontos de captação alterados'],
        'cronograma.arquivado'         => ['arquivo', 'Cronograma arquivado'],
        'cronograma.desarquivado'      => ['arquivo', 'Cronograma desarquivado'],
    ];

    /**
     * Cronogramas agrupados pelas 5 fases.
     *
     * Encerrados entram na coluna Entregues, do mais recente para o mais
     * antigo (o teto por coluna segura o acervo). Arquivado nao entra.
     *
     * @param  array<string, mixed>  $filtros
     * @return list<array<string, mixed>>
     */
    public function painel(array $filtros, ?User $user, ?int $municipioEscopo): array
    {
        $fasesDoUsuario = array_values(array_filter(
            FaseCronograma::cases(),
            fn (FaseCronograma $f) => $f->permissao() !== null && $user?->can($f->permissao()),
        ));

        $base = Cronograma::query()
            ->naoArquivado()
            ->when($municipioEscopo !== null, fn ($q) => $q->doMunicipio($municipioEscopo))
            ->when($filtros['ata_id'] ?? null, fn ($q, $id) => $q->where('ata_id', (int) $id))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->where('prestador_id', (int) $id))
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->where('municipio_id', (int) $id))
            ->when($filtros['lote_id'] ?? null, fn ($q, $id) => $q->where('lote_id', (int) $id))
            ->when($filtros['search'] ?? null, fn ($q, $termo) => $this->buscaAmpla($q, (string) $termo))
            // Vigencia que cruza a janela pedida (com a prorrogacao, se houver).
            ->when($filtros['vigencia_de'] ?? null, fn ($q, $d) => $q->whereRaw('COALESCE(dt_final_prorrogacao, dt_final) >= ?', [(string) $d]))
            ->when($filtros['vigencia_ate'] ?? null, fn ($q, $d) => $q->whereRaw('COALESCE(dt_inicio_prorrogacao, dt_inicio) <= ?', [(string) $d]))
            ->when(($filtros['caminhoes'] ?? null) === 'com', fn ($q) => $q->has('caminhoes'))
            ->when(($filtros['caminhoes'] ?? null) === 'sem', fn ($q) => $q->doesntHave('caminhoes'))
            ->when(
                filter_var($filtros['minha_fila'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($q) => $q->whereIn('fase_tramite', array_map(fn ($f) => $f->value, $fasesDoUsuario)),
            )
            // "Parado ha mais de N dias": a mesma data-base do card (ultimo
            // tramite; sem tramite, encerramento/ativacao/criacao), em SQL para
            // o corte por fase abaixo valer sobre o resultado ja filtrado.
            ->when((int) ($filtros['parado_ha'] ?? 0) > 0, fn ($q) => $q->whereRaw(
                "date_trunc('day', COALESCE((select max(t.created_at) from tdap_cronograma_tramites t where t.cronograma_id = tdap_cronogramas.id), encerrado_em, ativado_em, created_at)) < ?",
                [now()->startOfDay()->subDays((int) $filtros['parado_ha'])],
            ));

        // "Exibir apenas" uma fase (ex.: so os aprovados): o quadro vira uma
        // coluna so. Valor fora do enum e ignorado, nao zera a tela.
        $faseUnica = FaseCronograma::tryFrom((string) ($filtros['fase'] ?? ''));
        $fases = $faseUnica !== null ? [$faseUnica] : FaseCronograma::cases();

        // Teto aplicado NA CONSULTA, fase a fase: antes o painel trazia o
        // acervo inteiro e cortava em memoria. O total vem de um COUNT, entao
        // a coluna diz a verdade mesmo quando so parte dos cards e carregada.
        return array_map(function (FaseCronograma $fase) use ($base, $fasesDoUsuario): array {
            $daFase = (clone $base)->where('tdap_cronogramas.fase_tramite', $fase->value);

            $cards = (clone $daFase)
                ->with(['ata:id,numero', 'lote:id,numero,nome', 'municipio:id,nome,uf', 'prestador:id,nome'])
                ->withCount('caminhoes')
                ->withMax('tramites', 'created_at')
                // Entregues: os mais recentes primeiro; as demais, as mais antigas
                // (as que estao paradas ha mais tempo).
                ->when(
                    $fase === FaseCronograma::Entregue,
                    fn ($q) => $q->orderByDesc('encerrado_em')->orderByDesc('id'),
                    fn ($q) => $q->orderBy('dt_inicio')->orderBy('id'),
                )
                ->limit(self::LIMITE_POR_FASE)
                ->get();

            return [
                ...$fase->toArray(),
                'minha'  => in_array($fase, $fasesDoUsuario, true),
                'total'  => (clone $daFase)->count(),
                'cards'  => $cards->map(fn (Cronograma $c) => $this->card($c))->values()->all(),
            ];
        }, $fases);
    }

    /**
     * Busca do painel: numero, empenho e nota (Cronograma::buscar) e tambem
     * o nome do municipio e do prestador, que e como o operador procura.
     */
    private function buscaAmpla(\Illuminate\Database\Eloquent\Builder $q, string $termo): \Illuminate\Database\Eloquent\Builder
    {
        // Sem acento dos dois lados, por translate(): a extensao unaccent nao
        // e garantida em todo ambiente. "janauba" acha "Janaúba".
        $com = 'ÁÀÂÃÄÉÈÊËÍÌÎÏÓÒÔÕÖÚÙÛÜÇ';
        $sem = 'AAAAAEEEEIIIIOOOOOUUUUC';
        $like = '%'.strtr(mb_strtoupper(trim($termo)), array_combine(mb_str_split($com), str_split($sem))).'%';
        $semAcento = fn (string $coluna) => "translate(UPPER({$coluna}), '{$com}', '{$sem}') LIKE ?";

        return $q->where(function ($w) use ($termo, $like, $semAcento): void {
            $w->buscar($termo)
                ->orWhereHas('municipio', fn ($m) => $m->whereRaw($semAcento('nome'), [$like]))
                ->orWhereHas('prestador', fn ($p) => $p->whereRaw($semAcento('nome'), [$like]));
        });
    }

    /** @return array<string, mixed> */
    private function card(Cronograma $c): array
    {
        // Desde quando esta na fase: o ultimo tramite; sem tramite (acervo
        // anterior ao Acompanhamento), a ativacao ou a criacao.
        $desde = $c->tramites_max_created_at
            ? Carbon::parse($c->tramites_max_created_at)
            : ($c->encerrado_em ?? $c->ativado_em ?? $c->created_at);

        return [
            'id'          => $c->id,
            'numero'      => $c->numero,
            'estado'      => $c->estado,
            'ata'         => $c->ata?->numero,
            'lote'        => $c->lote?->numero,
            'municipio'   => $c->municipio ? trim($c->municipio->nome.($c->municipio->uf ? '/'.$c->municipio->uf : '')) : null,
            'prestador'   => $c->prestador?->nome,
            'dt_inicio'   => $c->dt_inicio?->toDateString(),
            'dt_final'    => $c->dt_final_efetiva?->toDateString(),
            'caminhoes'   => (int) $c->caminhoes_count,
            'desde'       => $desde?->toIso8601String(),
            'dias_na_fase' => $desde ? (int) $desde->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
        ];
    }
}
