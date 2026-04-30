<?php

declare(strict_types=1);

namespace App\Modules\Rat\Infrastructure\Persistence;

use App\Models\Rat\RatOcorrencia;
use App\Modules\Rat\Domain\Repositories\RatRepositoryInterface;
use App\Modules\Rat\DTOs\RatFilterDTO;
use App\Modules\Rat\Models\Rat;
use App\Modules\Rat\Models\Relatos\RatRelatoDadosGerais;
use App\Modules\Rat\Services\RatFilterService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Implementacao Eloquent do repositorio de RAT.
 *
 * Usa tabelas polimorficas (rat_ocorrencias + relatos) como fonte de dados.
 * Adapta para o formato esperado pelo frontend (compativel com estrutura Rat legada).
 */
class EloquentRatRepository implements RatRepositoryInterface
{
    public function __construct(
        private readonly RatFilterService $filterService,
    ) {}

    public function create(array $data): Rat
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        $ocorrencia = RatOcorrencia::create([
            'numero_bos'  => $this->generateProtocolo(),
            'status'      => 0,
            'created_by'  => $auth->id(),
            'updated_by'  => $auth->id(),
        ]);

        return $this->toRatModel($ocorrencia);
    }

    public function findById(string $id): ?Rat
    {
        $ocorrencia = RatOcorrencia::with([
            'relatosMorph', 
            'relatosMorph.conteudo',
            'relatosMorph.conteudo.agentes', // If available for resources
            'historicos'
        ])->find($id);

        if (!$ocorrencia) {
            return null;
        }

        return $this->toRatModel($ocorrencia);
    }

    public function paginate(RatFilterDTO $filters): LengthAwarePaginator
    {
        $query = RatOcorrencia::with(['relatosMorph'])
            ->orderBy('created_at', 'desc');

        if ($filters->status !== null && $filters->status !== '') {
            $statusInt = match($filters->status) {
                'rascunho', '0' => 0,
                'finalizado', '1' => 1,
                default => (int) $filters->status,
            };
            $query->where('status', $statusInt);
        }

        if ($filters->protocolo) {
            $query->where('numero_bos', 'like', "%{$filters->protocolo}%");
        }

        if ($filters->ano) {
            $query->whereYear('created_at', $filters->ano);
        }

        $paginator = $query->paginate($filters->perPage);

        $paginator->getCollection()->transform(fn($oc) => $this->toRatModel($oc));

        return $paginator;
    }

    public function getMunicipalities(): array
    {
        return RatRelatoDadosGerais::whereNotNull('local_municipio')
            ->distinct()
            ->pluck('local_municipio')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    public function delete(string $id): void
    {
        RatOcorrencia::where('id', $id)->delete();
    }

    public function updateStatus(string $id, string $status): void
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        RatOcorrencia::where('id', $id)->update([
            'status'     => $status === 'finalizado' ? 1 : 0,
            'updated_by' => $auth->id(),
        ]);
    }

    public function update(string $id, array $data): Rat
    {
        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        $ocorrencia = RatOcorrencia::findOrFail($id);
        $ocorrencia->update(['updated_by' => $auth->id()]);

        $this->syncRelatos($ocorrencia, $data);

        return $this->toRatModel($ocorrencia->fresh(['relatosMorph']));
    }

    private function syncRelatos(RatOcorrencia $ocorrencia, array $data): void
    {
        $ocorrencia->relatos()->get()->each(fn($r) => $r->delete());

        $dadosGeraisData = $this->buildDadosGeraisData($data);
        if (!empty($dadosGeraisData)) {
            $dg = RatRelatoDadosGerais::create(array_merge($dadosGeraisData, ['created_by' => auth()->id()]));
            RatOcorrenciaRelato::create([
                'ocorrencia_id' => $ocorrencia->id,
                'conteudo_type' => RatRelatoDadosGerais::class,
                'conteudo_id'   => $dg->id,
                'created_by'    => auth()->id(),
            ]);
        }

        foreach ((array) ($data['recursos'] ?? []) as $recurso) {
            $recursoData = $this->buildRecursoData($recurso);
            if (empty($recursoData)) {
                continue;
            }
            $rc = RatRelatoRecurso::create($recursoData);
            RatOcorrenciaRelato::create([
                'ocorrencia_id' => $ocorrencia->id,
                'conteudo_type' => RatRelatoRecurso::class,
                'conteudo_id'   => $rc->id,
                'created_by'    => auth()->id(),
            ]);
        }

        foreach ((array) ($data['envolvidos'] ?? []) as $envolvido) {
            $envolvidoData = $this->buildEnvolvidoData($envolvido);
            if (empty($envolvidoData)) {
                continue;
            }
            $ev = RatRelatoEnvolvidos::create($envolvidoData);
            RatOcorrenciaRelato::create([
                'ocorrencia_id' => $ocorrencia->id,
                'conteudo_type' => RatRelatoEnvolvidos::class,
                'conteudo_id'   => $ev->id,
                'created_by'    => auth()->id(),
            ]);
        }

        if (!empty($data['vistoria'])) {
            $vistoriaData = $this->buildVistoriaData($data['vistoria']);
            if (!empty($vistoriaData)) {
                $vt = RatRelatoVistoria::create(array_merge($vistoriaData, ['created_by' => auth()->id()]));
                RatOcorrenciaRelato::create([
                    'ocorrencia_id' => $ocorrencia->id,
                    'conteudo_type' => RatRelatoVistoria::class,
                    'conteudo_id'   => $vt->id,
                    'created_by'    => auth()->id(),
                ]);
            }
        }
    }

    private function buildDadosGeraisData(array $data): array
    {
        $dg  = (array) ($data['dados_gerais'] ?? []);
        $loc = (array) ($data['local']        ?? []);
        $end = (array) ($data['endereco']     ?? []);
        $com = (array) ($data['comunicacao']  ?? []);

        return array_filter([
            'data_fato'              => $dg['data_fato']              ?? null,
            'data_inicio_atividade'  => $dg['data_inicio_atividade']  ?? null,
            'data_termino_atividade' => $dg['data_termino_atividade'] ?? null,
            'nat_cobrade_id'         => $dg['nat_cobrade_id']         ?? null,
            'nat_nome_operacao'      => $dg['nat_nome_operacao']      ?? null,
            'local_municipio'        => $loc['municipio_id']          ?? null,
            'local_estadouf'         => $loc['uf']                    ?? null,
            'local_pais'             => $loc['pais_id']               ?? null,
            'local_cep'              => $end['cep']                   ?? null,
            'local_logradoura_1'     => $end['logradouro']            ?? null,
            'local_bairro'           => $end['bairro']                ?? null,
            'local_cruzamento'       => $end['cruzamento']            ?? null,
            'com_ocorrencia_data'    => $com['data_comunicacao']      ?? null,
            'com_ocorrencia_atendimento' => $com['tipo_solicitacao']  ?? null,
        ], fn($v) => $v !== null);
    }

    private function buildRecursoData(array $r): array
    {
        return array_filter([
            'recurso_tipo'          => $r['tipo_recurso']      ?? null,
            'viatura_tipo'          => $r['categoria']         ?? null,
            'recurso_descricao'     => $r['descricao']         ?? null,
            'viatura_orgao'         => $r['orgao_responsavel'] ?? null,
            'viatura_placa'         => $r['identificacao']     ?? null,
            'viatura_saida'         => $r['data_saida']        ?? null,
            'viatura_chegada'       => $r['data_chegada']      ?? null,
            'viatura_km'            => $r['km_percorrido']     ?? null,
            'viatura_local_origem'  => $r['local_origem']      ?? null,
            'viatura_local_destino' => $r['local_destino']     ?? null,
        ], fn($v) => $v !== null);
    }

    private function buildEnvolvidoData(array $e): array
    {
        return array_filter([
            'g_tipo_pessoa'     => $e['tipo_pessoa']     ?? null,
            'p_cpf'             => $e['cpf']             ?? null,
            'p_nome_completo'   => $e['nome']            ?? null,
            'p_nome_fantasia'   => $e['nome_social']     ?? null,
            'p_data_nascimento' => $e['data_nascimento'] ?? null,
            'p_sexo'            => $e['sexo']            ?? null,
            'p_nome_mae'        => $e['nome_mae']        ?? null,
            'p_nome_pai'        => $e['nome_pai']        ?? null,
            'p_ocupacao_atual'  => $e['ocupacao']        ?? null,
            'p_escolaridade'    => $e['escolaridade']    ?? null,
            'p_end_cep'         => $e['cep']             ?? null,
            'g_envolvido_uf'    => $e['uf']              ?? null,
        ], fn($v) => $v !== null);
    }

    private function buildVistoriaData(array $v): array
    {
        $sol = (array) ($v['solicitante'] ?? []);
        $imo = (array) ($v['imovel']      ?? []);
        $est = (array) ($v['estrutura']   ?? []);
        $mor = (array) ($v['moradores']   ?? []);

        return array_filter([
            'v_solicitante_nome'     => $sol['nome']              ?? null,
            'v_solicitante_cpf'      => $sol['cpf']               ?? null,
            'v_solicitante_telefone' => $sol['telefone']          ?? null,
            'v_solicitante_cep'      => $sol['cep']               ?? null,
            'v_solicitante_bairro'   => $sol['bairro']            ?? null,
            'v_solicitante_endereco' => $sol['endereco']          ?? null,
            'v_endereco_imovel'      => $imo['endereco']          ?? null,
            'v_bairro'               => $imo['bairro']            ?? null,
            'v_municipio'            => $imo['municipio']         ?? null,
            'v_cep'                  => $imo['cep']               ?? null,
            'v_tipo_imovel'          => $est['tipo_imovel']       ?? null,
            'v_tipo_construcao'      => $est['tipo_construcao']   ?? null,
            'v_tipo_edificacao'      => $est['tipo_edificacao']   ?? null,
            'v_sistema_estrutural'   => $est['sistema_estrutural'] ?? null,
            'v_estado_conservacao'   => $est['estado_conservacao'] ?? null,
            'v_regime_ocupacao'      => $est['regime_ocupacao']   ?? null,
            'v_numero_pavimentos'    => $est['num_pavimentos']    ?? null,
            'v_proprietario_morador' => $mor['proprietario']      ?? null,
            'v_contato_telefone'     => $mor['telefone']          ?? null,
        ], fn($v) => $v !== null);
    }

    public function getLatestSequence(int $year): int
    {
        $latest = RatOcorrencia::where('numero_bos', 'like', "RAT-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('numero_bos')
            ->value('numero_bos');

        if (!$latest) {
            return 0;
        }

        return (int) substr($latest, strrpos($latest, '-') + 1);
    }

    private function generateProtocolo(): string
    {
        $year = now()->year;
        $seq  = $this->getLatestSequence($year) + 1;

        return sprintf('RAT-%d-%06d', $year, $seq);
    }

    private function toRatModel(RatOcorrencia $ocorrencia): Rat
    {
        $dadosGerais = null;
        $recursos    = [];
        $envolvidos  = [];
        $vistoria    = null;

        foreach ($ocorrencia->relatosMorph ?? [] as $relato) {
            $conteudo = $relato->conteudo;
            if (!$conteudo) continue;

            $type = class_basename($conteudo);
            match ($type) {
                'RatRelatoDadosGerais' => $dadosGerais = $conteudo->toArray(),
                'RatRelatoRecurso'     => $recursos[] = $conteudo->toArray(),
                'RatRelatoEnvolvidos'  => $envolvidos[] = $conteudo->toArray(),
                'RatRelatoVistoria'    => $vistoria = $conteudo->toArray(),
                default                => null,
            };
        }

        $rat = new Rat();
        $rat->id          = (string) $ocorrencia->id;
        $rat->protocolo   = $ocorrencia->numero_bos;
        $rat->status      = $ocorrencia->status === 1 ? 'finalizado' : 'rascunho';
        $rat->tem_vistoria = $vistoria !== null;
        $rat->dados_gerais = $dadosGerais ?? [];
        $rat->local        = [
            'municipio' => $dadosGerais['local_municipio'] ?? null,
            'uf'        => $dadosGerais['local_estadouf'] ?? $dadosGerais['local_uf'] ?? 'MG',
        ];
        $rat->endereco     = [
            'logradouro' => $dadosGerais['local_logradoura_1'] ?? $dadosGerais['local_logradouro'] ?? null,
            'numero'     => $dadosGerais['local_numero'] ?? null,
            'bairro'     => $dadosGerais['local_bairro'] ?? null,
            'cep'        => $dadosGerais['local_cep'] ?? null,
        ];
        $rat->comunicacao  = [
            'data'        => $dadosGerais['com_ocorrencia_data'] ?? null,
            'atendimento' => $dadosGerais['com_ocorrencia_atendimento'] ?? null,
        ];
        $rat->recursos     = $recursos;
        $rat->envolvidos   = $envolvidos;
        $rat->vistoria     = $vistoria ?? [];
        $rat->historico    = $ocorrencia->historico ?? ($dadosGerais['descricao'] ?? null);
        $rat->anexos       = $ocorrencia->anexos ?? [];
        $rat->created_at   = $ocorrencia->created_at;
        $rat->updated_at   = $ocorrencia->updated_at;

        return $rat;
    }
}
