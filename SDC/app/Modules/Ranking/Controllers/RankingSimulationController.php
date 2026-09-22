<?php

declare(strict_types=1);

namespace App\Modules\Ranking\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ranking\DTOs\ScoreRuleData;
use App\Modules\Ranking\DTOs\ScoreTransactionData;
use App\Modules\Ranking\Services\ScoreCalculator;
use App\Modules\Ranking\Support\CatalogoRegras;
use App\Modules\Ranking\Support\RankingAccess;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class RankingSimulationController extends Controller
{
    public function __invoke(Request $request, ScoreCalculator $calculadora): JsonResponse
    {
        abort_unless(RankingAccess::preview($request->user()), 403);
        $dados = $request->validate([
            'regra' => ['required', Rule::in(array_column(CatalogoRegras::todos(), 'rule_key'))],
            'base' => ['required', 'integer', 'min:0', 'max:500'],
            'habilitada' => ['required', 'boolean'],
            'bonus' => ['required', 'boolean'],
            'exige_validacao' => ['required', 'boolean'],
            'validada' => ['required', 'boolean'],
            'autoria' => ['required', 'boolean'],
            'evidencia' => ['required', 'boolean'],
            'elegivel' => ['required', 'boolean'],
            'prazo' => ['required', Rule::in(['no_prazo', 'atrasado', 'desconhecido'])],
        ]);
        $agora = new DateTimeImmutable();
        $fato = new ScoreTransactionData(
            $agora, (bool) $dados['autoria'], (bool) $dados['evidencia'],
            (bool) $dados['elegivel'], (bool) $dados['validada'], $agora,
            match ($dados['prazo']) {
                'no_prazo' => $agora->modify('+1 day'),
                'atrasado' => $agora->modify('-1 day'),
                default => null,
            },
        );
        $regra = new ScoreRuleData((int) $dados['base'], (bool) $dados['habilitada'], (bool) $dados['bonus'], (bool) $dados['exige_validacao']);
        $decisao = $calculadora->calcular($fato, $regra);

        return response()->json([
            'decisao' => $decisao->decisao->label(), 'motivo' => $decisao->motivo,
            'base' => $decisao->pontosBase, 'bonus' => $decisao->pontosBonus,
            'total' => $decisao->pontosTotais(), 'saldo' => $decisao->pontosParaSaldo(),
        ]);
    }
}
