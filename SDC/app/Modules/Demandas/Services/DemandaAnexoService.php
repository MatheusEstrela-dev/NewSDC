<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAnexo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class DemandaAnexoService
{
    public function __construct(private readonly HistoricoDemanda $historico) {}

    public function anexar(Demanda $demanda, UploadedFile $arquivo, int $userId): DemandaAnexo
    {
        $disk = (string) config('demandas.anexos.disk');
        $path = $arquivo->store('demandas/'.$demanda->id, $disk);
        abort_if($path === false, 500, 'Falha ao armazenar o anexo.');

        try {
            return DB::transaction(function () use ($demanda, $arquivo, $userId, $path): DemandaAnexo {
                $anexo = $demanda->attachments()->create([
                    'user_id' => $userId,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'nome_arquivo' => basename($path),
                    'mime_type' => $arquivo->getMimeType(),
                    'tamanho_bytes' => $arquivo->getSize(),
                    'path' => $path,
                    'checksum_sha256' => hash_file('sha256', $arquivo->getRealPath()) ?: null,
                ]);
                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::ANEXO_ADICIONADO,
                    'Arquivo anexado: '.$arquivo->getClientOriginalName().'.',
                );

                return $anexo;
            });
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function baixar(Demanda $demanda, DemandaAnexo $anexo): StreamedResponse
    {
        abort_unless((int) $anexo->task_id === (int) $demanda->id, 404);
        // Anexo do legado sem o arquivo fisico: nem chega a olhar o disco.
        abort_unless((bool) $anexo->arquivo_disponivel, 404);

        $disk = Storage::disk((string) config('demandas.anexos.disk'));
        abort_unless($disk->exists($anexo->path), 404);

        return $disk->download($anexo->path, $anexo->nome_original);
    }
}
