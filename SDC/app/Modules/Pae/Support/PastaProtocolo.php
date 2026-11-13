<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support;

use App\Modules\Pae\Models\PaeProtocolo;

/**
 * Dados do protocolo que toda tela da pasta (relatorio, ficha, DCO, evacuacao,
 * simulados e anexos) recebe, incluindo as versoes do mesmo processo.
 *
 * Versoes compartilham o prefixo dd.mm.aaaa-NNNN de num_protocolo e mudam so o
 * sufixo -VVV (ver PaeProtocoloService::relacionar e CicloProtocolo).
 */
final class PastaProtocolo
{
    private const PADRAO_VERSAO = '/^(\d{2}\.\d{2}\.\d{4}-\d{4})-\d{3}$/';

    /**
     * @param  array<string, mixed>  $extras  Campos proprios de cada tela.
     * @return array<string, mixed>
     */
    public static function props(PaeProtocolo $protocolo, array $extras = []): array
    {
        return [
            'id' => $protocolo->id,
            'num_protocolo' => $protocolo->num_protocolo,
            'status' => $protocolo->status?->value,
            'arquivado' => (bool) $protocolo->arquivado,
            ...$extras,
            'versoes' => self::versoes($protocolo),
        ];
    }

    /**
     * @return list<array{id: int, num_protocolo: string, status_label: ?string, arquivado: bool, atual: bool}>
     */
    private static function versoes(PaeProtocolo $protocolo): array
    {
        if (preg_match(self::PADRAO_VERSAO, (string) $protocolo->num_protocolo, $m) !== 1) {
            return [];
        }

        return PaeProtocolo::query()
            ->where('num_protocolo', 'like', $m[1] . '-%')
            ->orderBy('num_protocolo')
            ->get(['id', 'num_protocolo', 'status', 'arquivado'])
            ->map(fn (PaeProtocolo $versao): array => [
                'id' => $versao->id,
                'num_protocolo' => $versao->num_protocolo,
                'status_label' => $versao->status?->getLabel(),
                'arquivado' => (bool) $versao->arquivado,
                'atual' => $versao->id === $protocolo->id,
            ])
            ->values()
            ->all();
    }
}
