<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Infrastructure;

use App\Modules\Acessos\Contracts\DiretorioCorporativo;
use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\DTOs\ReferenciaConta;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Exceptions\DiretorioIndisponivel;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

/**
 * Diretorio de mentira para testes, dev e homolog (spec 4.4), com a mesma
 * semantica do adaptador LDAP: escopo pela SearchBase nas leituras por login e
 * na listagem, `efetivada` false quando a conta ja estava no estado-alvo, bits
 * do userAccountControl preservados.
 *
 * Com `acessos.diretorio.fake.arquivo` o estado (contas e falhas programadas)
 * vai para um JSON travado com flock a cada operacao, porque em dev/homolog o
 * web semeia e o worker executa em processos diferentes. Chamadas e senhas
 * ficam so na memoria da instancia: a senha nunca vai para disco.
 */
final class FakeDiretorioCorporativo implements DiretorioCorporativo
{
    public const METODOS = [
        'consultar', 'buscarPorGuid', 'listarContas', 'desbloquear',
        'habilitar', 'desabilitar', 'exigirTrocaSenha', 'redefinirSenha',
    ];

    private const UAC_CONTA_NORMAL = 0x200;

    private const UAC_DESABILITADA = 0x2;

    private const ESTADO_VAZIO = ['contas' => [], 'falhas' => []];

    private readonly string $searchBase;

    private readonly ?string $arquivo;

    /** @var array{contas: array<string, array<string, mixed>>, falhas: array<string, array{codigo: string, restantes: ?int, depoisDeAplicar: bool}>} */
    private array $estado = self::ESTADO_VAZIO;

    /** @var list<array{metodo: string, guid: ?string, operationId: ?string}> */
    private array $chamadas = [];

    /** @var array<string, string> */
    private array $senhas = [];

    public function __construct(?string $searchBase = null, ?string $arquivo = null)
    {
        $this->searchBase = $searchBase ?? (string) config('acessos.diretorio.search_base', '');
        $arquivo ??= config('acessos.diretorio.fake.arquivo');
        $this->arquivo = is_string($arquivo) && $arquivo !== '' ? $arquivo : null;
    }

    public function semear(ContaDiretorio ...$contas): void
    {
        $this->comEstado(function (array &$estado) use ($contas): void {
            foreach ($contas as $conta) {
                $guid = self::guid($conta->objectGuid);
                // ressemear preserva os outros bits do UAC ja guardado
                $uac = (int) ($estado['contas'][$guid]['uac'] ?? self::UAC_CONTA_NORMAL);
                $estado['contas'][$guid] = ['objectGuid' => $guid] + $conta->toArray() + [
                    'uac' => $conta->ativa ? $uac & ~self::UAC_DESABILITADA : $uac | self::UAC_DESABILITADA,
                ];
            }
        });
    }

    /**
     * Programa falha em `$metodo`. Codigo de indisponibilidade lanca
     * DiretorioIndisponivel; os demais, DiretorioRecusou. `$vezes` null falha
     * sempre; `$depoisDeAplicar` aplica o efeito antes de lancar (modify que
     * chegou ao AD mas cuja resposta se perdeu).
     */
    public function falharCom(
        string $metodo,
        CodigoErroDiretorio|string $codigo,
        ?int $vezes = null,
        bool $depoisDeAplicar = false,
    ): void {
        if (! in_array($metodo, self::METODOS, true)) {
            throw new InvalidArgumentException('Metodo desconhecido do diretorio: '.$metodo);
        }
        $codigo = $codigo instanceof CodigoErroDiretorio ? $codigo : CodigoErroDiretorio::from($codigo);

        $this->comEstado(function (array &$estado) use ($metodo, $codigo, $vezes, $depoisDeAplicar): void {
            $estado['falhas'][$metodo] = ['codigo' => $codigo->value, 'restantes' => $vezes, 'depoisDeAplicar' => $depoisDeAplicar];
        });
    }

    public function limparFalhas(): void
    {
        $this->comEstado(function (array &$estado): void {
            $estado['falhas'] = [];
        });
    }

    /** @return list<array{metodo: string, guid: ?string, operationId: ?string}> */
    public function chamadas(): array
    {
        return $this->chamadas;
    }

    /** So o fake guarda a senha, e so em memoria, para o teste conferir a entrega. */
    public function ultimaSenha(string $guid): ?string
    {
        return $this->senhas[self::guid($guid)] ?? null;
    }

    /** userAccountControl bruto (fora do DTO): `ativa` passa a seguir o bit 2. */
    public function definirUac(string $guid, int $uac): void
    {
        $this->comEstado(function (array &$estado) use ($guid, $uac): void {
            $guid = self::guid($guid);
            if (! isset($estado['contas'][$guid])) {
                throw new InvalidArgumentException('Conta nao semeada: '.$guid);
            }
            $estado['contas'][$guid]['uac'] = $uac;
            $estado['contas'][$guid]['ativa'] = ($uac & self::UAC_DESABILITADA) === 0;
        });
    }

    public function uac(string $guid): ?int
    {
        return $this->comEstado(fn (array &$estado): ?int => isset($estado['contas'][self::guid($guid)])
            ? (int) $estado['contas'][self::guid($guid)]['uac']
            : null);
    }

    public function consultar(string $login): ?ContaDiretorio
    {
        return $this->executar('consultar', null, null, function (array &$estado) use ($login): ?ContaDiretorio {
            foreach ($estado['contas'] as $registro) {
                if (mb_strtolower((string) $registro['login']) === mb_strtolower($login) && $this->naSearchBase((string) $registro['dn'])) {
                    return ContaDiretorio::fromArray($registro);
                }
            }

            return null;
        });
    }

    public function buscarPorGuid(string $objectGuid): ?ContaDiretorio
    {
        $guid = self::guid($objectGuid);

        return $this->executar('buscarPorGuid', $guid, null, fn (array &$estado): ?ContaDiretorio => isset($estado['contas'][$guid])
            ? ContaDiretorio::fromArray($estado['contas'][$guid])
            : null);
    }

    /** @return list<ContaDiretorio> */
    public function listarContas(): iterable
    {
        return $this->executar('listarContas', null, null, fn (array &$estado): array => array_values(array_map(
            static fn (array $registro): ContaDiretorio => ContaDiretorio::fromArray($registro),
            array_filter($estado['contas'], fn (array $registro): bool => $this->naSearchBase((string) $registro['dn'])),
        )));
    }

    public function desbloquear(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->escrever('desbloquear', $conta, $operationId, static function (array &$registro): bool {
            $efetivada = (bool) $registro['bloqueada'];
            $registro['bloqueada'] = false;

            return $efetivada;
        });
    }

    public function habilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->escrever('habilitar', $conta, $operationId, static function (array &$registro): bool {
            $efetivada = ! $registro['ativa'];
            $registro['uac'] = (int) $registro['uac'] & ~self::UAC_DESABILITADA;
            $registro['ativa'] = true;

            return $efetivada;
        });
    }

    public function desabilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->escrever('desabilitar', $conta, $operationId, static function (array &$registro): bool {
            $efetivada = (bool) $registro['ativa'];
            $registro['uac'] = (int) $registro['uac'] | self::UAC_DESABILITADA;
            $registro['ativa'] = false;

            return $efetivada;
        });
    }

    public function exigirTrocaSenha(ReferenciaConta $conta, string $operationId): ResultadoOperacao
    {
        return $this->escrever('exigirTrocaSenha', $conta, $operationId, static function (array &$registro): bool {
            $efetivada = ! $registro['trocaSenhaPendente'];
            $registro['trocaSenhaPendente'] = true;

            return $efetivada;
        });
    }

    public function redefinirSenha(
        ReferenciaConta $conta,
        #[\SensitiveParameter] string $senha,
        bool $exigirTroca,
        string $operationId,
    ): ResultadoOperacao {
        return $this->escrever('redefinirSenha', $conta, $operationId, function (array &$registro) use ($senha, $exigirTroca): bool {
            $this->senhas[(string) $registro['objectGuid']] = $senha;
            $registro['trocaSenhaPendente'] = $exigirTroca;

            return true;
        });
    }

    /** @param callable(array<string, mixed>&): bool $efeito devolve se o estado mudou */
    private function escrever(string $metodo, ReferenciaConta $conta, string $operationId, callable $efeito): ResultadoOperacao
    {
        $guid = self::guid($conta->objectGuid);

        return $this->executar($metodo, $guid, $operationId, static function (array &$estado) use ($guid, $efeito): ResultadoOperacao {
            if (! isset($estado['contas'][$guid])) {
                throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_INEXISTENTE);
            }
            $efetivada = $efeito($estado['contas'][$guid]);
            if ($efetivada) {
                $estado['contas'][$guid]['alteradaEm'] = (new DateTimeImmutable())->format(DATE_ATOM);
            }

            return new ResultadoOperacao(ContaDiretorio::fromArray($estado['contas'][$guid]), $efetivada);
        });
    }

    /** Registra a chamada, aplica a falha programada e executa o efeito. */
    private function executar(string $metodo, ?string $guid, ?string $operationId, callable $efeito): mixed
    {
        $this->chamadas[] = ['metodo' => $metodo, 'guid' => $guid, 'operationId' => $operationId];

        return $this->comEstado(function (array &$estado) use ($metodo, $efeito): mixed {
            $falha = $this->consumirFalha($estado, $metodo);
            if ($falha !== null && ! $falha['depoisDeAplicar']) {
                throw self::excecao($falha['codigo']);
            }
            $resultado = $efeito($estado);
            if ($falha !== null) {
                throw self::excecao($falha['codigo']);
            }

            return $resultado;
        });
    }

    /**
     * @param array{falhas: array<string, array{codigo: string, restantes: ?int, depoisDeAplicar: bool}>} $estado
     * @return array{codigo: string, restantes: ?int, depoisDeAplicar: bool}|null
     */
    private function consumirFalha(array &$estado, string $metodo): ?array
    {
        $falha = $estado['falhas'][$metodo] ?? null;
        if ($falha !== null && $falha['restantes'] !== null) {
            $restantes = $falha['restantes'] - 1;
            if ($restantes <= 0) {
                unset($estado['falhas'][$metodo]);
            } else {
                $estado['falhas'][$metodo]['restantes'] = $restantes;
            }
        }

        return $falha;
    }

    /**
     * Executa `$operacao` sobre o estado: em memoria, ou lido e regravado no
     * JSON sob flock exclusivo. O estado e regravado mesmo se a operacao
     * lancar (falha programada depois de aplicar o efeito).
     */
    private function comEstado(callable $operacao): mixed
    {
        if ($this->arquivo === null) {
            return $operacao($this->estado);
        }

        $diretorio = dirname($this->arquivo);
        if (! is_dir($diretorio) && ! mkdir($diretorio, 0775, true) && ! is_dir($diretorio)) {
            throw new RuntimeException('Diretorio do estado do fake inacessivel.');
        }
        $handle = fopen($this->arquivo, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Arquivo do estado do fake inacessivel.');
        }

        try {
            flock($handle, LOCK_EX);
            $lido = json_decode((string) stream_get_contents($handle), true);
            $estado = (is_array($lido) ? $lido : []) + self::ESTADO_VAZIO;
            $original = $estado;

            try {
                return $operacao($estado);
            } finally {
                if ($estado !== $original) {
                    ftruncate($handle, 0);
                    rewind($handle);
                    fwrite($handle, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    fflush($handle);
                }
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function naSearchBase(string $dn): bool
    {
        return $this->searchBase !== ''
            && str_ends_with(mb_strtolower($dn), ','.mb_strtolower($this->searchBase));
    }

    private static function excecao(string $codigo): DiretorioIndisponivel|DiretorioRecusou
    {
        $codigo = CodigoErroDiretorio::from($codigo);

        return $codigo->indicaIndisponibilidade() ? new DiretorioIndisponivel($codigo) : new DiretorioRecusou($codigo);
    }

    private static function guid(string $guid): string
    {
        return mb_strtolower(trim($guid));
    }
}
