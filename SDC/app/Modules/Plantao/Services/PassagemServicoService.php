<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use App\Modules\Plantao\Enums\StatusPlantao;
use App\Modules\Plantao\Exceptions\PassagemInvalidaException;
use App\Modules\Plantao\Models\EscalaItem;
use App\Modules\Plantao\Models\Plantao;
use App\Modules\Plantao\Models\Viatura;
use App\Modules\Plantao\Models\ViaturaSnapshot;
use App\Modules\Shared\BaseService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Ritual central da passagem de servico: quem sai declara o estado das
 * viaturas (encerrar) e quem assume confere e aceita ou aponta divergencia.
 * Substitui o repasse informal por texto (WhatsApp) por um handshake formal
 * com lastro no banco.
 */
class PassagemServicoService extends BaseService
{
    /**
     * Abre um turno. Preenche o plantonista de saida a partir do ultimo turno
     * conhecido; se nao houver antecessor, os campos ficam nulos e o relatorio
     * omite a linha "Saindo de servico". Um turno anterior ainda PENDENTE_ACEITE
     * nao bloqueia a abertura: so nao pode haver outro turno ATIVO na mesma
     * data e periodo.
     */
    public function abrirTurno(array $dados): Plantao
    {
        return DB::transaction(function () use ($dados): Plantao {
            $data = $dados['data'];
            $periodo = $dados['periodo'];

            $jaAtivo = Plantao::query()
                ->whereDate('data', $data)
                ->where('periodo', $periodo)
                ->where('status', StatusPlantao::ATIVO->value)
                ->exists();

            if ($jaAtivo) {
                throw new PassagemInvalidaException(
                    "Ja existe um plantao ativo para {$data} ({$periodo}). Encerre-o antes de abrir um novo turno para o mesmo periodo."
                );
            }

            $plantonista = User::findOrFail((int) $dados['plantonista_id']);
            $anterior = $this->turnoAnterior();

            // O exists() acima e check-then-insert: nao serializa duplo-clique
            // nem double-submit do formulario. Quem garante a exclusividade de
            // fato e o indice unico parcial plantoes_turno_ativo_unico
            // (data, periodo) WHERE status = 'ATIVO', criado na migration
            // 2026_08_26_100004. Se duas chamadas concorrentes passarem pelo
            // exists() ao mesmo tempo, o banco deixa so uma completar o
            // insert; a outra recebe a violacao de unicidade abaixo, traduzida
            // para o mesmo erro de dominio.
            try {
                return Plantao::create([
                    'plantonista_id' => $plantonista->id,
                    'plantonista_nome' => $plantonista->name,
                    'plantonista_saida_id' => $anterior?->plantonista_id,
                    'plantonista_saida_nome' => $anterior?->plantonista_nome,
                    'data' => $data,
                    'periodo' => $periodo,
                    // Vaga da escala que este turno cumpre, quando veio de la.
                    // Null e caso legitimo: turno aberto fora de escala.
                    'escala_item_id' => $dados['escala_item_id'] ?? null,
                    'status' => StatusPlantao::ATIVO,
                    'localizacao' => $dados['localizacao'] ?? 'Predio Alterosas',
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new PassagemInvalidaException(
                    "Ja existe um plantao ativo para {$data} ({$periodo}). Encerre-o antes de abrir um novo turno para o mesmo periodo."
                );
            }
        });
    }

    /**
     * Estado sugerido de cada viatura ativa, derivado do estado corrente que o
     * MovimentacaoViaturaService mantem (hodometro_atual, nivel_combustivel,
     * ultimo_condutor_*). Nao persiste: alimenta a tela de encerramento, onde
     * o plantonista confirma ou corrige linha a linha antes de gravar.
     *
     * @return list<array<string,mixed>>
     */
    public function montarSnapshotSugerido(Plantao $plantao): array
    {
        return Viatura::query()
            ->ativas()
            ->orderBy('prefixo')
            ->orderBy('placa')
            ->get()
            ->map(fn (Viatura $v) => [
                'viatura_id' => $v->id,
                'prefixo' => $v->prefixo,
                'placa' => $v->placa,
                'hodometro' => $v->hodometro_atual,
                'nivel_combustivel' => $v->nivel_combustivel?->value,
                'alteracoes' => null,
                // Texto livre, e so isso. A marca (Exclusiva Sobreaviso) NAO e
                // copiada para ca: o relatorio a deriva da flag booleana da
                // viatura (spec 3.3.1). Canalizar a flag para dentro do texto
                // fazia o relatorio depender do formato de um campo que a
                // Release 2 vai substituir pela entidade de reservas.
                'anotacao' => null,
                'ultimo_condutor_id' => $v->ultimo_condutor_id,
                'ultimo_condutor_nome' => $v->ultimo_condutor_nome,
                // Ponto de partida do formulario. O plantonista pode sobrescrever
                // na conferencia fisica: viatura formalmente disponivel pode nao
                // estar, de fato, em condicoes.
                'em_condicoes' => $v->status->emCondicoes(),
            ])
            ->all();
    }

    /**
     * Quem sai declara o estado de cada viatura. O turno vai para
     * PENDENTE_ACEITE aguardando a conferencia de quem assume.
     *
     * @param list<array<string,mixed>> $snapshots
     */
    public function encerrar(
        int $plantaoId,
        array $snapshots,
        ?string $ocorrenciasDestaque = null,
        ?int $encerradoPorId = null
    ): Plantao {
        $plantao = DB::transaction(function () use ($plantaoId, $snapshots, $ocorrenciasDestaque, $encerradoPorId): Plantao {
            $plantao = Plantao::query()->lockForUpdate()->findOrFail($plantaoId);

            if ($plantao->status !== StatusPlantao::ATIVO) {
                throw new PassagemInvalidaException(
                    "Somente um plantao ativo pode ser encerrado. O plantao #{$plantao->id} esta como {$plantao->status->label()}."
                );
            }

            $this->exigirLinhaPorViaturaAtiva($snapshots);

            foreach ($snapshots as $linha) {
                $viatura = Viatura::findOrFail((int) $linha['viatura_id']);

                ViaturaSnapshot::updateOrCreate(
                    [
                        'plantao_id' => $plantao->id,
                        'viatura_id' => $viatura->id,
                    ],
                    [
                        // Espelhos: o snapshot e registro historico. Vem da
                        // viatura no momento do encerramento, nao do request -
                        // se a placa mudar amanha, o relatorio de hoje continua
                        // fiel ao que foi declarado.
                        'prefixo' => $viatura->prefixo,
                        'placa' => $viatura->placa,
                        'hodometro' => (int) $linha['hodometro'],
                        'nivel_combustivel' => $linha['nivel_combustivel'],
                        'alteracoes' => $linha['alteracoes'] ?? null,
                        'ultimo_condutor_id' => $viatura->ultimo_condutor_id,
                        'ultimo_condutor_nome' => $viatura->ultimo_condutor_nome,
                        'anotacao' => $linha['anotacao'] ?? null,
                        'em_condicoes' => (bool) ($linha['em_condicoes'] ?? true),
                    ]
                );
            }

            $plantao->update([
                'status' => StatusPlantao::PENDENTE_ACEITE,
                'encerrado_em' => now(),
                // Sem quem chamou informado, assume-se o proprio plantonista.
                // Quando difere de plantonista_id, o encerramento foi feito por
                // terceiro (secao 4.3 do spec): o sistema registra, nao esconde.
                'encerrado_por_id' => $encerradoPorId ?? $plantao->plantonista_id,
                'ocorrencias_destaque' => $ocorrenciasDestaque,
            ]);

            return $plantao->fresh();
        });

        // FORA da transacao de proposito: o job entra na fila do Redis, que nao
        // participa do commit do Postgres. Despachado la dentro, o worker
        // poderia ler o turno antes do commit e nao encontrar o PENDENTE_ACEITE.
        $this->avisarPendenteDeAceite($plantao, $encerradoPorId);

        return $plantao;
    }

    /**
     * Coloca a pendencia de aceite no sino, e nao so no banner da tela.
     *
     * O banner do Plantao Diario so aparece para quem abre aquela pagina. Quem
     * assume o turno seguinte costuma chegar, olhar o celular e nao ter ideia de
     * que ha viatura para conferir -- a passagem ficava dependendo de alguem
     * lembrar de avisar por fora do sistema, que e exatamente o que esta release
     * veio substituir.
     *
     * Quem recebe, em ordem de preferencia:
     *
     * 1. o proximo escalado, quando ha escala publicada. E a pessoa que de fato
     *    vai conferir e aceitar;
     * 2. o dono do turno, quando um terceiro encerrou por ele (supervisao usando
     *    `encerrar_alheio`) -- ele precisa saber que o turno dele foi fechado;
     * 3. como ultimo recurso, quem tem `plantao.passagem.aceitar`, limitado por
     *    config: sem escala montada o sistema nao sabe quem assume, e avisar o
     *    grupo e melhor que nao avisar ninguem.
     *
     * O autor da acao nunca entra: o dispatcher ja o descartaria, mas manter a
     * exclusao aqui deixa a regra visivel.
     */
    private function avisarPendenteDeAceite(Plantao $plantao, ?int $encerradoPorId): void
    {
        $ator = $encerradoPorId ?? (int) $plantao->plantonista_id;

        $destinatarios = array_values(array_unique(array_filter(
            [
                $this->proximoEscalado($plantao),
                (int) $plantao->plantonista_id,
            ],
            static fn (?int $id): bool => $id !== null && $id > 0 && $id !== $ator,
        )));

        if ($destinatarios === []) {
            $destinatarios = $this->quemPodeAceitar($ator);
        }

        if ($destinatarios === []) {
            return;
        }

        $periodo = $plantao->tipoTurno?->labelCurto() ?? (string) $plantao->periodo;

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: 'plantao',
                titulo: 'Passagem de servico pendente de aceite',
                mensagem: sprintf(
                    '%s encerrou o turno de %s (%s). Confira as viaturas antes de aceitar.',
                    $plantao->encerradoPor?->name ?? $plantao->plantonista_nome,
                    $plantao->data?->format('d/m/Y') ?? '',
                    $periodo,
                ),
                // Pendencia operacional que trava o turno seguinte: vai para a
                // fila de prioridade, nao para a normal.
                tipo: 'urgent',
                // Por turno: um aceite pendente nunca pode ser fundido com
                // outro, senao o segundo some do sino.
                groupKey: 'plantao:aceite:'.$plantao->id,
                acaoUrl: '/plantao',
                acaoTexto: 'Conferir e aceitar',
            ),
            $destinatarios,
        );
    }

    /**
     * Quem esta escalado para o turno seguinte a este, em escala publicada.
     *
     * Janela de dois dias para frente porque turno que vira o dia (16h-02h,
     * 20h-08h) empurra o proximo inicio para a data seguinte.
     */
    private function proximoEscalado(Plantao $plantao): ?int
    {
        $plantao->loadMissing(['tipoTurno', 'escalaItem']);

        $fimDoTurno = $plantao->escalaItem?->fimEm();

        // Turno aberto fora de escala: sem ancora de horario, apontar o
        // "proximo" seria chute. Cai para as regras seguintes.
        if ($fimDoTurno === null) {
            return null;
        }

        $candidato = EscalaItem::query()
            ->with('tipoTurno')
            ->deEscalaPublicada()
            ->pendentes()
            ->entre($plantao->data->copy()->subDay(), $plantao->data->copy()->addDays(2))
            ->get()
            ->filter(fn (EscalaItem $item) => $item->inicioEm()?->gte($fimDoTurno) ?? false)
            ->sortBy(fn (EscalaItem $item) => $item->inicioEm()->getTimestamp())
            ->first();

        return $candidato === null ? null : (int) $candidato->plantonista_id;
    }

    /**
     * @return list<int>
     */
    private function quemPodeAceitar(int $ator): array
    {
        $limite = (int) config('plantao.aceite.max_destinatarios_fallback', 15);

        return User::permission('plantao.passagem.aceitar')
            ->where('active', true)
            ->whereKeyNot($ator)
            ->orderBy('id')
            ->limit($limite)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Guarda da secao 10 do spec: o snapshot exige uma linha por viatura ativa.
     *
     * O modal de encerramento monta as linhas a partir do `snapshot_sugerido`
     * que veio na carga da pagina. Sem esta guarda, uma viatura cadastrada (ou
     * reativada) depois disso simplesmente nao entrava no snapshot e desaparecia
     * do relatorio - sem erro, sem aviso, sem log. Um relatorio incompleto com
     * aparencia de completo. Vale tambem para submissao adulterada.
     *
     * @param list<array<string,mixed>> $snapshots
     */
    private function exigirLinhaPorViaturaAtiva(array $snapshots): void
    {
        $recebidos = array_map(
            static fn (array $linha): int => (int) ($linha['viatura_id'] ?? 0),
            $snapshots
        );

        $faltantes = Viatura::query()
            ->ativas()
            ->whereNotIn('id', $recebidos === [] ? [0] : $recebidos)
            ->orderBy('prefixo')
            ->orderBy('placa')
            ->get(['id', 'prefixo', 'placa']);

        if ($faltantes->isEmpty()) {
            return;
        }

        $nomes = $faltantes
            ->map(static fn (Viatura $v): string => "{$v->prefixo} - {$v->placa}")
            ->implode(', ');

        throw new PassagemInvalidaException(
            'O encerramento precisa de uma linha por viatura ativa da frota. '
            ."Faltando: {$nomes}. Recarregue a tela de encerramento - a frota mudou "
            .'depois que ela foi aberta.'
        );
    }

    /**
     * Quem assume confere o que foi declarado e aceita sem ressalvas.
     */
    public function aceitar(int $plantaoId, int $aceitoPorId): Plantao
    {
        return $this->concluir($plantaoId, $aceitoPorId, StatusPlantao::FINALIZADO, null);
    }

    /**
     * Quem assume confere o que foi declarado e nao concorda: registra o que
     * diverge em vez de aceitar calado.
     */
    public function apontarDivergencia(int $plantaoId, int $aceitoPorId, string $divergencia): Plantao
    {
        return $this->concluir(
            $plantaoId,
            $aceitoPorId,
            StatusPlantao::FINALIZADO_COM_DIVERGENCIA,
            $divergencia
        );
    }

    private function concluir(
        int $plantaoId,
        int $aceitoPorId,
        StatusPlantao $status,
        ?string $divergencia
    ): Plantao {
        return DB::transaction(function () use ($plantaoId, $aceitoPorId, $status, $divergencia): Plantao {
            $plantao = Plantao::query()->lockForUpdate()->findOrFail($plantaoId);

            if ($plantao->status !== StatusPlantao::PENDENTE_ACEITE) {
                throw new PassagemInvalidaException(
                    "Somente um plantao pendente de aceite pode ser conferido. O plantao #{$plantao->id} esta como {$plantao->status->label()}."
                );
            }

            // O aceite formal perde sentido se quem confere e o proprio dono
            // do turno (plantonista_id) - mesmo que um terceiro tenha operado
            // o encerramento em nome dele. A guarda protege as duas partes
            // serem pessoas distintas, nao quem apertou o botao de encerrar.
            if ((int) $plantao->plantonista_id === $aceitoPorId) {
                throw new PassagemInvalidaException(
                    "O plantonista {$plantao->plantonista_nome} nao pode aceitar a propria passagem de servico. E preciso que outra pessoa confira."
                );
            }

            $plantao->update([
                'status' => $status,
                'aceito_em' => now(),
                'aceito_por_id' => $aceitoPorId,
                'divergencia' => $divergencia,
            ]);

            return $plantao->fresh();
        });
    }

    private function turnoAnterior(): ?Plantao
    {
        return Plantao::query()
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->first();
    }
}
