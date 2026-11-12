<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Prova;
use App\Modules\Treinamento\Models\ProvaLiberacao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use Illuminate\Validation\ValidationException;

/**
 * Tentativas extras concedidas pelo professor a um aluno travado na prova
 * (spec 8.3): gravadas com autor e data e registradas na trilha.
 */
class LiberacaoProvaService
{
    public function __construct(private readonly SituacaoProvaService $situacoes) {}

    public function liberar(Treinamento $treinamento, Prova $prova, int $inscricaoId, int $quantidade, User $autor): ProvaLiberacao
    {
        $liberacao = ConexaoTreinamento::transacao(function () use ($treinamento, $prova, $inscricaoId, $quantidade, $autor) {
            // Trava a inscricao: duas liberacoes simultaneas nao passam juntas pela checagem.
            $inscricao = Inscricao::query()
                ->porTreinamento($treinamento->id)
                ->whereKey($inscricaoId)
                ->lockForUpdate()
                ->first();

            $this->exigirTravada($prova, $inscricao);

            return ProvaLiberacao::query()->create([
                'prova_id' => $prova->id,
                'inscricao_id' => $inscricao->id,
                'quantidade' => $quantidade,
                'liberado_por' => $autor->id,
            ]);
        });

        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $treinamento->id,
            AcaoTrilha::Relacionado,
            "tentativas extras liberadas ({$quantidade}) na prova {$prova->id}, inscricao {$inscricaoId}",
        );

        return $liberacao;
    }

    private function exigirTravada(Prova $prova, ?Inscricao $inscricao): void
    {
        if ($inscricao === null || $inscricao->status !== StatusInscricao::APROVADA) {
            throw ValidationException::withMessages(['inscricao_id' => 'Aluno nao encontrado neste curso.']);
        }

        if (! $this->situacoes->daProva($inscricao, $prova)->travada()) {
            throw ValidationException::withMessages(['inscricao_id' => 'O aluno nao esta travado nesta prova.']);
        }
    }
}
