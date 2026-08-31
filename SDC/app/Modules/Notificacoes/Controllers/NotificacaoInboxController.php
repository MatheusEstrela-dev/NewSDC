<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notificacoes\Resources\NotificacaoResource;
use App\Modules\Notificacoes\Models\Notificacao;
use App\Modules\Notificacoes\Services\ArquivadorDeNotificacoes;
use App\Modules\Notificacoes\Services\ContadorNaoLidas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Inbox de notificacoes do usuario autenticado.
 *
 * O painel do sino chama index() de forma repetida (polling quando o websocket
 * nao esta disponivel), por isso a resposta carrega ETag: quando nada mudou desde
 * a ultima leitura, o servidor devolve 304 sem corpo e sem serializar nada. O
 * custo de um ciclo de polling ocioso cai para praticamente o do handshake HTTP.
 *
 * Todas as acoes sao escopadas ao proprio usuario pela query, nunca pelo id vindo
 * do cliente: um id de notificacao de outra pessoa simplesmente nao e encontrado.
 */
class NotificacaoInboxController extends Controller
{
    public function __construct(private readonly ContadorNaoLidas $contador) {}

    /**
     * Previa para o painel do sino: as N notificacoes mais recentes, lidas ou nao
     * (N em config notificacoes.inbox.painel_max), mais o total real de nao lidas
     * para o badge.
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();

        // O painel e uma previa: sempre as N mais recentes, lidas ou nao. Quem quer
        // a lista completa vai para o historico. Isso mantem o payload previsivel e
        // evita que o dropdown cresca conforme o usuario acumula avisos.
        $limite = (int) config('notificacoes.inbox.painel_max', 4);

        $itens = $this->base($request)
            ->maisRecentesPrimeiro()
            ->limit($limite)
            ->get();

        // Conta no banco e regrava o cache: a resposta que traz os cards nao pode
        // trazer um badge de outra epoca. Custa um index-only scan sobre o indice
        // parcial de nao lidas.
        $total = $this->contador->recalcular($user);

        $payload = [
            'items' => NotificacaoResource::collection($itens)->resolve(),
            'unread_count' => $total,

            // O cliente precisa do limite para cortar a lista quando um card chega
            // por websocket, senao o painel cresceria alem da previa configurada.
            // Mandar daqui mantem config/notificacoes.php como fonte unica.
            'limit' => $limite,
        ];

        // A assinatura cobre o que o cliente ve: quantidade nao lida e a versao
        // mais nova entre as linhas retornadas (updated_at muda tambem quando um
        // agrupamento incrementa o contador, sem criar linha).
        $etag = $this->assinatura($itens, $total);

        if (trim((string) $request->header('If-None-Match'), '"') === $etag) {
            return response()->noContent(Response::HTTP_NOT_MODIFIED)
                ->setEtag($etag)
                ->header('Cache-Control', 'private, must-revalidate');
        }

        return response()->json($payload)
            ->setEtag($etag)
            ->header('Cache-Control', 'private, must-revalidate');
    }

    /**
     * Marca uma notificacao como lida.
     */
    public function lida(Request $request, string $notificacao): JsonResponse
    {
        $alvo = $this->base($request)->whereKey($notificacao)->first();

        if ($alvo === null) {
            return response()->json(['message' => 'Notificacao nao encontrada.'], Response::HTTP_NOT_FOUND);
        }

        if ($alvo->read_at === null) {
            $alvo->markAsRead();
            $this->contador->invalidar($request->user()->getKey());
        }

        return response()->json(['unread_count' => $this->contador->para($request->user())]);
    }

    /**
     * Marca um conjunto de notificacoes como lidas.
     *
     * Serve o caso do card agrupado na UI, que representa varias linhas quando o
     * agrupamento aconteceu em janelas diferentes.
     */
    public function lidas(Request $request): JsonResponse
    {
        $validado = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'string', 'uuid'],
        ]);

        $afetadas = $this->base($request)
            ->naoLidas()
            ->whereIn('id', $validado['ids'])
            ->update(['read_at' => now()]);

        if ($afetadas > 0) {
            $this->contador->invalidar($request->user()->getKey());
        }

        return response()->json([
            'marcadas' => $afetadas,
            'unread_count' => $this->contador->para($request->user()),
        ]);
    }

    /**
     * "Ler todas" do cabecalho do painel.
     */
    public function todasLidas(Request $request): JsonResponse
    {
        $afetadas = $this->base($request)->naoLidas()->update(['read_at' => now()]);

        $this->contador->invalidar($request->user()->getKey());

        return response()->json(['marcadas' => $afetadas, 'unread_count' => 0]);
    }

    /**
     * Historico completo, paginado. Destino do botao "Ver Historico Completo",
     * que ate agora nao levava a lugar nenhum.
     */
    /**
     * Esvazia o sino de quem pediu.
     *
     * ARQUIVA, nao apaga: as linhas vao para notifications_archive, mesma
     * tratativa do notificacoes:arquivar e do padrao do projeto de preservar
     * para auditoria. O sino fica limpo, o historico continua consultavel.
     *
     * Escopo pelo `base($request)`, que ja recorta pelo destinatario -- nao ha
     * caminho aqui para limpar a caixa de outra pessoa.
     */
    public function limpar(Request $request, ArquivadorDeNotificacoes $arquivador): JsonResponse
    {
        $arquivadas = $arquivador->arquivar($this->base($request));

        $this->contador->invalidar($request->user()->getKey());

        return response()->json(['arquivadas' => $arquivadas, 'unread_count' => 0]);
    }

    public function historico(Request $request): InertiaResponse
    {
        $filtros = $request->validate([
            'tipo' => ['sometimes', 'nullable', 'string', 'in:'.implode(',', \App\Modules\Notificacoes\DTO\NotificacaoSpec::TIPOS)],
            'modulo' => ['sometimes', 'nullable', 'string', 'in:'.implode(',', array_keys((array) config('notificacoes.modulos', [])))],
            'apenas_nao_lidas' => ['sometimes', 'boolean'],
        ]);

        $query = $this->base($request)->maisRecentesPrimeiro();

        // data e jsonb: o filtro por severidade e por modulo e resolvido pelo
        // Postgres, sem trazer as linhas para a aplicacao.
        if (!empty($filtros['tipo'])) {
            $query->whereRaw("data->>'type' = ?", [$filtros['tipo']]);
        }

        if (!empty($filtros['modulo'])) {
            $query->whereRaw("data->>'module' = ?", [$filtros['modulo']]);
        }

        if ($request->boolean('apenas_nao_lidas')) {
            $query->naoLidas();
        }

        $pagina = $query->paginate((int) config('notificacoes.inbox.historico_por_pagina', 25))
            ->withQueryString();

        return Inertia::render('Notificacoes/Historico', [
            'notificacoes' => NotificacaoResource::collection($pagina),
            'filtros' => [
                'tipo' => $filtros['tipo'] ?? null,
                'modulo' => $filtros['modulo'] ?? null,
                'apenas_nao_lidas' => $request->boolean('apenas_nao_lidas'),
            ],
            'modulos' => collect((array) config('notificacoes.modulos', []))
                ->map(fn (array $m, string $slug): array => ['slug' => $slug, 'label' => $m['label'] ?? $slug])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Toda consulta parte daqui: escopo do destinatario autenticado.
     */
    private function base(Request $request): Builder
    {
        return Notificacao::query()->doDestinatario($request->user());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Notificacao>  $itens
     */
    private function assinatura(\Illuminate\Support\Collection $itens, int $naoLidas): string
    {
        $maisRecente = $itens
            ->map(fn (Notificacao $n) => $n->updated_at?->getTimestamp() ?? 0)
            ->max() ?? 0;

        return md5(sprintf('%d:%d:%d', $naoLidas, $itens->count(), $maisRecente));
    }
}
