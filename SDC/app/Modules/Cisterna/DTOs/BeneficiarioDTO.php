<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;

final readonly class BeneficiarioDTO
{
    /**
     * @param  array<int, array{responsavel: string, descricao: ?string}>  $atendimentosPipa
     */
    public function __construct(
        public string $cpf,
        public string $nome,
        public int $municipioId,
        public SituacaoAnalise $situacaoAnalise,
        public SituacaoObra $situacaoObra,
        public ?string $telefone = null,
        public ?string $dataNascimento = null,
        public ?string $cadastroUnico = null,
        public ?int $comunidadeId = null,
        public ?string $endereco = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $ordemServicoId = null,
        public ?string $situacaoAnaliseObs = null,
        public ?int $ranqueamentoOrdem = null,
        public ?int $qtdPessoas = null,
        public ?float $renda = null,
        public ?float $rendaPerCapita = null,
        public ?bool $possuiDeficiencia = null,
        public ?bool $possuiCrianca = null,
        public ?string $dataNascimentoCrianca = null,
        public ?bool $possuiIdoso = null,
        public ?bool $chefiadaMulher = null,
        public ?string $tipoMoradia = null,
        public ?string $tipoMoradiaOutro = null,
        public ?float $comprimentoTelhado = null,
        public ?float $larguraTelhado = null,
        public ?float $areaTelhado = null,
        public ?float $comprimentoTestada = null,
        public ?int $numCaidasTelhado = null,
        public ?string $coberturaTelhado = null,
        public ?string $coberturaOutro = null,
        public ?bool $possuiFogaoLenha = null,
        public ?float $medidaTelhadoAreaFogao = null,
        public ?float $testadaDispParteFogao = null,
        public ?bool $atendidoPorPipa = null,
        public ?string $agenteNome = null,
        public ?string $agenteCpf = null,
        public ?string $engenheiroNome = null,
        public ?string $engenheiroCrea = null,
        public ?string $observacoes = null,
        public ?int $legacyId = null,
        private array $atendimentosPipa = [],
    ) {}

    /**
     * @param  array<string, mixed>  $d  Ja validado e normalizado pelo FormRequest.
     */
    public static function deValidados(array $d): self
    {
        // area_telhado e derivada quando nao vem informada, como no legado.
        $area = $d['area_telhado'] ?? null;
        if ($area === null && isset($d['comprimento_telhado'], $d['largura_telhado'])) {
            $area = round((float) $d['comprimento_telhado'] * (float) $d['largura_telhado'], 2);
        }

        $renda = isset($d['renda']) ? (float) $d['renda'] : null;
        $pessoas = isset($d['qtd_pessoas']) ? (int) $d['qtd_pessoas'] : null;

        // renda_per_capita e derivada quando nao vem informada.
        $perCapita = $d['renda_per_capita'] ?? null;
        if ($perCapita === null && $renda !== null && $pessoas !== null && $pessoas > 0) {
            $perCapita = round($renda / $pessoas, 2);
        }

        return new self(
            cpf: (string) $d['cpf'],
            nome: (string) $d['nome'],
            municipioId: (int) $d['municipio_id'],
            situacaoAnalise: SituacaoAnalise::from((string) ($d['situacao_analise'] ?? SituacaoAnalise::EM_EDICAO->value)),
            situacaoObra: SituacaoObra::from((string) ($d['situacao_obra'] ?? SituacaoObra::PROCESSAMENTO->value)),
            telefone: $d['telefone'] ?? null,
            dataNascimento: $d['data_nascimento'] ?? null,
            cadastroUnico: $d['cadastro_unico'] ?? null,
            comunidadeId: isset($d['comunidade_id']) ? (int) $d['comunidade_id'] : null,
            endereco: $d['endereco'] ?? null,
            latitude: isset($d['latitude']) ? (float) $d['latitude'] : null,
            longitude: isset($d['longitude']) ? (float) $d['longitude'] : null,
            ordemServicoId: isset($d['ordem_servico_id']) ? (int) $d['ordem_servico_id'] : null,
            situacaoAnaliseObs: $d['situacao_analise_obs'] ?? null,
            ranqueamentoOrdem: isset($d['ranqueamento_ordem']) ? (int) $d['ranqueamento_ordem'] : null,
            qtdPessoas: $pessoas,
            renda: $renda,
            rendaPerCapita: $perCapita === null ? null : (float) $perCapita,
            possuiDeficiencia: $d['possui_deficiencia'] ?? null,
            possuiCrianca: $d['possui_crianca'] ?? null,
            dataNascimentoCrianca: ($d['possui_crianca'] ?? false) ? ($d['data_nascimento_crianca'] ?? null) : null,
            possuiIdoso: $d['possui_idoso'] ?? null,
            chefiadaMulher: $d['chefiada_mulher'] ?? null,
            tipoMoradia: $d['tipo_moradia'] ?? null,
            tipoMoradiaOutro: $d['tipo_moradia_outro'] ?? null,
            comprimentoTelhado: isset($d['comprimento_telhado']) ? (float) $d['comprimento_telhado'] : null,
            larguraTelhado: isset($d['largura_telhado']) ? (float) $d['largura_telhado'] : null,
            areaTelhado: $area === null ? null : (float) $area,
            comprimentoTestada: isset($d['comprimento_testada']) ? (float) $d['comprimento_testada'] : null,
            numCaidasTelhado: isset($d['num_caidas_telhado']) ? (int) $d['num_caidas_telhado'] : null,
            coberturaTelhado: $d['cobertura_telhado'] ?? null,
            coberturaOutro: $d['cobertura_outro'] ?? null,
            possuiFogaoLenha: $d['possui_fogao_lenha'] ?? null,
            medidaTelhadoAreaFogao: isset($d['medida_telhado_area_fogao']) ? (float) $d['medida_telhado_area_fogao'] : null,
            testadaDispParteFogao: isset($d['testada_disp_parte_fogao']) ? (float) $d['testada_disp_parte_fogao'] : null,
            atendidoPorPipa: $d['atendido_por_pipa'] ?? null,
            agenteNome: $d['agente_nome'] ?? null,
            agenteCpf: $d['agente_cpf'] ?? null,
            engenheiroNome: $d['engenheiro_nome'] ?? null,
            engenheiroCrea: $d['engenheiro_crea'] ?? null,
            observacoes: $d['observacoes'] ?? null,
            legacyId: isset($d['legacy_id']) ? (int) $d['legacy_id'] : null,
            atendimentosPipa: self::extrairAtendimentos($d),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cpf' => $this->cpf,
            'nome' => $this->nome,
            'telefone' => $this->telefone,
            'data_nascimento' => $this->dataNascimento,
            'cadastro_unico' => $this->cadastroUnico,
            'municipio_id' => $this->municipioId,
            'comunidade_id' => $this->comunidadeId,
            'endereco' => $this->endereco,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ordem_servico_id' => $this->ordemServicoId,
            'situacao_analise' => $this->situacaoAnalise->value,
            'situacao_analise_obs' => $this->situacaoAnaliseObs,
            'situacao_obra' => $this->situacaoObra->value,
            'ranqueamento_ordem' => $this->ranqueamentoOrdem,
            'qtd_pessoas' => $this->qtdPessoas,
            'renda' => $this->renda,
            'renda_per_capita' => $this->rendaPerCapita,
            'possui_deficiencia' => $this->possuiDeficiencia,
            'possui_crianca' => $this->possuiCrianca,
            'data_nascimento_crianca' => $this->dataNascimentoCrianca,
            'possui_idoso' => $this->possuiIdoso,
            'chefiada_mulher' => $this->chefiadaMulher,
            'tipo_moradia' => $this->tipoMoradia,
            'tipo_moradia_outro' => $this->tipoMoradiaOutro,
            'comprimento_telhado' => $this->comprimentoTelhado,
            'largura_telhado' => $this->larguraTelhado,
            'area_telhado' => $this->areaTelhado,
            'comprimento_testada' => $this->comprimentoTestada,
            'num_caidas_telhado' => $this->numCaidasTelhado,
            'cobertura_telhado' => $this->coberturaTelhado,
            'cobertura_outro' => $this->coberturaOutro,
            'possui_fogao_lenha' => $this->possuiFogaoLenha,
            'medida_telhado_area_fogao' => $this->medidaTelhadoAreaFogao,
            'testada_disp_parte_fogao' => $this->testadaDispParteFogao,
            'atendido_por_pipa' => $this->atendidoPorPipa,
            'agente_nome' => $this->agenteNome,
            'agente_cpf' => $this->agenteCpf,
            'engenheiro_nome' => $this->engenheiroNome,
            'engenheiro_crea' => $this->engenheiroCrea,
            'observacoes' => $this->observacoes,
            'legacy_id' => $this->legacyId,
        ];
    }

    /**
     * @return array<int, array{responsavel: string, descricao: ?string}>
     */
    public function atendimentosPipa(): array
    {
        return $this->atendimentosPipa;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<int, array{responsavel: string, descricao: ?string}>
     */
    private static function extrairAtendimentos(array $d): array
    {
        $selecionados = $d['responsaveis_pipa'] ?? [];
        if (! is_array($selecionados)) {
            return [];
        }

        $descricao = $d['atendimento_pipa_outro'] ?? null;
        $linhas = [];

        foreach ($selecionados as $valor) {
            $responsavel = ResponsavelPipa::tryFrom((string) $valor);
            if ($responsavel === null) {
                continue;
            }

            $linhas[] = [
                'responsavel' => $responsavel->value,
                'descricao' => $responsavel === ResponsavelPipa::OUTROS ? $descricao : null,
            ];
        }

        return $linhas;
    }
}
