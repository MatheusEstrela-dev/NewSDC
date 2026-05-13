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
        $produtos = $this->mockProdutos($filters);
        $activeSection = (string) ($request->route('section') ?? 'dashboard');

        return Inertia::render('Estoque/EstoqueIndex', [
            'produtos' => $produtos,
            'kits' => $this->mockKits(),
            'movimentacoes' => $this->mockMovimentacoes(),
            'alertas' => $this->mockAlertas(),
            'activeSection' => $activeSection,
            'excelSheets' => $this->mockExcelSheets(),
            'statistics' => [
                'produtos' => 42,
                'saldo_total' => 8460,
                'kits_montaveis' => 184,
                'lotes_vencendo' => 7,
                'requisicoes' => 5,
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
        $filters = $request->only(['search', 'unidade', 'status', 'validade']);
        $produtos = $this->mockProdutos($filters);
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
    private function mockProdutos(array $filters): array
    {
        $items = [
            [
                'id' => 1,
                'nome' => 'Cesta Basica Tipo A',
                'sku' => 'KIT-CBA-001',
                'categoria' => 'Kit humanitario',
                'unidade' => 'central',
                'unidade_label' => 'Almoxarifado Central',
                'saldo' => 126,
                'reservado' => 18,
                'minimo' => 80,
                'unidade_base' => 'kit',
                'lote' => 'CB-0526-01',
                'validade' => '2026-06-05',
                'endereco' => 'A1-C02-P03',
                'status' => 'atencao',
                'regra_saida' => 'FEFO',
                'custo_medio' => 118.45,
                'destino_obrigatorio' => 'CPF beneficiario ou evento',
                'ultima_movimentacao' => '2026-05-09',
            ],
            [
                'id' => 2,
                'nome' => 'Kit Limpeza Familiar',
                'sku' => 'KIT-LIM-004',
                'categoria' => 'Kit humanitario',
                'unidade' => 'regional',
                'unidade_label' => 'Deposito Regional',
                'saldo' => 72,
                'reservado' => 10,
                'minimo' => 60,
                'unidade_base' => 'kit',
                'lote' => 'KL-0426-03',
                'validade' => '2027-01-20',
                'endereco' => 'B2-C01-P01',
                'status' => 'normal',
                'regra_saida' => 'FEFO',
                'custo_medio' => 64.9,
                'destino_obrigatorio' => 'CPF beneficiario ou evento',
                'ultima_movimentacao' => '2026-05-08',
            ],
            [
                'id' => 3,
                'nome' => 'Agua Mineral 1,5L',
                'sku' => 'ALI-AGU-015',
                'categoria' => 'Alimento',
                'unidade' => 'movel',
                'unidade_label' => 'Unidade Movel',
                'saldo' => 348,
                'reservado' => 120,
                'minimo' => 500,
                'unidade_base' => 'un',
                'lote' => 'AG-0126-17',
                'validade' => '2026-05-18',
                'endereco' => 'UM-01',
                'status' => 'critico',
                'regra_saida' => 'FEFO',
                'custo_medio' => 2.1,
                'destino_obrigatorio' => 'Evento de desastre',
                'ultima_movimentacao' => '2026-05-10',
            ],
            [
                'id' => 4,
                'nome' => 'Arroz Tipo 1 - 5kg',
                'sku' => 'ALI-ARR-005',
                'categoria' => 'Componente de kit',
                'unidade' => 'central',
                'unidade_label' => 'Almoxarifado Central',
                'saldo' => 920,
                'reservado' => 400,
                'minimo' => 350,
                'unidade_base' => 'pacote',
                'lote' => 'AR-1125-09',
                'validade' => '2026-11-14',
                'endereco' => 'A3-C04-P02',
                'status' => 'normal',
                'regra_saida' => 'FEFO',
                'custo_medio' => 23.3,
                'destino_obrigatorio' => 'Montagem de kit',
                'ultima_movimentacao' => '2026-05-06',
            ],
            [
                'id' => 5,
                'nome' => 'Colchonete Solteiro',
                'sku' => 'ABR-COL-001',
                'categoria' => 'Abrigo',
                'unidade' => 'regional',
                'unidade_label' => 'Deposito Regional',
                'saldo' => 32,
                'reservado' => 0,
                'minimo' => 40,
                'unidade_base' => 'un',
                'lote' => 'COL-0525-02',
                'validade' => null,
                'endereco' => 'B4-C03-P01',
                'status' => 'critico',
                'regra_saida' => 'FIFO',
                'custo_medio' => 89.0,
                'destino_obrigatorio' => 'Evento de desastre',
                'ultima_movimentacao' => '2026-04-29',
            ],
            [
                'id' => 6,
                'nome' => 'Feijao Carioca 1kg',
                'sku' => 'ALI-FEJ-001',
                'categoria' => 'Componente de kit',
                'unidade' => 'central',
                'unidade_label' => 'Almoxarifado Central',
                'saldo' => 1480,
                'reservado' => 620,
                'minimo' => 500,
                'unidade_base' => 'pacote',
                'lote' => 'FJ-0426-22',
                'validade' => '2026-04-30',
                'endereco' => 'A3-C05-P01',
                'status' => 'quarentena',
                'regra_saida' => 'Bloqueado',
                'custo_medio' => 7.8,
                'destino_obrigatorio' => 'Montagem de kit',
                'ultima_movimentacao' => '2026-05-03',
            ],
        ];

        return array_values(array_filter($items, function (array $item) use ($filters): bool {
            if (!empty($filters['search'])) {
                $term = mb_strtolower((string) $filters['search']);
                $haystack = mb_strtolower($item['nome'] . ' ' . $item['sku'] . ' ' . $item['lote'] . ' ' . $item['endereco']);
                if (!str_contains($haystack, $term)) {
                    return false;
                }
            }

            if (!empty($filters['unidade']) && $item['unidade'] !== $filters['unidade']) {
                return false;
            }

            if (!empty($filters['status']) && $item['status'] !== $filters['status']) {
                return false;
            }

            if (!empty($filters['validade']) && $this->validadeBucket($item['validade']) !== $filters['validade']) {
                return false;
            }

            return true;
        }));
    }

    private function validadeBucket(?string $validade): string
    {
        if ($validade === null) {
            return 'em_dia';
        }

        $today = strtotime('2026-05-11');
        $expiration = strtotime($validade);

        if ($expiration < $today) {
            return 'vencido';
        }

        if ($expiration <= strtotime('+30 days', $today)) {
            return 'vence_30';
        }

        return 'em_dia';
    }

    private function mockKits(): array
    {
        return [
            [
                'id' => 1,
                'nome' => 'Cesta Basica Tipo A',
                'codigo' => 'BOM-CBA-A',
                'montaveis' => 184,
                'saldo' => 126,
                'custo_medio' => 118.45,
                'status' => 'liberado',
                'componentes' => ['Arroz 5kg x1', 'Feijao 1kg x2', 'Oleo 900ml x1', 'Acucar 2kg x1'],
            ],
            [
                'id' => 2,
                'nome' => 'Kit Limpeza Familiar',
                'codigo' => 'BOM-KLF-01',
                'montaveis' => 96,
                'saldo' => 72,
                'custo_medio' => 64.9,
                'status' => 'liberado',
                'componentes' => ['Agua sanitaria x1', 'Sabao em po x1', 'Detergente x2', 'Esponja x4'],
            ],
            [
                'id' => 3,
                'nome' => 'Kit Abrigo Emergencial',
                'codigo' => 'BOM-ABR-02',
                'montaveis' => 28,
                'saldo' => 14,
                'custo_medio' => 228.0,
                'status' => 'restrito',
                'componentes' => ['Colchonete x1', 'Cobertor x1', 'Lona x1', 'Lanterna x1'],
            ],
        ];
    }

    private function mockMovimentacoes(): array
    {
        return [
            [
                'id' => 1,
                'tipo' => 'Entrada',
                'motivo' => 'Doacao recebida',
                'referencia' => 'NF/DOA-2026-118',
                'produto' => 'Agua Mineral 1,5L',
                'quantidade' => 480,
                'status' => 'Conferido',
                'data' => '2026-05-10',
            ],
            [
                'id' => 2,
                'tipo' => 'Saida',
                'motivo' => 'Distribuicao humanitaria',
                'referencia' => 'EVT-CHUVAS-023',
                'produto' => 'Cesta Basica Tipo A',
                'quantidade' => 90,
                'status' => 'Aprovado',
                'data' => '2026-05-09',
            ],
            [
                'id' => 3,
                'tipo' => 'Montagem',
                'motivo' => 'Composicao de kit',
                'referencia' => 'OS-KIT-0042',
                'produto' => 'Kit Limpeza Familiar',
                'quantidade' => 40,
                'status' => 'Em separacao',
                'data' => '2026-05-08',
            ],
        ];
    }

    private function mockAlertas(): array
    {
        return [
            ['id' => 1, 'tipo' => 'validade', 'titulo' => 'Lote AG-0126-17 vence em 7 dias', 'nivel' => 'warning'],
            ['id' => 2, 'tipo' => 'minimo', 'titulo' => 'Agua Mineral abaixo do ponto de reposicao', 'nivel' => 'danger'],
            ['id' => 3, 'tipo' => 'bloqueio', 'titulo' => 'Feijao Carioca em quarentena por validade vencida', 'nivel' => 'danger'],
        ];
    }

    private function mockExcelSheets(): array
    {
        return [
            [
                'id' => 1,
                'nome' => 'Saldos por Lote',
                'linhas' => 128,
                'colunas' => ['SKU', 'Produto', 'Lote', 'Validade', 'Saldo', 'Unidade', 'Endereco'],
                'amostra' => [
                    ['KIT-CBA-001', 'Cesta Basica Tipo A', 'CB-0526-01', '05/06/2026', '126', 'Central', 'A1-C02-P03'],
                    ['ALI-AGU-015', 'Agua Mineral 1,5L', 'AG-0126-17', '18/05/2026', '348', 'Movel', 'UM-01'],
                    ['ALI-FEJ-001', 'Feijao Carioca 1kg', 'FJ-0426-22', '30/04/2026', '1480', 'Central', 'A3-C05-P01'],
                ],
            ],
            [
                'id' => 2,
                'nome' => 'Movimentacoes',
                'linhas' => 384,
                'colunas' => ['Data', 'Tipo', 'Motivo', 'Produto', 'Quantidade', 'Destino', 'Responsavel'],
                'amostra' => [
                    ['10/05/2026', 'Entrada', 'Doacao recebida', 'Agua Mineral 1,5L', '480', 'Almoxarifado Central', 'CEDEC'],
                    ['09/05/2026', 'Saida', 'Distribuicao', 'Cesta Basica Tipo A', '90', 'EVT-CHUVAS-023', 'Ajuda Humanitaria'],
                    ['08/05/2026', 'Montagem', 'Composicao de kit', 'Kit Limpeza Familiar', '40', 'Deposito Regional', 'Operacoes'],
                ],
            ],
            [
                'id' => 3,
                'nome' => 'Kits e Componentes',
                'linhas' => 42,
                'colunas' => ['Kit', 'Componente', 'Quantidade', 'Unidade', 'Custo Medio', 'Montaveis'],
                'amostra' => [
                    ['Cesta Basica Tipo A', 'Arroz 5kg', '1', 'pacote', '23,30', '184'],
                    ['Cesta Basica Tipo A', 'Feijao 1kg', '2', 'pacote', '7,80', '184'],
                    ['Kit Limpeza Familiar', 'Detergente', '2', 'un', '2,90', '96'],
                ],
            ],
        ];
    }
}

