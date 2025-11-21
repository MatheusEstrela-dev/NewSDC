# 📋 Log Viewer - Visualizador de Logs do Sistema

## 🎯 Visão Geral

O **Laravel Log Viewer** foi implementado para visualizar e gerenciar logs de eventos e erros do sistema de forma simples e intuitiva através de uma interface web.

## 🚀 Acesso

Após fazer login no sistema, acesse:

**URL**: `http://localhost/logs`

## ✨ Funcionalidades

- ✅ Visualização de todos os arquivos de log
- ✅ Filtro por nível de log (ERROR, WARNING, INFO, DEBUG)
- ✅ Busca em logs
- ✅ Visualização detalhada de cada entrada
- ✅ Download de arquivos de log
- ✅ Interface responsiva e moderna
- ✅ Protegido por autenticação

## 📊 Níveis de Log Disponíveis

O sistema suporta os seguintes níveis de log:

- **EMERGENCY** - Sistema inutilizável
- **ALERT** - Ação deve ser tomada imediatamente
- **CRITICAL** - Condições críticas
- **ERROR** - Erros de execução que não requerem ação imediata
- **WARNING** - Avisos
- **NOTICE** - Avisos normais mas significativos
- **INFO** - Informações informativas
- **DEBUG** - Informações de debug

## 🔐 Segurança

O Log Viewer está protegido por autenticação:

- ✅ Requer login no sistema
- ✅ Apenas usuários autenticados podem acessar
- ✅ Middleware `auth` aplicado

## 📁 Localização dos Logs

Os logs são armazenados em:

```
storage/logs/
├── laravel.log          # Log principal
├── laravel-2025-01-20.log  # Logs diários (se configurado)
└── ...
```

## 🛠️ Configuração

### Logs Diários (Recomendado)

Para usar logs diários, configure no `.env`:

```env
LOG_CHANNEL=daily
LOG_LEVEL=debug
```

Isso criará arquivos separados por data, facilitando a organização.

### Logs Únicos

Para usar um único arquivo de log:

```env
LOG_CHANNEL=single
LOG_LEVEL=debug
```

## 📝 Como Usar

### 1. Acessar o Log Viewer

1. Faça login no sistema
2. Navegue para `http://localhost/logs`
3. Selecione o arquivo de log desejado

### 2. Filtrar Logs

- Use o filtro de nível para ver apenas erros, warnings, etc.
- Use a busca para encontrar entradas específicas

### 3. Visualizar Detalhes

- Clique em uma entrada de log para ver detalhes completos
- Veja stack trace completo de erros
- Analise contexto e variáveis

### 4. Download

- Baixe arquivos de log completos para análise offline
- Útil para compartilhar com equipe de suporte

## 🔍 Exemplos de Uso

### Logar Erros no Código

```php
use Illuminate\Support\Facades\Log;

try {
    // Seu código aqui
} catch (\Exception $e) {
    Log::error('Erro ao processar requisição', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

### Logar Eventos Importantes

```php
Log::info('Usuário fez login', [
    'user_id' => $user->id,
    'ip' => request()->ip(),
]);

Log::warning('Tentativa de acesso não autorizado', [
    'route' => request()->path(),
    'user' => auth()->user()?->id,
]);
```

### Logar Informações de Debug

```php
Log::debug('Processando integração com API externa', [
    'api' => 'pae',
    'endpoint' => '/api/v1/empreendimentos',
    'response_time' => $responseTime,
]);
```

## 📊 Integração com Integrações Saloon

O sistema já está configurado para logar eventos das integrações:

```php
// app/Services/IntegrationTokenService.php
Log::info("Token obtido com sucesso para API: {$apiKey}");
Log::error("Erro ao obter token para API {$apiKey}: " . $e->getMessage());
```

Todos esses logs aparecerão no Log Viewer!

## 🎨 Interface

A interface do Log Viewer oferece:

- **Lista de arquivos de log** - Visualize todos os arquivos disponíveis
- **Filtros** - Por nível, data, busca
- **Visualização colorida** - Cores diferentes para cada nível
- **Stack trace** - Visualização completa de erros
- **Responsivo** - Funciona em desktop e mobile

## 🔧 Personalização

### Adicionar Middleware Customizado

Se precisar de permissões específicas, edite `routes/web.php`:

```php
Route::middleware(['auth', 'can:view-logs'])->group(function () {
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')
        ->name('logs.index');
});
```

### Configurar Caminho de Logs

Por padrão, o Log Viewer procura logs em `storage/logs/`. Se precisar mudar, publique a configuração:

```bash
php artisan vendor:publish --provider="Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider"
```

## 📚 Documentação Adicional

- [Documentação do Laravel Log Viewer](https://github.com/rap2hpoutre/laravel-log-viewer)
- [Documentação de Logs do Laravel](https://laravel.com/docs/logging)

## ✅ Checklist de Implementação

- ✅ Laravel Log Viewer instalado
- ✅ Rotas configuradas com autenticação
- ✅ Acessível em `/logs`
- ✅ Protegido por middleware `auth`
- ✅ Documentação criada

## 🚨 Notas Importantes

1. **Segurança**: O Log Viewer expõe informações sensíveis. Mantenha protegido!
2. **Performance**: Logs muito grandes podem ser lentos. Considere rotação de logs.
3. **Produção**: Em produção, considere restringir acesso apenas a administradores.

