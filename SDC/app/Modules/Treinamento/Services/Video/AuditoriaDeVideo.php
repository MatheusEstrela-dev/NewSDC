<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Models\Modulo;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Models\Video;

/**
 * Trilha de auditoria das acoes sobre videos. Video nao e Rastreavel: o
 * registro vai para o curso (Treinamento) afetado.
 */
class AuditoriaDeVideo
{
    public function noCursoDoItem(ModuloItem $item, string $detalhe): void
    {
        $treinamentoId = Modulo::withTrashed()->whereKey($item->modulo_id)->value('treinamento_id');

        $this->registrar($treinamentoId, $detalhe);
    }

    /** Um registro por curso distinto com item ativo apontando para o video. */
    public function nosCursosQueUsam(Video $video, string $detalhe): void
    {
        Modulo::query()
            ->whereIn('id', $video->itens()->select('modulo_id'))
            ->distinct()
            ->pluck('treinamento_id')
            ->each(fn ($treinamentoId) => $this->registrar($treinamentoId, $detalhe));
    }

    private function registrar(int|string|null $treinamentoId, string $detalhe): void
    {
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamentoId,
            AcaoTrilha::Relacionado,
            $detalhe,
        );
    }
}
