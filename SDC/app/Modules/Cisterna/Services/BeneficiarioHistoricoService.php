<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Enums\SituacaoAnalise;
use App\Modules\Cisterna\Enums\SituacaoObra;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use Illuminate\Support\Collection;

/**
 * Serie historica do beneficiario, no mesmo contrato que o modal do PAE espera:
 * { timeline, vistorias, notificacoes }.
 *
 * DERIVADA, e nao lida de uma tabela de eventos. O PAE tem
 * `pae_protocolo_timeline` porque nasceu registrando evento; o Cisterna nasceu
 * de uma migracao de 8.096 cadastros que ja tinham vistoria, notificacao e
 * ordem de servico. Uma tabela de eventos alimentada so dali para frente abriria
 * o historico VAZIO para todos eles -- a informacao existe, so nao no formato de
 * evento.
 *
 * Entao a linha do tempo e montada de quatro origens, e o audit_logs cobre
 * apenas o que nao da para deduzir do proprio dado (mudanca de situacao e
 * movimentacao entre ordens), que o observer passou a gravar.
 */
class BeneficiarioHistoricoService
{
    public function __construct(
        private readonly VistoriaService $vistorias,
    ) {}

    /**
     * @return array{timeline: array<int, array<string, mixed>>, vistorias: array<int, array<string, mixed>>, notificacoes: array<int, array<string, mixed>>}
     */
    public function para(CisternaBeneficiario $beneficiario): array
    {
        $beneficiario->loadMissing([
            'vistorias' => fn ($q) => $q->orderBy('created_at'),
            'notificacoes' => fn ($q) => $q->orderBy('created_at'),
            'ordemServico:id,nome',
        ]);

        $auditoria = $this->auditoria($beneficiario);

        $eventos = collect()
            ->concat($this->eventoDeCadastro($beneficiario, $auditoria))
            ->concat($this->eventosDeVistoria($beneficiario))
            ->concat($this->eventosDeNotificacao($beneficiario))
            ->concat($this->eventosDeAuditoria($auditoria))
            // Mais recente primeiro: quem abre o historico quer saber o que
            // aconteceu por ultimo, nao reler o cadastro desde o inicio.
            ->sortByDesc('dataISO')
            ->values();

        return [
            'cadeia' => $this->cadeiaDeFiscalizacao($beneficiario),
            'timeline' => $eventos->all(),
            'vistorias' => $this->detalheDasVistorias($beneficiario),
            'notificacoes' => $this->detalheDasNotificacoes($beneficiario),
        ];
    }

    /**
     * As tres etapas com o estado de cada uma, resolvido AQUI e nao na tela.
     *
     * Qual etapa esta liberada e regra de dominio: o COMPDEC so confere depois
     * do fornecedor, e a CEDEC so fiscaliza depois do COMPDEC. Reimplementar
     * isso no cliente criaria uma segunda versao da regra, livre para divergir.
     *
     * `em_aberto` e diferente de `concluida` de proposito: relatorio salvo pela
     * metade e caso comum na carga real, e tratar os dois como iguais mostraria
     * a cadeia mais adiantada do que ela esta.
     *
     * @return array{etapas: array<int, array<string, mixed>>, etapa_disponivel: ?string}
     */
    private function cadeiaDeFiscalizacao(CisternaBeneficiario $beneficiario): array
    {
        $disponivel = $this->vistorias->etapaDisponivel($beneficiario)?->value;

        $etapas = [];

        foreach (EtapaVistoria::cases() as $etapa) {
            $vistoria = $beneficiario->vistorias->first(
                fn ($v): bool => ($v->etapa instanceof EtapaVistoria ? $v->etapa->value : (string) $v->etapa) === $etapa->value,
            );

            $estado = match (true) {
                $vistoria !== null && $vistoria->concluida_em !== null => 'concluida',
                $vistoria !== null => 'em_aberto',
                $etapa->value === $disponivel => 'disponivel',
                default => 'bloqueada',
            };

            $etapas[] = [
                'valor' => $etapa->value,
                'rotulo' => $etapa->label(),
                'estado' => $estado,
                'vistoria_id' => $vistoria?->id,
                'numero_instalacao' => $vistoria?->numero_instalacao,
                'data' => $this->formatar($vistoria?->concluida_em ?? $vistoria?->data_relatorio),
                'engenheiro' => $vistoria?->engenheiro_nome,
            ];
        }

        return ['etapas' => $etapas, 'etapa_disponivel' => $disponivel];
    }

    /**
     * Uma consulta so para toda a auditoria do registro, reusada por dois
     * metodos: sem isto seriam duas varreduras na audit_logs por abertura do
     * modal.
     *
     * @return Collection<int, AuditLog>
     */
    private function auditoria(CisternaBeneficiario $beneficiario): Collection
    {
        return AuditLog::query()
            ->where('table_name', $beneficiario->getTable())
            ->where('row_id', $beneficiario->getKey())
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  Collection<int, AuditLog>  $auditoria
     * @return array<int, array<string, mixed>>
     */
    private function eventoDeCadastro(CisternaBeneficiario $beneficiario, Collection $auditoria): array
    {
        $insercao = $auditoria->firstWhere('event', 'insert');

        // Cadastro migrado do legado nao tem linha de auditoria: o evento de
        // entrada vem do proprio created_at, senao a serie de 8.096 registros
        // comecaria sem inicio.
        $quando = $insercao?->created_at ?? $beneficiario->created_at;

        if ($quando === null) {
            return [];
        }

        return [$this->evento(
            id: 'cadastro',
            tipo: 'criacao',
            titulo: 'Cadastro criado',
            descricao: $insercao === null
                ? 'Cadastro importado do sistema legado.'
                : 'Beneficiario cadastrado no SDC.',
            quando: $quando,
            responsavel: $this->nomeDoUsuario($insercao?->user_id ?? $beneficiario->created_by),
        )];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eventosDeVistoria(CisternaBeneficiario $beneficiario): array
    {
        $eventos = [];

        foreach ($beneficiario->vistorias as $vistoria) {
            $etapa = $vistoria->etapa instanceof EtapaVistoria
                ? $vistoria->etapa
                : EtapaVistoria::tryFrom((string) $vistoria->etapa);

            $rotulo = $etapa?->label() ?? (string) $vistoria->etapa;

            // `data_relatorio` na frente de `created_at`: nos 8.096 cadastros
            // migrados o created_at e a data da CARGA, nao do relatorio. Usar
            // created_at colocava a conclusao (que guarda a data legada real)
            // antes da abertura na linha do tempo -- o historico inteiro dos
            // registros antigos aparecia de tras para frente.
            $eventos[] = $this->evento(
                id: "vistoria-{$vistoria->id}-abertura",
                tipo: 'vistoria',
                titulo: "Relatorio aberto: {$rotulo}",
                descricao: $vistoria->numero_instalacao
                    ? "Nº de instalacao {$vistoria->numero_instalacao}."
                    : 'Etapa iniciada, ainda sem numero de instalacao.',
                quando: $vistoria->data_relatorio ?? $vistoria->created_at,
                responsavel: $this->nomeDoUsuario($vistoria->created_by),
            );

            if ($vistoria->concluida_em === null) {
                continue;
            }

            // A conclusao e o que destrava a etapa seguinte da cadeia, entao ela
            // e um evento proprio e nao um detalhe da abertura.
            $eventos[] = $this->evento(
                id: "vistoria-{$vistoria->id}-conclusao",
                tipo: 'conclusao',
                titulo: "Etapa concluida: {$rotulo}",
                descricao: $vistoria->engenheiro_nome
                    ? "Responsavel tecnico: {$vistoria->engenheiro_nome}."
                    : 'Etapa concluida.',
                quando: $vistoria->concluida_em,
                responsavel: $this->nomeDoUsuario($vistoria->created_by),
            );
        }

        return $eventos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eventosDeNotificacao(CisternaBeneficiario $beneficiario): array
    {
        $eventos = [];

        foreach ($beneficiario->notificacoes as $notificacao) {
            $eventos[] = $this->evento(
                id: "notificacao-{$notificacao->id}",
                tipo: 'notificacao',
                titulo: 'Notificacao emitida',
                descricao: $notificacao->observacao,
                quando: $notificacao->created_at,
                responsavel: $this->nomeDoUsuario($notificacao->created_by),
            );

            if (! $notificacao->respondida || $notificacao->respondida_em === null) {
                continue;
            }

            $eventos[] = $this->evento(
                id: "notificacao-{$notificacao->id}-resposta",
                tipo: 'resposta',
                titulo: 'Notificacao respondida',
                descricao: 'Apontamento marcado como respondido.',
                quando: $notificacao->respondida_em,
                responsavel: $this->nomeDoUsuario($notificacao->created_by),
            );
        }

        return $eventos;
    }

    /**
     * Mudanca de situacao e movimentacao de ordem: o unico trecho que depende do
     * observer, porque nao ha como deduzir do estado atual QUANDO a situacao
     * mudou nem de onde veio.
     *
     * @param  Collection<int, AuditLog>  $auditoria
     * @return array<int, array<string, mixed>>
     */
    private function eventosDeAuditoria(Collection $auditoria): array
    {
        $eventos = [];

        foreach ($auditoria->where('event', 'update') as $linha) {
            $antes = (array) ($linha->old_values ?? []);
            $depois = (array) ($linha->new_values ?? []);

            foreach ($depois as $coluna => $valor) {
                $descricao = $this->descreverMudanca($coluna, $antes[$coluna] ?? null, $valor);

                if ($descricao === null) {
                    continue;
                }

                $eventos[] = $this->evento(
                    id: "auditoria-{$linha->id}-{$coluna}",
                    tipo: $coluna === 'ordem_servico_id' ? 'alocacao' : 'situacao',
                    titulo: $this->tituloDaMudanca($coluna),
                    descricao: $descricao,
                    quando: $linha->created_at,
                    responsavel: $this->nomeDoUsuario($linha->user_id),
                );
            }
        }

        return $eventos;
    }

    private function tituloDaMudanca(string $coluna): string
    {
        return match ($coluna) {
            'situacao_analise' => 'Situacao da analise alterada',
            'situacao_obra' => 'Situacao da obra alterada',
            'ordem_servico_id' => 'Alocacao em ordem de servico',
            default => 'Cadastro atualizado',
        };
    }

    private function descreverMudanca(string $coluna, mixed $de, mixed $para): ?string
    {
        if ($de === $para) {
            return null;
        }

        return match ($coluna) {
            'situacao_analise' => sprintf(
                '%s -> %s',
                $this->rotuloDeAnalise($de),
                $this->rotuloDeAnalise($para),
            ),
            'situacao_obra' => sprintf(
                '%s -> %s',
                $this->rotuloDeObra($de),
                $this->rotuloDeObra($para),
            ),
            'ordem_servico_id' => $para === null
                ? 'Beneficiario retirado da ordem de servico.'
                : sprintf('Beneficiario alocado na ordem de servico #%s.', $para),
            default => null,
        };
    }

    private function rotuloDeAnalise(mixed $valor): string
    {
        return $valor === null
            ? 'nao definida'
            : (SituacaoAnalise::tryFrom((string) $valor)?->label() ?? (string) $valor);
    }

    private function rotuloDeObra(mixed $valor): string
    {
        return $valor === null
            ? 'nao definida'
            : (SituacaoObra::tryFrom((string) $valor)?->label() ?? (string) $valor);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detalheDasVistorias(CisternaBeneficiario $beneficiario): array
    {
        return $beneficiario->vistorias->map(function ($vistoria): array {
            $etapa = $vistoria->etapa instanceof EtapaVistoria
                ? $vistoria->etapa
                : EtapaVistoria::tryFrom((string) $vistoria->etapa);

            return [
                'id' => $vistoria->id,
                'etapa' => $etapa?->value ?? (string) $vistoria->etapa,
                'titulo' => $etapa?->label() ?? (string) $vistoria->etapa,
                'concluida' => $vistoria->concluida_em !== null,
                'data' => $this->formatar($vistoria->concluida_em ?? $vistoria->created_at),
                'numero_instalacao' => $vistoria->numero_instalacao,
                'engenheiro' => $vistoria->engenheiro_nome,
                'descricao' => $vistoria->observacoes,
                'responsavel' => $this->nomeDoUsuario($vistoria->created_by),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function detalheDasNotificacoes(CisternaBeneficiario $beneficiario): array
    {
        return $beneficiario->notificacoes->map(fn ($notificacao): array => [
            'id' => $notificacao->id,
            'titulo' => $notificacao->respondida ? 'Notificacao respondida' : 'Notificacao em aberto',
            'respondida' => (bool) $notificacao->respondida,
            'data' => $this->formatar($notificacao->created_at),
            'respondida_em' => $this->formatar($notificacao->respondida_em),
            'descricao' => $notificacao->observacao,
            'responsavel' => $this->nomeDoUsuario($notificacao->created_by),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function evento(
        string $id,
        string $tipo,
        string $titulo,
        ?string $descricao,
        mixed $quando,
        string $responsavel,
    ): array {
        return [
            'id' => $id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descricao' => $descricao,
            // dataISO ordena, data e exibida. Ordenar pelo texto formatado
            // colocaria 01/12 antes de 02/01.
            'dataISO' => $quando?->toIso8601String(),
            'data' => $this->formatar($quando),
            'responsavel' => $responsavel,
        ];
    }

    private function formatar(mixed $quando): ?string
    {
        return $quando?->format('d/m/Y, H:i');
    }

    /**
     * Nomes resolvidos em memoria e cacheados por instancia: uma serie com trinta
     * eventos costuma ter dois ou tres autores, e consultar por evento seria
     * N+1 na abertura do modal.
     *
     * @var array<int, string>
     */
    private array $nomes = [];

    private function nomeDoUsuario(?int $id): string
    {
        if ($id === null) {
            return 'Sistema';
        }

        return $this->nomes[$id] ??= User::withTrashed()->find($id)?->name ?? 'Usuario removido';
    }
}
