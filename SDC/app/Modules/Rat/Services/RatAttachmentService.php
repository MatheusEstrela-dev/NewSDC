<?php

declare(strict_types=1);

namespace App\Modules\Rat\Services;

use App\Modules\Rat\Models\RatAnexo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RatAttachmentService
{
    private string $disk = 'public';

    public function store(object $rat, UploadedFile $file): RatAnexo
    {
        $path = $file->store("rat/{$rat->id}/anexos", $this->disk);

        return RatAnexo::create([
            'rat_id'        => $rat->id,
            'categoria'     => 'documento',
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
