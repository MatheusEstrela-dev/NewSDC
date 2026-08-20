<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\NotificacaoDTO;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaNotificacao;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
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
     *
     * O perfil e opcional e vem por ultimo, para as telas web continuarem
     * chamando `listar($filtros, $porPagina)` sem mudanca de comportamento.
     */
    public function listar(
        array $filtros = [],
        int $porPagina = 25,
        ?PerfilCisterna $perfil = null,
    ): LengthAwarePaginator {
        $query = CisternaNotificacao::query()
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
            });

        if ($perfil !== null && EscopoPerfil::temRecorte($perfil)) {
            $this->aplicarEscopoNoNotificavel($query, $perfil);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * O notificavel e polimorfico e as duas pontas chegam ao municipio por
     * caminhos diferentes: o beneficiario tem a coluna, a vistoria chega pela
     * relacao. Cobrir so uma delas deixaria metade das notificacoes vazando
     * para fora do territorio.
     *
     * @param  Builder<CisternaNotificacao>  $query
     */
    private function aplicarEscopoNoNotificavel(Builder $query, PerfilCisterna $perfil): void
    {
        $query->whereHasMorph(
            'notificavel',
            [CisternaBeneficiario::class, CisternaVistoria::class],
            function (Builder $notificavel, string $tipo) use ($perfil): void {
                if ($tipo === CisternaBeneficiario::class) {
                    EscopoPerfil::aplicarEmBeneficiario($notificavel, $perfil);

                    return;
                }

                EscopoPerfil::aplicarViaBeneficiario($notificavel, $perfil);
            }
        );
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
