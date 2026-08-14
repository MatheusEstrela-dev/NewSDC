<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Notificacao de fiscalizacao, polimorfica.
 *
 * O legado disparava Mail::send([], [], ...) com HTML montado por
 * concatenacao para um Gmail pessoal hardcoded
 * (NotificacaoFiscalizacaoController.php:56). Aqui a notificacao e um
 * registro do dominio, e o aviso ao interessado e responsabilidade da trilha
 * do modulo Notificacoes, que ja resolve destinatario por perfil.
 */
class NotificacaoFiscalizacaoService
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros = [], int $porPagina = 25): LengthAwarePaginator
    {
        return CisternaNotificacao::query()
            ->with(['notificavel', 'criador:id,name', 'media'])
            ->when(($filtros['apenas_pendentes'] ?? false) === true, fn (Builder $q) => $q->pendentes())
            ->when($filtros['notificavel_type'] ?? null, function (Builder $q, $alias) use ($filtros): void {
                $classe = NotificacaoDTO::TIPOS_PERMITIDOS[(string) $alias] ?? null;

                if ($classe === null) {
                    return;
                }

                $q->where('notificavel_type', $classe);

                if (isset($filtros['notificavel_id'])) {
                    $q->where('notificavel_id', (int) $filtros['notificavel_id']);
                }
            })
            ->orderByDesc('created_at')
            ->paginate($porPagina)
            ->withQueryString();
    }

    public function emitir(NotificacaoDTO $dto, ?UploadedFile $arquivo = null): CisternaNotificacao
    {
        $this->garantirQueONotificavelExiste($dto);

        return DB::transaction(function () use ($dto, $arquivo): CisternaNotificacao {
            $notificacao = CisternaNotificacao::create(array_merge($dto->toArray(), [
                'respondida' => false,
                'created_by' => Auth::id(),
            ]));

            $this->anexar($notificacao, $arquivo);

            return $notificacao->load(['notificavel', 'media']);
        });
    }

    public function atualizar(
        CisternaNotificacao $notificacao,
        NotificacaoDTO $dto,
        ?UploadedFile $arquivo = null,
    ): CisternaNotificacao {
        return DB::transaction(function () use ($notificacao, $dto, $arquivo): CisternaNotificacao {
            // O alvo da notificacao nao muda na edicao: so o texto.
            $notificacao->update(['observacao' => $dto->observacao]);

            $this->anexar($notificacao, $arquivo);

            return $notificacao->fresh(['notificavel', 'media']);
        });
    }

    /**
     * Idempotente: responder duas vezes preserva a data original.
     */
    public function responder(CisternaNotificacao $notificacao, bool $respondida = true): CisternaNotificacao
    {
        if ($notificacao->respondida === $respondida) {
            return $notificacao;
        }

        $notificacao->update([
            'respondida' => $respondida,
            'respondida_em' => $respondida ? now() : null,
        ]);

        return $notificacao->fresh();
    }

    public function deletar(CisternaNotificacao $notificacao): bool
    {
        return (bool) $notificacao->delete();
    }

    /**
     * @throws ValidationException
     */
    private function garantirQueONotificavelExiste(NotificacaoDTO $dto): void
    {
        /** @var class-string<Model> $classe */
        $classe = $dto->notificavelType;

        if (! $classe::query()->whereKey($dto->notificavelId)->exists()) {
            throw ValidationException::withMessages([
                'notificavel_id' => 'O registro a notificar nao foi encontrado.',
            ]);
        }
    }

    private function anexar(CisternaNotificacao $notificacao, ?UploadedFile $arquivo): void
    {
        if ($arquivo === null) {
            return;
        }

        $notificacao->addMedia($arquivo)
            ->usingFileName($arquivo->getClientOriginalName())
            ->toMediaCollection('documentos');
    }
}
