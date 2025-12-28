<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Repositories\BeneficiarioRepositoryInterface;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: Excluir Beneficiário
 *
 * Remove um beneficiário do sistema (soft delete)
 */
class BeneficiarioDestroyController extends Controller
{
    public function __construct(
        private readonly BeneficiarioRepositoryInterface $beneficiarioRepository
    ) {
    }

    public function __invoke(int $id): RedirectResponse
    {
        $beneficiario = $this->beneficiarioRepository->findOrFail($id);

        // Verifica se pode ser excluído (não pode estar em abrigo)
        if ($beneficiario->abrigo_atual_id) {
            return redirect()
                ->back()
                ->with('error', 'Não é possível excluir beneficiário que está em um abrigo. Remova-o do abrigo primeiro.');
        }

        // Soft delete
        $beneficiario->delete();

        return redirect()
            ->route('ajuda-humanitaria.beneficiarios.index')
            ->with('success', 'Beneficiário excluído com sucesso!');
    }
}
