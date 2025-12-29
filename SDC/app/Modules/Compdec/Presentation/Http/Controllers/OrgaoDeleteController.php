<?php

declare(strict_types=1);

namespace App\Modules\Compdec\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compdec\Domain\Repositories\OrgaoRepositoryInterface;
use Illuminate\Http\RedirectResponse;

class OrgaoDeleteController extends Controller
{
    public function __construct(
        private readonly OrgaoRepositoryInterface $repository
    ) {
    }

    public function __invoke(int $id): RedirectResponse
    {
        try {
            $this->repository->delete($id);

            return redirect()
                ->route('compdec.index')
                ->with('success', 'Órgão deletado com sucesso!');
        } catch (\DomainException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
