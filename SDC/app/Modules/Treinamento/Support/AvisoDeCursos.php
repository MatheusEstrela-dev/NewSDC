<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Shared\Events\RecursoAtualizado;
use Throwable;

/**
 * Avisa as telas de curso abertas (aluno, professor, admin) que algo mudou.
 *
 * O aviso nao carrega dado: cada tela rebusca pela propria rota, que ja aplica
 * a visibilidade de quem esta olhando. Cursos e professor sao dois recursos
 * porque a Area do professor aceita duas permissoes e cada canal autoriza por
 * uma so; ver CanaisDeListagem.
 *
 * O painel de indicadores e um terceiro recurso: conta inscricao, turma,
 * avaliacao, certificado e catalogo. avisar() cobre tudo; quem muda so dado do
 * painel (avaliacao, certificado) usa avisarIndicadores(), e quem muda so o que
 * as telas de curso mostram (designar professor) usa avisarCursos(). Progresso
 * de item NAO passa por aqui: o painel nao o conta e e o evento mais frequente.
 */
final class AvisoDeCursos
{
    public const RECURSO_CURSOS = 'treinamento-cursos';

    public const RECURSO_PROFESSOR = 'treinamento-professor';

    public const RECURSO_INDICADORES = 'treinamento-indicadores';

    /** Mudanca que as telas de curso e o painel de indicadores enxergam. */
    public static function avisar(): void
    {
        self::despachar([self::RECURSO_CURSOS, self::RECURSO_PROFESSOR, self::RECURSO_INDICADORES]);
    }

    /** Mudanca que so as telas de curso enxergam. */
    public static function avisarCursos(): void
    {
        self::despachar([self::RECURSO_CURSOS, self::RECURSO_PROFESSOR]);
    }

    /** Mudanca que so o painel de indicadores enxerga. */
    public static function avisarIndicadores(): void
    {
        self::despachar([self::RECURSO_INDICADORES]);
    }

    /**
     * Aviso e conveniencia: websocket fora do ar nao pode derrubar a acao que
     * ja foi gravada (pausar, inscrever). A tela desatualizada se resolve no
     * proximo carregamento; a falha fica registrada.
     *
     * Sem coalescencia no servidor de proposito: descartar o 2o aviso de uma
     * janela deixaria o painel com o numero anterior ao ultimo commit, e nao
     * haveria 3o aviso para corrigir. A rajada e absorvida no cliente (debounce).
     *
     * @param list<string> $recursos
     */
    private static function despachar(array $recursos): void
    {
        try {
            foreach ($recursos as $recurso) {
                RecursoAtualizado::dispatch($recurso);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
