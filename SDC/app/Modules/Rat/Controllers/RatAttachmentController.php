<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Services\RatAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe requisições HTTP de anexos e delega ao RatAttachmentService.
 * Depende do serviço, sem acesso direto a Storage ou Str.
 *
 * 2 métodos públicos: store · destroy
 */
class RatAttachmentController extends Controller
{
    public function __construct(
        private readonly RatAttachmentService $attachmentService,
    ) {}

    /** POST /rat/{id}/attachments */
    public function store(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'file' => [
                'required', 'file',
                'max:' . $this->attachmentService->getMaxKb(),
                'mimetypes:' . implode(',', $this->attachmentService->getAllowedMimes()),
            ],
        ]);

        $rat   = Rat::findOrFail($id);
        $anexo = $this->attachmentService->store($rat, $request->file('file'));

        return response()->json($anexo, 201);
    }

    /** DELETE /rat/{id}/attachments/{anexoId} */
    public function destroy(string $id, string $anexoId): JsonResponse
    {
        $rat = Rat::findOrFail($id);
        $this->attachmentService->destroy($rat, $anexoId);

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }
}

