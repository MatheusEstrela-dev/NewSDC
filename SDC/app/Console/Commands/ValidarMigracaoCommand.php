<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Valida os dados trazidos do dump de producao (dbsdc) para o ambiente novo.
 *
 * Somente leitura por padrao. Cada verificacao vira uma linha OK / ALERTA /
 * ERRO; ERRO e o que impede alguem de usar o sistema (nao consegue logar,
 * perde o cargo, token nao autentica) e faz o comando sair com falha, para
 * poder rodar como portao depois da carga. Os detalhes trazem so IDs, nunca
 * nome, e-mail ou CPF.
 *
 * Unica escrita, opcional: --forcar-primeiro-acesso marca para troca de senha
 * quem veio do legado e ainda nao trocou a senha no sistema novo.
 */
class ValidarMigracaoCommand extends Command
{
    protected $signature = 'migracao:validar
                            {--secao=* : Secoes a validar: usuarios, cargos, tokens, primeiro-acesso, decretacoes, pmda (padrao: todas)}
                            {--forcar-primeiro-acesso : Marca must_change_password nos ativos que nunca trocaram a senha no sistema novo}
                            {--limite=20 : Quantos IDs mostrar por verificacao}';

    protected $description = 'Valida usuarios, cargos, tokens, primeiro acesso, decretacoes e PMDA apos a migracao do dump de producao.';

    private const SECOES = ['usuarios', 'cargos', 'tokens', 'primeiro-acesso', 'decretacoes', 'pmda'];

    private const OK = 'OK';
    private const ALERTA = 'ALERTA';
    private const ERRO = 'ERRO';

    /** @var array<int, array{0: string, 1: string, 2: int, 3: string}> */
    private array $linhas = [];

    public function handle(): int
    {
        $secoes = $this->option('secao') ?: self::SECOES;
        $invalidas = array_diff($secoes, self::SECOES);

        if ($invalidas !== []) {
            $this->error('Secao desconhecida: ' . implode(', ', $invalidas) . '. Validas: ' . implode(', ', self::SECOES));

            return self::INVALID;
        }

        foreach ($secoes as $secao) {
            match ($secao) {
                'usuarios'        => $this->validarUsuarios(),
                'cargos'          => $this->validarCargos(),
                'tokens'          => $this->validarTokens(),
                'primeiro-acesso' => $this->validarPrimeiroAcesso(),
                'decretacoes'     => $this->validarDecretacoes(),
                'pmda'            => $this->validarPmda(),
            };
        }

        $this->table(['secao', 'verificacao', 'qtd', 'status / detalhe'], array_map(
            fn (array $l) => [$l[0], $l[1], $l[2], $l[3]],
            $this->linhas,
        ));

        $erros = count(array_filter($this->linhas, fn (array $l) => str_starts_with($l[3], self::ERRO)));
        $alertas = count(array_filter($this->linhas, fn (array $l) => str_starts_with($l[3], self::ALERTA)));

        $this->newLine();

        if ($erros > 0) {
            $this->error("{$erros} verificacao(oes) com ERRO e {$alertas} com ALERTA.");

            return self::FAILURE;
        }

        $alertas > 0
            ? $this->warn("Sem erros; {$alertas} verificacao(oes) com ALERTA para revisar.")
            : $this->info('Tudo OK.');

        return self::SUCCESS;
    }

    // ── Usuarios ────────────────────────────────────────────────────────────

    private function validarUsuarios(): void
    {
        $ativos = fn () => DB::table('users')->whereNull('deleted_at')->where('active', true);

        $this->registrar('usuarios', 'ativos', $ativos()->count(), self::OK);

        // Hash::check (argon com verify=false) aceita bcrypt e argon2 via
        // password_verify; qualquer outro formato nunca autentica.
        $this->registrar(
            'usuarios',
            'senha em formato que nao autentica (nem bcrypt nem argon2)',
            $ativos()->where(fn (Builder $q) => $this->hashDesconhecido($q)),
            self::ERRO,
        );

        $this->registrar(
            'usuarios',
            'senha bcrypt herdada (vira argon2id no proximo login)',
            $ativos()->where(fn (Builder $q) => $q->where('password', 'like', '$2_$%')),
            self::OK,
            mostrarIds: false,
        );

        // O login e por CPF. A coluna e char(11) com indice unico, entao
        // repeticao nao passa pelo banco; o que sobra e CPF ausente ou curto
        // (char completa com espacos), e quem esta nesse caso nao entra.
        $this->registrar(
            'usuarios',
            'CPF ausente ou sem 11 digitos',
            $ativos()->where(fn (Builder $q) => $q->whereNull('cpf')
                ->orWhereRaw("TRIM(cpf) !~ '^[0-9]{11}$'")),
            self::ERRO,
        );

        $this->registrar(
            'usuarios',
            'sem orgao principal',
            $ativos()->whereNull('orgao_principal_id'),
            self::ALERTA,
        );

        $this->registrar(
            'usuarios',
            'orgao principal inexistente',
            $ativos()->whereNotNull('orgao_principal_id')
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('compdec_orgaos as o')
                    ->whereColumn('o.id', 'users.orgao_principal_id')),
            self::ALERTA,
        );
    }

    private function hashDesconhecido(Builder $q): Builder
    {
        return $q->whereNull('password')
            ->orWhere(fn (Builder $w) => $w
                ->where('password', 'not like', '$2_$%')
                ->where('password', 'not like', '$argon2%'));
    }

    // ── Cargos ──────────────────────────────────────────────────────────────

    private function validarCargos(): void
    {
        $slugsConfig = array_keys((array) config('permissions.roles', []));
        $guard = (string) config('permissions.guard', 'web');

        $this->registrar(
            'cargos',
            'usuario ativo sem nenhum cargo',
            DB::table('users')->whereNull('deleted_at')->where('active', true)
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('model_has_roles as mr')
                    ->whereColumn('mr.model_id', 'users.id')
                    ->where('mr.model_type', User::class)),
            self::ALERTA,
        );

        // O legado podia gravar App\User: o vinculo existe, mas o Spatie nao o
        // enxerga e a pessoa entra sem permissao nenhuma.
        $this->registrar(
            'cargos',
            'vinculo de cargo com model_type diferente de ' . User::class,
            DB::table('model_has_roles')->where('model_type', '!=', User::class),
            self::ERRO,
            coluna: 'model_id',
        );

        $this->registrar(
            'cargos',
            'vinculo de cargo de usuario inexistente',
            DB::table('model_has_roles as mr')->where('mr.model_type', User::class)
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('users')->whereColumn('users.id', 'mr.model_id')),
            self::ALERTA,
            coluna: 'mr.model_id',
        );

        $this->registrar(
            'cargos',
            'vinculo para cargo inexistente',
            DB::table('model_has_roles as mr')
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('roles')->whereColumn('roles.id', 'mr.role_id')),
            self::ERRO,
            coluna: 'mr.model_id',
        );

        $this->registrar(
            'cargos',
            'cargo no banco fora de config/permissions.php (slugs)',
            DB::table('roles')->whereNull('deleted_at')->whereNotIn('slug', $slugsConfig),
            self::ALERTA,
        );

        $this->registrar(
            'cargos',
            "cargo com guard diferente de '{$guard}'",
            DB::table('roles')->whereNull('deleted_at')->where('guard_name', '!=', $guard),
            self::ERRO,
        );

        $this->registrar(
            'cargos',
            'usuario em cargo inativo ou excluido',
            DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where(fn (Builder $q) => $q->where('r.is_active', false)->orWhereNotNull('r.deleted_at')),
            self::ALERTA,
            coluna: 'mr.model_id',
        );
    }

    // ── Tokens (Sanctum guarda SHA-256 do token) ────────────────────────────

    private function validarTokens(): void
    {
        $tokens = fn () => DB::table('personal_access_tokens');

        $this->registrar('tokens', 'tokens de API', $tokens()->count(), self::OK);

        $this->registrar(
            'tokens',
            'token fora do formato SHA-256 (64 hex) - nunca autentica',
            $tokens()->whereRaw("token !~ '^[0-9a-f]{64}$'"),
            self::ERRO,
        );

        $this->registrar(
            'tokens',
            'tokenable_type diferente de ' . User::class,
            $tokens()->where('tokenable_type', '!=', User::class),
            self::ERRO,
        );

        $this->registrar(
            'tokens',
            'token de usuario inexistente ou excluido',
            $tokens()->where('tokenable_type', User::class)
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('users')
                    ->whereColumn('users.id', 'personal_access_tokens.tokenable_id')
                    ->whereNull('users.deleted_at')),
            self::ALERTA,
        );

        $this->registrar(
            'tokens',
            'token expirado',
            $tokens()->whereNotNull('expires_at')->where('expires_at', '<', now()),
            self::OK,
            mostrarIds: false,
        );
    }

    // ── Primeiro acesso e boas-vindas ───────────────────────────────────────

    private function validarPrimeiroAcesso(): void
    {
        // Quem veio do legado e nunca trocou a senha no sistema novo entra com a
        // senha herdada e pula a tela de primeiro acesso (EnsurePasswordChanged
        // so age com must_change_password).
        $semTroca = fn () => DB::table('users')->whereNull('deleted_at')->where('active', true)
            ->whereNull('password_changed_at')
            ->where('must_change_password', false);

        if ($this->option('forcar-primeiro-acesso')) {
            $total = $semTroca()->count();

            if ($total > 0 && ! $this->confirm("Marcar {$total} usuario(s) para trocar a senha no proximo login?", false)) {
                $this->warn('Primeiro acesso: nada alterado.');
            } else {
                $marcados = $semTroca()->update(['must_change_password' => true, 'updated_at' => now()]);
                $this->registrar('primeiro-acesso', 'marcados para trocar a senha agora', $marcados, self::OK);
            }
        }

        $this->registrar(
            'primeiro-acesso',
            'ativo que nunca trocou a senha e nao sera levado ao primeiro acesso',
            $semTroca(),
            self::ALERTA,
            dica: 'rode com --forcar-primeiro-acesso',
        );

        $this->registrar(
            'primeiro-acesso',
            'aguardando troca de senha (vao para /primeiro-acesso)',
            DB::table('users')->whereNull('deleted_at')->where('active', true)->where('must_change_password', true),
            self::OK,
            mostrarIds: false,
        );

        $this->registrar(
            'primeiro-acesso',
            'tour de boas-vindas ainda nao concluido (exibido no proximo login)',
            DB::table('users')->whereNull('deleted_at')->where('active', true)->whereNull('welcome_tour_completed_at'),
            self::OK,
            mostrarIds: false,
        );
    }

    // ── Decretacoes ─────────────────────────────────────────────────────────

    private function validarDecretacoes(): void
    {
        $processos = fn () => DB::table('dec_entrada_processos')->whereNull('deleted_at');

        $this->registrar('decretacoes', 'processos', $processos()->count(), self::OK);

        // Colunas que ficaram varchar no Postgres. Antes de converter o tipo,
        // e preciso saber quantos valores nao converteriam (e seriam perdidos).
        $this->registrar(
            'decretacoes',
            'data_publicacao_diario (varchar) que nao converte para date',
            $processos()->whereNotNull('data_publicacao_diario')->where('data_publicacao_diario', '!=', '')
                ->whereRaw("data_publicacao_diario !~ '^\\d{4}-\\d{2}-\\d{2}$'")
                ->whereRaw("data_publicacao_diario !~ '^\\d{2}/\\d{2}/\\d{4}$'"),
            self::ALERTA,
            dica: 'corrigir antes de converter a coluna',
        );

        $this->registrar(
            'decretacoes',
            'created_by (varchar) que nao e id numerico de usuario',
            $processos()->whereNotNull('created_by')->whereRaw("created_by !~ '^[0-9]+$'"),
            self::ALERTA,
            dica: 'corrigir antes de converter a coluna',
        );

        $this->registrar(
            'decretacoes',
            'created_by numerico sem usuario correspondente',
            $processos()->whereRaw("created_by ~ '^[0-9]+$'")
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('users')
                    // Compara como texto: o Postgres nao garante que o filtro
                    // numerico acima rode antes de um cast ::bigint.
                    ->whereRaw('users.id::text = dec_entrada_processos.created_by')),
            self::ALERTA,
        );

        $this->registrar(
            'decretacoes',
            'vinculo processo-municipio orfao (processo ou municipio inexistente)',
            DB::table('dec_decreto_municipios as dm')->whereNull('dm.deleted_at')
                ->where(fn (Builder $q) => $q
                    ->whereNotExists(fn (Builder $s) => $s->selectRaw('1')->from('dec_entrada_processos as p')->whereColumn('p.id', 'dm.entrada_processos_id'))
                    ->orWhereNotExists(fn (Builder $s) => $s->selectRaw('1')->from('municipios as m')->whereColumn('m.id', 'dm.municipio_id'))),
            self::ERRO,
            coluna: 'dm.id',
        );

        $this->registrar(
            'decretacoes',
            'processo sem nenhum municipio vinculado',
            $processos()->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('dec_decreto_municipios as dm')
                ->whereColumn('dm.entrada_processos_id', 'dec_entrada_processos.id')
                ->whereNull('dm.deleted_at')),
            self::ALERTA,
        );
    }

    // ── PMDA ────────────────────────────────────────────────────────────────

    private function validarPmda(): void
    {
        $this->registrar('pmda', 'comunidades ativas', DB::table('comunidades')->whereNull('deleted_at')->count(), self::OK);

        // Homonimos ("Olhos d'Agua") so sao problema dentro do MESMO municipio.
        $this->registrar(
            'pmda',
            'comunidade com nome repetido no mesmo municipio',
            DB::table('comunidades as c')->whereNull('c.deleted_at')
                ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('comunidades as d')
                    ->whereNull('d.deleted_at')
                    ->whereColumn('d.municipio_id', 'c.municipio_id')
                    ->whereRaw('LOWER(TRIM(d.nome)) = LOWER(TRIM(c.nome))')
                    ->whereColumn('d.id', '!=', 'c.id')),
            self::ALERTA,
            coluna: 'c.id',
        );

        $this->registrar(
            'pmda',
            'comunidade sem municipio valido',
            DB::table('comunidades as c')->whereNull('c.deleted_at')
                ->whereNotExists(fn (Builder $q) => $q->selectRaw('1')->from('municipios as m')->whereColumn('m.id', 'c.municipio_id')),
            self::ERRO,
            coluna: 'c.id',
        );

        $this->registrar(
            'pmda',
            'comunidade sem coordenada',
            DB::table('comunidades')->whereNull('deleted_at')
                ->where(fn (Builder $q) => $q->whereNull('latitude')->orWhereNull('longitude')),
            self::ALERTA,
        );
    }

    // ── Saida ───────────────────────────────────────────────────────────────

    /**
     * Registra uma verificacao. Com um Builder, conta e (se houver ocorrencias)
     * anexa uma amostra de IDs; com um inteiro, so registra o numero.
     */
    private function registrar(
        string $secao,
        string $verificacao,
        Builder|int $consulta,
        string $statusSeHouver,
        bool $mostrarIds = true,
        string $coluna = 'id',
        string $dica = '',
    ): void {
        if (is_int($consulta)) {
            $this->linhas[] = [$secao, $verificacao, $consulta, $statusSeHouver];

            return;
        }

        $total = (clone $consulta)->count();

        if ($total === 0 || $statusSeHouver === self::OK) {
            $this->linhas[] = [$secao, $verificacao, $total, self::OK];

            return;
        }

        $detalhe = $statusSeHouver;

        if ($mostrarIds) {
            $limite = max(1, (int) $this->option('limite'));
            $ids = (clone $consulta)->orderBy($coluna)->limit($limite)->pluck($coluna)->all();
            $detalhe .= ' ids: ' . implode(',', $ids) . ($total > $limite ? ' ...' : '');
        }

        if ($dica !== '') {
            $detalhe .= " ({$dica})";
        }

        $this->linhas[] = [$secao, $verificacao, $total, $detalhe];
    }
}
