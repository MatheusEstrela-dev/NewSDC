<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\DTOs\PaeFormAnexoDTO;
use App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO;
use App\Modules\Pae\DTOs\PaeFormObjetivoDTO;
use App\Modules\Pae\Enums\PaeProtocoloStatus;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormAnexo;
use App\Modules\Pae\Models\PaeFormApontamento;
use App\Modules\Pae\Models\PaeFormConclusaoItem;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Models\PaeTimeline;
use App\Modules\Shared\BaseService;
use App\Support\Storage\AnexoPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PaeFormularioService extends BaseService
{
    public function __construct(
        private readonly PaeProtocoloService $protocoloService
    ) {}

    public function findById(int $id): ?PaeForm
    {
        return PaeForm::with(['apontamentos', 'conclusao', 'anexos'])->find($id);
    }

    public function create(PaeFormInfoGeraisDTO $dto): PaeForm
    {
        $data = array_merge($dto->toArray(), [
            'status'     => 'RASCUNHO',
            'created_by' => $dto->userId,
        ]);

        if ($dto->paeProtocoloId) {
            $existing = PaeForm::where('pae_protocolo_id', $dto->paeProtocoloId)->first();

            if ($existing) {
                $existing->update($dto->toArray());

                return $existing->fresh();
            }
        }

        return PaeForm::create(
            $data
        );
    }

    public function updateInfoGerais(PaeForm $form, PaeFormInfoGeraisDTO $dto): void
    {
        $form->update($dto->toArray());
    }

    public function updateObjetivoContexto(PaeForm $form, PaeFormObjetivoDTO $dto): void
    {
        $form->update($dto->toArray());
    }

    public function updateApontamentos(PaeForm $form, array $itens, User $user): void
    {
        $this->syncApontamentos($form, $itens);
        $form->update(['updated_by' => $user->id]);
    }

    public function updateConclusao(PaeForm $form, array $itens, User $user): void
    {
        $this->syncConclusao($form, $itens);
        $form->update(['updated_by' => $user->id]);
    }

    public function storeAnexo(PaeForm $form, PaeFormAnexoDTO $dto, User $user): PaeFormAnexo
    {
        $disk = 'pae';
        $directory = $this->anexoDirectory($form);
        $extension = mb_strtolower($dto->arquivo->getClientOriginalExtension());
        $nomeArquivo = (string) Str::uuid() . ($extension !== '' ? '.' . $extension : '');
        $path = null;

        try {
            $path = $dto->arquivo->storeAs($directory, $nomeArquivo, $disk);

            return $form->anexos()->create([
                'nome_original' => $dto->arquivo->getClientOriginalName(),
                'nome_arquivo' => $nomeArquivo,
                'mime_type' => $dto->arquivo->getMimeType() ?: $dto->arquivo->getClientMimeType(),
                'tamanho_bytes' => $dto->arquivo->getSize(),
                'path' => $path,
                'disk' => $disk,
                'descricao' => $dto->descricao,
                'uploaded_by' => $user->id,
            ]);
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    public function storeAnexoForProtocolo(PaeProtocolo $protocolo, PaeFormAnexoDTO $dto, User $user): PaeFormAnexo
    {
        $form = PaeForm::query()
            ->where('pae_protocolo_id', $protocolo->id)
            ->first();

        if (! $form) {
            $form = PaeForm::create([
                'pae_protocolo_id' => $protocolo->id,
                'status' => 'RASCUNHO',
                'pae_empnto_id' => $protocolo->pae_empnto_id,
                'emp_responsavel_nome' => $protocolo->empnto_search,
                'created_by' => $user->id,
            ]);
        }

        return $this->storeAnexo($form, $dto, $user);
    }

    public function deleteAnexo(PaeForm $form, PaeFormAnexo $anexo): void
    {
        $this->ensureAnexoBelongsToForm($form, $anexo);

        Storage::disk($anexo->disk)->delete($anexo->path);
        $anexo->delete();
    }

    public function downloadAnexo(PaeForm $form, PaeFormAnexo $anexo): StreamedResponse
    {
        $this->ensureAnexoBelongsToForm($form, $anexo);

        abort_unless(Storage::disk($anexo->disk)->exists($anexo->path), 404);

        return Storage::disk($anexo->disk)->download($anexo->path, $anexo->nome_original);
    }

    public function viewAnexo(PaeForm $form, PaeFormAnexo $anexo): StreamedResponse
    {
        $this->ensureAnexoBelongsToForm($form, $anexo);

        abort_unless(Storage::disk($anexo->disk)->exists($anexo->path), 404);

        return Storage::disk($anexo->disk)->response(
            $anexo->path,
            $anexo->nome_original,
            ['Content-Type' => $anexo->mime_type],
            'inline'
        );
    }

    public function finalizar(PaeForm $form, User $user): void
    {
        if ($form->pae_protocolo_id) {
            $form->update([
                'status'     => 'FINALIZADO',
                'updated_by' => $user->id,
            ]);
            return;
        }

        $protocolo = PaeProtocolo::create([
            'num_protocolo'  => $this->protocoloService->gerarNumProtocolo(),
            'status'         => PaeProtocoloStatus::NOVO->value,
            'user_id'        => $user->id,
            'created_by'     => $user->id,
            'dt_entrada'     => now()->toDateString(),
            'arquivado'      => false,
            'empnto_search'  => $form->emp_responsavel_nome,
            'pae_empnto_id'  => $form->pae_empnto_id,
        ]);

        PaeTimeline::create([
            'protocolo_id' => $protocolo->id,
            'evento'       => 'criacao',
            'descricao'    => 'Protocolo gerado a partir do formulário PAE.',
            'user_id'      => $user->id,
        ]);

        $form->update([
            'pae_protocolo_id' => $protocolo->id,
            'status'           => 'FINALIZADO',
            'updated_by'       => $user->id,
        ]);
    }

    public function formatForView(PaeForm $form): array
    {
        $apontamentos = $form->relationLoaded('apontamentos')
            ? $form->apontamentos
            : $form->apontamentos()->get();

        $conclusao = $form->relationLoaded('conclusao')
            ? $form->conclusao
            : $form->conclusao()->get();

        $anexos = $form->relationLoaded('anexos')
            ? $form->anexos
            : $form->anexos()->get();

        return [
            'id'                      => $form->id,
            'pae_protocolo_id'        => $form->pae_protocolo_id,
            'barragem'                => $form->barragem_nome,
            'municipio_id'            => $form->municipio_id,
            'pae_empnto_id'           => $form->pae_empnto_id,
            'empreendedor_res'        => $form->emp_responsavel_nome,
            'coordenador_pae'         => $form->coord_pae_nome,
            'email'                   => $form->coord_pae_email,
            'coordenador_mun_def_civ' => $form->coord_mun_def_civ,
            'coordenador_mun_compdec' => $form->coord_mun_compdec,
            'metodo_construtivo'      => $form->metodo_construtivo,
            'numero_zas'              => $form->num_zas,
            'nivel_emergencia'        => $form->nivel_emergencia,
            'objetivo'                => $form->objetivo,
            'contextualizacao'        => $form->contexto,
            'apontamentos'            => $this->buildTree($apontamentos),
            'anexos'                  => $this->formatAnexos($anexos),
            'conclusao'               => $this->buildTree($conclusao),
            'status'                  => $form->status,
        ];
    }

    private function ensureAnexoBelongsToForm(PaeForm $form, PaeFormAnexo $anexo): void
    {
        abort_unless((int) $anexo->pae_form_id === (int) $form->id, 404);
    }

    private function formatAnexos($anexos): array
    {
        return $anexos
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (PaeFormAnexo $anexo) => [
                'id' => $anexo->id,
                'nome_original' => $anexo->nome_original,
                'nome_arquivo' => $anexo->nome_arquivo,
                'mime_type' => $anexo->mime_type,
                'tamanho_bytes' => $anexo->tamanho_bytes,
                'tamanho_formatado' => $anexo->tamanho_formatado,
                'descricao' => $anexo->descricao,
                'disk' => $anexo->disk,
                'local_armazenamento' => dirname($anexo->path),
                'created_at' => $anexo->created_at?->format('Y-m-d H:i:s'),
            ])
            ->all();
    }

    private function anexoDirectory(PaeForm $form): string
    {
        $form->loadMissing('protocolo');

        if ($form->protocolo) {
            return AnexoPath::protocolo(
                $form->protocolo->num_protocolo ?: 'protocolo-' . $form->protocolo->id
            );
        }

        return 'formularios/' . $form->id . '/documentos/anexos';
    }

    private function syncApontamentos(PaeForm $form, array $itens): void
    {
        $form->apontamentos()->delete();

        $ordem = 0;
        foreach ($itens as $item) {
            $pai = PaeFormApontamento::create([
                'pae_form_id' => $form->id,
                'conteudo'    => $item['text'] ?? '',
                'ordem'       => $ordem++,
                'status'      => 'CONFORME',
            ]);

            foreach ($item['children'] ?? [] as $filho) {
                PaeFormApontamento::create([
                    'pae_form_id' => $form->id,
                    'parent_id'   => $pai->id,
                    'conteudo'    => $filho['text'] ?? '',
                    'ordem'       => $ordem++,
                    'status'      => 'CONFORME',
                ]);
            }
        }
    }

    private function syncConclusao(PaeForm $form, array $itens): void
    {
        $form->conclusao()->delete();

        $ordem = 0;
        foreach ($itens as $item) {
            $pai = PaeFormConclusaoItem::create([
                'pae_form_id' => $form->id,
                'conteudo'    => $item['text'] ?? '',
                'ordem'       => $ordem++,
                'status'      => 'CONFORME',
            ]);

            foreach ($item['children'] ?? [] as $filho) {
                PaeFormConclusaoItem::create([
                    'pae_form_id' => $form->id,
                    'parent_id'   => $pai->id,
                    'conteudo'    => $filho['text'] ?? '',
                    'ordem'       => $ordem++,
                    'status'      => 'CONFORME',
                ]);
            }
        }
    }

    private function buildTree($itens): array
    {
        return $itens->whereNull('parent_id')
            ->sortBy('ordem')
            ->values()
            ->map(function ($item) use ($itens) {
                return [
                    'id'       => $item->id,
                    'text'     => $item->conteudo,
                    'children' => $itens->where('parent_id', $item->id)
                        ->sortBy('ordem')
                        ->map(fn($f) => ['id' => $f->id, 'text' => $f->conteudo])
                        ->values()
                        ->all(),
                ];
            })->all();
    }
}
