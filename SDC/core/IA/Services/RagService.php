<?php

declare(strict_types=1);

namespace App\Core\IA\Services;

use App\Modules\Rat\Domain\Entities\Rat;
use Illuminate\Support\Facades\Log;

class RagService
{
    protected array $contextData = [];

    public function enrichMessage(string $message): string
    {
        $this->contextData = [];

        // Detectar mencoes a protocolos RAT
        $ratContext = $this->detectAndFetchRatData($message);

        if (empty($ratContext)) {
            return $message;
        }

        // Construir contexto enriquecido
        $enrichedMessage = $this->buildEnrichedMessage($message, $ratContext);

        return $enrichedMessage;
    }

    protected function detectAndFetchRatData(string $message): array
    {
        $contexts = [];

        // Padroes para detectar protocolos RAT
        $patterns = [
            '/RAT[-\s]?(\d{4})[-\s]?(\d{1,3})/i',  // RAT-2024-001
            '/protocolo\s*#?\s*(\d+)/i',            // protocolo #123 ou protocolo 123
            '/RAT\s*#?\s*(\d+)/i',                  // RAT #123
            '/(\d{4})[-\s](\d{3})/i',               // 2024-001
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $ratData = $this->fetchRatData($match[0]);
                    if ($ratData) {
                        $contexts[] = $ratData;
                    }
                }
            }
        }

        // Se nao encontrou padrao mas menciona RAT, buscar os mais recentes
        if (empty($contexts) && $this->mentionsRat($message)) {
            $contexts = $this->fetchRecentRats(3);
        }

        return $contexts;
    }

    protected function mentionsRat(string $message): bool
    {
        $keywords = ['rat', 'protocolo', 'ocorrencia', 'atendimento', 'vistoria'];
        $messageLower = strtolower($message);

        foreach ($keywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function fetchRatData(string $reference): ?array
    {
        try {
            $reference = strtoupper(trim($reference));

            $rat = Rat::where('protocolo', 'LIKE', "%{$reference}%")->first();

            if (!$rat) {
                preg_match('/(\d+)/', $reference, $numMatches);
                if (!empty($numMatches[1])) {
                    $rat = Rat::find((int) $numMatches[1]);
                }
            }

            if (!$rat) {
                return $this->getMockRatData($reference);
            }

            return $this->formatRatContext($rat);
        } catch (\Exception $e) {
            Log::warning('RagService: Erro ao buscar RAT, usando mock', ['error' => $e->getMessage()]);
            return $this->getMockRatData($reference);
        }
    }

    protected function fetchRecentRats(int $limit = 3): array
    {
        try {
            $rats = Rat::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            if ($rats->isEmpty()) {
                return $this->getMockRecentRats($limit);
            }

            return $rats->map(fn($rat) => $this->formatRatContext($rat))->toArray();
        } catch (\Exception $e) {
            Log::warning('RagService: Erro ao buscar RATs recentes, usando mock', ['error' => $e->getMessage()]);
            return $this->getMockRecentRats($limit);
        }
    }

    protected function getMockRatData(string $reference): array
    {
        preg_match('/(\d+)/', $reference, $matches);
        $id = $matches[1] ?? rand(1, 100);
        $year = date('Y');

        return [
            'id' => $id,
            'protocolo' => "RAT-{$year}-" . str_pad((string)$id, 3, '0', STR_PAD_LEFT),
            'status' => 'Em Andamento',
            'municipio' => 'Belo Horizonte/MG',
            'tipo_ocorrencia' => 'Inundacao',
            'codigo_cobrade' => '12302',
            'data_fato' => now()->subDays(2)->format('d/m/Y H:i'),
            'data_inicio' => now()->subDays(2)->format('d/m/Y H:i'),
            'data_termino' => null,
            'tem_vistoria' => 'Nao',
            'endereco_completo' => 'Rua Exemplo, 123, Centro',
            'criado_em' => now()->subDays(2)->format('d/m/Y H:i'),
            'atualizado_em' => now()->format('d/m/Y H:i'),
            'is_mock' => true,
        ];
    }

    protected function getMockRecentRats(int $limit): array
    {
        $mocks = [];
        $year = date('Y');

        for ($i = 1; $i <= $limit; $i++) {
            $mocks[] = [
                'id' => $i,
                'protocolo' => "RAT-{$year}-" . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'status' => ['Rascunho', 'Em Andamento', 'Finalizado'][rand(0, 2)],
                'municipio' => ['Belo Horizonte/MG', 'Uberlandia/MG', 'Juiz de Fora/MG'][rand(0, 2)],
                'tipo_ocorrencia' => ['Inundacao', 'Deslizamento', 'Vendaval'][rand(0, 2)],
                'codigo_cobrade' => '12302',
                'data_fato' => now()->subDays(rand(1, 30))->format('d/m/Y H:i'),
                'data_inicio' => now()->subDays(rand(1, 30))->format('d/m/Y H:i'),
                'data_termino' => null,
                'tem_vistoria' => rand(0, 1) ? 'Sim' : 'Nao',
                'endereco_completo' => 'Rua Exemplo, ' . rand(1, 999) . ', Centro',
                'criado_em' => now()->subDays(rand(1, 30))->format('d/m/Y H:i'),
                'atualizado_em' => now()->format('d/m/Y H:i'),
                'is_mock' => true,
            ];
        }

        return $mocks;
    }

    protected function formatRatContext($rat): array
    {
        $dadosGerais = $rat->dados_gerais ?? [];
        $local = $rat->local ?? [];
        $endereco = $rat->endereco ?? [];

        return [
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $this->translateStatus($rat->status),
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'codigo_cobrade' => $dadosGerais['nat_cobrade_id'] ?? null,
            'data_fato' => $this->formatDate($dadosGerais['data_fato'] ?? null),
            'data_inicio' => $this->formatDate($dadosGerais['data_inicio_atividade'] ?? null),
            'data_termino' => $this->formatDate($dadosGerais['data_termino_atividade'] ?? null),
            'tem_vistoria' => $rat->tem_vistoria ? 'Sim' : 'Nao',
            'endereco_completo' => $this->formatEndereco($endereco),
            'criado_em' => $rat->created_at?->format('d/m/Y H:i'),
            'atualizado_em' => $rat->updated_at?->format('d/m/Y H:i'),
        ];
    }

    protected function translateStatus(?string $status): string
    {
        return match ($status) {
            'rascunho' => 'Rascunho',
            'em_andamento' => 'Em Andamento',
            'finalizado' => 'Finalizado',
            'cancelado' => 'Cancelado',
            default => $status ?? 'Desconhecido',
        };
    }

    protected function formatDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    protected function formatEndereco(array $endereco): string
    {
        $parts = array_filter([
            $endereco['logradouro'] ?? null,
            $endereco['numero'] ?? null,
            $endereco['bairro'] ?? null,
            $endereco['cep'] ?? null,
        ]);

        return implode(', ', $parts) ?: 'Nao informado';
    }

    protected function buildEnrichedMessage(string $originalMessage, array $ratContexts): string
    {
        if (empty($ratContexts)) {
            return $originalMessage;
        }

        $contextText = "\n\n--- DADOS DO SISTEMA (RAT) ---\n";

        foreach ($ratContexts as $rat) {
            $contextText .= "\nProtocolo: {$rat['protocolo']}\n";
            $contextText .= "Status: {$rat['status']}\n";
            $contextText .= "Municipio: {$rat['municipio']}\n";
            $contextText .= "Tipo de Ocorrencia: {$rat['tipo_ocorrencia']}\n";

            if ($rat['codigo_cobrade']) {
                $contextText .= "Codigo COBRADE: {$rat['codigo_cobrade']}\n";
            }

            if ($rat['data_fato']) {
                $contextText .= "Data do Fato: {$rat['data_fato']}\n";
            }

            if ($rat['data_inicio']) {
                $contextText .= "Inicio da Atividade: {$rat['data_inicio']}\n";
            }

            if ($rat['data_termino']) {
                $contextText .= "Termino da Atividade: {$rat['data_termino']}\n";
            }

            $contextText .= "Tem Vistoria: {$rat['tem_vistoria']}\n";
            $contextText .= "Endereco: {$rat['endereco_completo']}\n";
            $contextText .= "Criado em: {$rat['criado_em']}\n";
            $contextText .= "---\n";
        }

        $contextText .= "\nPergunta do usuario: {$originalMessage}\n";
        $contextText .= "\nResponda baseado nos dados acima de forma clara e objetiva.";

        $this->contextData = $ratContexts;

        return $contextText;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
