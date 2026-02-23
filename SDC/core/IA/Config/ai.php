<?php

return [
    'default_driver' => env('AI_DEFAULT_DRIVER', 'openai'),

    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
        ],

        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4096),
            'temperature' => env('ANTHROPIC_TEMPERATURE', 0.7),
        ],

        'gemini' => [
            'api_key' => env('GOOGLE_AI_API_KEY'),
            'model' => env('GOOGLE_AI_MODEL', 'gemini-pro'),
            'base_url' => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'max_tokens' => env('GOOGLE_AI_MAX_TOKENS', 4096),
            'temperature' => env('GOOGLE_AI_TEMPERATURE', 0.7),
        ],
    ],

    'conversation' => [
        'max_history' => env('AI_MAX_HISTORY', 20),
        'ttl_minutes' => env('AI_CONVERSATION_TTL', 60),
        'store_messages' => env('AI_STORE_MESSAGES', true),
    ],

    'system_prompt' => env('AI_SYSTEM_PROMPT', 'Voce e o Assistente Virtual da Defesa Civil de Minas Gerais (SDC).
Seu papel e ajudar usuarios com:
- Consultas de protocolos RAT (Relatorio de Atendimento Tecnico)
- Informacoes meteorologicas e alertas
- Status de ajuda humanitaria
- Geracao de relatorios
- Consultas sobre procedimentos da Defesa Civil

Seja sempre objetivo, profissional e prestativo. Responda em portugues brasileiro.'),

    'tools' => [
        'enabled' => env('AI_TOOLS_ENABLED', true),
        'timeout' => env('AI_TOOLS_TIMEOUT', 30),
    ],

    'rate_limit' => [
        'enabled' => env('AI_RATE_LIMIT_ENABLED', true),
        'max_requests_per_minute' => env('AI_RATE_LIMIT_RPM', 10),
    ],

    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'channel' => env('AI_LOG_CHANNEL', 'daily'),
    ],
];
