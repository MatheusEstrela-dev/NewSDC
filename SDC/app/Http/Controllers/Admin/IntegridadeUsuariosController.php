<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Models\Orgao;
use App\Services\Admin\IntegridadeUsuariosService;
use App\Services\Export\CsvExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Painel de integridade do cadastro de usuarios.
 *
 * Somente leitura, por decisao de projeto: as duas acoes sao GET e nenhuma
 * escreve. Quem corrige e a tela de edicao de usuario que ja existe, uma conta
 * por vez, com a pessoa olhando. Ver IntegridadeUsuariosService para o porque.
 */
class IntegridadeUsuariosController extends Controller
{
    public function __construct(
        private readonly IntegridadeUsuariosService $service,
    ) {
        $this->middleware('can:users.view');
    }

    public function index(Request $request): Response
    {
        $regraId = (string) $request->query('regra', 'A2');

        if (! IntegridadeUsuariosService::regraExiste($regraId)) {
            $regraId = 'A2';
        }

        $filtros = $request->only(['orgao_id', 'search']);

        return Inertia::render('Admin/Permissions/Integridade/Index', [
            'resumo' => $this->service->resumo(),
            'regraSelecionada' => $regraId,
            'regra' => IntegridadeUsuariosService::REGRAS[$regraId],
            'linhas' => $this->service->linhas($regraId, $filtros),
            'filtros' => $filtros,
            'orgaos' => Orgao::query()
                ->select(['id', 'nome'])
                ->where('tipo', 'compdec')
                ->orderBy('nome')
                ->get(),
        ]);
    }

    public function exportar(Request $request, CsvExportService $csv): StreamedResponse
    {
        $regraId = (string) $request->query('regra', 'A2');

        abort_unless(IntegridadeUsuariosService::regraExiste($regraId), 404);

        // Os mesmos filtros da tela: exportar o conjunto inteiro embaixo de uma
        // tabela filtrada entrega um arquivo que nao corresponde ao que a
        // pessoa esta vendo.
        return $csv->export(
            $this->service->todasAsLinhas($regraId, $request->only(['orgao_id', 'search'])),
            ['Regra', 'Escopo', 'ID', 'Municipio', 'Orgao', 'Nome', 'Documento', 'E-mail', 'Evidencia'],
            fn (object $linha): array => [
                $regraId,
                $linha->escopo,
                $linha->ref_id,
                $linha->municipio,
                $linha->orgao_nome,
                $linha->nome,
                $linha->documento,
                $linha->email,
                $linha->evidencia,
            ],
            'integridade_usuarios_'.strtolower($regraId),
        );
    }
}
