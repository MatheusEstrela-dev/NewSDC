<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Services;

use App\Models\User;
use App\Modules\Acessos\Enums\AcaoDiretorio;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use App\Modules\Acessos\Exceptions\OperacaoDiretorioProibida;
use App\Modules\Acessos\Services\SolicitarOperacaoDiretorio;
use App\Modules\Demandas\Enums\AcaoHistoricoDemanda;
use App\Modules\Demandas\Models\Demanda;
use App\Modules\Demandas\Models\DemandaAuditLog;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Botao "Automacao" do chamado. No legado era shell_exec de script Python com
 * assunto fixo no codigo; aqui a acao vem do assunto e o login do campo que o
 * solicitante preencheu, e o pedido ao AD passa pelo caso de uso de Acessos
 * (operacao, fila `diretorio` e guardas de alvo). O resultado chega ao
 * historico pelo listener RegistraResultadoAutomacao.
 *
 * Autorizacao: a policy `automatizar` da demanda (spec 6.6); o caso de uso
 * aplica as regras comuns (login, propria conta, conta protegida).
 */
final class ExecutarAutomacaoDemanda
{
    public const ACOES = ['desbloquear', 'ativar', 'resetar'];

    public const MAPA_ACAO = [
        'desbloquear' => AcaoDiretorio::DESBLOQUEAR,
        'ativar' => AcaoDiretorio::HABILITAR,
        'resetar' => AcaoDiretorio::REDEFINIR_SENHA,
    ];

    // sAMAccountName: letras, digitos, ponto, hifen e sublinhado; ate 20.
    private const LOGIN_VALIDO = '/^[A-Za-z0-9._-]{1,20}$/';

    private const RESET_INDISPONIVEL = 'Redefinição de senha pela demanda ainda não disponível.';

    public function __construct(
        private readonly HistoricoDemanda $historico,
        private readonly SolicitarOperacaoDiretorio $diretorio,
    ) {}

    public function solicitar(Demanda $demanda, int $userId): string
    {
        $config = $demanda->assunto?->form_automacao;
        $acao = $config['acao'] ?? null;
        $acaoAd = is_string($acao) ? (self::MAPA_ACAO[$acao] ?? null) : null;
        if ($acaoAd === null) {
            throw new DomainException('O assunto desta demanda não tem automação configurada.');
        }

        $login = trim((string) ($demanda->campos_customizados[$config['campo_login'] ?? ''] ?? ''));
        if (preg_match(self::LOGIN_VALIDO, $login) !== 1) {
            throw new DomainException('Login AD ausente ou inválido no campo "'.($config['campo_login'] ?? '').'".');
        }

        // Sem o handler do reset nao ha entrega de leitura unica: a senha nova
        // nao chegaria a ninguem. Recusa antes de criar operacao ou historico.
        if ($acaoAd === AcaoDiretorio::REDEFINIR_SENHA && ! class_exists($acaoAd->handler())) {
            throw new DomainException(self::RESET_INDISPONIVEL);
        }

        $ator = User::query()->findOrFail($userId);

        try {
            return DB::transaction(function () use ($demanda, $userId, $ator, $acao, $acaoAd, $login): string {
                $sequencia = DemandaAuditLog::query()
                    ->where('task_id', $demanda->id)
                    ->where('acao', AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA->value)
                    ->count() + 1;
                $operationId = sprintf('demanda:%d:%s:%d', $demanda->id, $acao, $sequencia);

                // Pedido igual ja em voo para o login: o caso de uso devolve a
                // operacao existente, e o historico aponta para ela.
                $operacao = $this->diretorio->paraLogin(
                    $login, $acaoAd, $ator, OrigemOperacaoAd::DEMANDA, $demanda->id, $operationId,
                );

                $this->historico->registrar(
                    $demanda, $userId, AcaoHistoricoDemanda::AUTOMACAO_SOLICITADA,
                    sprintf('Solicitado "%s" para o login %s.', $acao, $login),
                    ['operation_id' => $operacao->chave_idempotencia],
                );

                return $operacao->chave_idempotencia;
            });
        } catch (OperacaoDiretorioProibida $e) {
            throw new DomainException($e->getMessage());
        }
    }
}
