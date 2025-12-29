# Sistema de Log Viewer Avançado

## Visão Geral

Sistema completo de visualização de logs com filtros avançados por data, tipo, nível e busca textual. Integrado com o sistema de logging existente (ActivityLogger) e otimizado para análise de produção 24/7.

## Características

### ✅ Funcionalidades Principais

1. **Filtros Avançados**
   - Intervalo de datas (data inicial e final)
   - Tipo de log (Laravel, Events, Critical, Queries, Jobs)
   - Nível de severidade (Debug, Info, Warning, Error, Critical, etc)
   - Busca textual em mensagens e contexto

2. **Visualização de Dados**
   - Tabela responsiva com paginação
   - Modal de detalhes com contexto completo
   - Stack trace formatado para erros
   - Visualização de JSON estruturado

3. **Estatísticas e Métricas**
   - Total de logs por período
   - Taxa de erros
   - Distribuição por nível
   - Top erros mais frequentes
   - Gráficos visuais

4. **Recursos Adicionais**
   - Auto-refresh configurável (30 segundos)
   - Exportação para JSON
   - Download de arquivos de log completos
   - Atalhos rápidos (Hoje, Ontem, 7 dias, 30 dias)
   - Limpeza de logs antigos

## Arquitetura

### Backend

```
SDC/
├── app/
│   ├── Services/Logging/
│   │   ├── ActivityLogger.php           # Sistema de logging centralizado
│   │   └── LogFileReaderService.php     # Parser e leitor de arquivos de log
│   └── Http/Controllers/Api/V1/
│       └── LogViewerController.php      # API endpoints
```

### Frontend

```
SDC/resources/js/
├── Pages/LogViewer/
│   └── Index.vue                        # Página principal
└── Components/Organisms/LogViewer/
    ├── LogViewerFilters.vue             # Componente de filtros
    ├── LogViewerTable.vue               # Tabela de logs
    ├── LogViewerDetail.vue              # Modal de detalhes
    └── LogViewerStats.vue               # Estatísticas e gráficos
```

## API Endpoints

### GET /api/v1/logs
Buscar logs com filtros avançados

**Query Parameters:**
- `start_date` (string): Data inicial (formato: Y-m-d)
- `end_date` (string): Data final (formato: Y-m-d)
- `type` (string): Tipo de log (laravel, events, critical, queries, jobs)
- `level` (string): Nível (debug, info, warning, error, critical, etc)
- `search` (string): Termo de busca
- `limit` (integer): Número máximo de logs (default: 1000, max: 5000)

**Exemplo:**
```bash
GET /api/v1/logs?start_date=2025-12-20&end_date=2025-12-27&level=error&limit=100
```

**Response:**
```json
{
  "logs": [
    {
      "timestamp": "2025-12-27T15:30:45+00:00",
      "level": "error",
      "message": "Database connection failed",
      "context": {...},
      "file": "laravel-2025-12-27.log",
      "line": 145,
      "format": "json"
    }
  ],
  "total": 42,
  "filters": {
    "start_date": "2025-12-20T00:00:00+00:00",
    "end_date": "2025-12-27T23:59:59+00:00",
    "type": "all",
    "level": "error",
    "search": null
  },
  "timestamp": "2025-12-27T18:30:00+00:00"
}
```

### GET /api/v1/logs/statistics
Estatísticas agregadas dos logs

**Query Parameters:**
- `start_date` (string): Data inicial
- `end_date` (string): Data final
- `type` (string): Tipo de log

**Response:**
```json
{
  "statistics": {
    "total_logs": 1542,
    "by_level": {
      "info": 1200,
      "warning": 250,
      "error": 85,
      "critical": 7
    },
    "by_hour": {
      "2025-12-27 14:00": 45,
      "2025-12-27 15:00": 52
    },
    "by_day": {
      "2025-12-26": 742,
      "2025-12-27": 800
    },
    "top_errors": [
      {
        "message": "Database connection timeout",
        "count": 12,
        "last_occurrence": "2025-12-27T15:30:45+00:00"
      }
    ],
    "error_rate": 5.97
  },
  "period": {
    "start_date": "2025-12-20T00:00:00+00:00",
    "end_date": "2025-12-27T23:59:59+00:00",
    "days": 8
  }
}
```

### GET /api/v1/logs/files
Listar arquivos de log disponíveis

**Query Parameters:**
- `type` (string, optional): Filtrar por tipo

**Response:**
```json
{
  "files": [
    {
      "path": "/path/to/laravel-2025-12-27.log",
      "name": "laravel-2025-12-27.log",
      "size": 159183,
      "size_human": "155.45 KB",
      "modified": "2025-12-27T15:30:00+00:00",
      "type": "laravel"
    }
  ],
  "total": 10
}
```

### GET /api/v1/logs/download/{filename}
Download de arquivo de log completo

**Response:** Arquivo de texto plano

### GET /api/v1/logs/recent
Logs recentes do Redis (tempo real)

**Query Parameters:**
- `type` (string): Tipo (all, api, webhook, integration, error, performance, security)
- `limit` (integer): Número de logs (default: 100)

### DELETE /api/v1/logs/clean
Limpar logs antigos

**Query Parameters:**
- `days` (integer): Manter logs dos últimos N dias (min: 7, max: 365, default: 30)

**Response:**
```json
{
  "message": "Logs antigos removidos com sucesso",
  "deleted": 15,
  "days_kept": 30
}
```

### GET /api/v1/logs/levels
Listar níveis e tipos disponíveis

**Response:**
```json
{
  "levels": [
    {"value": "debug", "label": "Debug", "color": "gray"},
    {"value": "info", "label": "Info", "color": "blue"},
    {"value": "warning", "label": "Warning", "color": "yellow"},
    {"value": "error", "label": "Error", "color": "orange"},
    {"value": "critical", "label": "Critical", "color": "red"}
  ],
  "types": [
    {"value": "laravel", "label": "Laravel"},
    {"value": "events", "label": "Events"},
    {"value": "critical", "label": "Critical"}
  ]
}
```

## Como Usar

### 1. Acessar a Interface Web

Navegue para: `http://localhost:9115/log-viewer`

### 2. Filtrar Logs

1. Selecione o intervalo de datas desejado
2. Escolha o tipo de log (opcional)
3. Selecione o nível de severidade (opcional)
4. Digite um termo de busca (opcional)
5. Clique em "Aplicar Filtros"

**Atalhos rápidos:**
- **Hoje**: Logs de hoje
- **Ontem**: Logs de ontem
- **7 dias**: Últimos 7 dias
- **30 dias**: Últimos 30 dias

### 3. Visualizar Detalhes

Clique em qualquer linha da tabela ou no botão "Ver detalhes" para abrir o modal com informações completas:

- Mensagem completa
- Nível de severidade
- Timestamp
- Arquivo e linha
- Contexto completo (JSON)
- Stack trace (para erros)
- Informações de requisição
- User ID, IP, User Agent

### 4. Exportar Logs

Clique no botão "Exportar" para baixar os logs filtrados em formato JSON.

### 5. Auto-refresh

Ative o auto-refresh para atualizar os logs automaticamente a cada 30 segundos.

### 6. Visualizar Estatísticas

Clique em "Mostrar Estatísticas" para ver:
- Total de logs
- Número de erros e avisos
- Taxa de erro
- Distribuição por nível (gráfico de barras)
- Top 5 erros mais frequentes

## Formato dos Logs

### Logs JSON (Produção)

```json
{
  "timestamp": "2025-12-27T15:30:45+00:00",
  "event_type": "api",
  "event_name": "request",
  "severity": "info",
  "request_id": "req_abc123",
  "user_id": 42,
  "ip_address": "192.168.1.100",
  "url": "https://api.example.com/v1/users",
  "http_method": "GET",
  "environment": "production",
  "data": {...}
}
```

### Logs Laravel Padrão

```
[2025-12-27 15:30:45] local.ERROR: Database connection failed {"exception":"..."}
```

## Performance

### Otimizações Implementadas

1. **Leitura Incremental**: Arquivos são lidos linha por linha (não carrega tudo na memória)
2. **Filtros no Parse**: Filtros aplicados durante a leitura (reduz processamento)
3. **Limite Configurável**: Máximo de 5000 logs por consulta
4. **Cache de Arquivos**: Lista de arquivos é otimizada
5. **Índices por Data**: Busca apenas arquivos relevantes ao período

### Limites

- **Máximo de logs por consulta**: 5000
- **Tamanho máximo de linha**: 2000 caracteres
- **Período máximo recomendado**: 30 dias
- **Auto-refresh**: 30 segundos

## Integração com ActivityLogger

O Log Viewer utiliza o `ActivityLogger` existente para:

1. **Logs em tempo real** (Redis): Via método `getRecentLogs()`
2. **Logs históricos** (Arquivos): Via método `getLogsByDateRange()`
3. **Estatísticas**: Via método `getLogStatistics()`

### Exemplo de uso do ActivityLogger

```php
use App\Services\Logging\ActivityLogger;

// Log de API
ActivityLogger::logApiRequest(
    endpoint: '/api/v1/users',
    statusCode: 200,
    duration: 45.2,
    userId: auth()->id()
);

// Log de erro crítico
ActivityLogger::logCriticalError(
    message: 'Database connection failed',
    exception: $exception,
    context: ['database' => 'mysql']
);

// Buscar logs por data
$logs = ActivityLogger::getLogsByDateRange(
    startDate: Carbon::now()->subDays(7),
    endDate: Carbon::now(),
    level: 'error'
);
```

## Estrutura de Arquivos de Log

```
storage/logs/
├── laravel-2025-12-27.log      # Logs gerais do Laravel
├── events-2025-12-27.log       # Logs de eventos do sistema
├── critical-2025-12-27.log     # Logs críticos
├── queries-2025-12-27.log      # Queries lentas
└── jobs-2025-12-27.log         # Jobs falhados
```

## Segurança

### Proteções Implementadas

1. **Autenticação Obrigatória**: Todas as rotas requerem `auth:sanctum`
2. **Path Traversal Protection**: Validação de nomes de arquivo
3. **Rate Limiting**: Limite de requisições por minuto
4. **Validação de Inputs**: Todos os parâmetros são validados
5. **Sanitização**: Dados sensíveis podem ser mascarados

### Permissões Recomendadas

- **Visualizar logs**: Usuários autenticados
- **Limpar logs**: Apenas administradores
- **Download de logs**: Apenas administradores

## Troubleshooting

### Logs não aparecem

1. Verifique se os arquivos de log existem em `storage/logs/`
2. Verifique permissões dos arquivos (devem ser legíveis)
3. Confirme o intervalo de datas selecionado
4. Verifique se há logs no período

### Performance lenta

1. Reduza o intervalo de datas
2. Aplique filtros de tipo e nível
3. Reduza o limite de logs retornados
4. Verifique tamanho dos arquivos de log

### Erros ao carregar

1. Verifique logs do Laravel: `storage/logs/laravel.log`
2. Confirme que Redis está disponível (para logs em tempo real)
3. Verifique permissões de arquivo
4. Confirme que as rotas API estão registradas

## Manutenção

### Limpeza de Logs

**Automática**: Configure no cron para rodar semanalmente
```bash
php artisan schedule:run
```

**Manual**: Via interface web ou API
```bash
curl -X DELETE "http://localhost:9115/api/v1/logs/clean?days=30" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Monitoramento

Monitore o tamanho dos arquivos de log:
```bash
du -sh storage/logs/
```

Rotação de logs configurada em `config/logging.php`:
- `days: 14` para logs diários
- `days: 30` para logs de eventos

## Próximos Passos

- [ ] Adicionar gráficos de linha temporal
- [ ] Implementar busca com regex
- [ ] Adicionar filtros por user_id e request_id
- [ ] Integração com Grafana/Loki
- [ ] Notificações em tempo real para erros críticos
- [ ] Exportação para CSV e PDF
- [ ] Comparação entre períodos
- [ ] Alertas configuráveis

## Suporte

Para reportar bugs ou sugerir melhorias, abra uma issue no repositório.
