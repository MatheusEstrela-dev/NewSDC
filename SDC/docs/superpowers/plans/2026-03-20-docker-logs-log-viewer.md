# Docker Logs no Log Viewer — Implementação via Docker Socket API

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expor os logs de todos os containers Docker (`docker logs <container>`) diretamente no Log Viewer existente, via nova aba "Docker" no topbar, sem dependências externas.

**Architecture:** O container `app` acessa o Docker daemon via Unix socket (`/var/run/docker.sock`) montado como volume. `DockerLogService` faz requisições HTTP ao socket usando `curl` nativo do PHP. `DockerLogController` expõe dois endpoints: listar containers e buscar logs de um container. O frontend adiciona uma aba "Docker" ao LogViewerTopbar e um novo componente `LogViewerDocker.vue` que lista containers e exibe os logs.

**Tech Stack:** PHP 8.3, Laravel 11, Vue 3.4, Tailwind CSS 3.2, Docker API v1.41 (sem novas dependências)

---

## Mapa de Arquivos

| Arquivo | Ação | Responsabilidade |
|---------|------|-----------------|
| `docker/docker-compose.yml` | Modificar | Montar `/var/run/docker.sock` no service `app` |
| `app/Services/Logging/DockerLogService.php` | Criar | Comunicação com Docker API via Unix socket |
| `app/Http/Controllers/Api/V1/DockerLogController.php` | Criar | Endpoints REST — lista containers e logs |
| `routes/web.php` | Modificar | Adicionar rotas `/api/v1/docker/...` protegidas por `can:system.logs.view` |
| `resources/js/Components/Organisms/LogViewer/LogViewerDocker.vue` | Criar | Selector de container + tabela de logs Docker |
| `resources/js/Components/Molecules/LogViewer/LogViewerTopbar.vue` | Modificar | Adicionar toggle "Docker" ao topbar |
| `resources/js/Pages/LogViewer/Index.vue` | Modificar | Integrar `LogViewerDocker` condicionalmente |

---

## Contexto da Docker API

### Endpoint containers
`GET /containers/json` via socket `unix:///var/run/docker.sock`

Resposta relevante por container:
```json
[
  {
    "Id": "abc123...",
    "Names": ["/newsdc_app"],
    "Image": "newsdc-app:latest",
    "Status": "Up 2 hours (healthy)",
    "State": "running"
  }
]
```

### Endpoint logs
`GET /containers/{id}/logs?stdout=1&stderr=1&tail=500&timestamps=1`

Resposta: stream de bytes com header multiplexado (8 bytes por frame: 1 byte stream type, 3 bytes zero, 4 bytes tamanho, depois conteúdo). Precisa de parser.

### PHP via curl + Unix socket
```php
$ch = curl_init();
curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/docker.sock');
curl_setopt($ch, CURLOPT_URL, 'http://localhost/containers/json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
```

---

## Task 1: Docker Compose — Montar o socket

**Arquivo:** `docker/docker-compose.yml` (service `app`, bloco `volumes`, linha ~30)

- [ ] Adicionar o socket ao bloco `volumes` do service `app`:
```yaml
volumes:
  - ../:/var/www:cached
  - newsdc_vendor:/var/www/vendor
  - ./logs/php:/var/log/php
  - /var/run/docker.sock:/var/run/docker.sock:ro
```

- [ ] Recriar o container para aplicar o mount:
```bash
cd SDC/docker && docker compose up -d --force-recreate app
```

- [ ] Verificar que o socket está acessível dentro do container:
```bash
docker exec newsdc_app ls -la /var/run/docker.sock
```
Esperado: `srw-rw---- ... /var/run/docker.sock`

---

## Task 2: Backend — `DockerLogService`

**Arquivo:** `app/Services/Logging/DockerLogService.php` (criar)

- [ ] Criar o service:
```php
<?php

namespace App\Services\Logging;

class DockerLogService
{
    private string $socketPath;

    public function __construct()
    {
        $this->socketPath = config('docker.socket_path', '/var/run/docker.sock');
    }

    public function isAvailable(): bool
    {
        return file_exists($this->socketPath) && is_readable($this->socketPath);
    }

    public function listContainers(): array
    {
        $response = $this->socketRequest('GET', '/containers/json?all=0');

        if ($response === null) {
            return [];
        }

        return array_map(fn($c) => [
            'id'     => substr($c['Id'] ?? '', 0, 12),
            'id_full'=> $c['Id'] ?? '',
            'name'   => ltrim($c['Names'][0] ?? $c['Id'] ?? 'unknown', '/'),
            'image'  => $c['Image'] ?? '',
            'status' => $c['Status'] ?? '',
            'state'  => $c['State'] ?? '',
        ], $response);
    }

    public function getContainerLogs(string $containerId, int $tail = 300): array
    {
        $query = http_build_query([
            'stdout'     => 1,
            'stderr'     => 1,
            'tail'       => $tail,
            'timestamps' => 1,
        ]);

        $raw = $this->socketRequest('GET', "/containers/{$containerId}/logs?{$query}", raw: true);

        if ($raw === null) {
            return [];
        }

        return $this->parseDockerLogStream($raw);
    }

    private function socketRequest(string $method, string $path, bool $raw = false): array|string|null
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL              => 'http://localhost' . $path,
            CURLOPT_RETURNTRANSFER   => true,
            CURLOPT_TIMEOUT          => 10,
            CURLOPT_CUSTOMREQUEST    => $method,
        ]);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            return null;
        }

        if ($raw) {
            return $body;
        }

        $decoded = json_decode($body, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function parseDockerLogStream(string $raw): array
    {
        $lines = [];
        $offset = 0;
        $length = strlen($raw);

        while ($offset < $length) {
            if ($offset + 8 > $length) {
                break;
            }

            $header     = substr($raw, $offset, 8);
            $streamType = ord($header[0]);
            $frameSize  = unpack('N', substr($header, 4, 4))[1];
            $offset    += 8;

            if ($offset + $frameSize > $length) {
                break;
            }

            $content = substr($raw, $offset, $frameSize);
            $offset += $frameSize;

            foreach (explode("\n", $content) as $line) {
                $line = rtrim($line);
                if ($line === '') {
                    continue;
                }

                // Extrai timestamp RFC3339 do inicio (gerado por --timestamps=1)
                $timestamp = null;
                $message   = $line;
                if (preg_match('/^(\d{4}-\d{2}-\d{2}T[\d:.]+Z)\s(.*)$/', $line, $m)) {
                    $timestamp = $m[1];
                    $message   = $m[2];
                }

                $lines[] = [
                    'stream'    => $streamType === 2 ? 'stderr' : 'stdout',
                    'timestamp' => $timestamp,
                    'message'   => $message,
                    'level'     => $streamType === 2 ? 'error' : $this->detectLevel($message),
                ];
            }
        }

        return array_reverse($lines); // mais recente primeiro
    }

    private function detectLevel(string $message): string
    {
        $lower = strtolower($message);
        if (str_contains($lower, 'critical') || str_contains($lower, 'fatal'))  return 'critical';
        if (str_contains($lower, 'error'))    return 'error';
        if (str_contains($lower, 'warn'))     return 'warning';
        if (str_contains($lower, 'debug'))    return 'debug';
        return 'info';
    }
}
```

- [ ] Criar `config/docker.php` para configurar o socket_path:
```php
<?php
return [
    'socket_path' => env('DOCKER_SOCKET_PATH', '/var/run/docker.sock'),
];
```

---

## Task 3: Backend — `DockerLogController`

**Arquivo:** `app/Http/Controllers/Api/V1/DockerLogController.php` (criar)

- [ ] Criar o controller:
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Logging\DockerLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DockerLogController extends Controller
{
    public function __construct(private DockerLogService $docker) {}

    public function containers(): JsonResponse
    {
        if (!$this->docker->isAvailable()) {
            return response()->json([
                'available' => false,
                'message'   => 'Docker socket nao acessivel. Monte /var/run/docker.sock no container.',
                'containers' => [],
            ]);
        }

        return response()->json([
            'available'  => true,
            'containers' => $this->docker->listContainers(),
        ]);
    }

    public function logs(Request $request, string $containerId): JsonResponse
    {
        $tail = (int) $request->query('tail', 300);
        $tail = max(50, min(1000, $tail));

        if (!$this->docker->isAvailable()) {
            return response()->json(['error' => 'Docker socket nao acessivel'], 503);
        }

        $logs = $this->docker->getContainerLogs($containerId, $tail);

        return response()->json([
            'container_id' => $containerId,
            'tail'         => $tail,
            'count'        => count($logs),
            'logs'         => $logs,
        ]);
    }
}
```

---

## Task 4: Rotas

**Arquivo:** `routes/web.php` — adicionar após as rotas do Log Viewer (linha ~101)

- [ ] Adicionar rotas protegidas pelo mesmo middleware `can:system.logs.view`:
```php
// Docker Logs — acesso aos logs dos containers via Docker Socket API
Route::middleware(['auth', 'can:system.logs.view'])->prefix('api/v1/docker')->group(function () {
    Route::get('/containers', [\App\Http\Controllers\Api\V1\DockerLogController::class, 'containers'])
        ->name('docker.containers');
    Route::get('/containers/{containerId}/logs', [\App\Http\Controllers\Api\V1\DockerLogController::class, 'logs'])
        ->name('docker.logs');
});
```

- [ ] Verificar que as rotas aparecem:
```bash
docker exec newsdc_app php artisan route:list | grep docker
```
Esperado: duas rotas `GET` listadas.

---

## Task 5: Frontend — `LogViewerDocker.vue`

**Arquivo:** `resources/js/Components/Organisms/LogViewer/LogViewerDocker.vue` (criar)

- [ ] Criar o componente:
```vue
<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const containers = ref([])
const selectedId = ref(null)
const logs = ref([])
const loading = ref(false)
const loadingContainers = ref(false)
const available = ref(true)
const tail = ref(300)
const search = ref('')

const filteredLogs = () => {
    if (!search.value) return logs.value
    const q = search.value.toLowerCase()
    return logs.value.filter(l => l.message.toLowerCase().includes(q))
}

const fetchContainers = async () => {
    loadingContainers.value = true
    try {
        const { data } = await axios.get('/api/v1/docker/containers')
        available.value = data.available
        containers.value = data.containers || []
        if (containers.value.length && !selectedId.value) {
            selectedId.value = containers.value[0].id
        }
    } finally {
        loadingContainers.value = false
    }
}

const fetchLogs = async () => {
    if (!selectedId.value) return
    loading.value = true
    try {
        const { data } = await axios.get(`/api/v1/docker/containers/${selectedId.value}/logs`, {
            params: { tail: tail.value }
        })
        logs.value = data.logs || []
    } finally {
        loading.value = false
    }
}

const getLevelColor = (level) => {
    const map = {
        critical: 'text-red-400',
        error:    'text-orange-400',
        warning:  'text-yellow-400',
        debug:    'text-gray-500',
        info:     'text-blue-400',
    }
    return map[level] || 'text-gray-400'
}

const getLevelBg = (level) => {
    const map = {
        critical: 'bg-red-500',
        error:    'bg-orange-500',
        warning:  'bg-yellow-500',
        debug:    'bg-gray-600',
        info:     'bg-blue-500',
    }
    return map[level] || 'bg-gray-600'
}

const formatTs = (ts) => {
    if (!ts) return ''
    try {
        return new Date(ts).toLocaleTimeString('pt-BR', { hour12: false })
    } catch { return ts }
}

const getContainerStatusColor = (state) => {
    if (state === 'running') return 'bg-green-500'
    if (state === 'exited')  return 'bg-red-500'
    return 'bg-yellow-500'
}

watch(selectedId, () => fetchLogs())

onMounted(() => {
    fetchContainers().then(() => fetchLogs())
})
</script>

<template>
    <div class="flex flex-col h-full bg-[#0b0e14]">
        <!-- Unavailable banner -->
        <div v-if="!available" class="flex items-center gap-3 px-6 py-3 bg-yellow-950/40 border-b border-yellow-900/50">
            <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span class="text-xs text-yellow-300">Docker socket nao acessivel. Adicione <code class="font-mono bg-yellow-900/50 px-1 rounded">/var/run/docker.sock:/var/run/docker.sock:ro</code> ao service <code class="font-mono bg-yellow-900/50 px-1 rounded">app</code> no docker-compose.yml e recrie o container.</span>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-800 bg-gray-900/40 shrink-0 flex-wrap">
            <!-- Container selector -->
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-gray-500 uppercase tracking-widest">Container:</span>
                <select
                    v-model="selectedId"
                    class="text-xs bg-gray-800 border border-gray-700 text-gray-200 rounded px-2 py-1 font-mono focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                    :disabled="loadingContainers || !available"
                >
                    <option v-if="loadingContainers" value="">Carregando...</option>
                    <option v-for="c in containers" :key="c.id" :value="c.id">
                        {{ c.name }}
                    </option>
                </select>
            </div>

            <!-- Container status badges -->
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="c in containers"
                    :key="c.id"
                    @click="selectedId = c.id"
                    class="flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono border transition-colors"
                    :class="selectedId === c.id
                        ? 'border-blue-700 bg-blue-900/30 text-blue-200'
                        : 'border-gray-700 bg-gray-800/50 text-gray-400 hover:border-gray-600'"
                >
                    <span class="w-1.5 h-1.5 rounded-full" :class="getContainerStatusColor(c.state)"></span>
                    {{ c.name }}
                </button>
            </div>

            <div class="flex-1"></div>

            <!-- Tail selector -->
            <select
                v-model="tail"
                @change="fetchLogs"
                class="text-[10px] bg-gray-800 border border-gray-700 text-gray-400 rounded px-2 py-1 font-mono"
            >
                <option :value="100">100 linhas</option>
                <option :value="300">300 linhas</option>
                <option :value="500">500 linhas</option>
                <option :value="1000">1000 linhas</option>
            </select>

            <!-- Search -->
            <div class="relative">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Filtrar..."
                    class="text-xs bg-gray-800 border border-gray-700 text-gray-200 rounded pl-7 pr-3 py-1 w-44 font-mono placeholder-gray-600 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                />
                <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Refresh -->
            <button
                @click="fetchLogs"
                :disabled="loading"
                class="p-1.5 text-gray-400 hover:text-white transition-colors"
                title="Atualizar"
            >
                <svg class="w-4 h-4" :class="loading ? 'animate-spin text-blue-500' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>

        <!-- Logs table -->
        <div class="flex-1 overflow-auto custom-scrollbar">
            <div v-if="loading" class="flex items-center justify-center h-32">
                <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div v-else-if="!filteredLogs().length" class="flex flex-col items-center justify-center h-32 text-gray-600">
                <p class="text-sm">Nenhum log encontrado</p>
            </div>
            <table v-else class="w-full text-xs font-mono border-collapse">
                <thead class="sticky top-0 bg-[#0b0e14] border-b border-gray-800 z-10">
                    <tr class="text-left text-gray-600 uppercase tracking-tighter h-8">
                        <th class="px-3 w-16">Stream</th>
                        <th class="px-2 w-20">Level</th>
                        <th class="px-2 w-20 text-right">Hora</th>
                        <th class="px-3">Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(log, i) in filteredLogs()"
                        :key="i"
                        class="border-b border-gray-800/30 hover:bg-gray-800/30 h-8"
                    >
                        <td class="px-3">
                            <span class="text-[9px] font-bold uppercase" :class="log.stream === 'stderr' ? 'text-red-500' : 'text-gray-600'">
                                {{ log.stream }}
                            </span>
                        </td>
                        <td class="px-2">
                            <span class="flex items-center gap-1 uppercase font-bold text-[10px]" :class="getLevelColor(log.level)">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getLevelBg(log.level)"></span>
                                {{ log.level }}
                            </span>
                        </td>
                        <td class="px-2 text-right text-gray-600 whitespace-nowrap">
                            {{ formatTs(log.timestamp) }}
                        </td>
                        <td class="px-3 text-gray-300 truncate max-w-0">
                            {{ log.message }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="px-6 py-2 border-t border-gray-800 bg-gray-900 text-[10px] text-gray-600 flex justify-between shrink-0">
            <span>Linhas: <span class="text-gray-400 font-mono">{{ filteredLogs().length }}</span></span>
            <span class="italic">Docker Socket API</span>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #0b0e14; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
</style>
```

---

## Task 6: Frontend — Topbar com aba Docker

**Arquivo:** `resources/js/Components/Molecules/LogViewer/LogViewerTopbar.vue`

- [ ] Adicionar prop `view` e emit `update:view` para alternar entre modo `logs` e `docker`:

No `<script setup>`, adicionar:
```js
const props = defineProps({
    stats:   { type: Object, default: () => ({}) },
    filters: { type: Object, required: true },
    loading: Boolean,
    levels:  { type: Array, default: () => ['debug', 'info', 'warning', 'error', 'critical'] },
    view:    { type: String, default: 'logs' },   // NOVO
})

const emit = defineEmits(['update:filters', 'refresh', 'update:view'])   // NOVO: update:view
```

- [ ] Adicionar botões de modo no início do template (antes do bloco dos níveis):
```html
<!-- Mode switcher -->
<div class="flex items-center gap-1 bg-gray-800/60 rounded-lg p-0.5 border border-gray-700 shrink-0">
    <button
        @click="emit('update:view', 'logs')"
        class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all"
        :class="view === 'logs'
            ? 'bg-blue-900/60 text-blue-200 border border-blue-700'
            : 'text-gray-500 hover:text-gray-300'"
    >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Logs
    </button>
    <button
        @click="emit('update:view', 'docker')"
        class="flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold transition-all"
        :class="view === 'docker'
            ? 'bg-cyan-900/60 text-cyan-200 border border-cyan-700'
            : 'text-gray-500 hover:text-gray-300'"
    >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Docker
    </button>
</div>
```

- [ ] Envolver os controles de nível/busca em `v-show="view === 'logs'"` para sumir no modo Docker:
```html
<div v-show="view === 'logs'" class="flex flex-wrap gap-2">
    <!-- botoes de nivel existentes -->
</div>
<div v-show="view === 'logs'" class="flex items-center gap-3 flex-1 min-w-[300px] justify-end">
    <!-- busca + refresh + settings existentes -->
</div>
```

---

## Task 7: Frontend — `LogViewer/Index.vue` — integrar Docker view

**Arquivo:** `resources/js/Pages/LogViewer/Index.vue`

- [ ] Ler o arquivo para entender a estrutura atual antes de modificar.

- [ ] Importar o novo componente:
```js
import LogViewerDocker from '@/Components/Organisms/LogViewer/LogViewerDocker.vue'
```

- [ ] Adicionar state de view:
```js
const currentView = ref('logs')
```

- [ ] Passar `view` e `@update:view` ao `LogViewerTopbar`:
```html
<LogViewerTopbar
    :stats="stats"
    v-model:filters="filters"
    :loading="loading"
    :view="currentView"
    @update:view="currentView = $event"
    @refresh="loadLogs"
/>
```

- [ ] Condicionalmente renderizar `LogViewerDocker` ou a tabela existente:
```html
<LogViewerDocker v-if="currentView === 'docker'" class="flex-1" />
<template v-else>
    <!-- conteudo existente da tabela + detail -->
</template>
```

---

## Task 8: Build e Verificação

- [ ] Executar o build:
```bash
cd SDC && npm run build
```
Esperado: `ok (no errors)`

- [ ] Recriar o container `app` para montar o socket:
```bash
cd SDC/docker && docker compose up -d --force-recreate app
```

- [ ] Aguardar container ficar `healthy`:
```bash
docker ps --filter "name=newsdc_app" --format "{{.Status}}"
```

- [ ] Testar endpoint de containers via curl:
```bash
curl -s -b "cookies..." http://localhost:18001/api/v1/docker/containers | python -m json.tool
```
Esperado: JSON com `available: true` e lista de containers.

- [ ] Abrir `http://localhost:18001/log-viewer` e clicar em "Docker" no topbar — verificar:
  - Lista de containers aparece como chips coloridos (verde=running, vermelho=exited)
  - Ao selecionar um container os logs aparecem na tabela
  - Coluna `stream` mostra `stdout`/`stderr`
  - Coluna `level` detecta erros/warnings nas mensagens

- [ ] Se socket não acessível: banner amarelo explica que precisa montar `/var/run/docker.sock`

- [ ] Verificar que o modo "Logs" original continua funcionando normalmente após troca de view

---

## Arquivos Críticos

| Arquivo | Linhas chave |
|---------|-------------|
| `docker/docker-compose.yml` | volumes do service `app` (~linha 36) — adicionar socket |
| `app/Services/Logging/DockerLogService.php` | Novo — socket + parser de stream multiplexado |
| `app/Http/Controllers/Api/V1/DockerLogController.php` | Novo — endpoints containers + logs |
| `routes/web.php` | Após linha 101 — novas rotas docker |
| `resources/js/Components/Organisms/LogViewer/LogViewerDocker.vue` | Novo — UI completa |
| `resources/js/Components/Molecules/LogViewer/LogViewerTopbar.vue` | Props view + toggle buttons |
| `resources/js/Pages/LogViewer/Index.vue` | Import + currentView state + renderização condicional |
