<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tdap\Models\Cronograma;
use App\Modules\Tdap\Models\CronogramaComprovante;
use App\Modules\Tdap\Services\CronogramaComprovanteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CronogramaComprovanteController extends Controller
{
    public function __construct(
        private readonly CronogramaComprovanteService $service,
    ) {}

    public function store(Request $request, Cronograma $cronograma): RedirectResponse
    {
        $request->validate([
            'comprovantes'   => ['required', 'array', 'min:1'],
            'comprovantes.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,odt,xls,xlsx'],
            'descricao'      => ['nullable', 'string', 'max:255'],
        ], [], [
            'comprovantes.*' => 'arquivo',
        ]);

        foreach ($request->file('comprovantes') as $file) {
            $this->service->store($cronograma, $file, $request->input('descricao'));
        }

        return back()->with('success', 'Comprovante(s) anexado(s) com sucesso.');
    }

    public function download(Cronograma $cronograma, CronogramaComprovante $comprovante): StreamedResponse
    {
        abort_unless($comprovante->cronograma_id === $cronograma->id, 404);

        return \Illuminate\Support\Facades\Storage::disk($comprovante->disk)
            ->download($comprovante->path, $comprovante->nome_original);
    }

    public function destroy(Cronograma $cronograma, CronogramaComprovante $comprovante): RedirectResponse
    {
        abort_unless($comprovante->cronograma_id === $cronograma->id, 404);

        $this->service->destroy($comprovante);

        return back()->with('success', 'Comprovante removido.');
    }
}
