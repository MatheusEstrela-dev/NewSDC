<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Cidadao;
use App\Modules\Treinamento\Resources\CertificadoResource;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CertificadoController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Cidadao $cidadao */
        $cidadao = Auth::guard('cidadao')->user();

        $inscricaoIds = $cidadao->inscricoes()->pluck('id');

        $certificados = Certificado::query()
            ->whereIn('inscricao_id', $inscricaoIds)
            ->with('treinamento')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Treinamento/Portal/Certificados', [
            'certificados' => CertificadoResource::collection($certificados),
        ]);
    }
}
