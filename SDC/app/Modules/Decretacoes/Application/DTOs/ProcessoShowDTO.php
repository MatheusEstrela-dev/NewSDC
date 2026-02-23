<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\Application\DTOs;

use App\Modules\Decretacoes\Domain\Entities\Processo;
use Illuminate\Contracts\Support\Arrayable;

/**
 * ViewModel: Processo para visualização
 * Implementa Arrayable para conversão automática pelo Inertia
 *
 * Regra DCU: Dados já formatados para o que o usuário vê
 * - Datas formatadas
 * - Badges de status prontos
 * - Labels humanizados
 */
final class ProcessoShowDTO implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly string $protocolo,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $statusColor,
        public readonly string $origem,
        public readonly string $origemLabel,
        public readonly string $situacao,
        public readonly string $situacaoLabel,
        public readonly ?string $dataEntrada,
        public readonly ?string $dataOcorrencia,
        public readonly ?string $dataVencimento,
        public readonly ?int $diasRestantes,
        public readonly ?string $diasRestantesLabel,
        public readonly ?string $tipoDesastre,
        public readonly ?string $cobrade,
        public readonly ?string $municipio,
        public readonly ?string $redec,
        public readonly ?string $analista,
        public readonly ?string $decretoMunicipal,
        public readonly ?string $dataDecretoMunicipal,
        public readonly ?string $decretoEstadual,
        public readonly ?string $dataDecretoEstadual,
        public readonly ?string $portariaFederal,
        public readonly ?string $observacoes,
        public readonly ?string $processoSei,
        public readonly array $municipios,
        public readonly array $danosHumanos,
        public readonly array $danosMateriais,
        public readonly array $prejuizos,
        public readonly array $anexos,
        public readonly array $logs,
        public readonly string $criadoEm,
        public readonly string $atualizadoEm,
    ) {
    }

    /**
     * Factory: Cria ViewModel a partir do Model
     * Toda formatação acontece aqui, não no Controller
     */
    public static function fromModel(Processo $processo): self
    {
        return new self(
            id: $processo->id,
            protocolo: $processo->n_protocolo_fide ?? '',
            status: $processo->status ?? '',
            statusLabel: self::getStatusLabel($processo->status),
            statusColor: self::getStatusColor($processo->status),
            origem: $processo->origem ?? '',
            origemLabel: $processo->origem === 'municipal' ? 'Municipal' : 'Estadual',
            situacao: $processo->situacao_anormalidade ?? '',
            situacaoLabel: self::getSituacaoLabel($processo->situacao_anormalidade),
            dataEntrada: $processo->data_entrada?->format('d/m/Y'),
            dataOcorrencia: $processo->data_ocorrencia?->format('d/m/Y'),
            dataVencimento: $processo->data_vencimento_decreto?->format('d/m/Y'),
            diasRestantes: $processo->dias_restantes,
            diasRestantesLabel: self::getDiasRestantesLabel($processo->dias_restantes),
            tipoDesastre: $processo->tipoDesastre?->nome,
            cobrade: $processo->cobrade?->descricao,
            municipio: $processo->municipio?->nome,
            redec: $processo->redec?->nome,
            analista: $processo->analista?->name,
            decretoMunicipal: $processo->n_decreto_municipal,
            dataDecretoMunicipal: $processo->data_decreto_municipal?->format('d/m/Y'),
            decretoEstadual: $processo->n_decreto_estadual,
            dataDecretoEstadual: $processo->data_decreto_estadual?->format('d/m/Y'),
            portariaFederal: $processo->n_portaria_federal,
            observacoes: $processo->observacoes,
            processoSei: $processo->n_processo_sei,
            municipios: self::mapMunicipios($processo->municipios ?? collect()),
            danosHumanos: self::mapDanosHumanos($processo->danosHumanos ?? collect()),
            danosMateriais: self::mapDanosMateriais($processo->danosMateriais ?? collect()),
            prejuizos: self::mapPrejuizos($processo->prejuizos ?? collect()),
            anexos: self::mapAnexos($processo->anexos ?? collect()),
            logs: self::mapLogs($processo->logs ?? collect()),
            criadoEm: $processo->created_at?->format('d/m/Y H:i') ?? '',
            atualizadoEm: $processo->updated_at?->format('d/m/Y H:i') ?? '',
        );
    }

    // ─── Labels formatados para DCU ─────────────────────────────────

    private static function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'em_analise' => 'Em Análise',
            'aprovado' => 'Aprovado',
            'rejeitado' => 'Rejeitado',
            'pendente' => 'Pendente',
            default => $status ?? 'Desconhecido',
        };
    }

    private static function getStatusColor(?string $status): string
    {
        return match ($status) {
            'em_analise' => 'yellow',
            'aprovado' => 'green',
            'rejeitado' => 'red',
            'pendente' => 'gray',
            default => 'gray',
        };
    }

    private static function getSituacaoLabel(?string $situacao): string
    {
        return match ($situacao) {
            'N1' => 'Situação de Emergência (SE)',
            'SE' => 'Estado de Calamidade Pública (ECP)',
            default => $situacao ?? 'Não definido',
        };
    }

    private static function getDiasRestantesLabel(?int $dias): string
    {
        if ($dias === null) {
            return 'Não definido';
        }
        if ($dias < 0) {
            return 'Expirado há ' . abs($dias) . ' dias';
        }
        if ($dias === 0) {
            return 'Expira hoje';
        }
        if ($dias <= 7) {
            return $dias . ' dias (urgente)';
        }
        return $dias . ' dias';
    }

    // ─── Mapeadores de relações ─────────────────────────────────────

    private static function mapMunicipios($municipios): array
    {
        return $municipios->map(fn($m) => [
            'id' => $m->id,
            'nome' => $m->nome,
            'ibge' => $m->ibge ?? null,
        ])->toArray();
    }

    private static function mapDanosHumanos($danos): array
    {
        return $danos->map(fn($d) => [
            'id' => $d->id,
            'tipo' => $d->tipo ?? null,
            'quantidade' => $d->quantidade ?? 0,
            'municipio' => $d->municipio?->nome ?? null,
        ])->toArray();
    }

    private static function mapDanosMateriais($danos): array
    {
        return $danos->map(fn($d) => [
            'id' => $d->id,
            'descricao' => $d->descricao ?? null,
            'quantidade' => $d->quantidade ?? 0,
            'municipio' => $d->municipio?->nome ?? null,
        ])->toArray();
    }

    private static function mapPrejuizos($prejuizos): array
    {
        return $prejuizos->map(fn($p) => [
            'id' => $p->id,
            'descricao' => $p->descricao ?? null,
            'valor' => number_format($p->valor ?? 0, 2, ',', '.'),
            'municipio' => $p->municipio?->nome ?? null,
        ])->toArray();
    }

    private static function mapAnexos($anexos): array
    {
        return $anexos->map(fn($a) => [
            'id' => $a->id,
            'nome' => $a->nome ?? null,
            'tipo' => $a->tipo ?? null,
            'url' => $a->url ?? null,
        ])->toArray();
    }

    private static function mapLogs($logs): array
    {
        return $logs->map(fn($l) => [
            'id' => $l->id,
            'acao' => $l->acao ?? null,
            'descricao' => $l->descricao ?? null,
            'usuario' => $l->user?->name ?? null,
            'data' => $l->created_at?->format('d/m/Y H:i'),
        ])->toArray();
    }

    /**
     * Arrayable: Conversão automática pelo Inertia
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'protocolo' => $this->protocolo,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'status_color' => $this->statusColor,
            'origem' => $this->origem,
            'origem_label' => $this->origemLabel,
            'situacao' => $this->situacao,
            'situacao_label' => $this->situacaoLabel,
            'data_entrada' => $this->dataEntrada,
            'data_ocorrencia' => $this->dataOcorrencia,
            'data_vencimento' => $this->dataVencimento,
            'dias_restantes' => $this->diasRestantes,
            'dias_restantes_label' => $this->diasRestantesLabel,
            'tipo_desastre' => $this->tipoDesastre,
            'cobrade' => $this->cobrade,
            'municipio' => $this->municipio,
            'redec' => $this->redec,
            'analista' => $this->analista,
            'decreto_municipal' => $this->decretoMunicipal,
            'data_decreto_municipal' => $this->dataDecretoMunicipal,
            'decreto_estadual' => $this->decretoEstadual,
            'data_decreto_estadual' => $this->dataDecretoEstadual,
            'portaria_federal' => $this->portariaFederal,
            'observacoes' => $this->observacoes,
            'processo_sei' => $this->processoSei,
            'municipios' => $this->municipios,
            'danos_humanos' => $this->danosHumanos,
            'danos_materiais' => $this->danosMateriais,
            'prejuizos' => $this->prejuizos,
            'anexos' => $this->anexos,
            'logs' => $this->logs,
            'criado_em' => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
