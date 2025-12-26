# Contexto do Projeto NewSDC

Você é um especialista em PHP, Laravel 11, Vue 3 (Composition API) e Domain-Driven Design (DDD).

## Visão Geral do Projeto

**NewSDC (Sistema de Dados Centralizados)** é uma plataforma web modular para gestão de processos administrativos, protocolos e demandas/chamados. O projeto utiliza arquitetura modular baseada em DDD com Atomic Design no frontend.

### Stack Tecnológica

**Backend:**
- Laravel 11.x (PHP 8.3)
- MySQL 8.0
- Redis (cache, sessões, filas)
- Spatie Laravel Permission (RBAC)
- Laravel Sanctum (autenticação API)
- Swagger/OpenAPI (documentação)

**Frontend:**
- Vue 3 (Composition API com `<script setup>`)
- Inertia.js (SSR híbrido)
- Tailwind CSS
- Vite 5.x (build tool)
- Ziggy (rotas Laravel no JavaScript)

**DevOps:**
- Docker Compose (desenvolvimento)
- Nginx (reverse proxy)
- Mailhog (testes de email)

---

## Estrutura do Projeto

```
NewSDC/
├── SDC/                          # Aplicação principal Laravel
│   ├── app/
│   │   ├── Modules/              # Módulos DDD
│   │   │   ├── Rat/              # Módulo RAT (Relatório de Atendimento Técnico)
│   │   │   ├── Demandas/         # Módulo Demandas/Chamados (Tasks)
│   │   │   │   ├── Application/  # Use Cases e DTOs
│   │   │   │   ├── Domain/       # Entities, Value Objects, Repositories (interfaces)
│   │   │   │   ├── Infrastructure/ # Repositories (implementações), Persistence
│   │   │   │   ├── Presentation/ # Controllers, Requests, Resources
│   │   │   │   └── DemandasServiceProvider.php
│   │   │   └── Pae/              # Módulo PAE (Processos Administrativos)
│   │   ├── Http/
│   │   │   ├── Controllers/      # Controllers globais
│   │   │   └── Middleware/
│   │   └── Providers/
│   ├── resources/
│   │   ├── js/
│   │   │   ├── Pages/            # Componentes de página (Inertia)
│   │   │   │   ├── Auth/
│   │   │   │   ├── Demandas/
│   │   │   │   ├── Admin/
│   │   │   │   └── ...
│   │   │   ├── Components/       # Componentes reutilizáveis (Atomic Design)
│   │   │   │   ├── Atoms/
│   │   │   │   ├── Molecules/
│   │   │   │   ├── Organisms/
│   │   │   │   └── Templates/
│   │   │   ├── Layouts/
│   │   │   ├── app.js            # Entry point
│   │   │   └── bootstrap.js
│   │   ├── css/
│   │   │   ├── app.css
│   │   │   └── pages/            # CSS específico por página
│   │   └── views/
│   │       └── app.blade.php     # Template base Inertia
│   ├── routes/
│   │   ├── web.php               # Rotas principais
│   │   ├── api.php               # Rotas API
│   │   └── modules/              # Rotas por módulo
│   │       ├── rat.php
│   │       ├── demandas.php
│   │       └── pae.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── docker/                   # Configurações Docker centralizadas
│   │   ├── docker-compose.yml
│   │   ├── Dockerfile.dev
│   │   ├── nginx/
│   │   └── mysql/
│   └── config/
└── docs/                         # Documentação do projeto
```

---

## Arquitetura e Padrões

### 1. Domain-Driven Design (DDD)

Cada módulo segue a estrutura DDD:

**Application Layer:**
- Use Cases: Lógica de aplicação (orquestração)
- DTOs: Data Transfer Objects para input/output

**Domain Layer:**
- Entities: Modelos de domínio (Eloquent Models)
- Value Objects: Objetos imutáveis (ex: Status, Prioridade, Urgência)
- Repository Interfaces: Contratos para persistência
- Domain Services: Lógica de negócio complexa

**Infrastructure Layer:**
- Repository Implementations: Eloquent repositories
- External Services: Integrações com APIs externas

**Presentation Layer:**
- Controllers: HTTP handlers (thin controllers)
- Form Requests: Validação
- Resources: Transformação de dados para API

### 2. Atomic Design (Frontend)

Componentes Vue organizados hierarquicamente:

- **Atoms:** Elementos básicos (botões, inputs, badges)
- **Molecules:** Combinação de atoms (card, form-group)
- **Organisms:** Componentes complexos (sidebar, navbar, data-table)
- **Templates:** Layouts de página
- **Pages:** Páginas completas (Inertia.js)

### 3. Sistema de Permissões (RBAC)

Utiliza **Spatie Laravel Permission**:

**Estrutura:**
- **Roles:** super-admin, admin, user, agent
- **Permissions:** Formato `modulo.acao` (ex: `demandas.manage`, `rat.create`)
- **Guards:** web, api

**Uso no código:**
```php
// Controller
$this->authorize('demandas.manage');

// Blade/Inertia
@can('demandas.manage')

// Middleware
Route::middleware(['can:demandas.manage'])
```

---

## Módulo DEMANDAS (Tasks/Chamados)

### Entidades Principais

**Task (Demanda/Chamado):**
- ID, título, descrição
- Tipo: incident, request, change, problem
- Status: open, in_progress, waiting, resolved, closed
- Prioridade: baixa, média, alta, crítica
- Urgência: baixa, média, alta
- Impacto: baixo, médio, alto
- Usuário solicitante, agente responsável
- Timestamps, SLA

**TaskComment:**
- Comentários na demanda
- Público ou interno (apenas agentes)

**TaskAttachment:**
- Anexos/arquivos
- Storage em `storage/app/tasks`

**TaskApproval:**
- Aprovações para changes
- Status: pending, approved, rejected

**TaskSlaInstance:**
- Controle de SLA por demanda
- Target time, resolution time

**TaskAuditLog:**
- Histórico de alterações
- Quem alterou, quando, o que mudou

### Value Objects

```php
TipoTask: incident, request, change, problem
TaskStatus: open, in_progress, waiting, resolved, closed
Prioridade: baixa, media, alta, critica
Urgencia: baixa, media, alta
Impacto: baixo, medio, alto
```

### Rotas

**Portal Usuário (autenticado):**
- `GET /demandas` - Lista demandas do usuário
- `GET /demandas/nova` - Formulário criar demanda
- `POST /demandas` - Salvar demanda
- `GET /demandas/{id}` - Detalhes da demanda
- `POST /demandas/{id}/comentarios` - Adicionar comentário
- `POST /demandas/{id}/anexos` - Upload anexo

**Console Agente (permission: demandas.manage):**
- `GET /admin/demandas` - Dashboard gestão
- `POST /admin/demandas/{id}/atribuir` - Atribuir agente
- `POST /admin/demandas/{id}/status` - Alterar status
- `GET /admin/demandas/{id}/editar` - Editar
- `PUT /admin/demandas/{id}` - Atualizar
- `DELETE /admin/demandas/{id}` - Deletar (soft delete)

### Migrations

```
2025_01_15_000001_create_tasks_table
2025_01_15_000002_create_task_comments_table
2025_01_15_000003_create_task_attachments_table
2025_01_15_000004_create_task_approvals_table
2025_01_15_000005_create_task_sla_definitions_table
2025_01_15_000006_create_task_sla_instances_table
2025_01_15_000007_create_task_audit_logs_table
```

---

## Configuração do Ambiente

### Docker

**Containers:**
- `newsdc_app` - Laravel (PHP 8.3-FPM Alpine)
- `newsdc_nginx` - Nginx 1.25
- `newsdc_db` - MySQL 8.0
- `newsdc_redis` - Redis 7
- `newsdc_mailhog` - Mailhog (email testing)

**Portas:**
- Laravel: http://localhost:8001
- Nginx: http://localhost:8082
- MySQL: localhost:3307
- Redis: localhost:6380
- Mailhog UI: http://localhost:8026
- Vite HMR: http://localhost:5175

**Comandos úteis:**
```bash
# Iniciar ambiente
cd SDC/docker && docker compose up -d

# Rebuildar
docker compose up -d --build

# Logs
docker compose logs -f app

# Executar comandos Laravel
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app composer install
```

### Vite (Frontend)

**Executar no HOST (não no container):**
```bash
cd SDC
npm run dev  # Dev server com HMR
npm run build  # Build produção
```

**Configuração (vite.config.js):**
- Server: 0.0.0.0:5175
- HMR: localhost:5175
- Lazy loading de componentes Vue
- Code splitting otimizado

---

## Regras de Código

### PHP/Laravel

1. **PSR-12** estritamente (indentação 4 espaços, sem tabs)
2. **Type hints** obrigatórios (strict_types=1)
3. **Thin Controllers** - lógica em Use Cases
4. **Repository Pattern** - sempre usar interfaces
5. **Form Requests** para validação
6. **Resources** para transformação de dados API
7. **Eloquent ORM** - evitar queries diretas
8. **Migrations** - sempre reversíveis
9. **Seeders** - dados de teste separados de produção
10. **Comentários** - não remover existentes

**Exemplo de Controller:**
```php
<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Presentation\Http\Controllers;

use App\Modules\Demandas\Application\UseCases\ListTasksUseCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DemandasIndexController
{
    public function __construct(
        private readonly ListTasksUseCase $listTasksUseCase
    ) {}

    public function index(Request $request): Response
    {
        $tasks = $this->listTasksUseCase->execute(
            userId: $request->user()->id,
            filters: $request->only(['status', 'tipo'])
        );

        return Inertia::render('Demandas/DemandasIndex', [
            'tasks' => $tasks,
        ]);
    }
}
```

### Vue 3/JavaScript

1. **Composition API** com `<script setup>` sempre
2. **TypeScript-style props** com `defineProps<T>()`
3. **Tailwind CSS** - utilitários, sem CSS inline
4. **Componentes atômicos** - reutilização máxima
5. **Lazy loading** - `defineAsyncComponent()` quando apropriado
6. **Ziggy** para rotas Laravel: `route('demandas.index')`
7. **Inertia.js** - `router.get()`, `router.post()`, etc.
8. **Comentários** - não remover existentes

**Exemplo de Componente:**
```vue
<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

const props = defineProps({
  task: Object,
  canManage: Boolean
})

const statusColor = computed(() => {
  const colors = {
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return colors[props.task.status] || 'bg-gray-100'
})

const viewTask = () => {
  router.get(route('demandas.show', props.task.id))
}
</script>

<template>
  <div class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
    <h3 class="font-semibold text-lg">{{ task.titulo }}</h3>
    <span :class="statusColor" class="px-2 py-1 rounded text-sm">
      {{ task.status }}
    </span>
    <button @click="viewTask" class="mt-2 text-blue-600 hover:underline">
      Ver detalhes
    </button>
  </div>
</template>
```

### CSS/Tailwind

1. **Utilitários primeiro** - evitar CSS customizado
2. **Responsive** - mobile-first (`sm:`, `md:`, `lg:`)
3. **Dark mode** - preparado (`dark:`)
4. **Consistência** - espaçamentos: 4, 8, 16, 24, 32px
5. **Cores** - palette do projeto (azul primário, cinza neutro)

---

## Fluxo de Trabalho

### Criar Nova Feature

1. **Branch:** `git checkout -b feature/nome-feature`
2. **Migration:** `php artisan make:migration create_x_table`
3. **Model/Entity:** Criar em `Domain/Entities`
4. **Repository:** Interface + Implementação
5. **Use Case:** Lógica de aplicação
6. **Controller:** Thin controller
7. **Routes:** Registrar em `routes/modules/`
8. **ServiceProvider:** Registrar em `config/app.php`
9. **Frontend:** Componente Vue + Página Inertia
10. **Testar:** Manual + automated tests
11. **Commit:** `git commit -m "feat: descrição"`
12. **Merge:** PR para `dev`

### Deploy

1. **Build frontend:** `npm run build`
2. **Otimizar:** `php artisan optimize`
3. **Migrations:** `php artisan migrate --force`
4. **Cache:** `php artisan config:cache && php artisan route:cache`

---

## Convenções de Nomenclatura

**PHP:**
- Classes: PascalCase (`ListTasksUseCase`)
- Métodos: camelCase (`execute()`)
- Propriedades: camelCase (`$userId`)
- Constantes: SCREAMING_SNAKE_CASE (`MAX_ATTEMPTS`)

**JavaScript/Vue:**
- Componentes: PascalCase (`DemandasIndex.vue`)
- Variáveis: camelCase (`taskList`)
- Constantes: SCREAMING_SNAKE_CASE (`API_URL`)
- Props: camelCase (`canManage`)
- Events: kebab-case (`task-updated`)

**Banco de Dados:**
- Tabelas: snake_case plural (`tasks`, `task_comments`)
- Colunas: snake_case (`created_at`, `user_id`)
- Foreign keys: `{tabela}_id` (`task_id`)
- Indexes: `idx_{tabela}_{coluna}`

**Rotas:**
- Web: kebab-case (`/demandas/nova`)
- API: kebab-case (`/api/v1/demandas`)
- Names: dot notation (`demandas.index`)

---

## Integração Inertia.js

### Backend (Controller):
```php
return Inertia::render('Demandas/DemandasIndex', [
    'tasks' => TaskResource::collection($tasks),
    'filters' => $request->only(['status']),
]);
```

### Frontend (Vue):
```vue
<script setup>
const props = defineProps({
  tasks: Array,
  filters: Object
})
</script>
```

### Navegação:
```js
import { router } from '@inertiajs/vue3'

// GET
router.get(route('demandas.show', id))

// POST
router.post(route('demandas.store'), formData)

// PUT
router.put(route('demandas.update', id), formData)

// DELETE
router.delete(route('demandas.destroy', id))
```

---

## Troubleshooting Comum

### Página em branco
1. Verificar Vite rodando: `npm run dev`
2. Hard refresh: Ctrl+Shift+R
3. Console do navegador (F12) → verificar erros
4. Verificar `app.blade.php` tem `@vite` e `@inertia`

### Rotas não aparecem
1. `php artisan route:clear`
2. Verificar ServiceProvider em `config/app.php`
3. Verificar middleware `auth` se necessário

### Erro 500
1. `docker compose logs -f app`
2. `storage/logs/laravel.log`
3. Verificar permissões: `chmod -R 775 storage bootstrap/cache`

### Vite não conecta
1. Verificar porta 5175 livre
2. Verificar `vite.config.js` → `server.host: '0.0.0.0'`
3. Limpar cache: `rm -rf node_modules/.vite`

---

## Comandos Úteis

```bash
# Docker
docker compose up -d
docker compose down
docker compose logs -f app
docker compose exec app bash

# Laravel
php artisan migrate
php artisan db:seed
php artisan route:list
php artisan tinker
php artisan make:controller Nome
php artisan make:model Nome
php artisan optimize:clear

# Composer
composer install
composer dump-autoload

# NPM
npm install
npm run dev
npm run build

# Git
git status
git add .
git commit -m "feat: descrição"
git push origin branch-name
```

---

## Próximos Passos (Roadmap)

- [ ] Implementar listagem com filtros avançados
- [ ] Sistema de SLA automático
- [ ] Notificações em tempo real (WebSockets)
- [ ] Dashboard com métricas e gráficos
- [ ] Integração com email (IMAP)
- [ ] API REST completa
- [ ] Testes automatizados (PHPUnit + Pest)
- [ ] CI/CD (GitHub Actions)

---

## Observações Finais

1. **Sempre responda em Português do Brasil**
2. **Mostre apenas trechos modificados**, não arquivos completos
3. **Não remova comentários existentes**
4. **Siga PSR-12 no PHP** e **Composition API no Vue**
5. **Priorize código limpo, legível e manutenível**
6. **Use type hints e strict types no PHP**
7. **Prefira composição sobre herança**
8. **Mantenha controllers finos - lógica em Use Cases**

---

**Versão do Contexto:** 1.0
**Última atualização:** 2025-12-26
**Autor:** Matheus Estrela (KvN)
