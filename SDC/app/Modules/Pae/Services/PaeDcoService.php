<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeDcoAvaliacao;
use App\Modules\Pae\Models\PaeDcoDocumento;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\PaeDcoCiclo;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class PaeDcoService
{
    public function avaliar(PaeProtocolo $protocolo, array $dados, User $user): PaeDcoAvaliacao
    {
        $dados = Validator::make($dados, [
            'resultado' => ['required', Rule::in(['aplicavel', 'nao_aplicavel'])],
            'fundamentacao' => ['required', 'string', 'max:5000'],
            'num_sei' => ['required', 'string', 'max:100'],
            'chave_idempotencia' => ['required', 'uuid'],
        ])->validate();
        $dados['fundamentacao'] = trim($dados['fundamentacao']);
        $dados['num_sei'] = trim($dados['num_sei']);
        if ($dados['fundamentacao'] === '' || $dados['num_sei'] === '') {
            throw ValidationException::withMessages(['avaliacao' => 'Informe fundamentação e número SEI.']);
        }

        return DB::transaction(function () use ($protocolo, $dados, $user): PaeDcoAvaliacao {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            $existente = $locked->avaliacoesDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                return $existente;
            }

            $avaliacao = $locked->avaliacoesDco()->create([
                'resultado' => $dados['resultado'],
                'fundamentacao' => $dados['fundamentacao'],
                'num_sei' => $dados['num_sei'],
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'decidido_por' => $user->id,
                'decidido_em' => now(),
            ]);
            TimelinePae::registrar($locked, 'dco_avaliacao',
                "Aplicabilidade da DCO: {$avaliacao->resultado}. SEI {$avaliacao->num_sei}.", $user);

            return $avaliacao;
        });
    }

    public function registrarDocumento(PaeProtocolo $protocolo, array $dados, UploadedFile $arquivo, User $user): PaeDcoDocumento
    {
        $dados = Validator::make($dados + ['arquivo' => $arquivo], [
            'competencia' => ['required', 'integer', 'between:2022,'.CarbonImmutable::today()->year],
            'resultado' => ['required', Rule::in(['positiva', 'nao_conforme'])],
            'dt_documento' => ['required', 'date', 'before_or_equal:today'],
            'dt_apresentacao' => ['required', 'date', 'before_or_equal:today'],
            'num_sei' => ['required', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:5000'],
            'chave_idempotencia' => ['required', 'uuid'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ])->validate();
        $dados['num_sei'] = trim($dados['num_sei']);
        if ($dados['num_sei'] === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }

        $path = null;
        try {
            return DB::transaction(function () use ($protocolo, $dados, $arquivo, $user, &$path): PaeDcoDocumento {
                $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
                $existente = $locked->documentosDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
                if ($existente !== null) {
                    return $existente;
                }
                if ($locked->avaliacoesDco()->first()?->resultado !== 'aplicavel') {
                    throw ValidationException::withMessages(['avaliacao' => 'Registre antes uma avaliação de DCO aplicável.']);
                }

                $competencia = (int) $dados['competencia'];
                $versao = (int) $locked->documentosDco()->where('competencia', $competencia)->max('versao') + 1;
                $diretorio = "dco/{$locked->id}/{$competencia}";
                $nome = (string) Str::uuid().'.pdf';
                $path = "{$diretorio}/{$nome}";
                if (Storage::disk('pae')->putFileAs($diretorio, $arquivo, $nome) !== $path) {
                    throw new RuntimeException('Não foi possível guardar a DCO.');
                }

                $documento = $locked->documentosDco()->create([
                    'competencia' => $competencia,
                    'versao' => $versao,
                    'resultado' => $dados['resultado'],
                    'dt_documento' => $dados['dt_documento'],
                    'dt_apresentacao' => $dados['dt_apresentacao'],
                    'num_sei' => $dados['num_sei'],
                    'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                    'arquivo_path' => $path,
                    'arquivo_nome_original' => $arquivo->getClientOriginalName(),
                    'arquivo_mime' => $arquivo->getMimeType(),
                    'arquivo_tamanho_bytes' => $arquivo->getSize(),
                    'chave_idempotencia' => $dados['chave_idempotencia'],
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                TimelinePae::registrar($locked, 'dco_documento',
                    "DCO {$competencia}, versão {$versao}: {$documento->resultado}. SEI {$documento->num_sei}.", $user);

                return $documento;
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::disk('pae')->delete($path);
            }
            throw $e;
        }
    }

    public function resumo(PaeProtocolo $protocolo, CarbonImmutable $hoje): array
    {
        $avaliacoes = $protocolo->avaliacoesDco()->with('decisor:id,name')->get();
        $documentos = $protocolo->documentosDco()
            ->with('registrador:id,name')->orderByDesc('competencia')->orderByDesc('versao')->get();
        $vigente = $avaliacoes->first();
        $competencia = $hoje->year;
        $documentoAtual = $this->ultimaVersao($documentos, $competencia, $hoje);
        $vencimento = PaeDcoCiclo::vencimento($competencia);

        $ccpae = $protocolo->ccpaeVigente()->first();
        $situacao = $this->situacaoAnual($vigente, $documentos, $hoje, $ccpae !== null);

        return [
            'situacao' => $situacao,
            'competencia_exigivel' => PaeDcoCiclo::competenciaExigivel($hoje),
            'competencia_anual' => $competencia,
            'vencimento' => $vencimento->toDateString(),
            'proximo_vencimento' => ($hoje->startOfDay()->greaterThan($vencimento)
                ? PaeDcoCiclo::vencimento($competencia + 1) : $vencimento)->toDateString(),
            'entrega_tardia' => $documentoAtual !== null
                && $documentoAtual->dt_apresentacao->toDateString() > $vencimento->toDateString(),
            'alerta_legado' => $vigente === null && $ccpae !== null,
            'avaliacao' => $vigente,
            'avaliacoes' => $avaliacoes,
            'documentos' => $documentos->map(function (PaeDcoDocumento $documento): array {
                return [
                    'id' => $documento->id,
                    'competencia' => $documento->competencia,
                    'versao' => $documento->versao,
                    'resultado' => $documento->resultado,
                    'dt_documento' => $documento->dt_documento->toDateString(),
                    'dt_apresentacao' => $documento->dt_apresentacao->toDateString(),
                    'num_sei' => $documento->num_sei,
                    'observacao' => $documento->observacao,
                    'arquivo_nome_original' => $documento->arquivo_nome_original,
                    'registrador' => $documento->registrador?->name,
                    'registrado_em' => $documento->registrado_em?->toIso8601String(),
                    'entrega_tardia' => $documento->dt_apresentacao->toDateString()
                        > PaeDcoCiclo::vencimento($documento->competencia)->toDateString(),
                ];
            })->all(),
            'ccpae' => $ccpae,
        ];
    }

    /**
     * @return array{avaliacao: PaeDcoAvaliacao, documento: ?PaeDcoDocumento}
     */
    public function evidenciaParaEmissao(PaeProtocolo $protocoloBloqueado, CarbonImmutable $dataEmissao): array
    {
        $avaliacao = $protocoloBloqueado->avaliacoesDco()->first();
        if ($avaliacao === null) {
            throw ValidationException::withMessages(['dco' => 'Avalie a aplicabilidade da DCO antes de emitir o CCPAE.']);
        }
        if ($avaliacao->resultado === 'nao_aplicavel') {
            return ['avaliacao' => $avaliacao, 'documento' => null];
        }

        $documento = $protocoloBloqueado->documentosDco()
            ->whereBetween('competencia', [PaeDcoCiclo::competenciaExigivel($dataEmissao), $dataEmissao->year])
            ->whereDate('dt_documento', '<=', $dataEmissao->toDateString())
            ->whereDate('dt_apresentacao', '<=', $dataEmissao->toDateString())
            ->orderByDesc('competencia')->orderByDesc('versao')->first();

        if ($documento === null || $documento->resultado !== 'positiva'
            || ! Storage::disk('pae')->exists($documento->arquivo_path)) {
            throw ValidationException::withMessages(['dco' => 'A emissão exige DCO positiva e disponível para a competência aplicável.']);
        }

        return ['avaliacao' => $avaliacao, 'documento' => $documento];
    }

    public function anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator
    {
        $ids = $pagina->getCollection()->map(fn ($item): int => (int) data_get($item, 'id'))->all();
        if ($ids === []) {
            return $pagina;
        }

        $avaliacoes = PaeDcoAvaliacao::query()->whereIn('protocolo_id', $ids)
            ->orderByDesc('id')->get()->unique('protocolo_id')->keyBy('protocolo_id');
        $documentos = PaeDcoDocumento::query()->whereIn('protocolo_id', $ids)
            ->whereBetween('competencia', [PaeDcoCiclo::competenciaExigivel($hoje), $hoje->year])
            ->whereDate('dt_apresentacao', '<=', $hoje->toDateString())
            ->whereDate('dt_documento', '<=', $hoje->toDateString())
            ->orderByDesc('competencia')->orderByDesc('versao')->get()->groupBy('protocolo_id');
        $certificados = PaeCcpae::query()->whereIn('protocolo_id', $ids)
            ->distinct()->pluck('protocolo_id')->flip();

        $pagina->setCollection($pagina->getCollection()->map(function ($item) use ($avaliacoes, $documentos, $certificados, $hoje): array {
            $id = (int) data_get($item, 'id');
            $avaliacao = $avaliacoes->get($id);
            $porProtocolo = $documentos->get($id, collect());
            $prova = $porProtocolo->first();

            return [
                ...(is_array($item) ? $item : $item->toArray()),
                'dco_situacao' => $this->situacaoAnual($avaliacao, $porProtocolo, $hoje, $certificados->has($id)),
                'dco_emissao_pronta' => $avaliacao !== null && (
                    $avaliacao->resultado === 'nao_aplicavel' || $prova?->resultado === 'positiva'
                ),
            ];
        }));

        return $pagina;
    }

    /**
     * @param  Collection<int, PaeDcoDocumento>  $documentos  ordenados por competencia e versao decrescentes
     */
    private function situacaoAnual(
        ?PaeDcoAvaliacao $avaliacao,
        Collection $documentos,
        CarbonImmutable $hoje,
        bool $temCcpae,
    ): string {
        if ($avaliacao === null) {
            return 'nao_avaliada';
        }
        if ($avaliacao->resultado === 'nao_aplicavel') {
            return 'nao_aplicavel';
        }

        $atual = $this->ultimaVersao($documentos, $hoje->year, $hoje);
        if ($atual !== null) {
            return $atual->resultado === 'positiva' ? 'comprovada' : 'nao_conforme';
        }

        $exigivel = PaeDcoCiclo::competenciaExigivel($hoje);
        $anterior = $exigivel === $hoje->year ? null : $this->ultimaVersao($documentos, $exigivel, $hoje);

        // Sem certificado nao ha ciclo anual em atraso: a falta da DCO so impede a emissao.
        return match ($anterior?->resultado) {
            'positiva' => 'aguardando_prazo',
            'nao_conforme' => 'nao_conforme',
            default => $temCcpae ? 'atrasada' : 'pendente_emissao',
        };
    }

    /**
     * @param  Collection<int, PaeDcoDocumento>  $documentos  ordenados por competencia e versao decrescentes
     */
    private function ultimaVersao(Collection $documentos, int $competencia, CarbonImmutable $hoje): ?PaeDcoDocumento
    {
        return $documentos->first(fn (PaeDcoDocumento $documento): bool => $documento->competencia === $competencia
            && $documento->dt_apresentacao->toDateString() <= $hoje->toDateString());
    }
}
