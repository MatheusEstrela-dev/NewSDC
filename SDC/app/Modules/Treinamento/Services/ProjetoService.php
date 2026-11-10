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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

class ProjetoService
{
    public function __construct(
        private readonly TrilhaService $trilha,
        private readonly ArquivoCursoService $arquivos,
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

    public function ultimaEntrega(Inscricao $inscricao, ModuloItem $item): ?ProjetoEntrega
    {
        return ProjetoEntrega::query()
            ->where('inscricao_id', $inscricao->id)
            ->where('modulo_item_id', $item->id)
            ->orderByDesc('id')
            ->first();
    }
}
