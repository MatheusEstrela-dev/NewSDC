<?php

declare(strict_types=1);

namespace App\Core\IA\Services;

use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\Infrastructure\Persistence\EloquentRatRepository;
use Illuminate\Support\Facades\Log;

class RagService
{
    protected array $contextData = [];
    protected RatRepositoryInterface $ratRepository;

    public function __construct(?RatRepositoryInterface $ratRepository = null)
    {
        $this->ratRepository = $ratRepository ?? app(EloquentRatRepository::class);
    }

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

            $rats = $this->ratRepository->findAll(['search' => $reference], -1);

            foreach ($rats as $rat) {
                if (str_contains(strtoupper($rat->protocolo), $reference)) {
                    return $this->formatRatContext($rat);
                }
            }

            preg_match('/(\d+)/', $reference, $numMatches);
            if (!empty($numMatches[1])) {
                $ratById = $this->ratRepository->find((int) $numMatches[1]);
                if ($ratById) {
                    return $this->formatRatContext($ratById);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('RagService: Erro ao buscar RAT', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function fetchRecentRats(int $limit = 3): array
    {
        try {
            $rats = $this->ratRepository->findAll([], $limit);

            if ($rats->isEmpty()) {
                return [];
            }

            $result = [];
            foreach ($rats as $rat) {
                $result[] = $this->formatRatContext($rat);
            }
            return $result;
        } catch (\Exception $e) {
            Log::warning('RagService: Erro ao buscar RATs recentes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function formatRatContext($rat): array
    {
        $dadosGerais = is_array($rat->dados_gerais) ? $rat->dados_gerais : [];
        $local = is_array($rat->local) ? $rat->local : [];
        $endereco = isset($rat->endereco) && is_array($rat->endereco) ? $rat->endereco : [];

        $createdAt = $rat->created_at ?? null;
        $updatedAt = $rat->updated_at ?? null;

        $formattedCreatedAt = null;
        $formattedUpdatedAt = null;

        if ($createdAt) {
            $formattedCreatedAt = is_string($createdAt)
                ? $this->formatDate($createdAt)
                : (method_exists($createdAt, 'format') ? $createdAt->format('d/m/Y H:i') : null);
        }

        if ($updatedAt) {
            $formattedUpdatedAt = is_string($updatedAt)
                ? $this->formatDate($updatedAt)
                : (method_exists($updatedAt, 'format') ? $updatedAt->format('d/m/Y H:i') : null);
        }

        return [
            'id' => $rat->id,
            'protocolo' => $rat->protocolo,
            'status' => $this->translateStatus($rat->status ?? null),
            'municipio' => $dadosGerais['local_municipio'] ?? $local['municipio'] ?? 'Nao informado',
            'tipo_ocorrencia' => $dadosGerais['nat_nome_operacao'] ?? 'Nao informado',
            'codigo_cobrade' => $dadosGerais['nat_cobrade_id'] ?? null,
            'data_fato' => $this->formatDate($dadosGerais['data_fato'] ?? null),
            'data_inicio' => $this->formatDate($dadosGerais['data_inicio_atividade'] ?? null),
            'data_termino' => $this->formatDate($dadosGerais['data_termino_atividade'] ?? null),
            'tem_vistoria' => !empty($rat->tem_vistoria) ? 'Sim' : 'Nao',
            'endereco_completo' => $this->formatEndereco($endereco),
            'criado_em' => $formattedCreatedAt,
            'atualizado_em' => $formattedUpdatedAt,
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

    protected function formatDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof \Carbon\Carbon || $date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y H:i');
        }

        if (is_string($date)) {
            try {
                return \Carbon\Carbon::parse($date)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
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
