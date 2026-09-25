<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Models\DemandaAnexo;
use Illuminate\Support\Facades\Storage;

/**
 * Copia o arquivo do disco legado. Arquivo ausente e rejeitado com motivo e
 * aparece no relatorio; o registro nao e criado apontando para lugar nenhum.
 */
final class ImportarAnexos extends EtapaBase
{
    public function nome(): string { return 'anexos'; }
    protected function tabelaOrigem(): string { return 'anexos'; }
    protected function tabelaDestino(): string { return 'task_attachments'; }

    protected function gravar(object $linha, ?int $destinoExistente): int|string
    {
        if ($destinoExistente !== null) {
            return $destinoExistente;
        }
        $demandaId = $this->mapa->alvo('chamados', (string) $linha->chamado_id);
        if ($demandaId === null) {
            return 'chamado_nao_importado';
        }

        $origem = Storage::disk((string) config('demandas.importacao.disk'));
        $caminho = ltrim((string) $linha->caminho_arquivo, '/');
        if ($caminho === '' || str_contains($caminho, '..') || ! $origem->exists($caminho)) {
            return 'arquivo_ausente';
        }

        $conteudo = $origem->get($caminho);
        $destino = 'demandas/'.$demandaId.'/legado-'.$linha->id.'-'.basename($caminho);
        Storage::disk((string) config('demandas.anexos.disk'))->put($destino, $conteudo);

        // create() dispara created/updated do TrilhaNoProtocoloPai (avisa o
        // protocolo pai); importacao nunca deve gerar trilha ou notificacao,
        // entao a gravacao aqui e silenciosa como nas demais etapas.
        $anexo = new DemandaAnexo();
        $anexo->forceFill([
            'task_id' => $demandaId,
            'user_id' => null,
            'nome_original' => (string) $linha->nome_original,
            'nome_arquivo' => basename($destino),
            'mime_type' => $origem->mimeType($caminho) ?: 'application/octet-stream',
            'tamanho_bytes' => strlen((string) $conteudo),
            'path' => $destino,
        ]);
        $anexo->saveQuietly();

        return (int) $anexo->id;
    }
}
