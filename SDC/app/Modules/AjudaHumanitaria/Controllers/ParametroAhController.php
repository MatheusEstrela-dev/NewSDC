<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Models\ParametroAh;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parametros do modulo (RN-16).
 *
 * A permissao humanitaria.parametros.manage existia no catalogo desde o
 * inicio e nao tinha tela: o prazo da prestacao de contas so mudava por
 * acesso direto ao banco.
 *
 * A tabela tem uma linha unica, garantida por ParametroAh::atual(). Nao ha
 * criar nem excluir, so editar.
 */
class ParametroAhController extends Controller
{
    public function index(): Response
    {
        $parametros = ParametroAh::atual();

        return Inertia::render('AjudaHumanitaria/Parametros/Index', [
            'parametros' => [
                'prazo_prestacao_contas_dias' => $parametros->prazo_prestacao_contas_dias,
                'atualizado_em'               => $parametros->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            // O limite alto nao e capricho: prazo e contado em dias corridos a
            // partir do atendimento, e um valor absurdo aqui viraria prestacao
            // que nunca vence. Um ano e o teto que ainda faz sentido.
            'prazo_prestacao_contas_dias' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'prazo_prestacao_contas_dias.required' => 'Informe o prazo da prestação de contas.',
            'prazo_prestacao_contas_dias.min'      => 'O prazo deve ser de ao menos um dia.',
            'prazo_prestacao_contas_dias.max'      => 'O prazo não pode passar de 365 dias.',
        ]);

        ParametroAh::atual()->update($dados);

        // O prazo novo vale para as proximas prestacoes. As que ja estao
        // abertas mantem a data que receberam quando o pedido foi atendido,
        // porque mudar prazo de processo em andamento seria mudar a regra no
        // meio do jogo.
        return back()->with('success', 'Parâmetros atualizados. O prazo novo vale para as próximas prestações.');
    }
}
