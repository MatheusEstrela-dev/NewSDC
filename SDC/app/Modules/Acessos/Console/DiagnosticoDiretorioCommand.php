<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Console;

use App\Modules\Acessos\DTOs\ChecagemDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Infrastructure\LdapDiretorioCorporativo;
use App\Modules\Acessos\Infrastructure\SegredoDiretorio;
use App\Modules\Acessos\Support\DnDiretorio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Diagnostico da integracao com o diretorio, somente leitura. Cada checagem
 * sai `ok`, `falha` ou `ignorado`; o detalhe e fixo ou o codigo de
 * CodigoErroDiretorio. Nunca imprime segredo (senha de bind, chave de entrega)
 * nem a mensagem crua do servidor LDAP, e nunca escreve no AD. Sai com
 * codigo diferente de zero se qualquer checagem falhar.
 */
final class DiagnosticoDiretorioCommand extends Command
{
    private const DRIVERS = ['desligado', 'ldap', 'fake'];

    private const CHECAGENS_DE_REDE = ['dns', 'tls', 'bind', 'search_base', 'conta_servico'];

    /** Esquema da spec 5: tabela => colunas obrigatorias. */
    private const ESQUEMA = [
        'acessos_cadastros' => ['object_guid', 'dn_ad', 'conta_ativa_ad', 'bloqueada_ad', 'troca_senha_pendente_ad', 'ad_sincronizado_em', 'status_ad'],
        'acessos_operacoes_ad' => ['id', 'chave_idempotencia', 'cadastro_id', 'login_ad', 'object_guid', 'acao', 'origem', 'origem_id', 'solicitado_por_id', 'motivo', 'estado', 'codigo_erro', 'tentativas', 'resultado', 'enviado_em', 'concluido_em'],
        'acessos_entregas_senha' => ['operacao_id', 'destinatario_id', 'senha_cifrada', 'expira_em', 'created_at'],
        'acessos_sincronizacoes_ad' => ['id', 'disparada_por_id', 'simulacao', 'estado', 'codigo_erro', 'totais', 'iniciada_em', 'concluida_em'],
        'acessos_divergencias_ad' => ['id', 'sincronizacao_id', 'cadastro_id', 'object_guid', 'login_ad', 'tipo', 'detalhe', 'created_at'],
        'acessos_fila_diretorio' => ['id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'],
    ];

    protected $signature = 'acessos:diretorio-diagnostico {--fila : Inclui a quantidade e a idade do job mais antigo da fila} {--json : Saida em JSON}';

    protected $description = 'Diagnostica a integração com o Active Directory (somente leitura, sem imprimir segredos).';

    public function handle(SegredoDiretorio $segredos): int
    {
        $driver = (string) config('acessos.diretorio.driver');

        $banco = $this->checarBanco();
        $config = $this->checarConfig($driver, $segredos);
        $checagens = [
            $banco,
            $this->checarEsquema($banco),
            $config,
            ...$this->checarRede($driver, $config),
            $this->checarFila($banco),
        ];

        $falhou = array_filter($checagens, static fn (ChecagemDiretorio $c): bool => ! $c->passou()) !== [];
        $linhas = array_map(static fn (ChecagemDiretorio $c): array => $c->toArray(), $checagens);

        if ($this->option('json')) {
            $this->line((string) json_encode(['ok' => ! $falhou, 'checagens' => $linhas], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Checagem', 'Estado', 'Detalhe'], $linhas);
            $falhou ? $this->error('Diagnóstico com falhas.') : $this->info('Diagnóstico sem falhas.');
        }

        return $falhou ? self::FAILURE : self::SUCCESS;
    }

    private function checarBanco(): ChecagemDiretorio
    {
        try {
            DB::select('select 1');

            return ChecagemDiretorio::ok('banco', 'conexao ativa');
        } catch (Throwable) {
            return ChecagemDiretorio::falha('banco', 'sem_conexao');
        }
    }

    private function checarEsquema(ChecagemDiretorio $banco): ChecagemDiretorio
    {
        if (! $banco->passou()) {
            return ChecagemDiretorio::ignorado('esquema', 'banco indisponivel');
        }

        try {
            $ausentes = [];
            foreach (self::ESQUEMA as $tabela => $colunas) {
                // uma consulta por tabela (hasColumn por coluna estoura o orcamento de queries)
                $existentes = Schema::getColumnListing($tabela);
                if ($existentes === []) {
                    $ausentes[] = $tabela;

                    continue;
                }
                foreach (array_diff($colunas, $existentes) as $coluna) {
                    $ausentes[] = $tabela.'.'.$coluna;
                }
            }
        } catch (Throwable) {
            return ChecagemDiretorio::falha('esquema', 'leitura_falhou');
        }

        return $ausentes === []
            ? ChecagemDiretorio::ok('esquema', count(self::ESQUEMA).' tabelas completas')
            : ChecagemDiretorio::falha('esquema', 'ausentes: '.implode(', ', $ausentes));
    }

    private function checarConfig(string $driver, SegredoDiretorio $segredos): ChecagemDiretorio
    {
        if (! in_array($driver, self::DRIVERS, true)) {
            return ChecagemDiretorio::falha('config', 'driver_invalido');
        }
        if ($driver === 'desligado') {
            return ChecagemDiretorio::ok('config', 'driver=desligado');
        }

        $config = (array) config('acessos.diretorio');
        $texto = static fn (string $chave): bool => is_string($config[$chave] ?? null) && trim((string) $config[$chave]) !== '';
        $chave = $this->segredoPresente(fn (): string => $segredos->chaveEntrega());

        $faltando = $chave ? [] : ['chave_entrega'];
        if ($driver === 'ldap') {
            $ca = $texto('ca_cert') && is_file((string) $config['ca_cert']) && is_readable((string) $config['ca_cert']);
            $faltando = array_merge($faltando, array_keys(array_filter([
                'base_dn' => ! $texto('base_dn'),
                'search_base' => ! $texto('search_base'),
                'usuario' => ! $texto('usuario'),
                'senha_bind' => ! $this->segredoPresente(fn (): string => $segredos->senhaBind()),
                'ca_cert' => ! $ca,
                'hosts_ou_dominio' => (array) ($config['hosts'] ?? []) === [] && ! $texto('dominio'),
                'search_base_dentro_do_base_dn' => $texto('search_base') && $texto('base_dn')
                    && ! DnDiretorio::estaDentroDe((string) $config['search_base'], (string) $config['base_dn']),
            ])));
        }

        $resumo = sprintf(
            'driver=%s; search_base=%s; chave_entrega=%s',
            $driver,
            $texto('search_base') ? $config['search_base'] : 'ausente',
            $chave ? 'sim' : 'nao',
        );

        return $faltando === []
            ? ChecagemDiretorio::ok('config', $resumo)
            : ChecagemDiretorio::falha('config', $resumo.'; faltando: '.implode(', ', $faltando));
    }

    /** @return list<ChecagemDiretorio> */
    private function checarRede(string $driver, ChecagemDiretorio $config): array
    {
        if (! $config->passou()) {
            return array_map(static fn (string $nome): ChecagemDiretorio => ChecagemDiretorio::ignorado($nome, 'config com falha'), self::CHECAGENS_DE_REDE);
        }
        if ($driver === 'ldap') {
            return app(LdapDiretorioCorporativo::class)->diagnosticar();
        }

        $motivo = 'driver '.($driver === 'fake' ? 'fake' : 'desligado').': sem rede';

        return array_map(static fn (string $nome): ChecagemDiretorio => ChecagemDiretorio::ignorado($nome, $motivo), self::CHECAGENS_DE_REDE);
    }

    private function checarFila(ChecagemDiretorio $banco): ChecagemDiretorio
    {
        if (! $this->option('fila')) {
            return ChecagemDiretorio::ignorado('fila', 'use --fila');
        }
        if (! $banco->passou()) {
            return ChecagemDiretorio::ignorado('fila', 'banco indisponivel');
        }

        try {
            $nome = 'queue.connections.'.config('acessos.diretorio.fila.conexao');
            $conexao = (string) config($nome.'.connection', config('database.default'));
            $tabela = (string) config($nome.'.table', 'acessos_fila_diretorio');
            $fila = DB::connection($conexao)->table($tabela);

            $total = (clone $fila)->count();
            $maisAntigo = (clone $fila)->min('created_at');
        } catch (Throwable) {
            return ChecagemDiretorio::falha('fila', 'leitura_falhou');
        }

        $idade = $maisAntigo === null ? 'fila vazia' : 'mais antigo ha '.max(0, now()->timestamp - (int) $maisAntigo).' s';

        return ChecagemDiretorio::ok('fila', $total.' job(s); '.$idade);
    }

    /** @param callable(): string $ler */
    private function segredoPresente(callable $ler): bool
    {
        try {
            $ler();

            return true;
        } catch (DiretorioIndisponivel) {
            return false;
        }
    }
}
