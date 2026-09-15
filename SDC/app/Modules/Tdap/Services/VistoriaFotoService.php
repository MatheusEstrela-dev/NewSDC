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
