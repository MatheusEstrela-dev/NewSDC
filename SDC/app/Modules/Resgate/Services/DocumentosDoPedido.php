<?php

declare(strict_types=1);

namespace App\Modules\Resgate\Services;

use App\Modules\Resgate\Exceptions\RegraDoResgate;
use App\Modules\Resgate\Support\Rastro;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Anexos do pedido (termo assinado, evidencias de entrega), com integridade.
 *
 * O arquivo e gravado no disco privado com o SHA-256 no nome; a linha guarda
 * o hash, e toda leitura recalcula e compara antes de servir. Troca de arquivo
 * no disco e detectada, e o registro no banco e append-only (trigger).
 */
final class DocumentosDoPedido
{
    public function anexar(Connection $db, int $pedidoId, string $tipo, UploadedFile $arquivo, int $autorId, Rastro $rastro): int
    {
        $caminhoTemp = $arquivo->getRealPath();
        if ($caminhoTemp === false) {
            throw new RegraDoResgate('Arquivo inválido.', 'arquivo');
        }
        $sha = hash_file('sha256', $caminhoTemp);
        $extensao = strtolower($arquivo->getClientOriginalExtension() ?: $arquivo->guessExtension() ?: 'bin');
        $caminho = "resgate/pedidos/{$pedidoId}/{$sha}.{$extensao}";

        $disco = Storage::disk((string) config('resgate.disco'));
        if (! $disco->exists($caminho)) {
            $disco->putFileAs(dirname($caminho), $arquivo, basename($caminho));
        }

        return (int) $db->selectOne(
            'INSERT INTO resgate.documentos (pedido_id, tipo, nome_original, caminho, sha256, tamanho, mime, enviado_por, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id',
            [$pedidoId, $tipo, mb_substr($arquivo->getClientOriginalName(), 0, 200), $caminho, $sha,
                (int) $arquivo->getSize(), (string) $arquivo->getMimeType(), $autorId, $rastro->ip, $rastro->userAgent],
        )->id;
    }

    /** @return list<array<string, mixed>> */
    public function listar(Connection $db, int $pedidoId): array
    {
        return array_map(static fn (object $d): array => (array) $d, $db->select(
            'SELECT id, tipo, nome_original, sha256, tamanho, mime, enviado_por, ip_address, criado_em
               FROM resgate.documentos WHERE pedido_id = ? ORDER BY id',
            [$pedidoId],
        ));
    }

    /**
     * Documento pronto para servir, com o hash conferido.
     *
     * @return array{caminho: string, nome: string, mime: string, integro: bool}|null
     */
    public function abrir(Connection $db, int $pedidoId, int $documentoId): ?array
    {
        $doc = $db->selectOne('SELECT * FROM resgate.documentos WHERE id = ? AND pedido_id = ?', [$documentoId, $pedidoId]);
        if ($doc === null) {
            return null;
        }
        $disco = Storage::disk((string) config('resgate.disco'));
        $integro = $disco->exists($doc->caminho) && hash('sha256', (string) $disco->get($doc->caminho)) === $doc->sha256;

        return ['caminho' => $doc->caminho, 'nome' => $doc->nome_original, 'mime' => $doc->mime, 'integro' => $integro];
    }
}
