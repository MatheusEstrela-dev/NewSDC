<?php

declare(strict_types=1);

namespace App\Modules\Notificacoes\DTO;

use InvalidArgumentException;

/**
 * Descreve UMA notificacao a ser entregue, sem saber quem recebe nem por qual
 * canal. E o contrato que os gatilhos de dominio preenchem: eles nao conhecem
 * inbox, fila, websocket ou preferencia de usuario.
 *
 * O payload e deliberadamente enxuto (titulo, mensagem curta e referencia de
 * acao). Icone e cor sao responsabilidade do frontend, a partir de tipo.
 */
final readonly class NotificacaoSpec
{
    public const TIPOS = ['info', 'success', 'warning', 'error', 'urgent'];

    /**
     * @param  string  $modulo    slug de config('notificacoes.modulos'), define preferencia e janela
     * @param  string|null  $groupKey  assunto para agrupamento, no formato "modulo:identificador".
     *                                 Null desliga o agrupamento desta notificacao.
     */
    public function __construct(
        public string $modulo,
        public string $titulo,
        public string $mensagem,
        public string $tipo = 'info',
        public ?string $groupKey = null,
        public ?string $acaoUrl = null,
        public ?string $acaoTexto = null,
    ) {
        if (!in_array($this->tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException(
                "Tipo de notificacao invalido: {$this->tipo}. Esperado um de: ".implode(', ', self::TIPOS)
            );
        }

        if ($this->titulo === '' || $this->mensagem === '') {
            throw new InvalidArgumentException('Notificacao exige titulo e mensagem.');
        }
    }

    /**
     * Notificacao urgente entra na fila de maior prioridade.
     */
    public function ehUrgente(): bool
    {
        return $this->tipo === 'urgent';
    }

    /**
     * Copia da spec com outro group_key. Usado quando a janela do modulo e zero:
     * o dispatcher zera a chave para que a linha nunca seja agrupada.
     */
    public function semAgrupamento(): self
    {
        return new self(
            modulo: $this->modulo,
            titulo: $this->titulo,
            mensagem: $this->mensagem,
            tipo: $this->tipo,
            groupKey: null,
            acaoUrl: $this->acaoUrl,
            acaoTexto: $this->acaoTexto,
        );
    }
}
