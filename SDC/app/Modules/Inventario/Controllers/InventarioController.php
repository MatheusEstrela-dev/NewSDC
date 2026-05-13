<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'categoria', 'situacao', 'responsavel']);

        return Inertia::render('Inventario/InventarioIndex', [
            'equipamentos' => $this->mockEquipamentos($filters),
            'statistics' => [
                'total' => 128,
                'disponiveis' => 82,
                'emprestados' => 31,
                'manutencao' => 9,
                'baixados' => 6,
            ],
            'filters' => $filters,
            'filterOptions' => [
                'categorias' => [
                    ['value' => 'notebook', 'label' => 'Notebook'],
                    ['value' => 'radio', 'label' => 'Radio'],
                    ['value' => 'drone', 'label' => 'Drone'],
                    ['value' => 'kit', 'label' => 'Kit operacional'],
                ],
                'situacoes' => [
                    ['value' => 'disponivel', 'label' => 'Disponivel'],
                    ['value' => 'emprestado', 'label' => 'Emprestado'],
                    ['value' => 'manutencao', 'label' => 'Manutencao'],
                    ['value' => 'baixado', 'label' => 'Baixado'],
                ],
            ],
        ]);
    }

    private function mockEquipamentos(array $filters): array
    {
        $items = [
            [
                'id' => 1,
                'nome' => 'Notebook Dell Latitude 5440',
                'patrimonio' => 'SDC-NTB-0048',
                'categoria' => 'Notebook',
                'situacao' => 'disponivel',
                'responsavel' => 'Deposito Central',
                'diretoria' => 'COMPDEC',
                'ultima_movimentacao' => '2026-05-08',
            ],
            [
                'id' => 2,
                'nome' => 'Radio comunicador VHF',
                'patrimonio' => 'SDC-RAD-0122',
                'categoria' => 'Radio',
                'situacao' => 'emprestado',
                'responsavel' => 'Plantao Diario',
                'diretoria' => 'CEDEC',
                'ultima_movimentacao' => '2026-05-06',
            ],
            [
                'id' => 3,
                'nome' => 'Drone DJI Mavic 3 Enterprise',
                'patrimonio' => 'SDC-DRN-0007',
                'categoria' => 'Drone',
                'situacao' => 'manutencao',
                'responsavel' => 'Equipe de Vistoria',
                'diretoria' => 'Operacoes',
                'ultima_movimentacao' => '2026-05-02',
            ],
            [
                'id' => 4,
                'nome' => 'Kit abrigo temporario',
                'patrimonio' => 'SDC-KIT-0184',
                'categoria' => 'Kit operacional',
                'situacao' => 'disponivel',
                'responsavel' => 'Deposito Regional',
                'diretoria' => 'Ajuda Humanitaria',
                'ultima_movimentacao' => '2026-04-29',
            ],
        ];

        return array_values(array_filter($items, function (array $item) use ($filters): bool {
            if (!empty($filters['search'])) {
                $term = mb_strtolower((string) $filters['search']);
                $haystack = mb_strtolower($item['nome'] . ' ' . $item['patrimonio'] . ' ' . $item['responsavel']);
                if (!str_contains($haystack, $term)) {
                    return false;
                }
            }

            if (!empty($filters['categoria']) && mb_strtolower($item['categoria']) !== $filters['categoria']) {
                return false;
            }

            if (!empty($filters['situacao']) && $item['situacao'] !== $filters['situacao']) {
                return false;
            }

            return true;
        }));
    }
}
