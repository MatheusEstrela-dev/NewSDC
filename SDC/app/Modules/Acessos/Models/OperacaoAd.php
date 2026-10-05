<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Models;

use App\Models\User;
use App\Modules\Acessos\DTOs\ResultadoOperacao;
use App\Modules\Acessos\Enums\AcaoDiretorio;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Enums\EstadoOperacaoAd;
use App\Modules\Acessos\Enums\OrigemOperacaoAd;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Razao unica de toda acao no AD, venha da tela de Acessos ou da automacao de
 * Demandas. O erro persistido e so o codigo da lista fechada; nunca texto do LDAP.
 *
 * Atribuicao em massa so para os campos do pedido: estado, tentativas, erro e
 * resultado mudam apenas pelos metodos de transicao, que respeitam
 * EstadoOperacaoAd::podeIrPara.
 */
class OperacaoAd extends Model
{
    use HasUuids;

    protected $table = 'acessos_operacoes_ad';

    protected $fillable = [
        'chave_idempotencia', 'cadastro_id', 'login_ad', 'object_guid', 'acao', 'origem', 'origem_id',
        'solicitado_por_id', 'motivo',
    ];

    protected $attributes = [
        'estado' => 'solicitado',
        'tentativas' => 0,
    ];

    protected function casts(): array
    {
        return [
            'acao' => AcaoDiretorio::class,
            'estado' => EstadoOperacaoAd::class,
            'origem' => OrigemOperacaoAd::class,
            'codigo_erro' => CodigoErroDiretorio::class,
            'origem_id' => 'integer',
            'tentativas' => 'integer',
            'resultado' => 'array',
            'enviado_em' => 'datetime',
            'concluido_em' => 'datetime',
        ];
    }

    public function cadastro(): BelongsTo
    {
        return $this->belongsTo(CadastroAcesso::class, 'cadastro_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por_id');
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(EntregaSenha::class, 'operacao_id');
    }

    public function estaEmVoo(): bool
    {
        return ! $this->estaFinalizada();
    }

    public function estaFinalizada(): bool
    {
        return $this->estado->final();
    }

    /**
     * Entregue ao worker. `$contarTentativa` false quando o job so volta para a
     * fila sem chamar o AD (circuito aberto).
     */
    public function marcarEnviada(bool $contarTentativa = true): void
    {
        $this->transicionar(EstadoOperacaoAd::ENVIADO, [
            'enviado_em' => now(),
            'tentativas' => $this->tentativas + ($contarTentativa ? 1 : 0),
        ]);
    }

    /** Confirmada pelo AD; `resultado` guarda so a lista fechada de booleanos. */
    public function confirmar(ResultadoOperacao $resultado): void
    {
        $conta = $resultado->contaDepois;

        $this->transicionar(EstadoOperacaoAd::CONFIRMADO, [
            'codigo_erro' => null,
            'resultado' => [
                'efetivada' => $resultado->efetivada,
                'ativa' => $conta->ativa,
                'bloqueada' => $conta->bloqueada,
                'troca_senha_pendente' => $conta->trocaSenhaPendente,
            ],
            'concluido_em' => now(),
        ]);
    }

    public function falhar(CodigoErroDiretorio $codigo): void
    {
        $this->transicionar(EstadoOperacaoAd::FALHOU, [
            'codigo_erro' => $codigo,
            'concluido_em' => now(),
        ]);
    }

    /** Grava o alvo resolvido na execucao, se ainda nao conhecido. */
    public function vincularConta(string $objectGuid): void
    {
        if ($this->object_guid === null) {
            $this->forceFill(['object_guid' => $objectGuid])->save();
        }
    }

    /**
     * Trilha `diretorio_<acao>_<etapa>` no cadastro (se houver). `dados` leva so
     * operacao_id e o que o chamador passar (codigo_erro, motivo): nenhum segredo.
     *
     * @param array<string, string> $dados
     */
    public function registrarAuditoria(string $etapa, array $dados = []): void
    {
        if ($this->cadastro_id === null) {
            return;
        }

        AuditoriaAcesso::create([
            'cadastro_id' => $this->cadastro_id,
            'actor_id' => $this->solicitado_por_id,
            'acao' => sprintf('diretorio_%s_%s', $this->acao->value, $etapa),
            'dados' => ['operacao_id' => $this->id] + $dados,
            'created_at' => now(),
        ]);
    }

    /**
     * Rede de seguranca contra forceFill/update fora dos metodos de transicao:
     * toda operacao nasce `solicitado` e todo estado gravado respeita podeIrPara.
     */
    protected static function booted(): void
    {
        static::saving(static function (self $operacao): void {
            if (! $operacao->exists) {
                if ($operacao->estado !== EstadoOperacaoAd::SOLICITADO) {
                    throw self::transicaoInvalida($operacao, null, $operacao->estado);
                }

                return;
            }
            if ($operacao->isDirty('estado')) {
                $anterior = EstadoOperacaoAd::from((string) $operacao->getRawOriginal('estado'));
                if (! $anterior->podeIrPara($operacao->estado)) {
                    throw self::transicaoInvalida($operacao, $anterior, $operacao->estado);
                }
            }
        });
    }

    /** @param array<string, mixed> $atributos */
    private function transicionar(EstadoOperacaoAd $novo, array $atributos): void
    {
        if (! $this->estado->podeIrPara($novo)) {
            throw self::transicaoInvalida($this, $this->estado, $novo);
        }

        $this->forceFill(['estado' => $novo] + $atributos)->save();
    }

    private static function transicaoInvalida(self $operacao, ?EstadoOperacaoAd $de, EstadoOperacaoAd $para): LogicException
    {
        return new LogicException(sprintf(
            'Transicao invalida da operacao %s: %s -> %s.',
            $operacao->id ?? '(nova)',
            $de->value ?? '(nova)',
            $para->value,
        ));
    }
}
