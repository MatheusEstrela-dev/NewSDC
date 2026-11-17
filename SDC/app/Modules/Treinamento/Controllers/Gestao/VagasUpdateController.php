<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Gestao;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Requests\Gestao\VagasUpdateRequest;
use App\Modules\Treinamento\Services\Gestao\GestaoCursoComandos;

class VagasUpdateController extends Controller
{
    public function __construct(private readonly GestaoCursoComandos $comandos)
    {
    }

    public function __invoke(VagasUpdateRequest $request, Treinamento $treinamento)
    {
        $vagas = $request->validated('numero_vagas');

        try {
            $this->comandos->definirVagasDoCurso($treinamento, $vagas === null ? null : (int) $vagas, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['numero_vagas' => $e->getMessage()]);
        }

        return back()->with('success', 'Vagas do curso atualizadas.');
    }
}
