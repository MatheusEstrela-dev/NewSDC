<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Modules\Acessos\DTOs\CadastroAcessoDTO;
use App\Modules\Acessos\Models\CadastroAcesso;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Escritas do cadastro de acesso, com a trilha em acessos_auditoria.
 *
 * Saiu do CadastroAcessoController (que gravava validated() direto no Model)
 * para seguir Request -> DTO -> Controller -> Service -> Model. As travas de
 * concorrencia (lockForUpdate) e as regras de transicao ficaram iguais.
 */
class CadastroAcessoService
{
    /** Transicoes permitidas a partir de cada status. */
    private const TRANSICOES = [
        'pendente' => ['rejeitado'],
        'aprovado' => ['ativo', 'inativo'],
        'ativo'    => ['inativo'],
        'inativo'  => ['ativo'],
    ];

    public function criar(CadastroAcessoDTO $dto, int $solicitanteId): CadastroAcesso
    {
        return DB::transaction(function () use ($dto, $solicitanteId): CadastroAcesso {
            $cadastro = CadastroAcesso::create([
                ...$dto->toArray(),
                'solicitado_por_id' => $solicitanteId,
                'data_solicitacao' => now(),
            ]);
            $this->auditar($cadastro, $solicitanteId, 'criado');

            return $cadastro;
        });
    }

    public function atualizar(CadastroAcesso $cadastro, CadastroAcessoDTO $dto, int $autorId): CadastroAcesso
    {
        return DB::transaction(function () use ($cadastro, $dto, $autorId): CadastroAcesso {
            $cadastro->update($dto->toArray());
            $this->auditar($cadastro, $autorId, 'editado');

            return $cadastro;
        });
    }

    /** @throws HttpException 409 fora de pendente; 403 quem solicitou nao aprova */
    public function aprovar(CadastroAcesso $cadastro, int $aprovadorId): void
    {
        DB::transaction(function () use ($cadastro, $aprovadorId): void {
            $locked = CadastroAcesso::query()->lockForUpdate()->findOrFail($cadastro->id);
            abort_unless($locked->status === 'pendente', 409, 'Cadastro não está pendente.');
            abort_if($locked->solicitado_por_id === $aprovadorId, 403, 'O solicitante não pode aprovar o próprio cadastro.');

            $locked->update([
                'status' => 'aprovado',
                'aprovado_por_id' => $aprovadorId,
                'data_aprovacao' => now(),
            ]);
            $this->auditar($locked, $aprovadorId, 'aprovado');
        });
    }

    /** @throws HttpException 409 transicao nao permitida */
    public function alterarStatus(CadastroAcesso $cadastro, string $novoStatus, int $autorId): void
    {
        DB::transaction(function () use ($cadastro, $novoStatus, $autorId): void {
            $locked = CadastroAcesso::query()->lockForUpdate()->findOrFail($cadastro->id);
            $permitidos = self::TRANSICOES[$locked->status] ?? [];
            abort_unless(in_array($novoStatus, $permitidos, true), 409, 'Transição de status não permitida.');

            $anterior = $locked->status;
            $locked->update(['status' => $novoStatus]);
            $this->auditar($locked, $autorId, 'status_alterado', ['anterior' => $anterior, 'novo' => $novoStatus]);
        });
    }

    /** @param array<string, mixed>|null $dados */
    private function auditar(CadastroAcesso $cadastro, int $autorId, string $acao, ?array $dados = null): void
    {
        $cadastro->auditoria()->create(array_filter([
            'actor_id' => $autorId,
            'acao' => $acao,
            'dados' => $dados,
            'created_at' => now(),
        ], fn ($valor) => $valor !== null));
    }
}
