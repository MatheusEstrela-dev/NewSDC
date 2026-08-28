<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Plantao\Enums\StatusEscala;
use App\Modules\Plantao\Enums\StatusEscalaItem;
use App\Modules\Plantao\Exceptions\EscalaInvalidaException;
use App\Modules\Plantao\Models\Escala;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Models\Plantonista;
use App\Modules\Plantao\Models\TipoTurno;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Monta, valida e publica a escala do mes.
 *
 * Fronteira deliberada: este servico NAO abre turno. Assumir a vaga chama o
 * PassagemServicoService que ja existia -- a escala apenas pre-preenche data,
 * periodo e plantonista. Ter um segundo caminho de abertura foi exatamente o
 * furo (F-6) encontrado na revisao final da release anterior.
 */
class EscalaService
{
    /**
     * Escala do mes, criada sob demanda em rascunho.
     *
     * firstOrCreate e nao create: a tela de montagem e a acao "abrir o mes que
     * vem" chegam aqui pelo mesmo caminho, e o indice unico parcial
     * (ano, mes) WHERE deleted_at IS NULL rejeitaria a segunda chamada.
     */
    public function obterOuCriar(int $ano, int $mes, User $autor): Escala
    {
        $this->validarCompetencia($ano, $mes);

        $existente = Escala::doMes($ano, $mes)->first();

        if ($existente !== null) {
            return $existente;
        }

        return Escala::create([
            'ano' => $ano,
            'mes' => $mes,
            'status' => StatusEscala::RASCUNHO->value,
            'criada_por_id' => $autor->id,
        ]);
    }

    /**
     * Acrescenta uma vaga. Valida antes de gravar para devolver mensagem util;
     * o indice unico do banco continua sendo a rede de seguranca contra corrida.
     */
    public function adicionarItem(Escala $escala, array $dados): EscalaItem
    {
        $this->garantirEditavel($escala);

        $tipo = $this->tipoEscalavel((int) $dados['tipo_turno_id']);
        $data = Carbon::parse($dados['data'])->startOfDay();
        $plantonista = $this->plantonistaAtivo((int) $dados['plantonista_id']);

        if (!$escala->contemData($data)) {
            throw new EscalaInvalidaException(
                'A data '.$data->format('d/m/Y').' nao pertence a escala de '.$escala->rotulo().'.'
            );
        }

        $candidato = new EscalaItem([
            'escala_id' => $escala->id,
            'tipo_turno_id' => $tipo->id,
            'data' => $data,
            'plantonista_id' => $plantonista->user_id,
            'plantonista_nome' => $plantonista->nomeComPosto(),
            'status' => StatusEscalaItem::ESCALADO->value,
        ]);
        $candidato->setRelation('tipoTurno', $tipo);

        $this->garantirSemConflito($candidato);

        try {
            $candidato->save();
        } catch (QueryException $e) {
            throw new EscalaInvalidaException(
                'A vaga de '.$data->format('d/m/Y').' ('.$tipo->label().') ja esta preenchida.',
                previous: $e,
            );
        }

        if ($this->estaPublicada($escala)) {
            $this->avisarEscalado($candidato);
        }

        return $candidato;
    }

    /**
     * Troca o plantonista de uma vaga ja existente.
     *
     * Em escala publicada avisa os DOIS lados: quem entrou precisa saber que
     * assumiu, e quem saiu precisa saber que nao precisa mais vir -- sem o
     * segundo aviso, a pessoa aparece para um turno que nao e mais dela.
     */
    public function trocarPlantonista(EscalaItem $item, int $novoPlantonistaId): EscalaItem
    {
        $escala = $item->escala;
        $this->garantirEditavel($escala);

        $anteriorId = (int) $item->plantonista_id;

        if ($anteriorId === $novoPlantonistaId) {
            return $item;
        }

        $novo = $this->plantonistaAtivo($novoPlantonistaId);

        $item->plantonista_id = $novo->user_id;
        $item->plantonista_nome = $novo->nomeComPosto();

        $item->loadMissing('tipoTurno');
        $this->garantirSemConflito($item);

        $item->save();

        if ($this->estaPublicada($escala)) {
            $this->avisarEscalado($item);
            $this->avisarRemocao($item, $anteriorId);
        }

        return $item;
    }

    /**
     * Remove a vaga. Em escala publicada, avisa quem perdeu o turno.
     */
    public function removerItem(EscalaItem $item): void
    {
        $escala = $item->escala;
        $this->garantirEditavel($escala);

        $item->loadMissing('tipoTurno');
        $plantonistaId = (int) $item->plantonista_id;
        $publicada = $this->estaPublicada($escala);

        $item->delete();

        if ($publicada) {
            $this->avisarRemocao($item, $plantonistaId);
        }
    }

    /**
     * Publica a escala: a transicao que tira o mes do rascunho e avisa todo
     * mundo de uma vez.
     *
     * O fan-out sai em UM job por plantonista distinto, com a lista das datas
     * dele. Um card por vaga transformaria a publicacao de um mes em trinta
     * notificacoes por pessoa, e o usuario desliga o sino.
     */
    public function publicar(Escala $escala, User $autor): Escala
    {
        if ($this->estaPublicada($escala)) {
            throw new EscalaInvalidaException('Esta escala ja foi publicada.');
        }

        $this->garantirEditavel($escala);

        $itens = $escala->itens()->with('tipoTurno')->get();

        if ($itens->isEmpty()) {
            throw new EscalaInvalidaException('Nao ha vagas preenchidas para publicar.');
        }

        DB::transaction(function () use ($escala, $autor): void {
            $escala->update([
                'status' => StatusEscala::PUBLICADA->value,
                'publicada_em' => now(),
                'publicada_por_id' => $autor->id,
            ]);
        });

        $this->avisarPublicacao($escala, $itens);

        return $escala->refresh();
    }

    /**
     * Exclusao suave da escala inteira.
     *
     * ARMADILHA: a cascata da FK so vale para delete fisico. Sem apagar os
     * itens aqui, eles ficariam com deleted_at nulo e o indice unico parcial
     * plantao_escala_itens_vaga_unica impediria montar o mes de novo.
     */
    public function excluir(Escala $escala): void
    {
        DB::transaction(function () use ($escala): void {
            $escala->itens()->delete();
            $escala->delete();
        });
    }

    /**
     * As vagas de um intervalo, ordenadas por data e pela ordem do tipo de
     * turno -- que e como o calendario e a lista mobile precisam receber.
     *
     * @return Collection<int, EscalaItem>
     */
    public function itensNoIntervalo(Carbon $de, Carbon $ate, ?int $apenasDoPlantonista = null): Collection
    {
        return EscalaItem::query()
            ->with(['tipoTurno', 'escala', 'plantao:id,escala_item_id,status'])
            ->entre($de, $ate)
            ->when($apenasDoPlantonista !== null, fn ($q) => $q->doPlantonista($apenasDoPlantonista))
            ->get()
            // Chave composta em string: sortBy com array de closures nao e
            // API valida do Collection, e ordenar so por data deixaria noturno
            // antes de diurno no mesmo dia conforme a ordem de insercao.
            ->sortBy(fn (EscalaItem $item) => $item->data->toDateString()
                .'-'.str_pad((string) ($item->tipoTurno?->ordem ?? 0), 3, '0', STR_PAD_LEFT))
            ->values();
    }

    // ─── Validacao ──────────────────────────────────────────────────────────

    /**
     * Sobreposicao de horario do mesmo plantonista bloqueia.
     *
     * Necessario porque o indice unico do banco cobre (data, tipo_turno_id) e
     * NAO pega a mesma pessoa em 06h-16h e 08h-20h no mesmo dia: sao tipos
     * diferentes cujos intervalos se cruzam.
     *
     * A janela consultada e de um dia para tras e um para frente: turno que
     * vira o dia (20h-08h) invade a data seguinte, e um turno de ontem pode
     * terminar dentro do de hoje.
     */
    private function garantirSemConflito(EscalaItem $candidato): void
    {
        if (config('plantao.escala.bloquear_sobreposicao') !== true) {
            return;
        }

        $vizinhos = EscalaItem::query()
            ->with('tipoTurno')
            ->doPlantonista((int) $candidato->plantonista_id)
            ->entre(
                $candidato->data->copy()->subDay(),
                $candidato->data->copy()->addDay(),
            )
            ->when(
                $candidato->exists,
                fn ($q) => $q->whereKeyNot($candidato->getKey()),
            )
            ->get();

        foreach ($vizinhos as $vizinho) {
            if ($candidato->conflitaCom($vizinho)) {
                throw new EscalaInvalidaException(sprintf(
                    '%s ja esta escalado em %s (%s), que se sobrepoe a este turno.',
                    $candidato->plantonista_nome,
                    $vizinho->data->format('d/m/Y'),
                    $vizinho->tipoTurno?->label() ?? 'turno',
                ));
            }
        }
    }

    /**
     * Avisos NAO bloqueantes que a tela mostra ao montador. Emenda de turno
     * acontece e as vezes e inevitavel: o sistema registra que foi consciente,
     * em vez de impedir a operacao.
     *
     * @return list<string>
     */
    public function alertasDeDescanso(EscalaItem $item): array
    {
        $minimo = (int) config('plantao.escala.intervalo_minimo_horas', 8);

        $item->loadMissing('tipoTurno');

        $vizinhos = EscalaItem::query()
            ->with('tipoTurno')
            ->doPlantonista((int) $item->plantonista_id)
            ->entre($item->data->copy()->subDay(), $item->data->copy()->addDay())
            ->whereKeyNot($item->getKey())
            ->get();

        $alertas = [];

        foreach ($vizinhos as $vizinho) {
            $anterior = $vizinho->data->lte($item->data) ? $vizinho : $item;
            $posterior = $anterior === $item ? $vizinho : $item;

            $intervalo = $anterior->horasDeIntervaloAte($posterior);

            if ($intervalo !== null && $intervalo >= 0 && $intervalo < $minimo) {
                $alertas[] = sprintf(
                    '%s tem apenas %.1fh de descanso entre o turno de %s e o de %s (minimo recomendado: %dh).',
                    $item->plantonista_nome,
                    $intervalo,
                    $anterior->data->format('d/m'),
                    $posterior->data->format('d/m'),
                    $minimo,
                );
            }
        }

        return $alertas;
    }

    private function estaPublicada(Escala $escala): bool
    {
        return $escala->status instanceof StatusEscala && $escala->status->publicada();
    }

    private function garantirEditavel(Escala $escala): void
    {
        if (!$escala->status instanceof StatusEscala || !$escala->status->editavel()) {
            throw new EscalaInvalidaException('Escala arquivada nao aceita alteracao de vagas.');
        }
    }

    private function validarCompetencia(int $ano, int $mes): void
    {
        if ($mes < 1 || $mes > 12) {
            throw new EscalaInvalidaException('Mes invalido: '.$mes.'.');
        }

        if ($ano < 2020 || $ano > 2100) {
            throw new EscalaInvalidaException('Ano fora do intervalo aceito: '.$ano.'.');
        }
    }

    private function tipoEscalavel(int $tipoTurnoId): TipoTurno
    {
        $tipo = TipoTurno::find($tipoTurnoId);

        if ($tipo === null || !$tipo->ativo) {
            throw new EscalaInvalidaException('Tipo de turno inexistente ou inativo.');
        }

        if (!$tipo->escalavel) {
            throw new EscalaInvalidaException(
                $tipo->nome.' nao pode ser escalado: e um turno sem horario definido, aberto so na hora.'
            );
        }

        return $tipo;
    }

    private function plantonistaAtivo(int $userId): Plantonista
    {
        $plantonista = Plantonista::with('user')
            ->where('user_id', $userId)
            ->first();

        if ($plantonista === null) {
            throw new EscalaInvalidaException('Usuario nao esta cadastrado como plantonista.');
        }

        if (!$plantonista->ativo) {
            throw new EscalaInvalidaException(
                $plantonista->nomeComPosto().' esta inativo e nao pode ser escalado.'
            );
        }

        return $plantonista;
    }

    // ─── Notificacao ────────────────────────────────────────────────────────

    /**
     * Um card por plantonista, listando as datas dele naquele mes.
     *
     * @param  Collection<int, EscalaItem>  $itens
     */
    private function avisarPublicacao(Escala $escala, Collection $itens): void
    {
        foreach ($itens->groupBy('plantonista_id') as $plantonistaId => $doPlantonista) {
            $ordenados = $doPlantonista->sortBy(fn (EscalaItem $i) => $i->data->toDateString());

            $datas = $ordenados
                ->take(6)
                ->map(fn (EscalaItem $i) => trim($i->data->format('d/m').' '.($i->tipoTurno?->labelCurto() ?? '')))
                ->implode(', ');

            $total = $ordenados->count();
            $sufixo = $total > 6 ? ' e mais '.($total - 6).'.' : '.';

            EntregarNotificacaoJob::dispatch(
                new NotificacaoSpec(
                    modulo: 'plantao',
                    titulo: 'Escala de '.$escala->rotulo().' publicada',
                    mensagem: 'Voce tem '.$total.' plantao(oes): '.$datas.$sufixo,
                    tipo: 'info',
                    groupKey: 'plantao:escala:'.$escala->id,
                    acaoUrl: '/plantao/escala',
                    acaoTexto: 'Ver escala',
                ),
                [(int) $plantonistaId],
            );
        }
    }

    private function avisarEscalado(EscalaItem $item): void
    {
        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'plantao',
                titulo: 'Voce foi escalado',
                mensagem: 'Plantao de '.$item->data->format('d/m/Y')
                    .' ('.($item->tipoTurno?->label() ?? 'turno').').',
                tipo: 'info',
                groupKey: 'plantao:escala:'.$item->escala_id,
                acaoUrl: '/plantao/escala',
                acaoTexto: 'Ver escala',
            ),
            [(int) $item->plantonista_id],
        );
    }

    private function avisarRemocao(EscalaItem $item, int $plantonistaId): void
    {
        if ($plantonistaId <= 0) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'plantao',
                titulo: 'Voce saiu de um plantao',
                mensagem: 'O plantao de '.$item->data->format('d/m/Y')
                    .' ('.($item->tipoTurno?->label() ?? 'turno').') nao e mais seu.',
                tipo: 'warning',
                groupKey: 'plantao:escala:'.$item->escala_id,
                acaoUrl: '/plantao/escala',
                acaoTexto: 'Ver escala',
            ),
            [$plantonistaId],
        );
    }
}
