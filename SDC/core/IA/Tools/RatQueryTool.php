<?php

namespace App\Core\IA\Tools;

use App\Core\IA\Contracts\ToolInterface;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;

class RatQueryTool implements ToolInterface
{
    protected RatRepositoryInterface $ratRepository;

    public function __construct(?RatRepositoryInterface $ratRepository = null)
    {
        $this->ratRepository = $ratRepository ?? new EloquentRatRepository();
    }
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
        $rat = $this->ratRepository->find($id);

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
        $protocolo = strtoupper(trim($protocolo));

        $rats = $this->ratRepository->findAll(['search' => $protocolo], -1);

        foreach ($rats as $rat) {
            if (strtoupper($rat->protocolo) === $protocolo ||
                str_contains(strtoupper($rat->protocolo), $protocolo)) {
                return $this->formatRat($rat);
            }
        }

        return [
            'encontrado' => false,
            'mensagem' => "Nenhum RAT encontrado com protocolo {$protocolo}",
        ];
    }

    protected function search(?string $municipio, ?string $status, int $limit): array
    {
        $filters = [];
        if ($municipio) {
            $filters['search'] = $municipio;
        }

        $rats = $this->ratRepository->findAll($filters, $limit);

        $filtered = collect();
        foreach ($rats as $rat) {
            $local = is_array($rat->local) ? $rat->local : [];
            $municipioRat = $local['municipio'] ?? '';

            $matchMunicipio = !$municipio || str_contains(strtolower($municipioRat), strtolower($municipio));
            $matchStatus = !$status || $rat->status === $status;

            if ($matchMunicipio && $matchStatus) {
                $filtered->push($rat);
            }
        }

        if ($filtered->isEmpty()) {
            return [
                'encontrado' => false,
                'total' => 0,
                'mensagem' => 'Nenhum RAT encontrado com os filtros especificados',
            ];
        }

        return [
            'encontrado' => true,
            'total' => $filtered->count(),
            'rats' => $filtered->map(fn($rat) => $this->formatRatSummary($rat))->toArray(),
        ];
    }

    protected function formatRat($rat): array
    {
        $dadosGerais = is_array($rat->dados_gerais) ? $rat->dados_gerais : [];
        $local = is_array($rat->local) ? $rat->local : [];

        $createdAt = $rat->created_at ?? null;
        $updatedAt = $rat->updated_at ?? null;

        $formattedCreatedAt = $this->formatDateTime($createdAt);
        $formattedUpdatedAt = $this->formatDateTime($updatedAt);

        return [
            'encontrado' => true,
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $rat->status,
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'cobrade' => $dadosGerais['nat_cobrade_id'] ?? 'Nao informado',
            'data_fato' => $this->formatDateTime($dadosGerais['data_fato'] ?? null),
            'data_inicio' => $dadosGerais['data_inicio_atividade'] ?? null,
            'data_termino' => $dadosGerais['data_termino_atividade'] ?? null,
            'tem_vistoria' => $rat->tem_vistoria ?? false,
            'endereco' => isset($rat->endereco) && is_array($rat->endereco) ? $rat->endereco : [],
            'criado_em' => $formattedCreatedAt,
            'atualizado_em' => $formattedUpdatedAt,
        ];
    }

    protected function formatRatSummary($rat): array
    {
        $dadosGerais = is_array($rat->dados_gerais) ? $rat->dados_gerais : [];
        $local = is_array($rat->local) ? $rat->local : [];

        $createdAt = $rat->created_at ?? null;
        $formattedCreatedAt = $this->formatDateTime($createdAt);

        return [
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $rat->status,
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'criado_em' => $formattedCreatedAt,
        ];
    }

    protected function formatDateTime($datetime): ?string
    {
        if (!$datetime) {
            return null;
        }

        if (is_string($datetime)) {
            try {
                return \Carbon\Carbon::parse($datetime)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return $datetime;
            }
        }

        if (method_exists($datetime, 'format')) {
            return $datetime->format('d/m/Y H:i');
        }

        return null;
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
