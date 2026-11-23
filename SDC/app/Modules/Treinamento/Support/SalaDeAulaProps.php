<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\Enums\NotaAvaliacaoCurso;
use App\Modules\Treinamento\Enums\NotaRelevanciaCurso;
use App\Modules\Treinamento\Enums\SituacaoAvaliacaoCurso;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\ConclusaoService;

/**
 * Props da pagina da sala de aula, as mesmas nos dois escopos.
 */
final class SalaDeAulaProps
{
    /**
     * @return array<string, mixed>
     */
    public static function de(Treinamento $curso, EstadoTrilha $estado, string $escopo, ?Inscricao $inscricao = null): array
    {
        return [
            'curso' => [
                'id' => $curso->id,
                'slug' => $curso->link_publico_slug,
                'titulo' => $curso->titulo,
                'carga_horaria' => $curso->carga_horaria,
            ],
            'versao_rotulo' => VersoesDoCurso::rotuloDe($curso),
            'estado' => $estado->toArray(),
            'escopo' => $escopo,
            'avaliacao_final' => $inscricao !== null ? self::avaliacaoFinal($inscricao, $estado) : null,
            'notas_avaliacao' => NotaAvaliacaoCurso::toSelectArray(),
            'notas_relevancia' => NotaRelevanciaCurso::toSelectArray(),
        ];
    }

    /**
     * Etapa final da trilha, gerada pelo sistema depois da ultima pasta (o
     * professor nao cadastra nada): a avaliacao do curso. A situacao vem do
     * backend na carga; durante a visita a sala libera a etapa sozinha quando
     * a trilha fecha (estado.concluidos >= estado.total), e o envio recarrega
     * esta prop com a conclusao e o certificado.
     *
     * @return array<string, mixed>
     */
    private static function avaliacaoFinal(Inscricao $inscricao, EstadoTrilha $estado): array
    {
        // A reavaliacao feita ao abrir a sala ja atualizou concluido_em nesta
        // mesma instancia (ConclusaoService::registrar): sem refresh.
        $inscricao->load(['avaliacao', 'certificado']);
        $avaliacao = $inscricao->avaliacao;
        $certificado = $inscricao->certificado;
        $requisitos = app(ConclusaoService::class)->requisitosCumpridos($inscricao, $estado);

        return [
            'situacao' => SituacaoAvaliacaoCurso::de($avaliacao !== null, $requisitos)->value,
            'concluido' => $inscricao->estaConcluida(),
            'concluido_em' => $inscricao->concluido_em?->toIso8601String(),
            'avaliacao' => $avaliacao === null ? null : [
                'nota' => $avaliacao->nota->value,
                'nota_label' => $avaliacao->nota->getLabel(),
                'nota_color' => $avaliacao->nota->getColor(),
                'nota_relevancia' => $avaliacao->nota_relevancia?->value,
                'nota_relevancia_label' => $avaliacao->nota_relevancia?->getLabel(),
                'comentario' => $avaliacao->comentario,
                'avaliado_em' => $avaliacao->created_at?->toIso8601String(),
            ],
            'certificado' => $certificado === null ? null : [
                'id' => $certificado->id,
                'disponivel' => $certificado->status->value === 'GERADO',
                'cancelado' => $certificado->estaCancelado(),
            ],
        ];
    }
}
