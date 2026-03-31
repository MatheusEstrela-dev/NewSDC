<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\DTOs\PaeFormInfoGeraisDTO;
use App\Modules\Pae\DTOs\PaeFormObjetivoDTO;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormApontamento;
use App\Modules\Pae\Models\PaeFormConclusaoItem;
use App\Modules\Shared\BaseService;

class PaeFormularioService extends BaseService
{
    public function findById(int $id): ?PaeForm
    {
        return PaeForm::with(['apontamentos', 'conclusao'])->find($id);
    }

    public function create(PaeFormInfoGeraisDTO $dto): PaeForm
    {
        return PaeForm::create(
            array_merge($dto->toArray(), [
                'status'     => 'RASCUNHO',
                'created_by' => $dto->userId,
            ])
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

    public function finalizar(PaeForm $form, User $user): void
    {
        $form->update([
            'status'     => 'FINALIZADO',
            'updated_by' => $user->id,
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

        return [
            'id'                      => $form->id,
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
            'conclusao'               => $this->buildTree($conclusao),
            'status'                  => $form->status,
        ];
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
