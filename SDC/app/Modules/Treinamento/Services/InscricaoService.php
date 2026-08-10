<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Shared\BaseService;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Enums\TipoTreinamento;
use App\Modules\Treinamento\Mail\InscricaoConfirmadaMail;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InscricaoService extends BaseService
{
    public function listPorTreinamento(int $treinamentoId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inscricao::query()
            ->with(['inscrito', 'certificado'])
            ->porTreinamento($treinamentoId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('data_inscricao', 'desc')->paginate($perPage);
    }

    /**
     * $inscrito e polimorfico: App\Models\User (servidor, area interna) ou
     * App\Modules\Treinamento\Models\Cidadao (portal do cidadao).
     */
    public function inscrever(Treinamento $treinamento, Model $inscrito): Inscricao
    {
        if (!$treinamento->podeReceberInscricao()) {
            throw new \DomainException('Este treinamento nao esta recebendo inscricoes no momento.');
        }

        $jaInscrito = Inscricao::query()
            ->porTreinamento($treinamento->id)
            ->where('inscrito_type', $inscrito::class)
            ->where('inscrito_id', $inscrito->getKey())
            ->exists();

        if ($jaInscrito) {
            throw new \DomainException('Voce ja possui uma inscricao neste treinamento.');
        }

        $inscricao = Inscricao::create([
            'treinamento_id' => $treinamento->id,
            'inscrito_type' => $inscrito::class,
            'inscrito_id' => $inscrito->getKey(),
            'status' => StatusInscricao::PENDENTE->value,
            'qr_code_token' => (string) Str::uuid(),
            'data_inscricao' => now(),
        ]);

        // O QR Code (o "ingresso") so sai por e-mail quando a inscricao e
        // aprovada (ver aprovar() abaixo) - antes disso a inscricao ainda pode
        // ser reprovada, e mandar o ingresso de uma inscricao pendente confunde
        // o cidadao (ingresso na mao antes de ter vaga confirmada).

        // Avisa o dono do treinamento pela trilha do protocolo. Relacionado e a
        // acao certa: um terceiro acrescentou conteudo, nada mudou de situacao.
        // O agrupamento do modulo cuida do volume -- um curso com 200 inscritos
        // vira um card com contador, nao 200 cards.
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamento->id,
            AcaoTrilha::Relacionado,
            'nova inscricao',
        );

        return $inscricao;
    }

    public function aprovar(Inscricao $inscricao, User $aprovador, ?string $observacoes = null): void
    {
        if ($inscricao->status !== StatusInscricao::PENDENTE) {
            throw new \DomainException('Somente inscricoes pendentes podem ser aprovadas.');
        }

        $inscricao->update([
            'status' => StatusInscricao::APROVADA->value,
            'data_aprovacao' => now(),
            'aprovado_por_id' => $aprovador->id,
            'observacoes' => $observacoes,
        ]);

        // RF03: QR Code por e-mail so faz sentido para presencial - online
        // confirma presenca direto na tela (ver PresencaService::autoconfirmar()).
        $inscricao->loadMissing(['treinamento', 'inscrito']);
        if ($inscricao->treinamento->tipo === TipoTreinamento::PRESENCIAL && $inscricao->inscrito?->email) {
            Mail::to($inscricao->inscrito->email)->queue(new InscricaoConfirmadaMail($inscricao));
        }
    }

    public function reprovar(Inscricao $inscricao, User $aprovador, string $observacoes): void
    {
        if ($inscricao->status !== StatusInscricao::PENDENTE) {
            throw new \DomainException('Somente inscricoes pendentes podem ser reprovadas.');
        }

        $inscricao->update([
            'status' => StatusInscricao::REPROVADA->value,
            'data_aprovacao' => now(),
            'aprovado_por_id' => $aprovador->id,
            'observacoes' => $observacoes,
        ]);
    }

    public function cancelar(Inscricao $inscricao): void
    {
        if ($inscricao->status->isFinal() && $inscricao->status !== StatusInscricao::APROVADA) {
            throw new \DomainException('Esta inscricao nao pode mais ser cancelada.');
        }

        $inscricao->update(['status' => StatusInscricao::CANCELADA->value]);
    }
}
