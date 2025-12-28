<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use App\Modules\AjudaHumanitaria\Presentation\Http\Requests\BeneficiarioUpdateRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: Atualizar Beneficiário
 *
 * Atualiza dados de um beneficiário existente
 */
class BeneficiarioUpdateController extends Controller
{
    public function __construct(
        private readonly BeneficiarioRepositoryInterface $beneficiarioRepository
    ) {
    }

    public function __invoke(BeneficiarioUpdateRequest $request, int $id): RedirectResponse
    {
        $beneficiario = $this->beneficiarioRepository->findOrFail($id);
        $data = $request->validated();

        // Atualiza dados do beneficiário
        $beneficiario->update($data);

        // Atualiza membros da família se fornecidos
        if (isset($data['membros'])) {
            // Remove membros antigos
            $beneficiario->membros()->delete();

            // Adiciona novos membros
            foreach ($data['membros'] as $membroData) {
                $beneficiario->membros()->create($membroData);
            }
        }

        return redirect()
            ->route('ajuda-humanitaria.beneficiarios.show', $beneficiario->id)
            ->with('success', 'Beneficiário atualizado com sucesso!');
    }
}
