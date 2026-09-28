<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\DemandaComentario;
use Carbon\CarbonImmutable;

final class ImportarComentarios extends EtapaBase
{
    public function __construct(MapaImportacao $mapa, private readonly ResolvedorUsuarioLegado $usuarios)
    {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'comentarios'; }
    protected function tabelaOrigem(): string { return 'comentarios'; }
    protected function tabelaDestino(): string { return 'task_comments'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }
        $autor = $this->usuarios->resolver((int) $linha->user_id);
        if ($autor === null) {
            return 'autor_nao_mapeado';
        }

        $comentario = $destinoExistente !== null ? DemandaComentario::find($destinoExistente) : null;
        $comentario ??= new DemandaComentario();
        $comentario->forceFill([
            'task_id' => $demandaId, 'user_id' => $autor, 'tipo' => 'comentario',
            'conteudo' => (string) $linha->comentario, 'interno' => false,
        ]);
        $comentario->created_at = CarbonImmutable::parse($linha->created_at);
        $comentario->timestamps = false;
        $comentario->saveQuietly();

        return (int) $comentario->id;
    }
}
