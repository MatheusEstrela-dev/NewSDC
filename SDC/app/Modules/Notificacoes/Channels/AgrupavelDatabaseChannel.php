<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\Channels;

use App\Modules\Notificacoes\Contracts\Agrupavel;
use App\Modules\Notificacoes\Models\Notificacao;
use App\Modules\Notificacoes\Support\JanelaAgrupamento;
use App\Support\Database\ConexaoDeLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Substitui o canal 'database' do Laravel para persistir notificacoes agrupadas.
 *
 * Diferenca em relacao ao canal nativo: em vez de sempre inserir uma linha, o
 * canal mantem UMA linha por assunto (destinatario + group_key) enquanto ela nao
 * for lida e a janela nao virar, incrementando group_count e trocando o payload
 * pelo do evento mais recente.
 *
 * No Postgres isso e um unico INSERT ... ON CONFLICT DO UPDATE sobre o indice
 * unico parcial notifications_group_upsert_uidx: atomico, sem lock de aplicacao,
 * imune a corrida entre dois eventos simultaneos.
 *
 * Nos demais drivers (sem indice parcial) o mesmo efeito e obtido com lock curto
 * no cache mais leitura e escrita. Producao roda Postgres; este segundo caminho
 * existe para nao travar ambientes de teste ou instalacoes legadas em MySQL.
 */
class AgrupavelDatabaseChannel
{
    public function __construct(private readonly JanelaAgrupamento $janela) {}

    public function send(mixed $notifiable, Notification $notification): ?Notificacao
    {
        $dados = $this->payload($notifiable, $notification);
        $modulo = $notification instanceof Agrupavel ? $notification->modulo() : 'geral';
        $groupKey = $notification instanceof Agrupavel ? $notification->chaveDeAgrupamento() : null;

        // Modulo com janela zero nunca agrupa, mesmo que o gatilho informe chave.
        if (!$this->janela->agrupa($modulo)) {
            $groupKey = null;
        }

        $linha = $groupKey === null
            ? $this->inserir($notifiable, $notification, $dados, null, null)
            : $this->agrupar($notifiable, $notification, $dados, $modulo, $groupKey);

        if ($linha !== null && $notification instanceof Agrupavel) {
            $notification->registrarPersistencia((string) $linha->id, (int) $linha->group_count);
        }

        return $linha;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function agrupar(
        mixed $notifiable,
        Notification $notification,
        array $dados,
        string $modulo,
        string $groupKey
    ): ?Notificacao {
        $bucket = $this->janela->bucket($modulo);

        if ((new Notificacao)->getConnection()->getDriverName() === 'pgsql') {
            return $this->upsertPostgres($notifiable, $notification, $dados, $groupKey, $bucket);
        }

        return $this->agruparComLock($notifiable, $notification, $dados, $groupKey, $bucket);
    }

    /**
     * Caminho otimizado: uma unica ida ao banco resolve inserir-ou-somar.
     *
     * O ON CONFLICT infere o indice unico parcial repetindo o predicado
     * (WHERE read_at IS NULL). Linhas ja lidas ficam fora do indice, o que faz o
     * mesmo assunto voltar a agrupar do zero depois que o usuario limpa o inbox.
     *
     * Vai pela conexao do model (a de log), resolvida na hora da escrita, e por
     * ConexaoDeLog::gravar: no worker nao ha transacao aberta e a escrita e
     * imediata, com o RETURNING devolvendo a linha agrupada. Se um chamador
     * sincrono estiver dentro de transacao critica, a escrita e adiada para o
     * commit e o retorno e null -- o broadcast sai sem id e sem contagem, mas
     * a notificacao nao vira fantasma de um rollback.
     *
     * @param  array<string, mixed>  $dados
     */
    private function upsertPostgres(
        mixed $notifiable,
        Notification $notification,
        array $dados,
        string $groupKey,
        ?int $bucket
    ): ?Notificacao {
        $tabela = (new Notificacao)->getTable();
        $agora = now();

        $registro = [
            // O id vem da propria notificacao (o Laravel gera antes de chamar
            // os canais). Usar um uuid novo aqui faria a linha do banco ter id
            // diferente do que os outros canais reportam para o mesmo evento.
            'id' => (string) ($notification->id ?? Str::uuid7()),
            'type' => $this->tipo($notification),
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'data' => json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'group_key' => $groupKey,
            'group_bucket' => $bucket,
            'group_count' => 1,
            'last_event_at' => $agora,
            'created_at' => $agora,
            'updated_at' => $agora,
        ];

        $colunas = implode(', ', array_keys($registro));
        $marcadores = implode(', ', array_fill(0, count($registro), '?'));

        $linha = ConexaoDeLog::gravar(
            fn () => (new Notificacao)->getConnection()->selectOne(
                "INSERT INTO {$tabela} ({$colunas})
                 VALUES ({$marcadores})
                 ON CONFLICT (notifiable_type, notifiable_id, group_key, group_bucket)
                    WHERE read_at IS NULL
                 DO UPDATE SET
                    group_count   = {$tabela}.group_count + 1,
                    data          = EXCLUDED.data,
                    last_event_at = EXCLUDED.last_event_at,
                    updated_at    = EXCLUDED.updated_at
                 RETURNING *",
                array_values($registro),
                false
            ),
            // Na retentativa pela fila a linha entra so se nao houver grupo
            // aberto (insertOrIgnore): o evento conta como novo assunto ou se
            // perde no contador, nunca duplica o card.
            fn (): array => [$tabela, [$registro]],
        );

        return $linha === null ? null : (new Notificacao)->newFromBuilder((array) $linha);
    }

    /**
     * Caminho portavel: o lock evita que dois eventos simultaneos do mesmo
     * assunto criem duas linhas abertas.
     *
     * @param  array<string, mixed>  $dados
     */
    private function agruparComLock(
        mixed $notifiable,
        Notification $notification,
        array $dados,
        string $groupKey,
        ?int $bucket
    ): ?Notificacao {
        $lock = Cache::lock(
            sprintf('notif:group:%s:%s:%s', $notifiable->getKey(), $groupKey, $bucket ?? 'x'),
            (int) config('notificacoes.agrupamento.lock_segundos', 5)
        );

        $espera = (int) config('notificacoes.agrupamento.lock_espera_segundos', 3);

        return $lock->block($espera, function () use ($notifiable, $notification, $dados, $groupKey, $bucket) {
            $existente = Notificacao::query()
                ->doDestinatario($notifiable)
                ->naoLidas()
                ->where('group_key', $groupKey)
                ->where('group_bucket', $bucket)
                ->first();

            if ($existente === null) {
                return $this->inserir($notifiable, $notification, $dados, $groupKey, $bucket);
            }

            $existente->forceFill([
                'group_count' => $existente->group_count + 1,
                'data' => $dados,
                'last_event_at' => now(),
            ])->save();

            return $existente;
        });
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function inserir(
        mixed $notifiable,
        Notification $notification,
        array $dados,
        ?string $groupKey,
        ?int $bucket
    ): Notificacao {
        return Notificacao::create([
            'id' => $notification->id ?? (string) Str::uuid7(),
            'type' => $this->tipo($notification),
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'data' => $dados,
            'group_key' => $groupKey,
            'group_bucket' => $bucket,
            'group_count' => 1,
            'last_event_at' => now(),
            'read_at' => null,
        ]);
    }

    /**
     * Mesma convencao do canal nativo do Laravel: a coluna type guarda a classe
     * da notificacao, salvo quando ela declara um tipo proprio.
     */
    private function tipo(Notification $notification): string
    {
        return method_exists($notification, 'databaseType')
            ? $notification->databaseType()
            : $notification::class;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(mixed $notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            return $notification->toDatabase($notifiable);
        }

        // toArray nao e declarado na classe base Notification: so existe quando a
        // notificacao concreta o define. Sem payload, a linha nasce vazia em vez
        // de derrubar a entrega.
        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        return [];
    }
}
