<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Models\User;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Services\Versoes\NovaVersaoDoCursoService;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Unica fonte da regra "este curso admite nova versao / tem rascunho a continuar",
 * usada pela tela do curso e pela listagem de cursos.
 *
 * Elegivel: EAD, publicado, versao vigente e servidor (User) com cursos.edit. Com
 * rascunho em andamento o atalho vira "continuar rascunho" (nunca cria duplicata).
 * Recebe qualquer usuario autenticado: a mesma listagem serve o portal, onde o
 * logado e um Cidadao (nunca cria versao). Quem nao edita cursos sai sem consultar
 * o banco, o que tambem poupa as consultas de rascunho em cada linha da listagem.
 */
class AtalhoDeNovaVersao
{
    private const NENHUM = ['rascunho_id' => null, 'pode_criar_versao' => false, 'pode_continuar_rascunho' => false];

    public function __construct(private readonly NovaVersaoDoCursoService $versoes) {}

    /**
     * @return array{rascunho_id: ?int, pode_criar_versao: bool, pode_continuar_rascunho: bool}
     */
    public function para(Treinamento $treinamento, ?Authenticatable $usuario): array
    {
        if (! $usuario instanceof User || ! $usuario->can('treinamento.cursos.edit') || ! $treinamento->isEad()) {
            return self::NENHUM;
        }

        $rascunhoId = $treinamento->curso_id
            ? $this->versoes->rascunhoDe($treinamento->curso_id)?->id
            : null;

        $elegivel = $treinamento->publicado_em !== null && $treinamento->eVigente();

        return [
            'rascunho_id' => $rascunhoId,
            'pode_criar_versao' => $elegivel && $rascunhoId === null,
            'pode_continuar_rascunho' => $elegivel && $rascunhoId !== null,
        ];
    }
}
