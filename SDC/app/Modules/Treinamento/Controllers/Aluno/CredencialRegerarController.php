<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\CredencialQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * O proprio servidor troca o token do seu QR (o antigo deixa de valer).
 */
class CredencialRegerarController extends Controller
{
    public function __construct(private readonly CredencialQrService $credenciais) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->credenciais->regerar($request->user());

        return redirect()->route('treinamentos.aluno.index')->with('success', 'Credencial regerada. O QR anterior nao vale mais.');
    }
}
