<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\CertificadoInstrutor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\EmitirCertificadoInstrutorRequest;
use App\Modules\Treinamento\Services\CertificadoInstrutorService;
use DomainException;
use Illuminate\Http\RedirectResponse;

/** Administrador emite o certificado de instrutor de um curso encerrado. */
class CertificadoInstrutorStoreController extends Controller
{
    public function __construct(private readonly CertificadoInstrutorService $certificados) {}

    public function __invoke(EmitirCertificadoInstrutorRequest $request, Treinamento $treinamento): RedirectResponse
    {
        $autor = $request->user();
        $userId = $request->validated('user_id');

        try {
            $certificado = $userId !== null
                ? $this->certificados->emitirParaProfessor($treinamento, User::query()->findOrFail($userId), $autor)
                : $this->certificados->emitirPorNome($treinamento, (string) $request->validated('nome'), $autor);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Certificado de instrutor de {$certificado->nome} emitido.");
    }
}
