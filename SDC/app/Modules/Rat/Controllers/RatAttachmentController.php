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

        $rat    = Rat::findOrFail($id);
        $imagem = $this->attachmentService->store($rat, $request->file('file'));

        return response()->json($imagem, 201);
    }

    /** DELETE /rat/{id}/attachments/{imagemId} */
    public function destroy(string $id, string $imagemId): JsonResponse
    {
        $rat = Rat::findOrFail($id);
        $this->attachmentService->destroy($rat, $imagemId);

        return response()->json(['message' => 'Imagem removida com sucesso.']);
    }
}

