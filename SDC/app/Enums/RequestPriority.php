<?php

namespace App\Enums;

/**
 * Enum de Prioridade de Requisições
 * Define os níveis de prioridade para processamento de requisições
 */
enum RequestPriority: string
{
    case LOW = 'low';           // Prioridade baixa - processamento assíncrono
    case NORMAL = 'normal';     // Prioridade normal - processamento padrão
    case HIGH = 'high';         // Prioridade alta - processamento prioritário
    case CRITICAL = 'critical'; // Prioridade crítica - processamento imediato
    case WEBHOOK = 'webhook';   // Webhooks externos

    /**
     * Retorna o tempo de timeout em segundos baseado na prioridade
     */
    public function timeout(): int
    {
        return match($this) {
            self::LOW => 300,      // 5 minutos
            self::NORMAL => 60,    // 1 minuto
            self::HIGH => 30,      // 30 segundos
            self::CRITICAL => 10,  // 10 segundos
            self::WEBHOOK => 45,   // 45 segundos
        };
    }

    /**
     * Retorna o número de tentativas em caso de falha
     */
    public function retries(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::CRITICAL => 5,
            self::WEBHOOK => 3,
        };
    }

    /**
     * Retorna a fila Redis apropriada
     */
    public function queue(): string
    {
        return match($this) {
            self::LOW => 'low',
            self::NORMAL => 'default',
            self::HIGH => 'high',
            self::CRITICAL => 'critical',
            self::WEBHOOK => 'webhooks',
        };
    }

    /**
     * Retorna o delay em segundos entre tentativas
     */
    public function backoff(): int
    {
        return match($this) {
            self::LOW => 60,
            self::NORMAL => 30,
            self::HIGH => 10,
            self::CRITICAL => 5,
            self::WEBHOOK => 15,
        };
    }
}
