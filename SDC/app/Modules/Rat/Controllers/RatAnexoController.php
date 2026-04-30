<?php

declare(strict_types=1);

namespace App\Modules\Rat\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Enums\CategoriaAnexo;
use App\Modules\Rat\Http\Requests\StoreRatAnexoRequest;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\RatAnexo;
use App\Modules\Rat\Services\RatAnexoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CRUD de anexos vinculados a um RAT.
 *
 * Suporta ambas as fontes de dados:
 *  - Tabela rats (UUID) - estrutura legada
 *  - Tabela rat_ocorrencias (int) - nova estrutura
 *
 * Rotas:
 *  POST   /rat/{id}/anexos          -> store
 *  GET    /rat/{id}/anexos          -> index
 *  GET    /rat/{id}/anexos/{anexo}  -> show (download)
 *  DELETE /rat/{id}/anexos/{anexo}  -> destroy
 */
class RatAnexoController extends Controller
{
    public function __construct(
        private readonly RatAnexoService $service,
    ) {}

    /** GET - Lista anexos do RAT, opcionalmente filtrados por categoria. */
    public function index(Request $request, string $id): JsonResponse
    {
        $this->findRatOrFail($id);

        $categoria = $request->query('categoria')
            ? CategoriaAnexo::tryFrom($request->query('categoria'))
            : null;

        $query = RatAnexo::where('rat_id', $id);

        if ($categoria) {
            $query->where('categoria', $categoria->value);
        }

        $anexos = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data'  => $anexos->map(fn (RatAnexo $a) => $this->formatAnexo($a)),
            'total' => $anexos->count(),
        ]);
    }

    /** POST - Upload de novo anexo. */
    public function store(StoreRatAnexoRequest $request, string $id): JsonResponse
    {
        $this->findRatOrFail($id);

        $categoriaValue = $request->validated('categoria');
        $categoria = $categoriaValue
            ? CategoriaAnexo::from($categoriaValue)
            : $this->detectCategoria($request->file('file'));

        $anexo = $this->service->storeForRatId(
            $id,
            $request->file('file'),
            $categoria,
            $request->validated('descricao'),
        );

        return response()->json($this->formatAnexo($anexo), 201);
    }

    private function detectCategoria(\Illuminate\Http\UploadedFile $file): CategoriaAnexo
    {
        $mime = $file->getMimeType() ?? '';

        if (str_starts_with($mime, 'image/')) {
            return CategoriaAnexo::IMAGEM;
        }

        return CategoriaAnexo::DOCUMENTO;
    }

    /** GET - Download do arquivo. */
    public function show(string $id, int $anexoId): StreamedResponse
    {
        $this->findRatOrFail($id);
        $anexo = RatAnexo::where('rat_id', $id)->findOrFail($anexoId);

        return $this->service->getDownloadResponse($anexo);
    }

    /** DELETE - Remove anexo do disco e da tabela. */
    public function destroy(string $id, int $anexoId): JsonResponse
    {
        $this->findRatOrFail($id);
        $anexo = RatAnexo::where('rat_id', $id)->findOrFail($anexoId);

        $this->service->destroy($anexo);

        return response()->json(['message' => 'Anexo removido com sucesso.']);
    }

    /**
     * Busca o RAT em ambas as tabelas (rats UUID ou rat_ocorrencias int).
     */
    private function findRatOrFail(string $id): void
    {
        $exists = Rat::where('id', $id)->exists()
               || RatOcorrencia::where('id', $id)->exists();

        abort_unless($exists, 404, 'RAT nao encontrado.');
    }

    private function formatAnexo(RatAnexo $a): array
    {
        return [
            'id'               => $a->id,
            'categoria'        => $a->categoria->value,
            'categoria_label'  => $a->categoria->getLabel(),
            'nome_original'    => $a->nome_original,
            'nome'             => $a->nome_original,
            'mime_type'        => $a->mime_type,
            'tipo'             => $a->mime_type,
            'tamanho_bytes'    => $a->tamanho_bytes,
            'tamanho'          => $a->tamanho_bytes,
            'tamanho_formatado'=> $a->tamanho_formatado,
            'url'              => $a->url,
            'path'             => $a->path,
            'descricao'        => $a->descricao,
            'uploaded_by'      => $a->uploaded_by,
            'data_upload'      => $a->created_at?->toIso8601String(),
            'created_at'       => $a->created_at?->toIso8601String(),
        ];
    }
}
