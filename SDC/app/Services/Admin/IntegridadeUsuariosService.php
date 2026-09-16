<?php

declare(strict_types=1);

namespace App\Services\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostico de integridade do cadastro de usuarios.
 *
 * Herda o proposito do "Painel Usuarios" do SDC legado -- achar quem ficou pelo
 * caminho entre as tabelas -- mas com taxonomia reescrita, porque o schema novo
 * matou metade das pendencias antigas por construcao (users.cpf e UNIQUE,
 * vinculo virou FK) e criou outras que o painel velho nem enxergaria.
 *
 * ESTE SERVICO NAO ESCREVE. Sem update, insert ou delete, em nenhum caminho.
 * O motivo e o de sempre: a origem conhecida de vinculo errado neste projeto e
 * o VinculoUsuarioService, que casou usuario com orgao por SIMILARIDADE DE NOME
 * (>= 65%) e pulou o resto em silencio. Um painel que corrigisse em lote
 * usando a mesma classe de heuristica propagaria o erro em vez de expo-lo.
 *
 * Por isso cada regra nasce com um NIVEL DE CONFIANCA, e o nivel decide o peso
 * da linha:
 *
 *   confirmado  - so chave forte: CPF de 11 digitos exatos, FK presente ou
 *                 ausente, contagem. Zero heuristica. Entra no total.
 *   suspeito    - match por nome. A linha carrega a evidencia do que casou.
 *                 Fica separada e NAO entra no total.
 *   informativo - estado legitimo que so parece defeito (status=pending do
 *                 onboarding, created_at nulo herdado da migracao). Contador
 *                 proprio, fora do total.
 *
 * O filtro de escopo importa tanto quanto a regra. "Orgao sem coordenador"
 * sobre a tabela inteira acusa 78 casos; restrito a COMPDEC ativa e nao
 * deletada -- a unica que precisa de coordenador -- acusa 48. Os 30 de
 * diferenca eram REDEC, CEDEC e orgaos excluidos: falso positivo puro.
 */
class IntegridadeUsuariosService
{
    public const NIVEL_CONFIRMADO = 'confirmado';

    public const NIVEL_SUSPEITO = 'suspeito';

    public const NIVEL_INFORMATIVO = 'informativo';

    /**
     * CPF so e chave de juncao com 11 digitos. Abaixo disso o dado do legado
     * nao identifica ninguem e a linha vira pendencia (A1), nunca juncao.
     */
    private const CPF_LIMPO = "regexp_replace(coalesce(%s, ''), '[^0-9]', '', 'g')";

    /**
     * @var array<string, array{eixo: string, nivel: string, titulo: string, descricao: string}>
     */
    public const REGRAS = [
        'A1' => [
            'eixo' => 'Equipe x Conta',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Membro de equipe com CPF ausente ou invalido',
            'descricao' => 'Membro ativo de compdec_equipes cujo CPF nao tem 11 digitos. Sem CPF valido nao ha como ligar o membro a uma conta.',
        ],
        'A2' => [
            'eixo' => 'Equipe x Conta',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Membro de equipe sem conta no sistema',
            'descricao' => 'CPF valido em compdec_equipes que nao existe em users. A pessoa esta na equipe mas nao consegue entrar.',
        ],
        'A3' => [
            'eixo' => 'Equipe x Conta',
            'nivel' => self::NIVEL_SUSPEITO,
            'titulo' => 'Nome divergente entre equipe e conta',
            'descricao' => 'Mesmo CPF, nomes diferentes. Pode ser grafia, abreviacao ou cadastro trocado -- exige leitura humana.',
        ],
        'B1' => [
            'eixo' => 'Conta x Vinculo',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Conta sem vinculo com orgao nenhum',
            'descricao' => 'Sem orgao_principal_id e sem linha no pivot. O sistema nao sabe de qual municipio a pessoa e.',
        ],
        'B2' => [
            'eixo' => 'Conta x Vinculo',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Orgao principal sem linha correspondente no pivot',
            'descricao' => 'users.orgao_principal_id aponta para um orgao que nao tem par em compdec_orgao_user. O app consulta os dois lugares.',
        ],
        'B3' => [
            'eixo' => 'Conta x Vinculo',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Orgao principal divergente do pivot',
            'descricao' => 'A linha marcada is_principal no pivot aponta para outro orgao.',
        ],
        'B4' => [
            'eixo' => 'Conta x Vinculo',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Funcao no pivot diferente da funcao na equipe',
            'descricao' => 'Mesmo CPF, mesmo orgao, funcoes diferentes. O vinculo automatico carimbou quase todo mundo como agente.',
        ],
        'B5' => [
            'eixo' => 'Conta x Vinculo',
            'nivel' => self::NIVEL_SUSPEITO,
            'titulo' => 'Vinculo possivelmente falso da heuristica de nome',
            'descricao' => 'Login no padrao MUNICIPIO+codigo cujo prefixo nao corresponde ao municipio do orgao vinculado.',
        ],
        'C1' => [
            'eixo' => 'Conta x Autorizacao',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'Conta com orgao e sem nenhuma autorizacao',
            'descricao' => 'Tem orgao vinculado, nenhuma role e nenhuma permissao direta. Autentica e cai em 403 na primeira tela.',
        ],
        'C2' => [
            'eixo' => 'Conta x Autorizacao',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'COMPDEC ativa sem coordenador',
            'descricao' => 'Orgao tipo compdec com status ativo e nenhum membro ativo com funcao coordenador.',
        ],
        'C3' => [
            'eixo' => 'Conta x Autorizacao',
            'nivel' => self::NIVEL_CONFIRMADO,
            'titulo' => 'COMPDEC ativa com mais de um coordenador',
            'descricao' => 'O unique do pivot e (orgao, usuario, funcao) e nao impede dois coordenadores no mesmo orgao.',
        ],
        'I1' => [
            'eixo' => 'Informativo',
            'nivel' => self::NIVEL_INFORMATIVO,
            'titulo' => 'Conta pendente de primeiro acesso',
            'descricao' => 'status=pending e estado legitimo do onboarding, nao defeito. Listado para dimensionar, fora do total.',
        ],
        'I2' => [
            'eixo' => 'Informativo',
            'nivel' => self::NIVEL_INFORMATIVO,
            'titulo' => 'Conta sem data de cadastro',
            'descricao' => 'created_at nulo herdado da carga do legado. Cosmetico: a tela mostra 31/12/1969.',
        ],
        'I3' => [
            'eixo' => 'Informativo',
            'nivel' => self::NIVEL_INFORMATIVO,
            'titulo' => 'Conta com e-mail fora do dominio gov.br',
            'descricao' => 'Comum e legitimo em prefeitura pequena. Serve de contexto, nao de pendencia.',
        ],
    ];

    /**
     * Contagem de cada regra, agrupada por eixo, mais os totais por nivel.
     *
     * @return array{eixos: array<string, list<array<string, mixed>>>, totais: array<string, int>}
     */
    public function resumo(): array
    {
        $eixos = [];
        $totais = [
            self::NIVEL_CONFIRMADO => 0,
            self::NIVEL_SUSPEITO => 0,
            self::NIVEL_INFORMATIVO => 0,
        ];

        foreach (self::REGRAS as $id => $regra) {
            $quantidade = $this->query($id)->count();

            $eixos[$regra['eixo']][] = [
                'id' => $id,
                'nivel' => $regra['nivel'],
                'titulo' => $regra['titulo'],
                'descricao' => $regra['descricao'],
                'quantidade' => $quantidade,
            ];

            $totais[$regra['nivel']] += $quantidade;
        }

        return ['eixos' => $eixos, 'totais' => $totais];
    }

    /**
     * Linhas de uma regra. Filtros aceitos: orgao_id, search.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function linhas(string $regraId, array $filtros = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->filtrada($regraId, $filtros)->paginate($perPage)->withQueryString();
    }

    /**
     * Todas as linhas de uma regra, sem paginacao -- para exportacao.
     *
     * Aceita os mesmos filtros da tela de proposito: e o unico jeito de a
     * planilha bater com a tabela que a pessoa tem na frente.
     *
     * @param  array<string, mixed>  $filtros
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function todasAsLinhas(string $regraId, array $filtros = [])
    {
        return $this->filtrada($regraId, $filtros)->get();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function filtrada(string $regraId, array $filtros): Builder
    {
        $query = $this->query($regraId);

        if (! empty($filtros['orgao_id'])) {
            $query->where('orgao_id', (int) $filtros['orgao_id']);
        }

        if (! empty($filtros['search'])) {
            $termo = '%'.mb_strtoupper((string) $filtros['search']).'%';
            $query->whereRaw('upper(nome) like ?', [$termo]);
        }

        return $query->orderBy('municipio')->orderBy('nome');
    }

    public static function regraExiste(string $regraId): bool
    {
        return array_key_exists($regraId, self::REGRAS);
    }

    /**
     * Cada regra devolve as mesmas colunas, para a tela ser uma so:
     * escopo, ref_id, nome, documento, email, orgao_id, orgao_nome, municipio, evidencia.
     *
     * As consultas sao construidas como subquery (fromSub) para que count,
     * filtro e paginacao funcionem sobre o conjunto ja normalizado, sem
     * reescrever cada regra tres vezes.
     */
    private function query(string $regraId): Builder
    {
        $interna = match ($regraId) {
            'A1' => $this->regraA1(),
            'A2' => $this->regraA2(),
            'A3' => $this->regraA3(),
            'B1' => $this->regraB1(),
            'B2' => $this->regraB2(),
            'B3' => $this->regraB3(),
            'B4' => $this->regraB4(),
            'B5' => $this->regraB5(),
            'C1' => $this->regraC1(),
            'C2' => $this->regraC2(),
            'C3' => $this->regraC3(),
            'I1' => $this->regraI1(),
            'I2' => $this->regraI2(),
            'I3' => $this->regraI3(),
            default => throw new \InvalidArgumentException("Regra de integridade desconhecida: {$regraId}"),
        };

        return DB::query()->fromSub($interna, 'linhas');
    }

    private function cpfLimpo(string $coluna): string
    {
        return sprintf(self::CPF_LIMPO, $coluna);
    }

    /**
     * Equipes ativas de orgaos vivos. Base dos eixos A e das regras de equipe:
     * membro excluido ou de orgao excluido nao e pendencia de ninguem.
     */
    private function equipesVivas(): Builder
    {
        return DB::table('compdec_equipes as e')
            ->join('compdec_orgaos as o', 'o.id', '=', 'e.orgao_id')
            ->leftJoin('municipios as m', 'm.id', '=', 'o.municipio_id')
            ->whereNull('e.deleted_at')
            ->whereNull('o.deleted_at')
            ->where('e.ativo', true);
    }

    private function regraA1(): Builder
    {
        return $this->equipesVivas()
            ->whereRaw('length('.$this->cpfLimpo('e.cpf').') <> 11')
            ->selectRaw("
                'equipe' as escopo,
                e.id as ref_id,
                e.nome as nome,
                coalesce(nullif(btrim(e.cpf), ''), '(vazio)') as documento,
                e.email as email,
                o.id as orgao_id,
                o.nome as orgao_nome,
                coalesce(m.nome, '(sem municipio)') as municipio,
                'funcao ' || e.funcao || ' | cpf gravado: ' || coalesce(nullif(btrim(e.cpf), ''), '(vazio)') as evidencia
            ");
    }

    private function regraA2(): Builder
    {
        return $this->equipesVivas()
            ->whereRaw('length('.$this->cpfLimpo('e.cpf').') = 11')
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('users as u')
                    ->whereNull('u.deleted_at')
                    ->whereRaw('u.cpf = '.$this->cpfLimpo('e.cpf'));
            })
            ->selectRaw("
                'equipe' as escopo,
                e.id as ref_id,
                e.nome as nome,
                ".$this->cpfLimpo('e.cpf')." as documento,
                e.email as email,
                o.id as orgao_id,
                o.nome as orgao_nome,
                coalesce(m.nome, '(sem municipio)') as municipio,
                'funcao ' || e.funcao || ' | nenhuma conta com este CPF' as evidencia
            ");
    }

    private function regraA3(): Builder
    {
        return $this->equipesVivas()
            ->join('users as u', function ($join): void {
                $join->whereRaw('u.cpf = '.$this->cpfLimpo('e.cpf'))
                    ->whereNull('u.deleted_at');
            })
            ->whereRaw('upper(btrim(e.nome)) <> upper(btrim(u.name))')
            ->selectRaw("
                'equipe' as escopo,
                e.id as ref_id,
                e.nome as nome,
                u.cpf as documento,
                coalesce(e.email, u.email) as email,
                o.id as orgao_id,
                o.nome as orgao_nome,
                coalesce(m.nome, '(sem municipio)') as municipio,
                'equipe: ' || e.nome || '  |  conta #' || u.id || ': ' || u.name as evidencia
            ");
    }

    /**
     * Base dos eixos B e C: contas vivas, com o orgao principal quando houver.
     */
    private function usuariosVivos(): Builder
    {
        return DB::table('users as u')
            ->leftJoin('compdec_orgaos as o', 'o.id', '=', 'u.orgao_principal_id')
            ->leftJoin('municipios as m', 'm.id', '=', 'o.municipio_id')
            ->whereNull('u.deleted_at');
    }

    /**
     * @param  string  $evidencia  expressao SQL ja montada
     */
    private function colunasDeUsuario(string $evidencia): string
    {
        return "
            'usuario' as escopo,
            u.id as ref_id,
            u.name as nome,
            u.cpf as documento,
            u.email as email,
            coalesce(o.id, 0) as orgao_id,
            coalesce(o.nome, '(sem orgao)') as orgao_nome,
            coalesce(m.nome, '(sem municipio)') as municipio,
            {$evidencia} as evidencia
        ";
    }

    private function regraB1(): Builder
    {
        return $this->usuariosVivos()
            ->whereNull('u.orgao_principal_id')
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('compdec_orgao_user as p')
                    ->whereColumn('p.user_id', 'u.id');
            })
            ->selectRaw($this->colunasDeUsuario("'status ' || u.status || ' | sem orgao principal e sem pivot'"));
    }

    private function regraB2(): Builder
    {
        return $this->usuariosVivos()
            ->whereNotNull('u.orgao_principal_id')
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('compdec_orgao_user as p')
                    ->whereColumn('p.user_id', 'u.id')
                    ->whereColumn('p.orgao_id', 'u.orgao_principal_id');
            })
            ->selectRaw($this->colunasDeUsuario("'orgao principal #' || u.orgao_principal_id || ' sem par no pivot'"));
    }

    private function regraB3(): Builder
    {
        return $this->usuariosVivos()
            ->join('compdec_orgao_user as p', function ($join): void {
                $join->on('p.user_id', '=', 'u.id')->where('p.is_principal', true);
            })
            ->whereRaw('u.orgao_principal_id is distinct from p.orgao_id')
            ->selectRaw($this->colunasDeUsuario(
                "'users aponta #' || coalesce(u.orgao_principal_id::text, 'nulo') || ' | pivot aponta #' || p.orgao_id"
            ));
    }

    /**
     * A divergencia de funcao acontece no orgao do PIVOT, que nem sempre e o
     * orgao principal da conta. Reportar a partir de `usuariosVivos()` mostra
     * o orgao errado -- ou "(sem orgao)" com id 0, o que ainda tira a acao de
     * abrir a tela. Hoje as 358 linhas da base coincidem, mas a regra existe
     * justamente para achar quem nao coincide.
     */
    private function regraB4(): Builder
    {
        return DB::table('users as u')
            ->join('compdec_orgao_user as p', 'p.user_id', '=', 'u.id')
            ->join('compdec_orgaos as o', 'o.id', '=', 'p.orgao_id')
            ->leftJoin('municipios as m', 'm.id', '=', 'o.municipio_id')
            ->join('compdec_equipes as e', function ($join): void {
                $join->on('e.orgao_id', '=', 'p.orgao_id')
                    ->whereNull('e.deleted_at')
                    ->where('e.ativo', true)
                    ->whereRaw('u.cpf = '.$this->cpfLimpo('e.cpf'));
            })
            ->whereNull('u.deleted_at')
            ->whereRaw('p.funcao <> e.funcao')
            ->selectRaw($this->colunasDeUsuario("'pivot: ' || p.funcao || '  |  equipe: ' || e.funcao"));
    }

    /**
     * O prefixo do login e truncado em ~10 caracteres, entao similaridade
     * trigram nao serve aqui: ABADIADOSD x ABADIADOSDOURADOS pontua baixo e
     * acusaria 469 contas legitimas. O teste certo e de PREFIXO sobre o nome
     * normalizado -- sem acento e sem pontuacao, porque Olhos-d'Agua vira
     * OLHOSDAGUA no login. Com isso sobram 7 candidatos, todos para leitura
     * humana. Continua sendo `suspeito`: o proprio VinculoUsuarioService
     * admite que o meio do vao e ambiguo.
     */
    private function regraB5(): Builder
    {
        $prefixo = "upper(regexp_replace(unaccent(substring(u.name from '^(.+?)[0-9]+$')), '[^A-Za-z]', '', 'g'))";
        $municipio = "upper(regexp_replace(unaccent(m.nome), '[^A-Za-z]', '', 'g'))";

        return $this->usuariosVivos()
            ->whereNotNull('u.orgao_principal_id')
            ->whereNotNull('m.nome')
            ->whereRaw("u.name ~ '^(.+?)[0-9]+$'")
            ->whereRaw("{$municipio} not like {$prefixo} || '%'")
            ->selectRaw($this->colunasDeUsuario(
                "'login ' || u.name || ' nao casa com o municipio ' || m.nome"
            ));
    }

    private function regraC1(): Builder
    {
        return $this->usuariosVivos()
            ->whereNotNull('u.orgao_principal_id')
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('model_has_roles as mr')
                    ->whereColumn('mr.model_id', 'u.id')
                    ->where('mr.model_type', 'App\\Models\\User');
            })
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('model_has_permissions as mp')
                    ->whereColumn('mp.model_id', 'u.id')
                    ->where('mp.model_type', 'App\\Models\\User');
            })
            ->selectRaw($this->colunasDeUsuario("'status ' || u.status || ' | sem role e sem permissao direta'"));
    }

    /**
     * Orgaos que precisam de coordenador: COMPDEC ativa e nao excluida.
     * REDEC, CEDEC e orgaos excluidos ficam de fora -- incluir os tres
     * inflava o resultado de 48 para 78 sem que nenhum deles fosse defeito.
     */
    private function compdecsAtivas(): Builder
    {
        return DB::table('compdec_orgaos as o')
            ->leftJoin('municipios as m', 'm.id', '=', 'o.municipio_id')
            ->whereNull('o.deleted_at')
            ->where('o.tipo', 'compdec')
            ->where('o.status', 'ativo');
    }

    private function regraC2(): Builder
    {
        return $this->compdecsAtivas()
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('compdec_equipes as e')
                    ->whereColumn('e.orgao_id', 'o.id')
                    ->whereNull('e.deleted_at')
                    ->where('e.ativo', true)
                    ->where('e.funcao', 'coordenador');
            })
            ->selectRaw("
                'orgao' as escopo,
                o.id as ref_id,
                o.nome as nome,
                coalesce(o.responsavel_cpf, '') as documento,
                o.email as email,
                o.id as orgao_id,
                o.nome as orgao_nome,
                coalesce(m.nome, '(sem municipio)') as municipio,
                'nenhum membro ativo com funcao coordenador' as evidencia
            ");
    }

    private function regraC3(): Builder
    {
        $contagem = DB::table('compdec_equipes as e')
            ->select('e.orgao_id', DB::raw('count(*) as total'))
            ->whereNull('e.deleted_at')
            ->where('e.ativo', true)
            ->where('e.funcao', 'coordenador')
            ->groupBy('e.orgao_id')
            ->havingRaw('count(*) > 1');

        return $this->compdecsAtivas()
            ->joinSub($contagem, 'c', 'c.orgao_id', '=', 'o.id')
            ->selectRaw("
                'orgao' as escopo,
                o.id as ref_id,
                o.nome as nome,
                coalesce(o.responsavel_cpf, '') as documento,
                o.email as email,
                o.id as orgao_id,
                o.nome as orgao_nome,
                coalesce(m.nome, '(sem municipio)') as municipio,
                c.total || ' coordenadores ativos no mesmo orgao' as evidencia
            ");
    }

    private function regraI1(): Builder
    {
        return $this->usuariosVivos()
            ->where('u.status', 'pending')
            ->selectRaw($this->colunasDeUsuario("'aguardando primeiro acesso'"));
    }

    private function regraI2(): Builder
    {
        return $this->usuariosVivos()
            ->whereNull('u.created_at')
            ->selectRaw($this->colunasDeUsuario("'created_at nulo (carga do legado)'"));
    }

    private function regraI3(): Builder
    {
        return $this->usuariosVivos()
            ->whereRaw("u.email not like '%.gov.br'")
            ->selectRaw($this->colunasDeUsuario("'dominio externo: ' || split_part(u.email, '@', 2)"));
    }
}
