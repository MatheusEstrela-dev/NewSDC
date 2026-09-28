<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\ResolvedorUsuarioLegado;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Services\HistoricoDemanda;
use Carbon\CarbonImmutable;

final class ImportarHistorico extends EtapaBase
{
    public function __construct(
        MapaImportacao $mapa,
        private readonly ResolvedorUsuarioLegado $usuarios,
        private readonly HistoricoDemanda $historico,
    ) {
        parent::__construct($mapa);
    }

    public function nome(): string { return 'historico'; }
    protected function tabelaOrigem(): string { return 'historico_chamados'; }
    protected function tabelaDestino(): string { return 'task_audit_logs'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        if ($destinoExistente !== null) {
            return $destinoExistente; // log e imutavel; hash novo so reconfirma
        }
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }

        $log = $this->historico->registrar(
            Demanda::withTrashed()->findOrFail($demandaId),
            $this->usuarios->resolver((int) $linha->user_id),
            $this->acao((string) $linha->acao),
            trim((string) ($linha->detalhes ?? '')) ?: (string) $linha->acao,
            ['legado_acao' => (string) $linha->acao, 'rotulo' => (string) $linha->acao],
            em: CarbonImmutable::parse($linha->created_at),
        );

        return (int) $log->id;
    }

    private function acao(string $legado): AcaoHistoricoDemanda
    {
        $a = mb_strtolower($legado);

        return match (true) {
            str_contains($a, 'criou') => AcaoHistoricoDemanda::CRIADA,
            str_contains($a, 'transfer') => AcaoHistoricoDemanda::ATRIBUIDA,
            str_contains($a, 'status') => AcaoHistoricoDemanda::STATUS_ALTERADO,
            str_contains($a, 'anexo') => AcaoHistoricoDemanda::ANEXO_ADICIONADO,
            default => AcaoHistoricoDemanda::EDITADA,
        };
    }
}
