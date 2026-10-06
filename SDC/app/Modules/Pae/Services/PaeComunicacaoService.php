<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeComunicacao;
use App\Modules\Pae\Models\PaeNotificacao;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Support\TimelinePae;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class PaeComunicacaoService
{
    private const ORIGENS_FEAM = ['ccpae', 'reprovacao_sumaria', 'reprovacao_analise'];

    private const ORIGENS_COMPDEC = ['ccpae', 'reprovacao_sumaria', 'reprovacao_analise', 'notificacao'];

    public function abrir(PaeProtocolo $protocolo, string $origemTipo, int $origemId, ?string $motivos = null): void
    {
        if (! in_array($origemTipo, self::ORIGENS_COMPDEC, true) || $origemId < 1) {
            throw new InvalidArgumentException('Origem de comunicação PAE inválida.');
        }

        $base = [
            'protocolo_id' => $protocolo->id,
            'origem_tipo' => $origemTipo,
            'origem_id' => $origemId,
        ];
        $novos = ['motivos' => $motivos, 'status' => 'pendente'];

        if (in_array($origemTipo, self::ORIGENS_FEAM, true)) {
            PaeComunicacao::query()->firstOrCreate($base + [
                'destinatario_tipo' => 'feam', 'municipio_id' => null,
            ], $novos);
        }

        $zas = $protocolo->municipiosImpactados()->where('na_zas', true)
            ->whereNotNull('confirmado_em')->pluck('municipio_id');
        foreach ($zas as $municipioId) {
            PaeComunicacao::query()->firstOrCreate($base + [
                'destinatario_tipo' => 'compdec', 'municipio_id' => $municipioId,
            ], $novos);
        }
    }

    public function reconciliar(PaeProtocolo $protocolo): void
    {
        $origens = $protocolo->comunicacoes()
            ->get(['origem_tipo', 'origem_id', 'motivos'])
            ->map(fn (PaeComunicacao $comunicacao): array => [
                'tipo' => $comunicacao->origem_tipo,
                'id' => (int) $comunicacao->origem_id,
                'motivos' => $comunicacao->motivos,
            ]);

        $analise = $protocolo->analise()->first();
        if ($analise !== null) {
            foreach ($analise->notificacoes()->where('copia_compdec_zas_obrigatoria', true)->pluck('id') as $id) {
                $origens->push(['tipo' => 'notificacao', 'id' => (int) $id, 'motivos' => null]);
            }
        }

        foreach ($origens->unique(fn (array $origem): string => $origem['tipo'].':'.$origem['id']) as $origem) {
            $this->abrir($protocolo, $origem['tipo'], $origem['id'], $origem['motivos']);
        }
    }

    public function registrar(
        PaeComunicacao $comunicacao,
        CarbonImmutable $data,
        string $sei,
        UploadedFile $comprovante,
        User $user,
    ): PaeComunicacao {
        $sei = trim($sei);
        if ($sei === '' || $data->startOfDay()->isAfter(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['comunicacao' => 'Informe data de envio válida e número SEI.']);
        }

        $path = null;
        try {
            return DB::transaction(function () use ($comunicacao, $data, $sei, $comprovante, $user, &$path): PaeComunicacao {
                $locked = PaeComunicacao::query()->whereKey($comunicacao->id)->lockForUpdate()->firstOrFail();
                $protocolo = PaeProtocolo::query()->whereKey($locked->protocolo_id)->firstOrFail();
                if ($locked->status === 'registrada') {
                    return $locked;
                }

                $diretorio = "comunicacoes/{$protocolo->id}/{$locked->id}";
                $nome = (string) \Illuminate\Support\Str::uuid().'.'.($comprovante->guessExtension() ?: 'bin');
                $path = "{$diretorio}/{$nome}";
                if (Storage::disk('pae')->putFileAs($diretorio, $comprovante, $nome) !== $path) {
                    throw new RuntimeException('Não foi possível guardar o comprovante de envio.');
                }

                $locked->update([
                    'status' => 'registrada',
                    'dt_envio' => $data->toDateString(),
                    'num_sei' => $sei,
                    'comprovante_path' => $path,
                    'comprovante_nome_original' => $comprovante->getClientOriginalName(),
                    'comprovante_mime' => $comprovante->getMimeType(),
                    'comprovante_tamanho_bytes' => $comprovante->getSize(),
                    'registrado_por' => $user->id,
                    'registrado_em' => now(),
                ]);
                TimelinePae::registrar($protocolo, 'comunicacao_registrada',
                    "Comunicação oficial {$locked->destinatario_tipo} registrada. SEI {$sei}.", $user);

                return $locked;
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::disk('pae')->delete($path);
            }
            throw $e;
        }
    }

    public function resumo(PaeProtocolo $protocolo): array
    {
        $comunicacoes = $protocolo->comunicacoes()->with('municipio:id,nome,uf', 'registrador:id,name')->get();
        $notificacoes = PaeNotificacao::query()
            ->whereIn('id', $comunicacoes->where('origem_tipo', 'notificacao')->pluck('origem_id')->unique())
            ->get(['id', 'num_sei', 'dt_notificacao'])->keyBy('id');

        return [
            'comunicacoes' => $comunicacoes->map(fn (PaeComunicacao $comunicacao): array => [
                'id' => $comunicacao->id,
                'origem_tipo' => $comunicacao->origem_tipo,
                'origem_id' => $comunicacao->origem_id,
                'origem_referencia' => $this->referenciaOrigem($comunicacao, $notificacoes),
                'destinatario_tipo' => $comunicacao->destinatario_tipo,
                'municipio_id' => $comunicacao->municipio_id,
                'municipio' => $comunicacao->municipio?->nome,
                'uf' => $comunicacao->municipio?->uf,
                'motivos' => $comunicacao->motivos,
                'status' => $comunicacao->status,
                'dt_envio' => $comunicacao->dt_envio?->toDateString(),
                'num_sei' => $comunicacao->num_sei,
                'comprovante_nome_original' => $comunicacao->comprovante_nome_original,
                'registrado_por' => $comunicacao->registrador?->name,
            ])->all(),
            'pendentes' => $comunicacoes->where('status', 'pendente')->count(),
            'registradas' => $comunicacoes->where('status', 'registrada')->count(),
            'enderecamento_pendente' => $this->enderecamentoPendente($protocolo, $comunicacoes),
        ];
    }

    private function referenciaOrigem(PaeComunicacao $comunicacao, Collection $notificacoes): ?string
    {
        if ($comunicacao->origem_tipo !== 'notificacao') {
            return null;
        }

        $notificacao = $notificacoes->get($comunicacao->origem_id);
        if ($notificacao === null) {
            return "Notificação #{$comunicacao->origem_id}";
        }

        return "Notificação #{$notificacao->id} · SEI {$notificacao->num_sei} · {$notificacao->dt_notificacao->format('d/m/Y')}";
    }

    private function enderecamentoPendente(PaeProtocolo $protocolo, Collection $comunicacoes): bool
    {
        $origens = $comunicacoes->map(fn (PaeComunicacao $item): array => [
            'tipo' => $item->origem_tipo, 'id' => (int) $item->origem_id,
        ]);
        $analise = $protocolo->analise()->first();
        if ($analise !== null) {
            foreach ($analise->notificacoes()->where('copia_compdec_zas_obrigatoria', true)->pluck('id') as $notificacaoId) {
                $origens->push(['tipo' => 'notificacao', 'id' => (int) $notificacaoId]);
            }
        }
        $origens = $origens->unique(fn (array $origem): string => $origem['tipo'].':'.$origem['id']);
        if ($origens->isEmpty()) {
            return false;
        }

        $zasIds = $protocolo->municipiosImpactados()->where('na_zas', true)
            ->whereNotNull('confirmado_em')->pluck('municipio_id');
        if ($zasIds->isEmpty()) {
            return true;
        }

        foreach ($origens as $origem) {
            foreach ($zasIds as $municipioId) {
                if (! $comunicacoes->contains(fn (PaeComunicacao $item): bool =>
                    $item->origem_tipo === $origem['tipo']
                    && (int) $item->origem_id === $origem['id']
                    && $item->destinatario_tipo === 'compdec'
                    && (int) $item->municipio_id === (int) $municipioId)) {
                    return true;
                }
            }
        }

        return false;
    }
}
