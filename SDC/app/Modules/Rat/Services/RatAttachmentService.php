<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatAnexo;
use App\Support\Storage\AnexoPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RatAttachmentService
{
    // Disco dedicado: bind mount (ANEXOS_ROOT/RAT) na VM, local (symlink
    // public) em dev puro, Azure Blob (sdc-rat) no App Service (FS efemero).
    // Nomenclatura canonica por protocolo (numero_bos), igual ao PAE.
    private string $disk = 'rat';

    public function store(object $rat, UploadedFile $file, string $tipo = 'documento'): RatAnexo
    {
        $path = $file->store(
            AnexoPath::protocolo($rat->numero_bos ?: 'rat-' . $rat->id),
            $this->disk,
        );

        return RatAnexo::create([
            'rat_id'        => $rat->id,
            'categoria'     => $tipo,
            'nome_original' => $file->getClientOriginalName(),
            'nome_arquivo'  => basename($path),
            'mime_type'     => $file->getMimeType(),
            'tamanho_bytes' => $file->getSize(),
            'path'          => $path,
            'disk'          => $this->disk,
            'uploaded_by'   => Auth::id(),
        ]);
    }

    public function destroy(object $rat, string $attachmentId): void
    {
        $anexo = RatAnexo::where('rat_id', $rat->id)->findOrFail($attachmentId);

        Storage::disk($this->disk)->delete($anexo->path);
        $anexo->delete();
    }
}
