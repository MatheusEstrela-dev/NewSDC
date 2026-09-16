<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Core\Events\DomainEvent;
use App\Core\Outbox\OutboxDispatcher;
use App\Models\User;
use App\Support\Perfil\OrgaoDeLotacao;
use App\Modules\Tdap\Domain\Events\ViagemValidadaV1;
use App\Modules\Tdap\DTOs\CronoViagemDTO;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CronoViagemService
{
    public function __construct(
        private readonly CronoCaminhaoService $cronoCaminhaoService,
        private readonly OutboxDispatcher $outbox,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listarDoCaminhao(int $cronoCaminhaoId, array $filtros = []): Collection
    {
        return CronoViagem::query()
            ->doCaminhao($cronoCaminhaoId)
            ->with(['validador:id,name'])
            ->when($filtros['status'] ?? null, function ($q, $status) {
                match ($status) {
                    'pendente'  => $q->pendente(),
                    'aprovada'  => $q->aprovada(),
                    'rejeitada' => $q->rejeitada(),
                    default     => null,
                };
            })
            ->orderByDesc('data_registro')
            ->get();
    }

    /**
     * Municipio ao qual o usuario esta restrito, ou null quando e estadual.
     *
     * Reusa OrgaoDeLotacao, a mesma regra que AjudaHumanitaria, PMDA e a
     * PedidoAhPolicy ja usam -- inclusive a cadeia de fallback (orgao
     * principal -> pivot is_principal -> unico orgao vinculado). Escrever uma
     * consulta propria aqui criaria uma segunda definicao de "de qual municipio
     * esta pessoa e".
     *
     * Null significa CEDEC: opera em ambito estadual e ve a fila inteira.
     */
    private function municipioDoUsuario(?User $user): ?int
    {
        return $user !== null ? OrgaoDeLotacao::municipioId($user) : null;
    }

    /**
     * Fila de viagens aguardando decisao.
     *
     * O eager load carrega o cronograma INTEIRO (e nao `id,numero`) porque a
     * tela passou a mostrar prazo, municipio, prestador e valor: os accessors
     * `estado`, `dt_final_efetiva` e `dias_restantes` dependem de colunas que a
     * projecao enxuta deixava de fora, e cada uma delas viraria uma consulta
     * extra por linha.
     *
     * Quem NAO e estadual so enxerga o proprio municipio, mesmo com permissao
     * de validar: a permissao diz o que a pessoa pode fazer, o escopo diz sobre
     * o que. Antes desta linha um coordenador municipal com `validar` via a
     * fila do estado inteiro.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listarPendentesValidacao(int $perPage = 25, array $filtros = [], ?User $usuario = null): LengthAwarePaginator
    {
        $municipioDoUsuario = $this->municipioDoUsuario($usuario);

        return CronoViagem::query()
            ->when($municipioDoUsuario !== null, fn ($q) => $q->doMunicipio($municipioDoUsuario))
            ->with([
                'cronoCaminhao.cronograma.municipio:id,nome,uf',
                'cronoCaminhao.cronograma.prestador:id,nome',
                'cronoCaminhao.cronograma.lote:id,numero,valor_m3',
                'cronoCaminhao.caminhao:id,placa,marca,modelo,capacidade_m3,ativo',
            ])
            ->pendente()
            ->whereHas('cronoCaminhao')
            ->when($filtros['search'] ?? null, function ($q, $termo): void {
                $termo = '%'.mb_strtoupper((string) $termo).'%';

                $q->whereHas('cronoCaminhao', function ($sub) use ($termo): void {
                    $sub->whereHas('caminhao', fn ($c) => $c->whereRaw('upper(placa) like ?', [$termo]))
                        ->orWhereHas('cronograma', fn ($c) => $c->whereRaw('upper(numero) like ?', [$termo]));
                });
            })
            ->when($filtros['municipio_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'cronoCaminhao.cronograma',
                fn ($c) => $c->where('municipio_id', (int) $id),
            ))
            ->when($filtros['prestador_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'cronoCaminhao.cronograma',
                fn ($c) => $c->where('prestador_id', (int) $id),
            ))
            ->when($filtros['cronograma_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'cronoCaminhao',
                fn ($c) => $c->where('cronograma_id', (int) $id),
            ))
            // Fila velha primeiro: quem espera ha mais tempo decide antes.
            ->orderBy('data_registro')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Fila do COMPDEC: viagens do municipio do usuario ainda sem confirmacao.
     *
     * O municipio e OBRIGATORIO e vem do usuario, nunca do request -- aceitar
     * `municipio_id` de fora daria a qualquer COMPDEC a fila de qualquer outro
     * com uma troca de querystring.
     *
     * Usuario sem municipio de lotacao recebe fila VAZIA, e nao a fila inteira:
     * cadastro incompleto nao pode virar acesso total. Falha fechada.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listarParaConfirmacao(User $usuario, int $perPage = 25, array $filtros = []): LengthAwarePaginator
    {
        $municipioId = $this->municipioDoUsuario($usuario);

        return CronoViagem::query()
            ->when(
                $municipioId !== null,
                fn ($q) => $q->doMunicipio($municipioId),
                fn ($q) => $q->whereRaw('1 = 0'),
            )
            ->naoConfirmada()
            ->whereHas('cronoCaminhao')
            ->with([
                'cronoCaminhao.cronograma.municipio:id,nome,uf',
                'cronoCaminhao.cronograma.prestador:id,nome',
                'cronoCaminhao.cronograma.lote:id,numero,valor_m3',
                'cronoCaminhao.caminhao:id,placa,marca,modelo,capacidade_m3,ativo',
            ])
            ->when($filtros['cronograma_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'cronoCaminhao',
                fn ($c) => $c->where('cronograma_id', (int) $id),
            ))
            ->orderBy('data_registro')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Confirma em lote as viagens que o municipio reconhece ter recebido.
     *
     * Duas checagens que NAO podem viver so na tela:
     *
     * 1. cada viagem tem de pertencer ao municipio do usuario -- os ids chegam
     *    do navegador e podem ser trocados a mao;
     * 2. a data final do cronograma tem de estar valida -- e o limite que a
     *    issue #62 pede ("Compdec so poder aceitar ou reprovar as viagens
     *    dentro do limite do cronograma").
     *
     * Confirmar NAO mexe em `validado`: o aceite para pagamento continua sendo
     * ato da CEDEC.
     *
     * @param  list<int>  $ids
     * @return array{confirmadas: int, recusadas: list<array{id: int, motivo: string}>}
     */
    public function confirmarEmLote(User $usuario, array $ids, ?string $observacao = null): array
    {
        $municipioId = $this->municipioDoUsuario($usuario);

        if ($municipioId === null || $ids === []) {
            return ['confirmadas' => 0, 'recusadas' => []];
        }

        $viagens = CronoViagem::query()
            ->whereIn('id', $ids)
            ->doMunicipio($municipioId)
            ->naoConfirmada()
            ->with('cronoCaminhao.cronograma')
            ->get();

        $recusadas = [];
        $confirmadas = 0;
        $hoje = now()->startOfDay();

        DB::transaction(function () use ($viagens, $usuario, $observacao, $hoje, &$recusadas, &$confirmadas): void {
            foreach ($viagens as $viagem) {
                $limite = $viagem->cronoCaminhao?->cronograma?->dt_final_efetiva;

                if ($limite !== null && $hoje->greaterThan($limite->copy()->startOfDay())) {
                    $recusadas[] = [
                        'id' => (int) $viagem->id,
                        'motivo' => 'Fora do limite do cronograma (encerrado em '.$limite->format('d/m/Y').').',
                    ];

                    continue;
                }

                $viagem->forceFill([
                    'confirmado_em'   => now(),
                    'confirmado_por'  => $usuario->id,
                    'obs_confirmacao' => $observacao,
                ])->save();

                $confirmadas++;
            }
        });

        return ['confirmadas' => $confirmadas, 'recusadas' => $recusadas];
    }

    /**
     * Contadores da fila, em UMA consulta.
     *
     * Mesmo padrao de CronogramaService::obterEstatisticas: COUNT(*) FILTER do
     * PostgreSQL em vez de uma consulta por card.
     *
     * RECEBE O MESMO USUARIO da listagem, e nao e detalhe: sem o recorte, o
     * card anunciava 12 pendentes acima de uma tabela vazia -- os numeros
     * contavam o estado inteiro enquanto a lista ja mostrava so o municipio de
     * quem olhava.
     *
     * @return array<string, int>
     */
    public function obterEstatisticas(?User $usuario = null): array
    {
        $municipioDoUsuario = $this->municipioDoUsuario($usuario);

        $linha = DB::table('tdap_crono_viagens as v')
            ->join('tdap_crono_caminhoes as cc', 'cc.id', '=', 'v.crono_caminhao_id')
            ->join('tdap_cronogramas as c', 'c.id', '=', 'cc.cronograma_id')
            ->whereNull('v.deleted_at')
            ->whereNull('v.validado')
            ->when($municipioDoUsuario !== null, fn ($q) => $q->where('c.municipio_id', $municipioDoUsuario))
            ->selectRaw('count(*) as pendentes')
            ->selectRaw("count(*) FILTER (WHERE v.data_registro < now() - interval '7 days') as aguardando_mais_de_7d")
            ->selectRaw('count(*) FILTER (WHERE c.encerrado_em is not null) as de_cronograma_encerrado')
            ->selectRaw('count(DISTINCT c.municipio_id) as municipios')
            ->first();

        return [
            'pendentes'               => (int) ($linha->pendentes ?? 0),
            'aguardando_mais_de_7d'   => (int) ($linha->aguardando_mais_de_7d ?? 0),
            'de_cronograma_encerrado' => (int) ($linha->de_cronograma_encerrado ?? 0),
            'municipios'              => (int) ($linha->municipios ?? 0),
        ];
    }

    public function registrar(CronoViagemDTO $dto): CronoViagem
    {
        return DB::transaction(function () use ($dto): CronoViagem {
            $cc = CronoCaminhao::query()
                ->with('cronograma:id,ativo,encerrado_em')
                ->findOrFail($dto->crono_caminhao_id);

            $cronograma = $cc->cronograma;

            if (! $cronograma?->ativo) {
                throw new \DomainException('So e possivel registrar viagens em cronogramas ativos.');
            }
            if ($cronograma->encerrado_em) {
                throw new \DomainException('Cronograma encerrado nao aceita novas viagens.');
            }

            /*
             * NAO ha checagem de `data_registro` dentro da vigencia do
             * cronograma, e isso e deliberado: 1.165 das 3.808 viagens da base
             * (31%) tem data anterior ao dt_inicio do proprio cronograma. Ou o
             * acervo legado esta errado, ou `data_registro` nao significa "dia
             * da viagem" nesta operacao. Ligar a regra aqui, com esse numero,
             * quebraria o registro de viagem em producao -- decisao de negocio,
             * nao de implementacao.
             */
            return CronoViagem::create([
                'crono_caminhao_id' => $dto->crono_caminhao_id,
                'data_registro'     => $dto->data_registro,
                'obs'               => $dto->obs,
                'validado'          => CronoViagem::STATUS_PENDENTE,
            ]);
        });
    }

    /**
     * Aprova ou rejeita uma viagem pendente.
     *
     * Na APROVACAO emite ViagemValidadaV1 no outbox, na mesma transacao do
     * update. Hoje o unico consumidor e o registro em tdap_historicos -- a
     * projecao do read-model e a EncerramentoSaga sairam com o modulo Processos.
     *
     * Rejeicao nao emite: nada foi entregue, nao ha execucao a acumular.
     */
    public function validar(int $id, bool $aprovada, ?string $obsAprovacao = null): CronoViagem
    {
        return DB::transaction(function () use ($id, $aprovada, $obsAprovacao): CronoViagem {
            $viagem = CronoViagem::query()->lockForUpdate()->findOrFail($id);

            if ($viagem->validado !== null) {
                throw new \DomainException('Viagem ja foi validada anteriormente.');
            }

            $viagem->update([
                'validado'          => $aprovada ? CronoViagem::STATUS_APROVADA : CronoViagem::STATUS_REJEITADA,
                'data_aprovacao'    => now(),
                'obs_aprovacao'     => $obsAprovacao,
                'user_validacao_id' => Auth::id(),
            ]);

            // Recalcula agregados do CronoCaminhao parent
            $this->cronoCaminhaoService->recalcularEntregas($viagem->crono_caminhao_id);

            if ($aprovada) {
                $this->publicarViagemValidada($viagem);
            }

            return $viagem->fresh();
        });
    }

    public function remover(int $id): bool
    {
        $viagem = CronoViagem::findOrFail($id);
        if ($viagem->validado === CronoViagem::STATUS_APROVADA) {
            throw new \DomainException('Viagem aprovada nao pode ser excluida. Rejeite primeiro.');
        }

        return DB::transaction(function () use ($viagem): bool {
            $cronoCaminhaoId = $viagem->crono_caminhao_id;
            $ok = (bool) $viagem->delete();
            $this->cronoCaminhaoService->recalcularEntregas($cronoCaminhaoId);

            return $ok;
        });
    }

    /**
     * Monta e persiste ViagemValidadaV1 com o contexto que os consumidores
     * esperam (ver ViagemValidadaV1::payload()).
     *
     * `processo_id` saiu do payload junto com o modulo Processos: o unico
     * consumidor era a EncerramentoSaga, e a chave chegava sempre nula porque
     * nenhuma tela jamais preencheu `processo_tdap_id`.
     */
    private function publicarViagemValidada(CronoViagem $viagem): void
    {
        $contexto = CronoCaminhao::query()
            ->with('caminhao:id,capacidade_m3')
            ->find($viagem->crono_caminhao_id);

        $this->outbox->persist(new ViagemValidadaV1(
            eventId:       DomainEvent::newId(),
            aggregateType: 'crono_viagem',
            aggregateId:   (string) $viagem->id,
            occurredAt:    new \DateTimeImmutable(),
            metadata: [
                'viagem_id'         => $viagem->id,
                'crono_caminhao_id' => $viagem->crono_caminhao_id,
                'cronograma_id'     => $contexto?->cronograma_id,
                'capacidade_m3'     => (float) ($contexto?->caminhao?->capacidade_m3 ?? 0),
                'user_id'           => Auth::id(),
            ],
        ));
    }
}
