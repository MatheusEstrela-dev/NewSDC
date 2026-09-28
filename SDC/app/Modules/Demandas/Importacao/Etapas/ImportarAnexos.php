<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Models\DemandaAnexo;
use Illuminate\Support\Facades\Storage;

/**
 * Copia o arquivo do disco legado. Arquivo ausente (ou caminho suspeito) nao
 * rejeita a linha: o vinculo com a demanda e o unico dado confiavel que resta,
 * entao o registro e criado marcado indisponivel e um aviso aparece no
 * relatorio -- diferente de rejeitar, que perderia o vinculo.
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
        $seguro = $caminho !== '' && ! str_contains($caminho, '..');

        // create() dispara created/updated do TrilhaNoProtocoloPai (avisa o
        // protocolo pai); importacao nunca deve gerar trilha ou notificacao,
        // entao a gravacao aqui e silenciosa como nas demais etapas.
        $anexo = new DemandaAnexo();

        if ($seguro && $origem->exists($caminho)) {
            $conteudo = $origem->get($caminho);
            $destino = 'demandas/'.$demandaId.'/legado-'.$linha->id.'-'.basename($caminho);
            Storage::disk((string) config('demandas.anexos.disk'))->put($destino, $conteudo);

            $anexo->forceFill([
                'task_id' => $demandaId,
                'user_id' => null,
                'nome_original' => (string) $linha->nome_original,
                'nome_arquivo' => basename($destino),
                'mime_type' => $origem->mimeType($caminho) ?: 'application/octet-stream',
                'tamanho_bytes' => strlen((string) $conteudo),
                'path' => $destino,
                'arquivo_disponivel' => true,
                'checksum_sha256' => hash('sha256', $conteudo),
            ]);
        } else {
            $this->avisar('arquivo_ausente');

            $anexo->forceFill([
                'task_id' => $demandaId,
                'user_id' => null,
                'nome_original' => (string) $linha->nome_original,
                'nome_arquivo' => basename($caminho !== '' ? $caminho : (string) $linha->nome_original),
                'mime_type' => 'application/octet-stream',
                'tamanho_bytes' => 0,
                'path' => 'legado-indisponivel/'.$this->caminhoSanitizado($caminho, (string) $linha->nome_original),
                'arquivo_disponivel' => false,
                'checksum_sha256' => null,
            ]);
        }

        $anexo->saveQuietly();

        return (int) $anexo->id;
    }

    /**
     * Caminho legado sem segmentos '..' nem barra inicial, para o registro
     * indisponivel guardar de onde o arquivo deveria ter vindo sem virar
     * traversal. Cai no nome original quando o caminho fica vazio.
     */
    private function caminhoSanitizado(string $caminho, string $nomeOriginal): string
    {
        $partes = array_filter(
            explode('/', $caminho),
            static fn (string $p): bool => $p !== '' && $p !== '.' && $p !== '..',
        );
        $limpo = implode('/', $partes);

        return $limpo !== '' ? $limpo : ($nomeOriginal !== '' ? $nomeOriginal : 'sem-nome');
    }
}
