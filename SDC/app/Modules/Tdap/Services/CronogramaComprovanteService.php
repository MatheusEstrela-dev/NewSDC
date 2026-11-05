<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Services;

use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronogramaComprovante;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Armazena e remove comprovantes (anexos) de cronograma no disco 'tdap'.
 * Espelha RatAttachmentService: binario no disco, metadados em tabela.
 */
class CronogramaComprovanteService
{
    private string $disk = 'tdap';

    public function store(Cronograma $cronograma, UploadedFile $file, ?string $descricao = null): CronogramaComprovante
    {
        // Regra propria do TDAP: comprovante pertence a um cronograma, nao a
        // um protocolo. O prefixo do modulo ja e o root do disk 'tdap'.
        $path = $file->store("cronogramas/{$cronograma->id}/comprovantes", $this->disk);

        return CronogramaComprovante::create([
            'cronograma_id' => $cronograma->id,
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
     * @throws \DomainException quando o cronograma esta encerrado
     */
    public function destroy(CronogramaComprovante $comprovante): void
    {
        // Encerrado e registro historico (mesma regra de CronogramaService::deletar):
        // o comprovante e a prova do que foi executado e pago.
        $encerrado = Cronograma::query()->whereKey($comprovante->cronograma_id)->whereNotNull('encerrado_em')->exists();
        if ($encerrado) {
            throw new \DomainException('Cronograma encerrado e registro historico: seus comprovantes nao podem ser removidos.');
        }

        Storage::disk($comprovante->disk)->delete($comprovante->path);
        $comprovante->delete();
    }
}
