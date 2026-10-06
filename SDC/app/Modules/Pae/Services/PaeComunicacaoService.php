<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Modules\Pae\Models\PaeComunicacao;
use App\Modules\Pae\Models\PaeProtocolo;
use InvalidArgumentException;

final class PaeComunicacaoService
{
    private const ORIGENS_FEAM = ['ccpae', 'reprovacao_sumaria', 'reprovacao_analise'];

    private const ORIGENS_COMPDEC = ['ccpae', 'reprovacao_sumaria', 'reprovacao_analise', 'notificacao'];

    public function abrir(PaeProtocolo $protocolo, string $origemTipo, int $origemId, ?string $motivos = null): void
    {
        if (! in_array($origemTipo, self::ORIGENS_COMPDEC, true) || $origemId < 1) {
            throw new InvalidArgumentException('Origem de comunicação PAE inválida.');
        }

        $base = [
            'protocolo_id' => $protocolo->id,
            'origem_tipo' => $origemTipo,
            'origem_id' => $origemId,
        ];
        $novos = ['motivos' => $motivos, 'status' => 'pendente'];

        if (in_array($origemTipo, self::ORIGENS_FEAM, true)) {
            PaeComunicacao::query()->firstOrCreate($base + [
                'destinatario_tipo' => 'feam', 'municipio_id' => null,
            ], $novos);
        }

        $zas = $protocolo->municipiosImpactados()->where('na_zas', true)
            ->whereNotNull('confirmado_em')->pluck('municipio_id');
        foreach ($zas as $municipioId) {
            PaeComunicacao::query()->firstOrCreate($base + [
                'destinatario_tipo' => 'compdec', 'municipio_id' => $municipioId,
            ], $novos);
        }
    }

    public function resumo(PaeProtocolo $protocolo): array
    {
        $comunicacoes = $protocolo->comunicacoes()->with('municipio:id,nome,uf', 'registrador:id,name')->get();

        return [
            'comunicacoes' => $comunicacoes->map(fn (PaeComunicacao $comunicacao): array => [
                'id' => $comunicacao->id,
                'origem_tipo' => $comunicacao->origem_tipo,
                'origem_id' => $comunicacao->origem_id,
                'destinatario_tipo' => $comunicacao->destinatario_tipo,
                'municipio_id' => $comunicacao->municipio_id,
                'municipio' => $comunicacao->municipio?->nome,
                'uf' => $comunicacao->municipio?->uf,
                'motivos' => $comunicacao->motivos,
                'status' => $comunicacao->status,
                'dt_envio' => $comunicacao->dt_envio?->toDateString(),
                'num_sei' => $comunicacao->num_sei,
                'comprovante_nome_original' => $comunicacao->comprovante_nome_original,
                'registrado_por' => $comunicacao->registrador?->name,
            ])->all(),
            'pendentes' => $comunicacoes->where('status', 'pendente')->count(),
            'registradas' => $comunicacoes->where('status', 'registrada')->count(),
            'enderecamento_pendente' => ! $protocolo->municipiosImpactados()
                ->where('na_zas', true)->whereNotNull('confirmado_em')->exists(),
        ];
    }
}
