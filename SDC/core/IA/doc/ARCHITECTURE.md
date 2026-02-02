# Arquitetura do Modulo de IA - SDC

## Visao Geral

O modulo de IA do SDC implementa uma arquitetura hibrida que combina:
- **Backend Laravel**: Orquestracao de LLMs (OpenAI, Claude, Gemini)
- **Frontend Vue**: Interface de chat com Web Workers
- **Python/WebAssembly**: Processamento local no browser via Pyodide

```
+------------------------------------------------------------------+
|                         FRONTEND (Vue.js)                         |
|  +--------------------+  +-------------------+  +----------------+ |
|  | Chat UI            |  | useAI.ts          |  | ai.worker.js   | |
|  | (Component)        |  | (Composable)      |  | (Pyodide)      | |
|  +--------------------+  +-------------------+  +----------------+ |
|                               |                        |           |
|                               v                        v           |
|                    +---------------------------+                   |
|                    |  Processamento Local       |                   |
|                    |  - Deteccao de Intent     |                   |
|                    |  - Extracao de Entidades  |                   |
|                    +---------------------------+                   |
+------------------------------------------------------------------+
                               |
                               | HTTP/SSE
                               v
+------------------------------------------------------------------+
|                      BACKEND (Laravel)                            |
|  +----------------+  +-------------------+  +------------------+   |
|  | ChatController |  | AIService         |  | LLMDriverInterface|  |
|  | (API Gateway)  |  | (Orquestrador)    |  | (Contrato)       |   |
|  +----------------+  +-------------------+  +------------------+   |
|                               |                                    |
|         +---------------------+---------------------+              |
|         v                     v                     v              |
|  +-------------+      +---------------+     +----------------+     |
|  | Drivers/    |      | Plugins/      |     | Models/        |     |
|  | - OpenAI    |      | - Vistoria    |     | - Conversation |     |
|  | - Claude    |      | - WhatsApp    |     | - Message      |     |
|  | - Gemini    |      | - Analysis    |     | - ExecutionLog |     |
|  +-------------+      +---------------+     +----------------+     |
+------------------------------------------------------------------+
```

## Estrutura de Pastas

```
core/IA/
    Contracts/
        AIPluginInterface.php      # Interface para plugins legados
        LLMDriverInterface.php     # Interface para drivers de LLM
        ToolInterface.php          # Interface para function calling
        VectorStoreInterface.php   # Interface para RAG
    Config/
        ai.php                     # Configuracoes do modulo
    Database/
        migrations/
            create_ai_conversations_table.php
            create_ai_messages_table.php
    Drivers/
        OpenAIDriver.php           # Driver GPT-4o
        ClaudeDriver.php           # Driver Claude 3.5
        GeminiDriver.php           # Driver Gemini Pro
    Http/
        Controllers/
            AIPluginController.php # API de plugins legados
            ChatController.php     # API de chat principal
    Models/
        AIConversation.php         # Model de conversas
        AIMessage.php              # Model de mensagens
        AIExecutionLog.php         # Model de logs (legado)
    Plugins/
        CivilDefense/
            VistoriaPlugin.php     # Plugin de consulta RAT
        Communication/
            WhatsAppPlugin.php     # Plugin de WhatsApp
        Analysis/
            DocumentAnalysisPlugin.php
    Resources/
        js/
            composables/
                useAI.ts           # Vue composable principal
            workers/
                ai.worker.js       # Web Worker com Pyodide
    doc/
        ARCHITECTURE.md            # Este arquivo
    AIService.php                  # Servico principal
    IAServices.php                 # Servico legado de plugins
```

## Componentes Principais

### 1. LLMDriverInterface

Interface que abstrai a comunicacao com diferentes provedores de LLM.

```php
interface LLMDriverInterface
{
    public function chat(array $messages, array $options = []): string;
    public function chatStream(array $messages, array $options = []): Generator;
    public function chatWithTools(array $tools, array $messages, array $options = []): array;
    public function embedding(string $text): array;
    public function getDriverName(): string;
    public function getModel(): string;
}
```

### 2. AIService

Orquestrador central que gerencia:
- Selecao de driver (OpenAI/Claude/Gemini)
- Historico de conversas
- Function calling (tools)
- Persistencia de mensagens

```php
$ai = new AIService('openai'); // ou 'claude', 'gemini'
$ai->registerTool(new VistoriaPlugin());
$ai->conversation($conversationId);
$response = $ai->chat("Qual o status do protocolo 12345?");
```

### 3. Drivers

Cada driver implementa a comunicacao com seu respectivo provider:

| Driver | Provider | Model Padrao | Function Calling | Embedding |
|--------|----------|--------------|------------------|-----------|
| OpenAIDriver | OpenAI | gpt-4o | Sim | Sim |
| ClaudeDriver | Anthropic | claude-3-5-sonnet | Sim | Nao |
| GeminiDriver | Google | gemini-pro | Sim | Sim |

### 4. Web Worker (Pyodide)

Executa Python no browser para processamento local:

```javascript
// Inicializacao automatica
worker.postMessage({ type: 'preprocess', text: 'Consultar RAT 12345' });

// Resposta
{
    text: 'Consultar RAT 12345',
    intent: 'rat',
    entities: { protocolo: '12345' },
}
```

**Funcionalidades:**
- Deteccao de intent (rat, meteorologia, emergencia, etc.)
- Extracao de entidades (protocolo, municipio, data)
- Limpeza de texto

### 5. Vue Composable (useAI)

Hook para integracao com componentes Vue:

```typescript
const { send, messages, isLoading, isStreaming, error } = useAI();

// Enviar mensagem
await send('Qual a previsao do tempo?');

// Enviar com streaming
await send('Gere um relatorio', true);
```

## Fluxo de Dados

```
1. Usuario digita mensagem
         |
         v
2. useAI.ts intercepta
         |
         v
3. Web Worker (Pyodide) processa localmente:
   - Limpa texto
   - Detecta intent
   - Extrai entidades
         |
         v
4. POST /api/ai/chat
   {
     message: "...",
     intent: "rat",
     conversation_id: "uuid"
   }
         |
         v
5. ChatController recebe
         |
         v
6. AIService:
   - Carrega historico
   - Monta mensagens com system prompt
   - Se tem tools: chatWithTools()
   - Senao: chat()
         |
         v
7. Driver (OpenAI/Claude/Gemini):
   - Envia para API
   - Se retorna tool_call: executa plugin
   - Retorna resposta final
         |
         v
8. Salva no banco (ai_messages)
         |
         v
9. Retorna JSON para frontend
         |
         v
10. Vue renderiza mensagem
```

## Configuracao

### Variaveis de Ambiente

```env
# Driver padrao
AI_DEFAULT_DRIVER=openai

# OpenAI
OPENAI_API_KEY=sk-xxx
OPENAI_MODEL=gpt-4o

# Anthropic (Claude)
ANTHROPIC_API_KEY=sk-ant-xxx
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022

# Google (Gemini)
GOOGLE_AI_API_KEY=xxx
GOOGLE_AI_MODEL=gemini-pro

# Conversas
AI_MAX_HISTORY=20
AI_CONVERSATION_TTL=60
AI_STORE_MESSAGES=true

# Tools
AI_TOOLS_ENABLED=true

# Rate Limit
AI_RATE_LIMIT_ENABLED=true
AI_RATE_LIMIT_RPM=10
```

### config/ai.php

Registre o config no AppServiceProvider:

```php
$this->mergeConfigFrom(
    __DIR__.'/../../core/IA/Config/ai.php', 'ai'
);
```

## Rotas API

```
POST   /api/ai/chat                    # Enviar mensagem
POST   /api/ai/chat/stream             # Enviar com streaming (SSE)
GET    /api/ai/conversations           # Listar conversas
GET    /api/ai/conversations/{id}/messages  # Mensagens de uma conversa
DELETE /api/ai/conversations/{id}      # Deletar conversa
GET    /api/ai/tools                   # Listar tools disponiveis
```

## Migrations

Execute as migrations para criar as tabelas:

```bash
php artisan migrate --path=core/IA/Database/migrations
```

**Tabelas criadas:**
- `ai_conversations`: Sessoes de conversa
- `ai_messages`: Historico de mensagens

## Criando um Novo Driver

```php
namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;

class MyCustomDriver implements LLMDriverInterface
{
    public function chat(array $messages, array $options = []): string
    {
        // Implementacao
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        // Implementacao
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        // Implementacao
    }

    public function embedding(string $text): array
    {
        // Implementacao
    }

    public function getDriverName(): string
    {
        return 'mydriver';
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
```

Registre no AIService:

```php
protected function resolveDriver(string $name): LLMDriverInterface
{
    return match ($name) {
        'openai' => new OpenAIDriver(),
        'claude' => new ClaudeDriver(),
        'gemini' => new GeminiDriver(),
        'mydriver' => new MyCustomDriver(), // Novo driver
        default => throw new InvalidArgumentException("Driver [$name] not supported"),
    };
}
```

## Criando um Novo Tool/Plugin

```php
namespace App\Core\IA\Plugins;

use App\Core\IA\Contracts\ToolInterface;

class MeuPlugin implements ToolInterface
{
    public function getName(): string
    {
        return 'meu_plugin';
    }

    public function getDescription(): string
    {
        return 'Descricao para o LLM entender quando usar';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'param1' => [
                    'type' => 'string',
                    'description' => 'Descricao do parametro',
                ],
            ],
            'required' => ['param1'],
        ];
    }

    public function validateParameters(array $parameters): bool
    {
        return isset($parameters['param1']);
    }

    public function execute(array $parameters): mixed
    {
        // Logica do plugin
        return ['resultado' => 'sucesso'];
    }

    public function toFunctionDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'parameters' => $this->getParametersSchema(),
        ];
    }
}
```

Registre no ChatController:

```php
protected function registerTools(): void
{
    $this->aiService->registerTool(new MeuPlugin());
}
```

## Beneficios da Arquitetura

| Aspecto | Beneficio |
|---------|-----------|
| **Custo** | Pre-processamento local reduz tokens enviados |
| **Latencia** | Intent detection instantaneo no browser |
| **Privacidade** | Dados sensiveis podem ser filtrados localmente |
| **Escalabilidade** | Processamento distribuido nos clientes |
| **Flexibilidade** | Troca de LLM sem alterar frontend |
| **Extensibilidade** | Novos Tools/Plugins facilmente adicionaveis |
