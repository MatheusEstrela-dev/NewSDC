<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Services;

use App\Modules\Geoespacial\Jobs\AtualizarGoldGeoJob;
use App\Modules\Notificacoes\DTO\NotificacaoSpec;
use App\Modules\Notificacoes\Jobs\EntregarNotificacaoJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fila de revisao das camadas municipais e as duas decisoes possiveis.
 *
 * A fila NAO e matview: muda a cada decisao e precisa ler o estado do
 * instante. Um snapshot mostraria pendente o que acabou de ser aprovado, e o
 * revisor decidiria duas vezes sobre a mesma camada.
 */
final class RevisaoDeCamadas
{
    private const MODULO = 'geoespacial';

    /**
     * Pendentes, com o que o revisor precisa para decidir sem sair da tela.
     *
     * @return Collection<int, object>
     */
    public function pendentes(): Collection
    {
        return DB::table('silver.geo_camadas as c')
            ->leftJoin('municipios as m', 'm.id', '=', 'c.municipio_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.enviado_por')
            ->selectRaw("
                c.id, c.nome, c.dominio, c.nivel, c.emitido_em, c.valido_ate,
                c.arquivo_nome, c.created_at,
                m.nome AS municipio_nome, m.codigo_ibge,
                u.name AS enviado_por_nome,
                (SELECT count(*) FROM silver.geo_feicoes f WHERE f.camada_id = c.id) AS feicoes,
                (SELECT round(sum(ST_Area(f.geom::geography) / 1000000)::numeric, 0)
                   FROM silver.geo_feicoes f WHERE f.camada_id = c.id) AS area_km2,
                -- Plausibilidade territorial: distancia entre o centroide da
                -- geometria enviada e o centroide do municipio do remetente.
                -- E AVISO, nao bloqueio: nao existe poligono municipal no
                -- banco (municipios tem so latitude/longitude), entao nao ha
                -- como afirmar que a area esta dentro do territorio. Municipio
                -- grande com area de risco na borda daria falso positivo.
                (SELECT round((ST_Distance(
                          ST_Centroid(ST_Collect(f.geom))::geography,
                          ST_SetSRID(ST_MakePoint(m.longitude::float8, m.latitude::float8), 4326)::geography
                        ) / 1000)::numeric, 1)
                   FROM silver.geo_feicoes f WHERE f.camada_id = c.id) AS distancia_km
            ")
            ->where('c.status', 'pendente')
            ->orderBy('c.created_at')
            ->get();
    }

    /** Aprova e publica: e o refresh do Gold que torna a geometria visivel. */
    public function aprovar(int $camadaId, int $revisorId): void
    {
        $camada = $this->pendenteOuFalha($camadaId);

        DB::table('silver.geo_camadas')->where('id', $camadaId)->update([
            'status' => 'aprovada',
            'revisado_por' => $revisorId,
            'revisado_em' => now(),
            'motivo_recusa' => null,
            'updated_at' => now(),
        ]);

        // Sem isto a camada fica aprovada no Silver e invisivel no mapa: o
        // gold.geo_feicao_mapa filtra por status e nao se refaz sozinho.
        AtualizarGoldGeoJob::dispatch();

        $this->avisarRemetente(
            $camada,
            'Camada aprovada',
            "A camada \"{$camada->nome}\" foi aprovada e ja aparece no mapa estadual.",
            'success'
        );
    }

    /**
     * Recusa com motivo. O motivo e obrigatorio por decisao de produto: recusa
     * sem justificativa deixa o municipio sem saber o que corrigir, e ele
     * reenvia o mesmo arquivo.
     */
    public function recusar(int $camadaId, int $revisorId, string $motivo): void
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException('A recusa exige motivo.');
        }

        $camada = $this->pendenteOuFalha($camadaId);

        DB::table('silver.geo_camadas')->where('id', $camadaId)->update([
            'status' => 'recusada',
            'revisado_por' => $revisorId,
            'revisado_em' => now(),
            'motivo_recusa' => $motivo,
            'updated_at' => now(),
        ]);

        // Sem refresh do Gold aqui de proposito: recusada nunca esteve no mapa,
        // porque nasceu pendente. Refazer a matview seria I/O para nada.

        $this->avisarRemetente(
            $camada,
            'Camada recusada',
            "A camada \"{$camada->nome}\" foi recusada. Motivo: {$motivo}",
            'warning'
        );
    }

    /** Avisa a CEDEC de que ha camada nova esperando revisao. */
    public function avisarRevisores(int $camadaId): void
    {
        $camada = DB::table('silver.geo_camadas as c')
            ->leftJoin('municipios as m', 'm.id', '=', 'c.municipio_id')
            ->selectRaw('c.id, c.nome, m.nome AS municipio_nome')
            ->where('c.id', $camadaId)
            ->first();

        if ($camada === null) {
            return;
        }

        $revisores = DB::table('users as u')
            ->join('model_has_permissions as mp', function ($j): void {
                $j->on('mp.model_id', '=', 'u.id')->where('mp.model_type', '=', \App\Models\User::class);
            })
            ->join('permissions as p', 'p.id', '=', 'mp.permission_id')
            ->where('p.name', 'geoespacial.camadas.revisar')
            ->whereNull('u.deleted_at')
            ->pluck('u.id')
            ->all();

        if ($revisores === []) {
            // Nao ha a quem avisar. Nao e erro: em base recem-instalada
            // ninguem tem a permissao ainda, e a camada segue pendente na fila
            // esperando alguem com acesso.
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: self::MODULO,
                titulo: 'Camada municipal aguardando revisao',
                mensagem: sprintf(
                    '%s enviou a camada "%s".',
                    $camada->municipio_nome ?? 'Um municipio',
                    $camada->nome
                ),
                tipo: 'info',
                acaoUrl: '/geoespacial/revisao',
                acaoTexto: 'Revisar',
            ),
            $revisores,
        );
    }

    private function pendenteOuFalha(int $camadaId): object
    {
        $camada = DB::table('silver.geo_camadas')->where('id', $camadaId)->first();

        if ($camada === null) {
            throw new RuntimeException("Camada {$camadaId} nao existe.");
        }

        // Guarda contra decisao dupla: dois revisores com a fila aberta ao
        // mesmo tempo clicariam em aprovar, e o segundo sobrescreveria o
        // revisado_por do primeiro sem que ninguem notasse.
        if ($camada->status !== 'pendente') {
            throw new RuntimeException(
                "A camada \"{$camada->nome}\" ja foi {$camada->status} e nao esta mais em revisao."
            );
        }

        return $camada;
    }

    private function avisarRemetente(object $camada, string $titulo, string $mensagem, string $tipo): void
    {
        if ($camada->enviado_por === null) {
            return;
        }

        EntregarNotificacaoJob::dispatch(
            new NotificacaoSpec(
                modulo: self::MODULO,
                titulo: $titulo,
                mensagem: $mensagem,
                tipo: $tipo,
                acaoUrl: '/geoespacial',
                acaoTexto: 'Ver camadas',
            ),
            [(int) $camada->enviado_por],
        );
    }
}
