<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Support\CodigoVerificacao;
use App\Modules\Treinamento\Support\ValidacaoDeCertificado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Validacao publica de certificado (sem login). Codigo desconhecido, mal
 * formado ou de certificado nao emitido responde igual: 200, sem resultado.
 */
class ValidarCertificadoController extends Controller
{
    public function __construct(private readonly ValidacaoDeCertificado $validacao) {}

    public function mostrar(?string $codigo = null): Response
    {
        $normalizado = $codigo === null ? null : CodigoVerificacao::normalizar($codigo);

        return Inertia::render('Treinamento/Publico/ValidarCertificado', [
            'codigo' => $codigo === null ? null : ($normalizado ?? mb_substr($codigo, 0, 40)),
            'consultado' => $codigo !== null,
            'resultado' => $normalizado === null ? null : $this->validacao->buscar($normalizado),
        ]);
    }

    public function buscar(Request $request)
    {
        $dados = $request->validate(['codigo' => ['required', 'string', 'max:40']]);

        $codigo = CodigoVerificacao::normalizar($dados['codigo'])
            ?? strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', $dados['codigo']));

        return redirect()->route('certificados.validar', $codigo);
    }
}
