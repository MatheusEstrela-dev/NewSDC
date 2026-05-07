<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Services;

use App\Modules\Compdec\DTOs\PrefeituraDTO;
use App\Modules\Compdec\Models\Orgao;
use App\Modules\Compdec\Models\Prefeitura;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PrefeituraService
{
    public function obterPorOrgao(int $orgaoId): ?Prefeitura
    {
        $orgao = Orgao::findOrFail($orgaoId);

        if (! $orgao->municipio_id) {
            return null;
        }

        return Prefeitura::query()
            ->where('municipio_id', $orgao->municipio_id)
            ->first();
    }

    public function upsertPorOrgao(int $orgaoId, PrefeituraDTO $dto): Prefeitura
    {
        return DB::transaction(function () use ($orgaoId, $dto): Prefeitura {
            $orgao = Orgao::findOrFail($orgaoId);

            if (! $orgao->municipio_id) {
                throw new InvalidArgumentException('Orgao nao possui municipio vinculado; nao e possivel criar prefeitura.');
            }

            // garante que o DTO traz o municipio_id correto do orgao
            $payload = $dto->toArray();
            $payload['municipio_id'] = $orgao->municipio_id;

            return Prefeitura::updateOrCreate(
                ['municipio_id' => $orgao->municipio_id],
                $payload,
            );
        });
    }

    public function uploadFoto(int $prefeituraId, UploadedFile $arquivo): Media
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);

        return $prefeitura
            ->addMedia($arquivo->getRealPath())
            ->usingFileName($arquivo->hashName())
            ->usingName($arquivo->getClientOriginalName())
            ->toMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO, config('compdec.disk', 'compdec'));
    }

    public function removerFoto(int $prefeituraId): bool
    {
        $prefeitura = Prefeitura::findOrFail($prefeituraId);
        $prefeitura->clearMediaCollection(Prefeitura::MEDIA_FOTO_PREFEITO);

        return true;
    }

    public function obterPrefeituraPorOrgaoOuFalhar(int $orgaoId): Prefeitura
    {
        $prefeitura = $this->obterPorOrgao($orgaoId);

        if (! $prefeitura) {
            throw new ModelNotFoundException('Prefeitura nao cadastrada para este orgao.');
        }

        return $prefeitura;
    }
}
