<?php

declare(strict_types=1);

namespace App\Modules\Pae\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pae\Models\PaeForm;
use App\Modules\Pae\Models\PaeFormAnexo;
use App\Modules\Pae\Services\PaeLeituraPdfService;
use Illuminate\Http\JsonResponse;

final class PaeLeituraPdfController extends Controller
{
    public function __construct(private readonly PaeLeituraPdfService $leitura)
    {
    }

    /** Sugestoes lidas de um anexo PDF do formulario; somente leitura. */
    public function show(PaeForm $paeForm, PaeFormAnexo $paeFormAnexo): JsonResponse
    {
        abort_unless($paeFormAnexo->pae_form_id === $paeForm->id, 404);

        return response()->json($this->leitura->lerAnexo($paeFormAnexo));
    }
}
