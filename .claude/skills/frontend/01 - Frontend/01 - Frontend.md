# 🧠 NewSDC Frontend: The Definitive LLM Handbook (v3)

Este guia é a "fonte da verdade" para o frontend do NewSDC. Ele é projetado para permitir que uma IA entenda a arquitetura, os padrões de código e a lógica de negócios em nível de implementação.

---

### **Sumário Executivo**
- **Arquitetura:** Monolito Híbrido (Laravel) + SPA (Vue 3/Inertia).
- **Gerenciamento de Estado:** Três camadas (Inertia Props, Vue Query, Dexie).
- **Padrões Notáveis:**
    - Inicialização Otimizada (Deferred Init).
    - UI Reativa a Permissões.
    - Offline-First para Módulos Críticos (RAT).
    - Design System Atômico.
    - Arquitetura Limpa (Domain Layer) para módulos complexos.

---

## 1. Bootstrap e Ciclo de Vida da Aplicação (`app.js`)

A inicialização do app é otimizada para performance e resiliência.

#### **1.1. Inicialização Deferida (Deferred Initialization)**
Para não bloquear o render inicial, tarefas pesadas são adiadas usando `requestIdleCallback` (ou `setTimeout` como fallback).

```javascript
// SDC/resources/js/app.js

const deferInit = window.requestIdleCallback || ((cb) => setTimeout(cb, 2000));
deferInit(() => {
    if (import.meta.env.PROD) {
        registerServiceWorker(); // PWA
    }
    SyncService.init(); // Sincronização de dados offline
});
```
**Implicação:** A UI é interativa quase instantaneamente. O Service Worker e o serviço de sincronização (`SyncService`) só rodam quando o navegador está ocioso.

#### **1.2. Prefetching Inteligente**
O sistema antecipa a navegação do usuário para acelerar a percepção de velocidade.

```javascript
// SDC/resources/js/app.js

const setupPrefetching = () => {
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a[href]');
        // ... Lógica com timer ...
        router.prefetch(href, { method: 'get' });
    }, { passive: true });
};
```
**Implicação:** Quando o usuário paira o mouse sobre um link por 150ms, o Inertia já requisita os dados daquela página em background.

#### **1.3. Carregamento Dinâmico de CSS**
Para evitar um `app.css` monolítico, os estilos são carregados sob demanda com base na página sendo renderizada.

```javascript
// SDC/resources/js/app.js

const loadPageCSS = (pageName) => {
    const cssMap = {
        'Dashboard': () => import('../css/pages/dashboard/dashboard.css'),
        'Rat': () => Promise.all([/* ... */]),
        // ... etc
    };
    const loader = cssMap[pageName] || cssMap[pageName.split('/').pop()];
    if (loader) loader().catch(() => {});
};
```
**Implicação:** O *bundle* inicial é mínimo, e o CSS de módulos pesados como o RAT só é carregado se o usuário acessar aquele módulo.

---

## 2. UI e Arquitetura de Estado (`Sidebar.vue`)

O componente da barra lateral é um microcosmo dos padrões de UI/Estado do projeto.

#### **2.1. Permissões Reativas e Otimizadas**
As permissões não são verificadas repetidamente. Elas são cacheadas em um `Set` e só são atualizadas se o ID do usuário mudar (login/logout).

```vue
// SDC/resources/js/Components/Sidebar.vue

const _permSet = shallowRef(new Set(page.props?.auth?.user?.permissions ?? []));

watch(
  () => page.props?.auth?.user?.id,
  (newId, prevId) => {
    if (newId !== prevId) {
      _permSet.value = new Set(page.props?.auth?.user?.permissions ?? []);
    }
  }
);

const hasPermission = (permissionList) => {
  // ...
  return permissionList.some(p => _permSet.value.has(p)); // O(1) lookup
};

const canSeeDecretacoes = computed(() => {
  return hasPermission(['decretacoes.processos.view']);
});
```

#### **2.2. Renderização Dinâmica da UI**
A UI é construída dinamicamente com base nas permissões do computed property.

```vue
<!-- SDC/resources/js/Components/Sidebar.vue -->

<NavItem
  v-if="canSeeDecretacoes && _routes.hasDecretacoes"
  :href="route('decretacoes.index')"
  icon="scale"
>
  Decretacoes
</NavItem>
```
**Implicação:** O mesmo componente `Sidebar` renderiza uma UI completamente diferente para um administrador vs. um usuário comum, sem lógica complexa no template.

---

## 3. Offline-First e Sincronização de Dados (`useRat.js`)

O módulo RAT (Relatório de Atividade Técnica) é projetado para funcionar offline.

#### **3.1. Detecção de Conectividade**
O hook `useRat` contém a lógica de decisão para salvar localmente ou no servidor. A função `salvarRat` é o coração dessa funcionalidade.

```javascript
// SDC/resources/js/composables/rat/useRat.js

async function salvarRat(data) {
    // ...
    if (!payload.id) {
        payload.id = uuidv4(); // Garante um ID único para a chave do Dexie
    }

    if (!navigator.onLine) {
      try {
        await db.rat_pendentes.add({ // Salva no IndexedDB via Dexie
          ...payload,
          sync_status: 'pending',
          created_at: new Date().toISOString()
        });
        alert('Você está offline. O RAT foi salvo...');
      } catch (error) { /* ... */ }
    } else {
      // Se online, usa o Inertia para salvar no backend
      if (!rat.value?.id) {
        router.post(route('rat.store'), payload, { /* ... */ });
      } else {
        router.put(route('rat.update', rat.value.id), payload, { /* ... */ });
      }
    }
}
```

#### **3.2. Schema do Banco de Dados Local**
O `db.js` define a estrutura do IndexedDB. A tabela `rat_pendentes` armazena os relatórios que aguardam sincronização.

```javascript
// SDC/resources/js/infrastructure/database/db.js

import Dexie from 'dexie';
export const db = new Dexie('SDC_MG_Database');

db.version(1).stores({
    rat_pendentes: 'id, sync_status, created_at' // id é UUID
});
```
**Implicação:** O `SyncService` (inicializado em `app.js`) pode agora consultar `db.rat_pendentes.where('sync_status').equals('pending')` para encontrar e enviar os dados quando a conexão for restaurada.

---

## 4. O "Domain Layer" (Padrão de Arquitetura Limpa)

Para módulos de alta complexidade como o **PAE** (Plano de Ação de Emergência), o projeto adota um padrão de Arquitetura Limpa para isolar a lógica de negócios da UI.

A estrutura de diretórios em `SDC/resources/js/domain/pae/` revela essa intenção:

- **`entities/`**: Contém as classes ou objetos puros que representam os modelos de negócio (ex: um `Protocolo`, um `Empreendimento`). Eles não sabem nada sobre Vue ou HTML.
- **`repositories/`**: Define as *interfaces* para buscar e salvar dados (ex: `ProtocoloRepository`). A implementação real pode usar Axios, Dexie, etc., mas o resto do domínio não se importa.
- **`usecases/`**: Orquestra a lógica de negócios. Um `usecase` (ex: `SubmitProtocoloPae`) utiliza `entities` e `repositories` para executar uma tarefa específica.

**Implicação:** Este padrão, embora mais complexo, permite que a lógica de negócios do PAE seja testada de forma isolada e reutilizada em diferentes contextos, protegendo-a de mudanças na UI ou na infraestrutura.

---

## 💡 Guia para Modificação por LLM

1.  **Reatividade é Rei:** Sempre verifique a origem dos dados. Se vierem de `props`, são gerenciados pelo Inertia. Se vierem de um `ref` dentro de um hook `use...`, siga a lógica daquele hook.
2.  **Permissões são Baratas:** Não hesite em usar os `computed` de permissão (ex: `canSee...`) na UI. Eles são altamente otimizados.
3.  **Considere o Offline:** Ao modificar o RAT, lembre-se que os dados podem existir apenas no IndexedDB. Teste cenários online e offline.
4.  **Respeite o Domínio:** Ao trabalhar no PAE ou em módulos similares, coloque a lógica de negócios nos `usecases` e `entities`, não diretamente nos componentes `.vue`.
5.  **Estilo Unificado:** Use classes utilitárias do Tailwind. Evite CSS em tags `<style>` a menos que seja para um componente de terceiros.
