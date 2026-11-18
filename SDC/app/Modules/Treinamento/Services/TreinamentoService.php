<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\Municipio;
use App\Models\User;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Enums\TipoEventoCurso;
use App\Modules\Treinamento\Jobs\EmitirCertificadosTreinamentoJob;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Shared\BaseService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;

class TreinamentoService extends BaseService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Treinamento::query()->with(['modulos']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('titulo', 'like', "%{$filters['search']}%")
                  ->orWhere('descricao', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Municipios de MG para o select de "municipio de realizacao" (catalogo
     * cacheado do sdc, so id e nome).
     *
     * @return list<array{id: int, nome: string}>
     */
    public function municipiosDeMg(): array
    {
        return Municipio::catalogo()
            ->where('uf', 'MG')
            ->map(fn (array $m) => ['id' => $m['id'], 'nome' => $m['nome']])
            ->values()
            ->all();
    }

    public function findById(int $id): ?Treinamento
    {
        // Nota: `frequencias` pertence a Modulo, nao a Treinamento diretamente
        // (bug pre-existente aqui tentava eager-load de uma relacao inexistente).
        return Treinamento::with(['curso', 'modulos.frequencias', 'inscricoes.inscrito', 'inscricoes.certificado'])->find($id);
    }

    public function create(array $data): Treinamento
    {
        $treinamento = $this->semColisaoDeVersao(fn () => Treinamento::create($data));

        app(HistoricoDoCursoService::class)->registrar(
            (int) $treinamento->id,
            TipoEventoCurso::CURSO_CRIADO,
            'Curso criado.',
            ['titulo' => $treinamento->titulo, 'tipo' => $treinamento->tipo?->value],
        );

        return $treinamento;
    }

    public function update(int $id, array $data): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }

        $alterado = $this->semColisaoDeVersao(fn () => $treinamento->update($data));

        // So vira linha de historico o que de fato mudou: salvar o formulario
        // sem mexer em nada nao enche a tela do gestor.
        $mudancas = array_keys($treinamento->getChanges());
        $mudancas = array_values(array_diff($mudancas, ['updated_at']));

        if ($alterado && $mudancas !== []) {
            app(HistoricoDoCursoService::class)->registrar(
                (int) $treinamento->id,
                TipoEventoCurso::CURSO_ATUALIZADO,
                'Curso atualizado: '.implode(', ', $mudancas).'.',
                ['campos' => $mudancas],
            );
        }

        return $alterado;
    }

    /** Indice unico (curso, versao) do EAD estourado por corrida: vira mensagem, nao 500. */
    private function semColisaoDeVersao(callable $grava): mixed
    {
        try {
            return $grava();
        } catch (UniqueConstraintViolationException) {
            throw new \DomainException('Este curso do catalogo ja tem um EAD. Para uma nova edicao, use "Nova versao" na tela do curso.');
        }
    }

    public function delete(int $id): bool
    {
        $treinamento = Treinamento::find($id);
        if (!$treinamento) {
            return false;
        }
        return $treinamento->delete();
    }

    public function getStatistics(): array
    {
        return [
            'total' => Treinamento::count(),
            'planejados' => Treinamento::where('status', StatusTreinamento::PLANEJADO->value)->count(),
            'em_andamento' => Treinamento::where('status', StatusTreinamento::EM_ANDAMENTO->value)->count(),
            'concluidos' => Treinamento::where('status', StatusTreinamento::CONCLUIDO->value)->count(),
        ];
    }

    public function export(array $filters = []): array
    {
        $query = Treinamento::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->toArray();
    }

    /**
     * Publica o treinamento no catalogo do Portal do Cidadao (gera o slug
     * publico). Nao depende do status interno (PLANEJADO/EM_ANDAMENTO ainda
     * podem ser publicados para abrir inscricao antecipada).
     */
    public function publicar(Treinamento $treinamento): void
    {
        if ($treinamento->status === StatusTreinamento::CANCELADO || $treinamento->status === StatusTreinamento::CONCLUIDO) {
            throw new \DomainException('Nao e possivel publicar um treinamento cancelado ou concluido.');
        }

        $treinamento->publicarNoPortal();

        app(HistoricoDoCursoService::class)->registrar(
            (int) $treinamento->id,
            TipoEventoCurso::CURSO_PUBLICADO,
            'Curso publicado no catalogo.',
            ['publicado_em' => $treinamento->publicado_em?->toIso8601String()],
        );
    }

    public function liberarPresenca(Treinamento $treinamento, User $por): void
    {
        if (!$treinamento->status->podeRegistrarFrequencia()) {
            throw new \DomainException('So e possivel liberar a presenca de um treinamento Em Andamento.');
        }

        $treinamento->liberarPresenca($por);
    }

    public function bloquearPresenca(Treinamento $treinamento): void
    {
        $treinamento->bloquearPresenca();
    }

    public function transicionarStatus(Treinamento $treinamento, StatusTreinamento $novoStatus): void
    {
        if (!$treinamento->status->canTransitionTo($novoStatus)) {
            throw new \DomainException(
                "Nao e possivel mudar o status de {$treinamento->status->getLabel()} para {$novoStatus->getLabel()}."
            );
        }

        $anterior = $treinamento->status;

        if ($novoStatus === StatusTreinamento::CONCLUIDO) {
            $treinamento->finalizar();
            $this->registrarMudancaDeStatus($treinamento, $anterior, $novoStatus);
            EmitirCertificadosTreinamentoJob::dispatch($treinamento->id);
            return;
        }

        $treinamento->update(['status' => $novoStatus->value]);
        $this->registrarMudancaDeStatus($treinamento, $anterior, $novoStatus);
    }

    private function registrarMudancaDeStatus(Treinamento $treinamento, StatusTreinamento $anterior, StatusTreinamento $novo): void
    {
        app(HistoricoDoCursoService::class)->registrar(
            (int) $treinamento->id,
            $novo === StatusTreinamento::CANCELADO ? TipoEventoCurso::CURSO_CANCELADO : TipoEventoCurso::STATUS_ALTERADO,
            'Status alterado de '.$anterior->getLabel().' para '.$novo->getLabel().'.',
            ['de' => $anterior->value, 'para' => $novo->value],
        );
    }
}
