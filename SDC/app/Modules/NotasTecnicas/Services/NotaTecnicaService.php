<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Services;

use App\Models\User;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaDTO;
use App\Modules\NotasTecnicas\DTOs\NotaTecnicaVersaoDTO;
use App\Modules\NotasTecnicas\Enums\NotaTecnicaStatus;
use App\Modules\NotasTecnicas\Jobs\ExtrairTextoNotaTecnicaJob;
use App\Modules\NotasTecnicas\Models\NotaTecnica;
use App\Modules\NotasTecnicas\Models\NotaTecnicaVersao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Escrita do modulo: cadastrar, editar, versionar, publicar, revogar e excluir.
 *
 * Nota nasce rascunho. Versao enviada para nota ja publicada entra em vigor na
 * hora; em rascunho, so ganha data de publicacao quando a nota for publicada.
 */
final class NotaTecnicaService
{
    /** Chave do advisory lock que serializa a geracao de codigo NT-AAAA-NNN. */
    private const LOCK_CODIGO = 'notas_tecnicas_codigo';

    public function criar(NotaTecnicaDTO $dados, NotaTecnicaVersaoDTO $versao, User $autor): NotaTecnica
    {
        return $this->comArquivo($versao, function (string $path) use ($dados, $versao, $autor): NotaTecnica {
            $nota = NotaTecnica::create([
                ...$dados->toArray(),
                'codigo' => $this->proximoCodigo(),
                'status' => NotaTecnicaStatus::Rascunho,
                'elaborado_por' => $autor->id,
            ]);

            $this->gravarVersao($nota, $versao, $path, null);

            return $nota;
        });
    }

    public function atualizar(NotaTecnica $nota, NotaTecnicaDTO $dados): NotaTecnica
    {
        $nota->update($dados->toArray());

        return $nota;
    }

    public function novaVersao(NotaTecnica $nota, NotaTecnicaVersaoDTO $versao, User $autor): NotaTecnicaVersao
    {
        return $this->comArquivo($versao, fn (string $path): NotaTecnicaVersao => $this->gravarVersao(
            $nota,
            $versao,
            $path,
            $nota->status === NotaTecnicaStatus::Publicada ? $autor : null,
        ));
    }

    /**
     * Publica (ou republica uma revogada). Exige PDF. Quem publica fica como
     * aprovador; a versao corrente ganha data de publicacao se ainda nao tinha.
     */
    public function publicar(NotaTecnica $nota, User $aprovador): NotaTecnica
    {
        $atual = $nota->versaoAtual()->first();

        if ($atual === null) {
            throw ValidationException::withMessages(['nota' => 'Envie um PDF antes de publicar a nota.']);
        }

        DB::transaction(function () use ($nota, $atual, $aprovador): void {
            $nota->update([
                'status' => NotaTecnicaStatus::Publicada,
                'aprovado_por' => $aprovador->id,
                'vigencia_inicio' => $nota->vigencia_inicio ?? now()->toDateString(),
            ]);

            if ($atual->publicada_em === null) {
                $atual->update(['publicada_em' => now(), 'publicada_por' => $aprovador->id]);
            }
        });

        return $nota;
    }

    public function revogar(NotaTecnica $nota): NotaTecnica
    {
        if ($nota->status !== NotaTecnicaStatus::Publicada) {
            throw ValidationException::withMessages(['nota' => 'Só uma nota publicada pode ser revogada.']);
        }

        $nota->update(['status' => NotaTecnicaStatus::Revogada]);

        return $nota;
    }

    /** Soft delete: os PDFs ficam no disk, a nota some do workspace. */
    public function excluir(NotaTecnica $nota): void
    {
        $nota->delete();
    }

    /** Sugestao para o campo "versao" do formulario: proxima versao maior. */
    public function sugerirProximaVersao(NotaTecnica $nota): string
    {
        $maior = $nota->versoes()->pluck('numero')
            ->map(fn (string $numero): int => (int) explode('.', $numero)[0])
            ->max();

        return $maior === null ? '1.0' : ($maior + 1) . '.0';
    }

    /**
     * Grava o PDF no disk e roda a escrita no banco numa transacao. Se o banco
     * falhar, o arquivo e apagado: nada de PDF orfao no storage.
     *
     * @template T
     *
     * @param  callable(string): T  $escrita
     * @return T
     */
    private function comArquivo(NotaTecnicaVersaoDTO $versao, callable $escrita): mixed
    {
        $path = $versao->arquivo->storeAs('notas', Str::uuid()->toString() . '.pdf', NotaTecnicaVersao::DISK);

        if ($path === false) {
            throw ValidationException::withMessages(['arquivo' => 'Não foi possível gravar o PDF. Tente de novo.']);
        }

        try {
            return DB::transaction(fn () => $escrita($path));
        } catch (Throwable $erro) {
            Storage::disk(NotaTecnicaVersao::DISK)->delete($path);

            throw $erro;
        }
    }

    private function gravarVersao(NotaTecnica $nota, NotaTecnicaVersaoDTO $versao, string $path, ?User $publicadaPor): NotaTecnicaVersao
    {
        $registro = $nota->versoes()->create([
            'numero' => $versao->numero,
            'arquivo_path' => $path,
            'arquivo_nome' => $versao->arquivo->getClientOriginalName(),
            'arquivo_tamanho' => (int) $versao->arquivo->getSize(),
            'changelog' => $versao->changelog,
            'publicada_em' => $publicadaPor === null ? null : now(),
            'publicada_por' => $publicadaPor?->id,
        ]);

        // afterCommit: o worker nao pode buscar uma versao que a transacao ainda
        // nao gravou (ou que um rollback desfez).
        ExtrairTextoNotaTecnicaJob::dispatch($registro->id)->afterCommit();

        return $registro;
    }

    /**
     * NT-AAAA-NNN sequencial por ano. O advisory lock vale ate o fim da
     * transacao e impede dois cadastros simultaneos de lerem o mesmo maximo.
     * Conta as excluidas tambem: codigo nao e reaproveitado.
     */
    private function proximoCodigo(): string
    {
        DB::select('select pg_advisory_xact_lock(hashtext(?))', [self::LOCK_CODIGO]);

        $prefixo = 'NT-' . now()->year . '-';
        // Maximo numerico, nao lexicografico: como texto "999" > "1000" e o codigo
        // se repetiria na milesima nota do ano. O filtro de digitos protege o cast.
        $ultimo = NotaTecnica::withTrashed()
            ->where('codigo', 'like', $prefixo . '%')
            ->whereRaw("substring(codigo from cast(? as integer)) ~ '^[0-9]+$'", [strlen($prefixo) + 1])
            ->selectRaw('max(cast(substring(codigo from cast(? as integer)) as integer)) as ultimo', [strlen($prefixo) + 1])
            ->value('ultimo');

        $sequencia = ((int) $ultimo) + 1;

        return $prefixo . str_pad((string) $sequencia, 3, '0', STR_PAD_LEFT);
    }
}
