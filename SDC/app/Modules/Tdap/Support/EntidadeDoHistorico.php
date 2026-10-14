<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use App\Modules\Tdap\Models\Ata;
use App\Modules\Tdap\Models\Caminhao;
use App\Modules\Tdap\Models\CronoCaminhao;
use App\Modules\Tdap\Models\CronoViagem;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\Historico;
use App\Modules\Tdap\Models\Lote;
use App\Modules\Tdap\Models\Prestador;
use App\Modules\Tdap\Models\Vistoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Traduz o `entity_type#entity_id` cru do historico (#62, "Historico #
 * legado") para o que a pessoa reconhece -- "Cronograma CR-12/2026",
 * "Caminhao ABC1D23" -- com o link para o registro no banco atual.
 *
 * Carrega em lote, um SELECT por tipo, e inclui os excluidos: o evento de um
 * registro apagado continua no historico, mas sem link e marcado `excluido`.
 */
final class EntidadeDoHistorico
{
    /** tipo => [model, rota de exibicao, rotulo] */
    private const TIPOS = [
        'cronograma'     => [Cronograma::class, 'tdap.cronogramas.show', 'Cronograma'],
        'viagem'         => [CronoViagem::class, null, 'Viagem'],
        'vistoria'       => [Vistoria::class, 'tdap.frota.vistorias.show', 'Vistoria'],
        'crono_caminhao' => [CronoCaminhao::class, null, 'Caminhao alocado'],
        'prestador'      => [Prestador::class, 'tdap.prestadores.show', 'Prestador'],
        'caminhao'       => [Caminhao::class, 'tdap.frota.show', 'Caminhao'],
        'ata'            => [Ata::class, 'tdap.atas.show', 'Ata'],
        'lote'           => [Lote::class, 'tdap.lotes.show', 'Lote'],
    ];

    /**
     * Grava em cada historico o atributo transitorio `entidade_resolvida`
     * ({rotulo, url, excluido}), lido pelo HistoricoResource.
     *
     * @param  iterable<Historico>  $historicos
     */
    public static function resolver(iterable $historicos): void
    {
        $porTipo = [];
        foreach ($historicos as $h) {
            $porTipo[$h->entity_type][] = (int) $h->entity_id;
        }

        $carregados = [];
        foreach ($porTipo as $tipo => $ids) {
            $classe = self::TIPOS[$tipo][0] ?? null;
            if ($classe === null) {
                continue;
            }

            $query = $classe::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($classe), true)) {
                $query->withTrashed();
            }
            if ($classe === Vistoria::class) {
                $query->with('caminhao:id,placa');
            }
            if (in_array($classe, [CronoViagem::class, CronoCaminhao::class], true)) {
                $query->with($classe === CronoViagem::class ? 'cronoCaminhao.cronograma:id,numero' : 'cronograma:id,numero');
            }

            $carregados[$tipo] = $query->whereIn('id', array_unique($ids))->get()->keyBy('id');
        }

        foreach ($historicos as $h) {
            $h->setAttribute('entidade_resolvida', self::descrever(
                (string) $h->entity_type,
                (int) $h->entity_id,
                $carregados[$h->entity_type][(int) $h->entity_id] ?? null,
            ));
        }
    }

    /** "cronograma.pontos_alterados" -> "Cronograma pontos alterados". */
    public static function rotuloDoEvento(string $tipoEvento): string
    {
        return ucfirst(str_replace(['.', '_'], ' ', $tipoEvento));
    }

    /** @return array{rotulo: string, url: ?string, excluido: bool} */
    private static function descrever(string $tipo, int $id, ?Model $m): array
    {
        [, $rota, $nome] = self::TIPOS[$tipo] ?? [null, null, ucfirst(str_replace('_', ' ', $tipo))];

        if ($m === null) {
            return ['rotulo' => "{$nome} (registro nao encontrado)", 'url' => null, 'excluido' => true];
        }

        $excluido = method_exists($m, 'trashed') && $m->trashed();

        [$identificacao, $url] = match (true) {
            $m instanceof Cronograma    => [$m->numero, $rota ? route($rota, $m->id) : null],
            $m instanceof Prestador     => [$m->nome, route($rota, $m->id)],
            $m instanceof Caminhao      => [$m->placa, route($rota, $m->id)],
            $m instanceof Ata, $m instanceof Lote => [$m->numero, route($rota, $m->id)],
            $m instanceof Vistoria      => [trim(($m->caminhao?->placa ?? '') . ' ' . ($m->data?->format('d/m/Y') ?? '')), route($rota, $m->id)],
            $m instanceof CronoViagem   => self::doCronograma($m->cronoCaminhao?->cronograma, $m->data_registro?->format('d/m/Y')),
            $m instanceof CronoCaminhao => self::doCronograma($m->cronograma, null),
            default                     => [null, null],
        };

        return [
            'rotulo'   => trim($nome . ' ' . ($identificacao ?: '')),
            'url'      => $excluido ? null : $url,
            'excluido' => $excluido,
        ];
    }

    /** Viagem e alocacao nao tem tela propria: o link vai para o cronograma. */
    private static function doCronograma(?Cronograma $c, ?string $detalhe): array
    {
        $texto = trim(($detalhe ?? '') . ($c ? " no cronograma {$c->numero}" : ''));

        return [$texto, $c ? route('tdap.cronogramas.show', $c->id) : null];
    }
}
