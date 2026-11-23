<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Edital;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\DTOs\ConsultaEditalFiltroDTO;
use App\Modules\Treinamento\Services\ConsultaEditalService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV da consulta para edital: todas as pessoas do recorte (sem paginar), com
 * o CPF sempre mascarado. UTF-8 com BOM e ";" para abrir direto no Excel.
 */
class ConsultaEditalExportController extends Controller
{
    private const CABECALHO = ['Nome', 'CPF', 'Tipo', 'Órgão', 'Município', 'REDEC', 'Cursos concluídos'];

    public function __construct(private readonly ConsultaEditalService $consulta) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $filtro = ConsultaEditalFiltroDTO::fromArray($request->query());
        $pessoas = $filtro->temCursos() ? $this->consulta->todas($filtro) : collect();

        return response()->streamDownload(function () use ($pessoas): void {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, self::CABECALHO, ';', '"', '');

            foreach ($pessoas as $p) {
                fputcsv($saida, [
                    $p['nome'],
                    $p['cpf'],
                    $p['tipo'],
                    $p['orgao'] ?? '',
                    $p['municipio'] ?? '',
                    $p['redec'] ?? '',
                    implode(' | ', array_map(
                        fn (array $c) => $c['nome'] . ' (' . date('d/m/Y', strtotime($c['concluido_em'])) . ')',
                        $p['cursos']
                    )),
                ], ';', '"', '');
            }

            fclose($saida);
        }, 'consulta_edital_' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
