<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeCcpae;
use App\Modules\Pae\Models\PaeDcoAvaliacao;
use App\Modules\Pae\Models\PaeDcoDocumento;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\AvaliarDcoRequest;
use App\Modules\Pae\Requests\RegistrarDcoRequest;
use App\Modules\Pae\Support\PaeArquivoPdf;
use App\Modules\Pae\Support\PaeDcoCiclo;
use App\Modules\Pae\Support\PaeIdempotencia;
use App\Modules\Pae\Support\PaeListagem;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PaeDcoService
{
    public function avaliar(PaeProtocolo $protocolo, array $dados, User $user): PaeDcoAvaliacao
    {
        $dados = Validator::make($dados, AvaliarDcoRequest::regras())->validate();
        $dados['fundamentacao'] = trim($dados['fundamentacao']);
        $dados['num_sei'] = trim($dados['num_sei']);
        if ($dados['fundamentacao'] === '' || $dados['num_sei'] === '') {
            throw ValidationException::withMessages(['avaliacao' => 'Informe fundamentação e número SEI.']);
        }

        return DB::transaction(function () use ($protocolo, $dados, $user): PaeDcoAvaliacao {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            $existente = $locked->avaliacoesDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                PaeIdempotencia::exigirMesmosDados($existente, $dados, ['resultado', 'fundamentacao', 'num_sei']);

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
        $dados = Validator::make(
            $dados + ['arquivo' => $arquivo],
            RegistrarDcoRequest::regras($dados['competencia'] ?? null),
            RegistrarDcoRequest::mensagens(),
        )->validate();
        $dados['num_sei'] = trim($dados['num_sei']);
        if ($dados['num_sei'] === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }

        return PaeArquivoPdf::executar(fn (PaeArquivoPdf $pdf): PaeDcoDocumento => DB::transaction(
            function () use ($protocolo, $dados, $arquivo, $user, $pdf): PaeDcoDocumento {
                $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
                $existente = $locked->documentosDco()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
                if ($existente !== null) {
                    PaeIdempotencia::exigirMesmosDados($existente, $dados, [
                        'competencia', 'resultado', 'dt_documento', 'dt_apresentacao', 'num_sei',
                    ]);

                    return $existente;
                }
                if ($locked->avaliacoesDco()->first()?->resultado !== 'aplicavel') {
                    throw ValidationException::withMessages(['avaliacao' => 'Registre antes uma avaliação de DCO aplicável.']);
                }

                $competencia = (int) $dados['competencia'];
                $versao = (int) $locked->documentosDco()->where('competencia', $competencia)->max('versao') + 1;
                $metadados = $pdf->guardar("dco/{$locked->id}/{$competencia}", $arquivo, 'Não foi possível guardar a DCO.');

                $documento = $locked->documentosDco()->create([
                    'competencia' => $competencia,
                    'versao' => $versao,
                    'resultado' => $dados['resultado'],
                    'dt_documento' => $dados['dt_documento'],
                    'dt_apresentacao' => $dados['dt_apresentacao'],
                    'num_sei' => $dados['num_sei'],
                    'observacao' => trim((string) ($dados['observacao'] ?? '')) ?: null,
                    ...$metadados,
                    'chave_idempotencia' => $dados['chave_idempotencia'],
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                TimelinePae::registrar($locked, 'dco_documento',
                    "DCO {$competencia}, versão {$versao}: {$documento->resultado}. SEI {$documento->num_sei}.", $user);

                return $documento;
            },
        ));
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
            || ! PaeArquivoPdf::existe($documento->arquivo_path)) {
            throw ValidationException::withMessages(['dco' => 'A emissão exige DCO positiva e disponível para a competência aplicável.']);
        }

        return ['avaliacao' => $avaliacao, 'documento' => $documento];
    }

    public function anotarListagem(LengthAwarePaginator $pagina, CarbonImmutable $hoje): LengthAwarePaginator
    {
        return PaeListagem::anotar(
            $pagina,
            fn (array $ids): array => [
                'avaliacoes' => PaeDcoAvaliacao::query()->whereIn('protocolo_id', $ids)
                    ->orderByDesc('id')->get()->unique('protocolo_id')->keyBy('protocolo_id'),
                'documentos' => PaeDcoDocumento::query()->whereIn('protocolo_id', $ids)
                    ->whereBetween('competencia', [PaeDcoCiclo::competenciaExigivel($hoje), $hoje->year])
                    ->whereDate('dt_apresentacao', '<=', $hoje->toDateString())
                    ->whereDate('dt_documento', '<=', $hoje->toDateString())
                    ->orderByDesc('competencia')->orderByDesc('versao')->get()->groupBy('protocolo_id'),
                'certificados' => PaeCcpae::query()->whereIn('protocolo_id', $ids)
                    ->distinct()->pluck('protocolo_id')->flip(),
            ],
            function (int $id, array $contexto) use ($hoje): array {
                $avaliacao = $contexto['avaliacoes']->get($id);
                $porProtocolo = $contexto['documentos']->get($id, collect());
                $prova = $porProtocolo->first();

                return [
                    'dco_situacao' => $this->situacaoAnual($avaliacao, $porProtocolo, $hoje, $contexto['certificados']->has($id)),
                    'dco_emissao_pronta' => $avaliacao !== null && (
                        $avaliacao->resultado === 'nao_aplicavel' || $prova?->resultado === 'positiva'
                    ),
                ];
            },
        );
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
