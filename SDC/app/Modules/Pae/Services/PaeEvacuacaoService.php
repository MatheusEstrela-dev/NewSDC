<?php

declare(strict_types=1);

namespace App\Modules\Pae\Services;

use App\Models\User;
use App\Modules\Pae\Models\PaeEvacuacaoConferencia;
use App\Modules\Pae\Models\PaeProtocolo;
use App\Modules\Pae\Requests\RegistrarEvacuacaoRequest;
use App\Modules\Pae\Requests\SimularEvacuacaoRequest;
use App\Modules\Pae\Support\Evacuacao\CalculoEvacuacaoAnexoE;
use App\Modules\Pae\Support\Evacuacao\ReferenciasEvacuacao;
use App\Modules\Pae\Support\Evacuacao\TempoAnexoE;
use App\Modules\Pae\Support\TimelinePae;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PaeEvacuacaoService
{
    public function __construct(private readonly CalculoEvacuacaoAnexoE $calculo)
    {
    }

    public function simular(array $dados): array
    {
        $entrada = $this->entrada($dados, SimularEvacuacaoRequest::regras());

        return TempoAnexoE::formatarResultado($this->calculo->calcular($entrada));
    }

    public function registrar(PaeProtocolo $protocolo, array $dados, User $user): PaeEvacuacaoConferencia
    {
        $entrada = $this->entrada($dados, RegistrarEvacuacaoRequest::regras());
        $numSei = trim((string) $dados['num_sei']);
        if ($numSei === '') {
            throw ValidationException::withMessages(['num_sei' => 'Informe o número SEI.']);
        }
        $observacao = trim((string) ($dados['observacao'] ?? '')) ?: null;
        $resultado = TempoAnexoE::formatarResultado($this->calculo->calcular($entrada));

        return DB::transaction(function () use ($protocolo, $dados, $user, $entrada, $numSei, $observacao, $resultado): PaeEvacuacaoConferencia {
            $locked = PaeProtocolo::query()->whereKey($protocolo->id)->lockForUpdate()->firstOrFail();
            if ($locked->arquivado) {
                throw ValidationException::withMessages(['protocolo' => 'Protocolo arquivado: a conferência está somente para consulta.']);
            }

            $existente = $locked->conferenciasEvacuacao()->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente !== null) {
                if ($existente->entrada() != $entrada || $existente->num_sei !== $numSei || $existente->observacao !== $observacao) {
                    throw ValidationException::withMessages(['chave_idempotencia' => 'Esta chave de idempotência já foi usada com outros dados.']);
                }

                return $existente;
            }

            $versao = (int) $locked->conferenciasEvacuacao()->max('versao') + 1;
            $conferencia = $locked->conferenciasEvacuacao()->create([
                ...$entrada,
                'versao' => $versao,
                'num_sei' => $numSei,
                'observacao' => $observacao,
                'tmd_segundos' => $resultado['tmd_segundos'],
                'te_segundos' => $resultado['te_segundos'],
                'tte_segundos' => $resultado['tte_segundos'],
                'criterio1_conforme' => $resultado['criterio1_conforme'],
                'criterio2_conforme' => $resultado['criterio2_conforme'],
                'possui_rota_invalida' => $resultado['possui_rota_invalida'],
                'possui_setor_inviavel' => $resultado['possui_setor_inviavel'],
                'excede_declarado' => $resultado['excede_declarado'],
                'resultado' => $resultado,
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'criado_por' => $user->id,
            ]);
            TimelinePae::registrar($locked, 'evacuacao_conferencia', sprintf(
                'Conferência de evacuação versão %d: TTE %s, %s. SEI %s.',
                $versao,
                $resultado['tte_fmt'] ?? 'não calculado',
                $conferencia->conforme() ? 'conforme' : 'não conforme',
                $numSei,
            ), $user);

            return $conferencia;
        });
    }

    public function visualizar(PaeProtocolo $protocolo, ?int $versao): array
    {
        $historico = $protocolo->conferenciasEvacuacao()
            ->select(['id', 'protocolo_id', 'versao', 'num_sei', 'tte_segundos', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida', 'possui_setor_inviavel', 'excede_declarado', 'criado_por', 'created_at'])
            ->with('autor:id,name')
            ->orderByDesc('versao')
            ->get();
        $atual = $historico->first();
        $alvo = $versao ?? $atual?->versao;
        $selecionada = $alvo === null ? null : $this->completa($protocolo, $alvo);
        if ($versao !== null && $selecionada === null) {
            throw (new ModelNotFoundException())->setModel(PaeEvacuacaoConferencia::class, [$versao]);
        }

        return [
            'conferencia' => $selecionada === null ? null : $this->apresentar($selecionada),
            'historico' => $historico->map(fn (PaeEvacuacaoConferencia $c): array => [
                'versao' => $c->versao,
                'autor' => $c->autor?->name,
                'created_at' => $c->created_at?->toIso8601String(),
                'num_sei' => $c->num_sei,
                'tte_fmt' => TempoAnexoE::formatar($c->tte_segundos),
                'conforme' => $c->conforme(),
            ])->values()->all(),
            'historica' => $selecionada !== null && $atual->versao !== $selecionada->versao,
            'versao_atual' => $atual?->versao ?? 0,
        ];
    }

    public function anotarListagem(LengthAwarePaginator $pagina): LengthAwarePaginator
    {
        $ids = $pagina->getCollection()->map(fn ($item): int => (int) data_get($item, 'id'))->all();
        if ($ids === []) {
            return $pagina;
        }

        $vigentes = PaeEvacuacaoConferencia::query()
            ->whereIn('id', PaeEvacuacaoConferencia::query()->selectRaw('max(id)')->whereIn('protocolo_id', $ids)->groupBy('protocolo_id'))
            ->get(['protocolo_id', 'criterio1_conforme', 'criterio2_conforme', 'possui_rota_invalida', 'possui_setor_inviavel', 'excede_declarado'])
            ->keyBy('protocolo_id');

        $pagina->setCollection($pagina->getCollection()->map(function ($item) use ($vigentes): array {
            $conferencia = $vigentes->get((int) data_get($item, 'id'));

            return [
                ...(is_array($item) ? $item : $item->toArray()),
                'evacuacao_situacao' => match (true) {
                    $conferencia === null => 'nao_conferida',
                    $conferencia->conforme() => 'conforme',
                    default => 'nao_conforme',
                },
            ];
        }));

        return $pagina;
    }

    /** Valida, normaliza e confere as referencias; o resultado nunca vem do cliente. */
    private function entrada(array $dados, array $regras): array
    {
        $validado = Validator::make($dados, $regras)->validate();
        $entrada = [
            'setores' => array_map(fn (array $s): array => [
                'id' => trim($s['id']),
                'populacao' => (int) $s['populacao'],
                'comercial' => (bool) $s['comercial'],
                'via' => $s['via'],
                'largura' => (float) $s['largura'],
                'lados' => $s['via'] === 'calcada' ? (int) $s['lados'] : null,
                'distancia' => (float) $s['distancia'],
                'terreno' => $s['terreno'],
            ], $validado['setores']),
            'rotas' => array_map(fn (array $r): array => [
                'id' => trim($r['id']),
                'setores' => array_values(array_map('trim', $r['setores'])),
                'chegada_onda' => $r['chegada_onda'],
                'chegada_onda_segundos' => TempoAnexoE::paraSegundos($r['chegada_onda']),
                'nivel_emergencia' => (int) $r['nivel_emergencia'],
            ], $validado['rotas']),
            'acessos' => array_map(fn (array $a): array => [
                'id' => trim($a['id']),
                'largura' => (float) $a['largura'],
                'terreno' => $a['terreno'],
                'rotas' => array_values(array_map('trim', $a['rotas'])),
            ], $validado['acessos'] ?? []),
            'pontos_encontro' => array_map(fn (array $p): array => [
                'nome' => trim($p['nome']),
                'endereco' => trim($p['endereco']),
                'populacao' => (int) $p['populacao'],
                'area' => (float) $p['area'],
            ], $validado['pontos_encontro']),
            'tte_declarado_segundos' => isset($validado['tte_declarado']) ? TempoAnexoE::paraSegundos($validado['tte_declarado']) : null,
        ];

        $erros = ReferenciasEvacuacao::erros($entrada);
        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        return $entrada;
    }

    /** Linha completa (com os jsonb) so da versao exibida; null quando a versao nao existe no protocolo. */
    private function completa(PaeProtocolo $protocolo, int $versao): ?PaeEvacuacaoConferencia
    {
        return $protocolo->conferenciasEvacuacao()->with('autor:id,name')->where('versao', $versao)->first();
    }

    private function apresentar(PaeEvacuacaoConferencia $c): array
    {
        return [
            'versao' => $c->versao,
            'entrada' => [...$c->entrada(), 'tte_declarado' => TempoAnexoE::formatar($c->tte_declarado_segundos)],
            'resultado' => $c->resultado,
            'num_sei' => $c->num_sei,
            'observacao' => $c->observacao,
            'autor' => $c->autor?->name,
            'created_at' => $c->created_at?->toIso8601String(),
            'conforme' => $c->conforme(),
        ];
    }
}
