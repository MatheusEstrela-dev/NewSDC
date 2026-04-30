<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI driver that will be used to serve
    | requests. You may set this to any of the connections defined in the
    | "drivers" array below.
    |
    | Supported: "openai", "claude", "gemini", "ollama", "mock"
    |
    */

    'default_driver' => env('AI_DRIVER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | AI Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each driver.
    |
    */

    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
        ],

        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('CLAUDE_MODEL', 'claude-3-opus-20240229'),
        ],

        'gemini' => [
            'api_key'    => env('GEMINI_API_KEY'),
            'model'      => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base_url'   => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 4096),
        ],

        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3'),
            'timeout' => env('OLLAMA_TIMEOUT', 120),
        ],

        'mock' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | The default system prompt to be used when no specific system prompt
    | is provided in the request options.
    |
    */

    'system_prompt' => null,

    /*
    |--------------------------------------------------------------------------
    | Conversation Settings
    |--------------------------------------------------------------------------
    */

    'conversation' => [
        'store_messages' => true,
        'max_history' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Configuration
    |--------------------------------------------------------------------------
    */

    'tools' => [
        'enabled' => true,
    ],

];
