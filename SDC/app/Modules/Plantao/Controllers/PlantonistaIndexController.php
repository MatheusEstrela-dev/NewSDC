<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Plantao\Models\Plantonista;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cadastro de quem entra na escala.
 *
 * O select de "adicionar" NAO lista a base inteira de usuarios: sao milhares de
 * contas COMPDEC municipais que nunca fazem plantao no Predio Alterosas. Busca
 * por termo, com teto, e a unica forma utilizavel.
 */
class PlantonistaIndexController extends Controller
{
    /**
     * Teto do autocomplete. Alto o suficiente para achar quem se procura,
     * baixo o suficiente para nao serializar meio banco no payload do Inertia.
     */
    private const LIMITE_BUSCA = 20;

    public function __invoke(Request $request): Response
    {
        $busca = trim((string) $request->query('busca', ''));

        return Inertia::render('Plantao/PlantonistasIndex', [
            'plantonistas' => Plantonista::with('user:id,name,email')
                ->get()
                ->map(fn (Plantonista $p) => [
                    'id' => $p->id,
                    'user_id' => (int) $p->user_id,
                    'nome' => $p->user?->name,
                    'email' => $p->user?->email,
                    'posto' => $p->posto,
                    'nome_com_posto' => $p->nomeComPosto(),
                    'ativo' => $p->ativo,
                    'observacao' => $p->observacao,
                ])
                ->sortBy('nome_com_posto')
                ->values()
                ->all(),
            'filtros' => ['busca' => $busca],
            'candidatos' => $busca === '' ? [] : $this->candidatos($busca),
            'can' => [
                'gerir' => (bool) $request->user()?->can('plantao.plantonistas.manage'),
            ],
        ]);
    }

    /**
     * Usuarios que ainda nao sao plantonistas e casam com a busca.
     *
     * @return list<array<string,mixed>>
     */
    private function candidatos(string $busca): array
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->whereNotIn('id', Plantonista::query()->select('user_id'))
            ->where(function ($q) use ($busca): void {
                $q->where('name', 'ilike', "%{$busca}%")
                    ->orWhere('email', 'ilike', "%{$busca}%");
            })
            ->orderBy('name')
            ->limit(self::LIMITE_BUSCA)
            ->get()
            ->map(fn (User $u) => [
                'value' => $u->id,
                'label' => $u->name,
                'email' => $u->email,
            ])
            ->all();
    }
}
