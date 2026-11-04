<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\Models\Vistoria;
use App\Modules\Tdap\Models\VistoriaFoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Armazena e remove fotos de vistoria no disco 'tdap'.
 * Espelha CronogramaComprovanteService: binario no disco, metadados em tabela.
 */
class VistoriaFotoService
{
    private string $disk = 'tdap';

    public function store(Vistoria $vistoria, UploadedFile $file, ?string $descricao = null): VistoriaFoto
    {
        $path = $file->store("vistorias/{$vistoria->id}/fotos", $this->disk);

        try {
            return VistoriaFoto::create([
                'vistoria_id'   => $vistoria->id,
                'nome_original' => $file->getClientOriginalName(),
                'nome_arquivo'  => basename($path),
                'mime_type'     => $file->getMimeType(),
                'tamanho_bytes' => $file->getSize(),
                'path'          => $path,
                'disk'          => $this->disk,
                'descricao'     => $descricao,
                'uploaded_by'   => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            // Linha nao gravou: o arquivo ficaria no disco sem ninguem saber o caminho.
            Storage::disk($this->disk)->delete($path);

            throw $e;
        }
    }

    /**
     * Apaga do disco os arquivos de fotos cuja transacao foi desfeita.
     *
     * O rollback desfaz as linhas de tdap_vistoria_fotos, nao os binarios ja
     * gravados por store(). Quem grava fotos dentro de DB::transaction chama
     * isto no catch, com as fotos que store() devolveu. Falha ao apagar e so
     * registrada: nao pode esconder a excecao original.
     *
     * @param  iterable<VistoriaFoto>  $fotos
     */
    public function descartarArquivos(iterable $fotos): void
    {
        foreach ($fotos as $foto) {
            try {
                Storage::disk($foto->disk)->delete($foto->path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Apaga o binario ANTES da linha. Na ordem inversa, uma falha no disco
     * deixaria a tabela sem o registro e o arquivo orfao no storage, sem
     * ninguem que soubesse o caminho dele.
     */
    public function destroy(VistoriaFoto $foto): void
    {
        Storage::disk($foto->disk)->delete($foto->path);
        $foto->delete();
    }
}
