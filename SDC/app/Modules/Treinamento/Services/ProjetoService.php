<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\StatusEntrega;
use App\Modules\Treinamento\Enums\StatusProgresso;
use App\Modules\Treinamento\Enums\TipoItem;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\ModuloItem;
use App\Modules\Treinamento\Models\ProgressoItem;
use App\Modules\Treinamento\Models\ProjetoEntrega;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Models\User;
use App\Modules\Treinamento\Mail\EntregaCorrigidaMail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class ProjetoService
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ArquivoCursoService $arquivos,
        private readonly ProgressoService $progresso,
    ) {}

    public function enviar(Inscricao $inscricao, ModuloItem $item, UploadedFile $arquivo, ?string $comentario): ProjetoEntrega
    {
        if ($item->tipo !== TipoItem::PROJETO) {
            throw new \DomainException('Este item nao recebe entrega de projeto.');
        }
        if (! $this->trilha->itemLiberado($inscricao, $item)) {
            throw new AuthorizationException('Este conteudo ainda esta bloqueado.');
        }

        $dados = $this->arquivos->guardar($arquivo, "cursos/{$inscricao->treinamento_id}/entregas/{$inscricao->id}");

        try {
            $entrega = ConexaoTreinamento::transacao(function () use ($inscricao, $item, $comentario, $dados) {
                // Trava a inscricao: dois envios simultaneos nao passam juntos pela checagem.
                Inscricao::query()->whereKey($inscricao->id)->lockForUpdate()->first();

                $ultima = $this->ultimaEntrega($inscricao, $item);
                if ($ultima !== null && in_array($ultima->status, [StatusEntrega::ENVIADA, StatusEntrega::APROVADA], true)) {
                    throw new \DomainException('Ja existe uma entrega '.mb_strtolower($ultima->status->getLabel()).' para este projeto.');
                }

                $nova = ProjetoEntrega::query()->create($dados + [
                    'inscricao_id' => $inscricao->id,
                    'modulo_item_id' => $item->id,
                    'comentario' => $comentario,
                    'status' => StatusEntrega::ENVIADA,
                ]);

                ProgressoItem::query()->firstOrCreate(
                    ['inscricao_id' => $inscricao->id, 'modulo_item_id' => $item->id],
                    ['status' => StatusProgresso::EM_ANDAMENTO, 'posicao' => 0],
                );

                return $nova;
            });
        } catch (\Throwable $e) {
            $this->arquivos->apagar($dados['arquivo_path']);
            throw $e;
        }

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $inscricao->treinamento_id,
            AcaoTrilha::Relacionado,
            'nova entrega de projeto',
        );

        return $entrega;
    }

    /**
     * Correcao pelo professor: so entrega ENVIADA. Aprovar conclui o item
     * (e pode liberar a pasta seguinte); devolver exige parecer e deixa o
     * aluno reenviar. O aluno e avisado por e-mail, se tiver um.
     */
    public function corrigir(ProjetoEntrega $entrega, User $professor, bool $aprovar, ?string $parecer): void
    {
        $parecer = $parecer === null ? null : trim($parecer);
        if (! $aprovar && ($parecer === null || $parecer === '')) {
            throw new \DomainException('Informe o parecer para devolver a entrega.');
        }

        $inscricao = ConexaoTreinamento::transacao(function () use ($entrega, $professor, $aprovar, $parecer) {
            // Trava a entrega: duas correcoes simultaneas nao passam juntas.
            $atual = ProjetoEntrega::query()->whereKey($entrega->id)->lockForUpdate()->firstOrFail();
            if ($atual->status !== StatusEntrega::ENVIADA) {
                throw new \DomainException('Esta entrega ja foi corrigida.');
            }

            $atual->update([
                'status' => $aprovar ? StatusEntrega::APROVADA : StatusEntrega::DEVOLVIDA,
                'parecer' => $parecer === '' ? null : $parecer,
                'corrigido_por' => $professor->getKey(),
                'corrigido_em' => now(),
            ]);

            $inscricao = Inscricao::query()->findOrFail($atual->inscricao_id);
            if ($aprovar) {
                // concluir() ja atualiza a conclusao das pastas.
                $this->progresso->concluir($inscricao, ModuloItem::query()->findOrFail($atual->modulo_item_id));
            }

            $entrega->setRawAttributes($atual->getAttributes(), true);

            return $inscricao;
        });

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $inscricao->treinamento_id,
            AcaoTrilha::Relacionado,
            $aprovar ? 'entrega de projeto aprovada' : 'entrega de projeto devolvida',
        );

        $this->avisarAluno($inscricao, $entrega, $aprovar);
    }

    private function avisarAluno(Inscricao $inscricao, ProjetoEntrega $entrega, bool $aprovada): void
    {
        $inscrito = $inscricao->inscrito;
        if ($inscrito === null || empty($inscrito->email)) {
            return;
        }

        Mail::to($inscrito->email)->queue(new EntregaCorrigidaMail(
            nomeInscrito: (string) $inscrito->name,
            curso: (string) $inscricao->treinamento()->value('titulo'),
            item: (string) ModuloItem::query()->whereKey($entrega->modulo_item_id)->value('titulo'),
            aprovada: $aprovada,
            parecer: $entrega->parecer,
        ));
    }

    public function ultimaEntrega(Inscricao $inscricao, ModuloItem $item): ?ProjetoEntrega
    {
        return ProjetoEntrega::query()
            ->where('inscricao_id', $inscricao->id)
            ->where('modulo_item_id', $item->id)
            ->orderByDesc('id')
            ->first();
    }
}
