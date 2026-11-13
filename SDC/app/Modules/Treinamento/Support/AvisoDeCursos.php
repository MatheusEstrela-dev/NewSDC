<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Shared\Events\RecursoAtualizado;
use Throwable;

/**
 * Avisa as telas de curso abertas (aluno, professor, admin) que algo mudou.
 *
 * O aviso nao carrega dado: cada tela rebusca pela propria rota, que ja aplica
 * a visibilidade de quem esta olhando. Sao dois recursos porque a Area do
 * professor aceita duas permissoes e cada canal autoriza por uma so; ver
 * CanaisDeListagem.
 */
final class AvisoDeCursos
{
    public const RECURSO_CURSOS = 'treinamento-cursos';

    public const RECURSO_PROFESSOR = 'treinamento-professor';

    /**
     * Aviso e conveniencia: websocket fora do ar nao pode derrubar a acao que
     * ja foi gravada (pausar, inscrever). A tela desatualizada se resolve no
     * proximo carregamento; a falha fica registrada.
     */
    public static function avisar(): void
    {
        try {
            RecursoAtualizado::dispatch(self::RECURSO_CURSOS);
            RecursoAtualizado::dispatch(self::RECURSO_PROFESSOR);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
