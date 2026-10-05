<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ChecagemDiretorio;
use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ReferenciaConta;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use App\Modules\Acessos\Support\DnDiretorio;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use LdapRecord\Connection;
use LdapRecord\LdapInterface;
use LdapRecord\LdapRecordException;
use LdapRecord\Models\Attributes\EscapedValue;
use LdapRecord\Models\Attributes\Guid;
use LdapRecord\Models\BatchModification;
use LdapRecord\Query\Builder;
use Throwable;

/**
 * Adaptador do Active Directory por LDAPS/StartTLS (spec 4.3). Le so a lista
 * fechada de atributos, sempre com valor escapado no filtro, e nunca monta DN
 * a partir de entrada. Antes de qualquer escrita rele a conta por GUID e
 * aplica as guardas de escopo (D7) sobre essa leitura: DN dentro da
 * SearchBase, adminCount diferente de 1, login fora da lista de protegidas e
 * conta diferente da propria conta de servico (por GUID e DN). Toda falha do
 * LdapRecord vira excecao de dominio com mensagem fixa, sem `previous`.
 */
final class LdapDiretorioCorporativo implements DiretorioCorporativo
{
    /** Lista fechada de atributos lidos, em minusculas (forma devolvida pelo ext-ldap). */
    public const ATRIBUTOS = [
        'objectguid',
        'samaccountname',
        'userprincipalname',
        'distinguishedname',
        'displayname',
        'mail',
        'useraccountcontrol',
        'msds-user-account-control-computed',
        'pwdlastset',
        'admincount',
        'objectclass',
        'whenchanged',
    ];

    public const FILTRO_CONTA = '(&(objectCategory=person)(objectClass=user)(!(objectClass=computer)))';

    /** ACCOUNTDISABLE do userAccountControl. */
    public const BIT_DESABILITADA = 0x2;

    /** LOCKOUT do msDS-User-Account-Control-Computed. */
    public const BIT_BLOQUEADA = 0x10;

    private const ATRIBUTOS_CONTA_SERVICO = ['objectguid', 'distinguishedname', 'objectclass'];

    private ?Connection $conexao = null;

    /** @var array{guids: list<string>, dns: list<string>}|null conta de servico desta conexao (GUIDs canonicos, DNs como lidos) */
    private ?array $contaServico = null;

    public function __construct(
        private readonly FabricaConexaoLdap $fabrica,
        private readonly TradutorErroLdap $tradutor,
        private readonly SondaRedeDiretorio $sonda = new SondaRedeDiretorio(),
    ) {}

    public function consultar(string $login): ?ContaDiretorio
    {
        return $this->executar(function () use ($login): ?ContaDiretorio {
            $entrada = $this->primeira($this->searchBase(), '(samaccountname='.$this->escapar($login).')');

            return $entrada === null ? null : $this->paraConta($entrada);
        });
    }

    public function buscarPorGuid(string $objectGuid): ?ContaDiretorio
    {
        if (! Guid::isValid($objectGuid)) {
            return null;
        }

        return $this->executar(function () use ($objectGuid): ?ContaDiretorio {
            $entrada = $this->entradaPorGuid($objectGuid);

            return $entrada === null ? null : $this->paraConta($entrada);
        });
    }

    /** @return list<ContaDiretorio> */
    public function listarContas(): iterable
    {
        return $this->executar(fn (): array => array_values(array_map(
            fn (array $entrada): ContaDiretorio => $this->paraConta($entrada),
            $this->busca($this->searchBase())
                ->rawFilter(self::FILTRO_CONTA)
                ->paginate($this->tamanhoPagina()),
        )));
    }

    /**
     * Diagnostico passo a passo (somente leitura): dns, tls, bind, search_base
     * e resolucao da conta de servico. Cada passo depende do anterior; o
     * detalhe e fixo ou o codigo de CodigoErroDiretorio, nunca a mensagem do
     * servidor. Nao escreve no AD.
     *
     * @return list<ChecagemDiretorio>
     */
    public function diagnosticar(): array
    {
        $hosts = [];
        $dns = $this->passo('dns', function () use (&$hosts): ChecagemDiretorio {
            $hosts = $this->fabrica->hosts();
            $semResposta = array_values(array_filter($hosts, fn (string $host): bool => ! $this->sonda->resolve($host)));

            return $semResposta === []
                ? ChecagemDiretorio::ok('dns', count($hosts).' host(s) resolvem')
                : ChecagemDiretorio::falha('dns', 'nao resolvem: '.implode(', ', $semResposta));
        });

        $tls = $dns->estado === ChecagemDiretorio::OK
            ? $this->passo('tls', fn (): ChecagemDiretorio => $this->checarTls($hosts))
            : ChecagemDiretorio::ignorado('tls', 'etapa anterior falhou');

        $bind = $dns->estado === ChecagemDiretorio::OK && $tls->passou()
            ? $this->passo('bind', function (): ChecagemDiretorio {
                $this->conexao()->connect();

                return ChecagemDiretorio::ok('bind', 'conta de servico autenticada');
            })
            : ChecagemDiretorio::ignorado('bind', 'etapa anterior falhou');

        $autenticado = $bind->estado === ChecagemDiretorio::OK;
        $base = $autenticado
            ? $this->passo('search_base', function (): ChecagemDiretorio {
                $entrada = $this->busca($this->searchBase(), ['distinguishedname'])->read()->rawFilter('(objectClass=*)')->first();

                return is_array($entrada)
                    ? ChecagemDiretorio::ok('search_base', 'leitura da base permitida')
                    : ChecagemDiretorio::falha('search_base', 'search_base_inexistente');
            })
            : ChecagemDiretorio::ignorado('search_base', 'bind nao executado com sucesso');
        $servico = $autenticado
            ? $this->passo('conta_servico', fn (): ChecagemDiretorio => ChecagemDiretorio::ok(
                'conta_servico',
                count($this->contaServico()['guids']).' conta(s) de servico protegida(s)',
            ))
            : ChecagemDiretorio::ignorado('conta_servico', 'bind nao executado com sucesso');

        $this->descartarConexao();

        return [$dns, $tls, $bind, $base, $servico];
    }

    /** @param list<string> $hosts */
    private function checarTls(array $hosts): ChecagemDiretorio
    {
        if (config('acessos.diretorio.seguranca') === 'starttls') {
            return ChecagemDiretorio::ignorado('tls', 'starttls: certificado validado no bind');
        }

        $ca = $this->configTexto('ca_cert');
        if (! is_file($ca) || ! is_readable($ca)) {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CERTIFICADO);
        }
        $porta = (int) config('acessos.diretorio.porta');
        $timeout = max(1, (int) config('acessos.diretorio.timeout_conexao'));

        $dias = [];
        $falhas = [];
        foreach ($hosts as $host) {
            try {
                $dias[] = $this->sonda->diasAteVencerCertificado($host, $porta, $ca, $timeout);
            } catch (DiretorioIndisponivel $e) {
                $falhas[] = $host.' ('.$e->codigo()->value.')';
            }
        }

        return $falhas === []
            ? ChecagemDiretorio::ok('tls', 'certificado valido; vence em '.min($dias).' dia(s)')
            : ChecagemDiretorio::falha('tls', implode(', ', $falhas));
    }

    /** @param callable(): ChecagemDiretorio $passo */
    private function passo(string $nome, callable $passo): ChecagemDiretorio
    {
        try {
            return $passo();
        } catch (LdapRecordException $e) {
            $this->descartarConexao();

            return ChecagemDiretorio::falhaComCodigo($nome, $this->tradutor->traduzir($e)->codigo());
        } catch (DiretorioIndisponivel|DiretorioRecusou $e) {
            $this->descartarConexao();

            return ChecagemDiretorio::falhaComCodigo($nome, $e->codigo());
        } catch (Throwable) {
            $this->descartarConexao();

            return ChecagemDiretorio::falhaComCodigo($nome, CodigoErroDiretorio::ERRO_INTERNO);
        }
    }

    public function desbloquear(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->executar(function () use ($conta): ResultadoOperacao {
            [$atual] = $this->lerParaEscrita($conta);
            $this->modificar($atual->dn, [$this->substituir('lockoutTime', '0')]);

            return new ResultadoOperacao($this->relerOuFalhar($conta->objectGuid), efetivada: $atual->bloqueada);
        });
    }

    public function habilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->executar(fn (): ResultadoOperacao => $this->alterarDesabilitada($conta, desabilitar: false));
    }

    public function desabilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->executar(fn (): ResultadoOperacao => $this->alterarDesabilitada($conta, desabilitar: true));
    }

    public function exigirTrocaSenha(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->executar(function () use ($conta): ResultadoOperacao {
            [$atual] = $this->lerParaEscrita($conta);
            $this->modificar($atual->dn, [$this->substituir('pwdLastSet', '0')]);

            return new ResultadoOperacao($this->relerOuFalhar($conta->objectGuid), efetivada: ! $atual->trocaSenhaPendente);
        });
    }

    public function redefinirSenha(
        ReferenciaConta $conta,
        #[\SensitiveParameter] string $senha,
        bool $exigirTroca,
        string $operationId,
    ): ResultadoOperacao {
        return $this->executar(function () use ($conta, $senha, $exigirTroca): ResultadoOperacao {
            // convertida antes de qualquer chamada ao AD: senha invalida nao gera trafego
            $unicodePwd = $this->unicodePwd($senha);
            [$atual] = $this->lerParaEscrita($conta);

            // um unico modify com unicodePwd e, se pedido, pwdLastSet.
            $mods = [$this->substituir('unicodePwd', $unicodePwd)];
            if ($exigirTroca) {
                $mods[] = $this->substituir('pwdLastSet', '0');
            }
            $this->modificar($atual->dn, $mods);

            return new ResultadoOperacao($this->relerOuFalhar($conta->objectGuid), efetivada: true);
        });
    }

    /**
     * Le o UAC atual por GUID e grava so o bit 2 alterado; os outros bits sao
     * preservados. Ja no estado-alvo: nada e gravado e `efetivada` e false.
     */
    private function alterarDesabilitada(ReferenciaConta $conta, bool $desabilitar): ResultadoOperacao
    {
        [$atual, $entrada] = $this->lerParaEscrita($conta);
        $bruto = $this->valor($entrada, 'useraccountcontrol');
        if ($bruto === null || ! ctype_digit($bruto)) {
            // sem o UAC atual nao ha como preservar os outros bits: nada e gravado
            throw new DiretorioRecusou(CodigoErroDiretorio::ERRO_INTERNO);
        }
        $uac = (int) $bruto;
        $novo = $desabilitar ? $uac | self::BIT_DESABILITADA : $uac & ~self::BIT_DESABILITADA;

        if ($novo === $uac) {
            return new ResultadoOperacao($atual, efetivada: false);
        }
        $this->modificar($atual->dn, [$this->substituir('userAccountControl', (string) $novo)]);

        return new ResultadoOperacao($this->relerOuFalhar($conta->objectGuid), efetivada: true);
    }

    /**
     * Releitura por GUID imediatamente antes da escrita, com as guardas sobre
     * o DN lido agora (a conta pode ter mudado de OU desde a referencia).
     *
     * @return array{0: ContaDiretorio, 1: array<string, mixed>}
     */
    private function lerParaEscrita(ReferenciaConta $referencia): array
    {
        $entrada = Guid::isValid($referencia->objectGuid) ? $this->entradaPorGuid($referencia->objectGuid) : null;
        if ($entrada === null) {
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
        }
        $conta = $this->paraConta($entrada);
        $this->assegurarAlvo($conta);

        return [$conta, $entrada];
    }

    private function assegurarAlvo(ContaDiretorio $conta): void
    {
        if (! DnDiretorio::estaDentroDe($conta->dn, $this->searchBase())) {
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_FORA_DO_ESCOPO);
        }

        $protegidas = array_map('mb_strtolower', (array) config('acessos.diretorio.contas_protegidas', []));
        if ($conta->protegida || in_array(mb_strtolower($conta->login), $protegidas, true)) {
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_PROTEGIDA);
        }

        $servico = $this->contaServico();
        if (in_array(mb_strtolower($conta->objectGuid), $servico['guids'], true)) {
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_PROTEGIDA);
        }
        foreach ($servico['dns'] as $dnServico) {
            if (DnDiretorio::iguais($conta->dn, $dnServico)) {
                throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_PROTEGIDA);
            }
        }
    }

    /**
     * Valor de unicodePwd: UTF-16LE da senha entre aspas. Senha que nao e
     * UTF-8 valido e defeito do gerador: codigo fixo, sem a senha em mensagem.
     */
    private function unicodePwd(#[\SensitiveParameter] string $senha): string
    {
        $convertida = mb_check_encoding($senha, 'UTF-8') ? iconv('UTF-8', 'UTF-16LE', '"'.$senha.'"') : false;
        if ($convertida === false || $convertida === '') {
            throw new DiretorioRecusou(CodigoErroDiretorio::ERRO_INTERNO);
        }

        return $convertida;
    }

    /**
     * A conta do bind, resolvida uma vez por conexao pelo UPN configurado (ou
     * pelo sAMAccountName derivado dele, para o UPN implicito). Toda conta que
     * casar fica protegida. Sem nenhuma, a escrita e recusada: sem saber quem
     * e a conta de servico, nao ha como garantir que ela nao e o alvo.
     *
     * @return array{guids: list<string>, dns: list<string>}
     */
    private function contaServico(): array
    {
        if ($this->contaServico !== null) {
            return $this->contaServico;
        }

        $usuario = trim((string) config('acessos.diretorio.usuario'));
        $login = (string) preg_replace(['/@.*$/', '/^.*\\\\/'], '', $usuario);
        if ($usuario === '' || $login === '') {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
        }

        $filtro = sprintf('(|(userprincipalname=%s)(samaccountname=%s))', $this->escapar($usuario), $this->escapar($login));
        $entradas = $this->busca($this->baseDn(), self::ATRIBUTOS_CONTA_SERVICO)
            ->rawFilter('(&'.self::FILTRO_CONTA.$filtro.')')
            ->get();
        if ($entradas === []) {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
        }

        $guids = [];
        $dns = [];
        foreach ($entradas as $entrada) {
            $guids[] = $this->guidDe($entrada);
            $dns[] = $this->dnDe($entrada);
        }

        return $this->contaServico = ['guids' => $guids, 'dns' => $dns];
    }

    /** @return array<string, mixed>|null */
    private function entradaPorGuid(string $objectGuid): ?array
    {
        return $this->primeira($this->baseDn(), '(objectguid='.(new Guid($objectGuid))->getEncodedHex().')');
    }

    private function relerOuFalhar(string $objectGuid): ContaDiretorio
    {
        $entrada = $this->entradaPorGuid($objectGuid);
        if ($entrada === null) {
            throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
        }

        return $this->paraConta($entrada);
    }

    /** @return array<string, mixed>|null */
    private function primeira(string $base, string $filtroEscapado): ?array
    {
        $entrada = $this->busca($base)->rawFilter('(&'.self::FILTRO_CONTA.$filtroEscapado.')')->first();

        return is_array($entrada) ? $entrada : null;
    }

    /** @param list<string> $atributos */
    private function busca(string $base, array $atributos = self::ATRIBUTOS): Builder
    {
        return $this->conexao()->query()->setDn($base)->select($atributos);
    }

    /** @param list<array<string, mixed>> $modificacoes */
    private function modificar(string $dn, #[\SensitiveParameter] array $modificacoes): void
    {
        $this->conexao()->run(fn (LdapInterface $ldap): bool => $ldap->modifyBatch($dn, $modificacoes));
    }

    /** @return array<string, mixed> */
    private function substituir(string $atributo, #[\SensitiveParameter] string $valor): array
    {
        return (array) (new BatchModification($atributo, LDAP_MODIFY_BATCH_REPLACE, [$valor]))->get();
    }

    /** @param array<string, mixed> $entrada */
    private function paraConta(array $entrada): ContaDiretorio
    {
        $uac = (int) $this->valor($entrada, 'useraccountcontrol');
        $computado = (int) $this->valor($entrada, 'msds-user-account-control-computed');

        return new ContaDiretorio(
            objectGuid: $this->guidDe($entrada),
            login: (string) $this->valor($entrada, 'samaccountname'),
            upn: $this->valor($entrada, 'userprincipalname'),
            dn: $this->dnDe($entrada),
            nomeExibicao: $this->valor($entrada, 'displayname'),
            email: $this->valor($entrada, 'mail'),
            ativa: ($uac & self::BIT_DESABILITADA) === 0,
            bloqueada: ($computado & self::BIT_BLOQUEADA) !== 0,
            trocaSenhaPendente: $this->valor($entrada, 'pwdlastset') === '0',
            protegida: $this->valor($entrada, 'admincount') === '1',
            alteradaEm: $this->dataGeneralizada($this->valor($entrada, 'whenchanged')),
        );
    }

    /**
     * Primeiro valor do atributo, no formato de ldap_get_entries (chaves em
     * minusculas, valores em lista com `count`).
     *
     * @param array<string, mixed> $entrada
     */
    private function valor(array $entrada, string $atributo): ?string
    {
        $valores = array_change_key_case($entrada, CASE_LOWER)[$atributo] ?? null;
        $valor = is_array($valores) ? ($valores[0] ?? null) : $valores;

        return is_scalar($valor) && (string) $valor !== '' ? (string) $valor : null;
    }

    /** @param array<string, mixed> $entrada */
    private function dnDe(array $entrada): string
    {
        return $this->valor($entrada, 'dn') ?? (string) $this->valor($entrada, 'distinguishedname');
    }

    /** Generalized time do AD (`20261005120000.0Z`), em UTC. */
    private function dataGeneralizada(?string $valor): ?DateTimeImmutable
    {
        if ($valor === null || preg_match('/^(\d{14})/', $valor, $partes) !== 1) {
            return null;
        }
        $data = DateTimeImmutable::createFromFormat('!YmdHis', $partes[1], new DateTimeZone('UTC'));

        return $data === false ? null : $data;
    }

    private function escapar(string $valor): string
    {
        return (new EscapedValue($valor))->forFilter()->get();
    }

    /**
     * objectGUID binario da entrada na forma canonica. Entrada sem GUID (ou
     * com um que nao decodifica) e resposta inesperada do servidor.
     *
     * @param array<string, mixed> $entrada
     */
    private function guidDe(array $entrada): string
    {
        $binario = $this->valor($entrada, 'objectguid');
        try {
            $guid = $binario === null ? null : (new Guid($binario))->getValue();
        } catch (InvalidArgumentException) {
            $guid = null;
        }
        if ($guid === null || ! Guid::isValid($guid)) {
            throw new DiretorioRecusou(CodigoErroDiretorio::ERRO_INTERNO);
        }

        return mb_strtolower($guid);
    }

    private function searchBase(): string
    {
        return $this->configTexto('search_base');
    }

    private function baseDn(): string
    {
        return $this->configTexto('base_dn');
    }

    private function tamanhoPagina(): int
    {
        $tamanho = config('acessos.diretorio.sincronizar.tamanho_pagina');
        if (! is_int($tamanho) || $tamanho < 1) {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
        }

        return $tamanho;
    }

    private function configTexto(string $chave): string
    {
        $valor = config('acessos.diretorio.'.$chave);
        if (! is_string($valor) || trim($valor) === '') {
            throw new DiretorioIndisponivel(CodigoErroDiretorio::CONFIG_AUSENTE);
        }

        return trim($valor);
    }

    private function conexao(): Connection
    {
        return $this->conexao ??= $this->fabrica->criar();
    }

    /**
     * Executa a operacao traduzindo a falha do LdapRecord. A conexao e
     * descartada na falha: a proxima chamada reconecta e resolve de novo a
     * conta de servico.
     *
     * @template T
     *
     * @param callable(): T $operacao
     * @return T
     */
    private function executar(callable $operacao): mixed
    {
        try {
            return $operacao();
        } catch (LdapRecordException $e) {
            $this->descartarConexao();

            throw $this->tradutor->traduzir($e);
        }
    }

    private function descartarConexao(): void
    {
        try {
            $this->conexao?->disconnect();
        } catch (Throwable) {
            // conexao ja perdida: nada a fechar
        }
        $this->conexao = null;
        $this->contaServico = null;
    }
}
