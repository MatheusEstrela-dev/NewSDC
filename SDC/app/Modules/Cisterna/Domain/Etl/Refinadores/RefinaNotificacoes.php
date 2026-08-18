<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Throwable;

class RefinaNotificacoes implements Refinador
{
    public function recurso(): string
    {
        return 'notificacoes';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_notificacoes';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado.', $doc);

            return;
        }

        $observacao = trim((string) ($doc['obs'] ?? ''));

        if ($observacao === '') {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Notificacao sem texto.');

            return;
        }

        $respondida = NormalizaEntrada::booleanoSimNao($doc['respondida'] ?? null) ?? false;

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria notificacao para o beneficiario {$beneficiarioId}.");

            return;
        }

        $atributos = [
            // No legado a notificacao so podia pender da cisterna. O morph
            // permite pender de uma vistoria, mas o importado mantem a
            // semantica original.
            'notificavel_type' => CisternaBeneficiario::class,
            'notificavel_id' => (int) $beneficiarioId,
            'observacao' => $observacao,
            'respondida' => $respondida,
            'respondida_em' => $respondida ? ($doc['updated_at'] ?? now()) : null,
            'legacy_id' => $legacyId,
        ];

        try {
            $existente = CisternaNotificacao::where('legacy_id', $legacyId)->first();

            if ($existente !== null) {
                $existente->update($atributos);
                RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

                return;
            }

            $criada = CisternaNotificacao::create($atributos);
            RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar notificacao: '.$e->getMessage(), $doc);
        }
    }
}
