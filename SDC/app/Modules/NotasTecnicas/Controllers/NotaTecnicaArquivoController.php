<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega o PDF de uma versao. Inline por padrao, para o leitor nativo do
 * navegador abrir dentro do iframe do workspace; `?download=1` vira anexo.
 *
 * O disk e privado: o binario so sai por aqui, atras do auth e da policy da
 * nota, qualquer que seja o backend (Azure Blob, bind mount ou local).
 */
final class NotaTecnicaArquivoController extends Controller
{
    public function show(Request $request, NotaTecnicaVersao $versao): StreamedResponse
    {
        $this->authorize('view', $versao->nota);

        $disk = Storage::disk(NotaTecnicaVersao::DISK);
        abort_unless($disk->exists($versao->arquivo_path), 404);

        return $disk->response(
            $versao->arquivo_path,
            $versao->arquivo_nome,
            ['Content-Type' => 'application/pdf'],
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }
}
