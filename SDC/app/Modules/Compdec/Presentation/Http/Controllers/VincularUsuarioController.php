<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Application\UseCases\VincularUsuarioUseCase;
use App\Modules\Compdec\Presentation\Http\Requests\VincularUsuarioRequest;
use Illuminate\Http\RedirectResponse;

class VincularUsuarioController extends Controller
{
    public function __construct(
        private readonly VincularUsuarioUseCase $vincularUsuarioUseCase
    ) {
    }

    public function __invoke(int $orgaoId, VincularUsuarioRequest $request): RedirectResponse
    {
        try {
            $this->vincularUsuarioUseCase->execute(
                $orgaoId,
                $request->input('user_id'),
                $request->input('funcao', 'agente'),
                $request->boolean('is_principal', false)
            );

            return redirect()
                ->back()
                ->with('success', 'Usuário vinculado com sucesso!');
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
