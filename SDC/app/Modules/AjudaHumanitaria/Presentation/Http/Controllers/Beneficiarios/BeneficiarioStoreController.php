<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Presentation\Http\Controllers\Beneficiarios;

use App\Http\Controllers\Controller;
use App\Modules\AjudaHumanitaria\Domain\Entities\Beneficiario;
use App\Modules\AjudaHumanitaria\Presentation\Http\Requests\BeneficiarioStoreRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Controller: Criar Beneficiário
 *
 * Cria um novo beneficiário
 */
class BeneficiarioStoreController extends Controller
{
    public function __invoke(BeneficiarioStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Cria o beneficiário
        $beneficiario = Beneficiario::create($data);

        // Cria membros da família se fornecidos
        if (!empty($data['membros'])) {
            foreach ($data['membros'] as $membroData) {
                $beneficiario->membros()->create($membroData);
            }
        }

        return redirect()
            ->route('ajuda-humanitaria.beneficiarios.show', $beneficiario->id)
            ->with('success', 'Beneficiário cadastrado com sucesso!');
    }
}
