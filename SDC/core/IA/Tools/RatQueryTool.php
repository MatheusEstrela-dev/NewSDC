<?php

namespace App\Core\IA\Tools;

use App\Core\IA\Contracts\ToolInterface;
use App\Modules\Rat\Domain\Entities\Rat;
use Illuminate\Support\Facades\DB;

class RatQueryTool implements ToolInterface
{
    public function getName(): string
    {
        return 'consultar_rat';
    }

    public function getDescription(): string
    {
        return 'Consulta informacoes de protocolos RAT (Registro de Atendimento Tecnico) no banco de dados da Defesa Civil. Use quando o usuario perguntar sobre um protocolo especifico ou quiser listar RATs.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'protocolo' => [
                    'type' => 'string',
                    'description' => 'Numero do protocolo RAT (ex: RAT-2024-001)',
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID numerico do RAT',
                ],
                'municipio' => [
                    'type' => 'string',
                    'description' => 'Nome do municipio para filtrar',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Status do RAT (rascunho, em_andamento, finalizado)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limite de resultados (padrao: 5)',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $parameters): mixed
    {
        $protocolo = $parameters['protocolo'] ?? null;
        $id = $parameters['id'] ?? null;
        $municipio = $parameters['municipio'] ?? null;
        $status = $parameters['status'] ?? null;
        $limit = $parameters['limit'] ?? 5;

        // Busca por ID especifico
        if ($id) {
            return $this->findById($id);
        }

        // Busca por protocolo especifico
        if ($protocolo) {
            return $this->findByProtocolo($protocolo);
        }

        // Busca com filtros
        return $this->search($municipio, $status, $limit);
    }

    protected function findById(int $id): array
    {
        $rat = Rat::find($id);

        if (!$rat) {
            return [
                'encontrado' => false,
                'mensagem' => "Nenhum RAT encontrado com ID {$id}",
            ];
        }

        return $this->formatRat($rat);
    }

    protected function findByProtocolo(string $protocolo): array
    {
        // Normaliza o protocolo (remove espacos, converte para maiusculo)
        $protocolo = strtoupper(trim($protocolo));

        // Tenta encontrar pelo protocolo exato
        $rat = Rat::where('protocolo', $protocolo)->first();

        // Se nao encontrar, tenta busca parcial
        if (!$rat) {
            $rat = Rat::where('protocolo', 'LIKE', "%{$protocolo}%")->first();
        }

        if (!$rat) {
            return [
                'encontrado' => false,
                'mensagem' => "Nenhum RAT encontrado com protocolo {$protocolo}",
            ];
        }

        return $this->formatRat($rat);
    }

    protected function search(?string $municipio, ?string $status, int $limit): array
    {
        $query = Rat::query();

        if ($municipio) {
            $query->where(function ($q) use ($municipio) {
                $q->whereRaw("JSON_EXTRACT(dados_gerais, '$.local_municipio') LIKE ?", ["%{$municipio}%"])
                    ->orWhereRaw("JSON_EXTRACT(local, '$.municipio') LIKE ?", ["%{$municipio}%"]);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $rats = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($rats->isEmpty()) {
            return [
                'encontrado' => false,
                'total' => 0,
                'mensagem' => 'Nenhum RAT encontrado com os filtros especificados',
            ];
        }

        return [
            'encontrado' => true,
            'total' => $rats->count(),
            'rats' => $rats->map(fn($rat) => $this->formatRatSummary($rat))->toArray(),
        ];
    }

    protected function formatRat($rat): array
    {
        $dadosGerais = $rat->dados_gerais ?? [];
        $local = $rat->local ?? [];

        return [
            'encontrado' => true,
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $rat->status,
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'cobrade' => $dadosGerais['nat_cobrade_id'] ?? 'Nao informado',
            'data_fato' => $dadosGerais['data_fato'] ?? null,
            'data_inicio' => $dadosGerais['data_inicio_atividade'] ?? null,
            'data_termino' => $dadosGerais['data_termino_atividade'] ?? null,
            'tem_vistoria' => $rat->tem_vistoria ?? false,
            'endereco' => $rat->endereco ?? [],
            'criado_em' => $rat->created_at?->format('d/m/Y H:i') ?? null,
            'atualizado_em' => $rat->updated_at?->format('d/m/Y H:i') ?? null,
        ];
    }

    protected function formatRatSummary($rat): array
    {
        $dadosGerais = $rat->dados_gerais ?? [];
        $local = $rat->local ?? [];

        return [
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $rat->status,
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'criado_em' => $rat->created_at?->format('d/m/Y H:i') ?? null,
        ];
    }

    public function validateParameters(array $parameters): bool
    {
        // Pelo menos um parametro deve ser fornecido para busca especifica
        // ou nenhum para listagem geral
        return true;
    }

    public function toFunctionDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'parameters' => $this->getParametersSchema(),
        ];
    }
}
