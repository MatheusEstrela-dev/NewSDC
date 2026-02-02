# Plano de Otimizacao de Performance SPA - NewSDC

## Analise Atual

| Aspecto | Status |
|---------|--------|
| Framework | Laravel 12 + Vue 3 + Inertia.js |
| Build Tool | Vite 5 (code splitting configurado) |
| CSS | Tailwind 3 + lazy loading por pagina |
| Cache Backend | Redis (session + cache) |
| PWA/Service Worker | NAO implementado |
| SSR | Desabilitado |
| Pre-fetching | NAO implementado |
| Skeleton Screens | NAO implementado |

---

## Plano de Implementacao

### FASE 1: PWA + Service Worker (Prioridade Alta)

**Objetivo:** Cache de assets e requisicoes API para carregamento instantaneo em visitas subsequentes.

**Tarefas:**
1. Instalar `vite-plugin-pwa`
2. Configurar manifest.json (nome, icones, theme_color)
3. Configurar Workbox para cache strategies:
   - **Assets estaticos**: CacheFirst (JS, CSS, imagens)
   - **API calls**: StaleWhileRevalidate (dados dinamicos)
   - **Paginas HTML**: NetworkFirst
4. Adicionar registro do Service Worker em app.js
5. Criar icones PWA (192x192, 512x512)

**Arquivos a modificar:**
- `vite.config.js`
- `resources/js/app.js`
- `public/` (icones)

---

### FASE 2: Pre-fetching Inteligente (Prioridade Alta)

**Objetivo:** Carregar dados antes do usuario clicar no link.

**Tarefas:**
1. Usar `@inertiajs/vue3` built-in prefetch com `<Link prefetch="hover">`
2. Criar diretiva Vue `v-prefetch` para links criticos
3. Configurar IntersectionObserver para prefetch de itens em viewport

**Arquivos a modificar:**
- Componentes de navegacao (NavItem.vue, etc)
- Links criticos em todas as paginas

---

### FASE 3: Cache de Dados com SWR Pattern (Prioridade Alta)

**Objetivo:** Mostrar dados em cache imediatamente enquanto atualiza em background.

**Tarefas:**
1. Instalar `@tanstack/vue-query` (TanStack Query)
2. Configurar QueryClient com defaults otimizados:
   - `staleTime: 5 * 60 * 1000` (5 minutos)
   - `cacheTime: 30 * 60 * 1000` (30 minutos)
   - `refetchOnWindowFocus: true`
3. Refatorar composables para usar useQuery:
   - `useDashboard.js`
   - `useRat.js`
   - `usePae.js`
   - `useDemandas.js`
4. Implementar cache persistence com localStorage

**Arquivos a modificar:**
- `resources/js/app.js` (setup QueryClient)
- Todos os composables em `Composables/`

---

### FASE 4: Skeleton Screens + Loading States (Prioridade Media)

**Objetivo:** Eliminar spinners e mostrar estrutura do conteudo durante carregamento.

**Tarefas:**
1. Criar componente `SkeletonLoader.vue` generico
2. Criar skeletons especificos:
   - `TableSkeleton.vue` (para tabelas de dados)
   - `CardSkeleton.vue` (para cards de dashboard)
   - `FormSkeleton.vue` (para formularios)
3. Aplicar em todas as paginas principais:
   - Dashboard
   - RAT (listagem e detalhes)
   - PAE (listagem e detalhes)
   - Demandas

**Arquivos a criar:**
- `resources/js/Components/Atoms/SkeletonLoader.vue`
- `resources/js/Components/Molecules/TableSkeleton.vue`
- `resources/js/Components/Molecules/CardSkeleton.vue`

---

### FASE 5: Optimistic UI (Prioridade Media)

**Objetivo:** Atualizar interface imediatamente antes da confirmacao do servidor.

**Tarefas:**
1. Identificar acoes criticas:
   - Salvar formulario
   - Atualizar status
   - Adicionar comentarios
2. Implementar pattern optimistic em mutations do TanStack Query
3. Adicionar rollback em caso de erro

**Arquivos a modificar:**
- Composables que fazem mutacoes (POST, PUT, DELETE)

---

### FASE 6: Otimizacao de Imagens (Prioridade Media)

**Objetivo:** Reduzir tempo de carregamento de imagens.

**Tarefas:**
1. Configurar `vite-plugin-image-optimizer` ou `vite-plugin-imagemin`
2. Converter imagens para WebP/AVIF
3. Implementar lazy loading nativo: `loading="lazy"`
4. Usar `srcset` para imagens responsivas
5. Implementar blur placeholder (LQIP - Low Quality Image Placeholder)

**Arquivos a modificar:**
- `vite.config.js`
- Componentes que usam imagens

---

### FASE 7: Compressao e Headers (Prioridade Media)

**Objetivo:** Reduzir tamanho de transferencia.

**Tarefas:**
1. Habilitar Brotli compression no servidor (nginx/apache)
2. Configurar headers de cache otimizados:
   - `Cache-Control: public, max-age=31536000` para assets hasheados
   - `Cache-Control: no-cache` para HTML
3. Implementar HTTP/2 push para assets criticos (se suportado)

**Arquivos a modificar:**
- Configuracao do servidor (nginx.conf ou .htaccess)
- `config/cors.php` ou middleware de headers

---

### FASE 8: Web Workers para Operacoes Pesadas (Prioridade Baixa)

**Objetivo:** Manter main thread livre para interacoes.

**Tarefas:**
1. Identificar operacoes pesadas:
   - Processamento de dados de mapas (Leaflet)
   - Calculos complexos em dashboards
   - Parsing de JSON grandes
2. Mover para Web Workers usando `vite-plugin-comlink`
3. Implementar workers para tarefas especificas

**Arquivos a criar:**
- `resources/js/workers/`

---

## Ordem de Implementacao Recomendada

| Ordem | Fase | Impacto | Esforco |
|-------|------|---------|---------|
| 1 | PWA + Service Worker | ALTO | Medio |
| 2 | Pre-fetching | ALTO | Baixo |
| 3 | TanStack Query (SWR) | ALTO | Alto |
| 4 | Skeleton Screens | MEDIO | Medio |
| 5 | Optimistic UI | MEDIO | Medio |
| 6 | Imagens Otimizadas | MEDIO | Baixo |
| 7 | Compressao | MEDIO | Baixo |
| 8 | Web Workers | BAIXO | Alto |

---

## Metricas de Sucesso

| Metrica | Atual | Meta |
|---------|-------|------|
| LCP (Largest Contentful Paint) | A medir | < 2.5s |
| FID (First Input Delay) | A medir | < 100ms |
| CLS (Cumulative Layout Shift) | A medir | < 0.1 |
| TTI (Time to Interactive) | A medir | < 3.8s |
| Navegacao entre paginas | A medir | < 200ms |

---

## Dependencias a Instalar

```bash
npm install @tanstack/vue-query
npm install -D vite-plugin-pwa workbox-window
npm install -D vite-plugin-imagemin
npm install -D vite-plugin-comlink comlink
```

---

## Proximos Passos

Apos aprovacao deste plano, implementaremos cada fase sequencialmente, testando metricas de performance apos cada fase usando:
- Lighthouse (Chrome DevTools)
- WebPageTest
- Core Web Vitals (Search Console)
