<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstoqueController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'unidade', 'status', 'validade']);
        $activeSection = (string) ($request->route('section') ?? 'dashboard');

        // TODO: integrar com tabelas estoque_produtos/kits/movimentacoes/alertas quando o modulo for implementado.
        return Inertia::render('Estoque/EstoqueIndex', [
            'produtos' => [],
            'kits' => [],
            'movimentacoes' => [],
            'alertas' => [],
            'activeSection' => $activeSection,
            'excelSheets' => [],
            'statistics' => [
                'produtos' => 0,
                'saldo_total' => 0,
                'kits_montaveis' => 0,
                'lotes_vencendo' => 0,
                'requisicoes' => 0,
            ],
            'filters' => $filters,
            'filterOptions' => [
                'unidades' => [
                    ['value' => 'central', 'label' => 'Almoxarifado Central'],
                    ['value' => 'regional', 'label' => 'Deposito Regional'],
                    ['value' => 'movel', 'label' => 'Unidade Movel'],
                ],
                'status' => [
                    ['value' => 'normal', 'label' => 'Normal'],
                    ['value' => 'atencao', 'label' => 'Atencao'],
                    ['value' => 'critico', 'label' => 'Critico'],
                    ['value' => 'quarentena', 'label' => 'Quarentena'],
                ],
                'validade' => [
                    ['value' => 'em_dia', 'label' => 'Em dia'],
                    ['value' => 'vence_30', 'label' => 'Vence em 30 dias'],
                    ['value' => 'vencido', 'label' => 'Vencido'],
                ],
            ],
        ]);
    }


    public function exportExcel(Request $request): StreamedResponse
    {
        // TODO: integrar com tabela de estoque quando o modulo for implementado.
        $produtos = [];
        $filename = 'estoque_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($produtos): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'SKU',
                'Produto',
                'Categoria',
                'Unidade',
                'Saldo',
                'Reservado',
                'Minimo',
                'Unidade Base',
                'Lote',
                'Validade',
                'Endereco',
                'Status',
                'Regra de Saida',
                'Custo Medio',
                'Destino Obrigatorio',
                'Ultima Movimentacao',
            ], ';');

            foreach ($produtos as $produto) {
                fputcsv($handle, [
                    $produto['sku'],
                    $produto['nome'],
                    $produto['categoria'],
                    $produto['unidade_label'],
                    $produto['saldo'],
                    $produto['reservado'],
                    $produto['minimo'],
                    $produto['unidade_base'],
                    $produto['lote'],
                    $this->formatExportDate($produto['validade']),
                    $produto['endereco'],
                    $produto['status'],
                    $produto['regra_saida'],
                    number_format((float) $produto['custo_medio'], 2, ',', '.'),
                    $produto['destino_obrigatorio'],
                    $this->formatExportDate($produto['ultima_movimentacao']),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatExportDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return date('d/m/Y', strtotime($date));
    }
}
