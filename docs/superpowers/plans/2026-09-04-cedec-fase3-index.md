# Cedec Fase 3 — Indice de Prefeituras Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Entregar a tela `Cedec/Prefeituras/Index` — cabecalho, quatro stat cards (tres deles filtro rapido), secao de filtros colapsavel, tabela em `lg+` com bloco no mobile, paginacao e estado vazio — consumindo as props que a fase 2 ja publica.

**Architecture:** Tres componentes novos sobre os componentes canonicos do projeto (`PageHeader`, `StatCardsGrid`/`StatCard`, `CollapsibleSection`, `Pagination`, `ListEmptyState`, `ActionButton`) e uma pagina que orquestra. A pagina e o unico lugar que fala com o servidor, e sempre por reload PARCIAL (`only: ['prefeituras', 'filtros']`), para que a prop closure `estatisticas` nao seja recalculada a cada troca de filtro. A molecula so emite; os organismos concentram a interacao.

**Tech Stack:** Vue 3 (`<script setup>`), Inertia 2, Tailwind 3 (`darkMode: 'class'`), Vite, Ziggy, Heroicons v2, Playwright para o E2E de responsividade.

**Spec:** `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md` (secao 6)
**Contrato de interfaces:** `docs/superpowers/plans/2026-09-04-cedec-contrato-interfaces.md` (secoes 0 e 8 sao as desta fase)

---

## Global Constraints

Valem para TODAS as tasks. Copiadas do contrato de interfaces (secao 0) e das skills
`.claude/skills/frontend/03 - Layout` e `04 - Responsividade`.

- App executavel em `NewSDC/SDC`. Frontend em `resources/js`. Todo caminho abaixo e relativo a `SDC/`.
- **Sem emoji dentro do codigo.** Emoji so na mensagem de commit (gitmoji).
- **Nenhum `<style scoped>` novo.** So utilitario Tailwind.
- **Dark mode por CLASSE** (`dark:`), nunca `@media (prefers-color-scheme: dark)`.
- **A calha horizontal e do `<main>`:** a div raiz da pagina NAO leva `p-*` nem `px-*`.
- **Ritmo vertical de 24px de UMA fonte so.** Esta fase usa a Forma B: `w-full space-y-6 pb-8` na raiz, e todo filho que tenha margem propria desliga com `:espaco-inferior="false"`.
- **`document.documentElement.scrollWidth - clientWidth === 0`** em 375px e em 840px. Quem transborda rola dentro de si, com a receita de tres partes: pai `min-w-0`, container `min-w-0 overflow-x-auto`, filho `w-max`.
- **Breakpoint por `useMobile`, alinhado em `lg`.** Use `isDesktop` (matchMedia `min-width: 1024px`), nunca `window.innerWidth`. Tabela vira BLOCO abaixo disso.
- **Alvo de toque de 40px** (`h-10`) em campo e botao.
- **Paginacao pelo `Molecules/Navigation/Pagination.vue`**, com o objeto de paginacao ACHATADO (`current_page`, `last_page`, `per_page`, `total`, `from`, `to`) — o backend da fase 2 ja entrega assim, sem `meta` aninhado.
- **Nao reimplementar** header, secao, card, estado vazio ou paginacao: use os componentes canonicos.
- **Contrato de camada:** atomo e molecula NAO chamam API nem estado global; organismo concentra interacao; a pagina orquestra.
- **Prop closure do Inertia e reavaliada em TODA visita completa.** Filtro usa reload parcial com `only: [...]`.
- **Tailwind so escaneia** `resources/js/**/*.vue`, `resources/views/**/*.blade.php` e `storage/framework/views/*.php` (ver `tailwind.config.js`). Classe montada em `.js` ou vinda de enum PHP so entra no CSS se o literal existir em algum `.vue` ou no `safelist`. Nesta fase todas as classes sao literais dentro de `.vue`.
- **Commit:** gitmoji, `<emoji> tipo(cedec): descricao` em pt-BR, **sem trailer de co-autor**. Commit ATOMICO — a tela inteira e UMA mudanca e vai num commit so (Task 4). Tasks 1 a 3 nao commitam.
- **Arquivo de teste criado para verificacao nao entra no commit** (regra de ouro 10). O spec Playwright da Task 5 e removido antes de fechar a fase.

---

## Dependencia: o que a fase 2 tem de estar entregue

Esta fase NAO cria nada de backend. Antes do primeiro step, confirme que a fase 2 esta no
branch: `docker exec newsdc_frankenphp_local php artisan route:list --name=cedec` tem de
listar `cedec.prefeituras.index` e `cedec.prefeituras.edit`. Sem isso, `route()` estoura em
runtime e o `npm run build` NAO acusa (Ziggy resolve em tempo de execucao).

---

## Estrutura de arquivos

| Arquivo | Responsabilidade |
| --- | --- |
| `resources/js/Components/Molecules/Cedec/PrefeituraStatCards.vue` | Quatro numeros. Tres deles emitem `filter(pendencia)`. Nao navega. |
| `resources/js/Components/Organisms/Cedec/PrefeituraFiltersSection.vue` | Formulario de busca/REDEC/macrorregiao dentro de `CollapsibleSection namespace="cedec"`. Emite `apply` e `clear`. Nao navega. |
| `resources/js/Components/Organisms/Cedec/PrefeituraTable.vue` | Tabela em `lg+`, bloco abaixo. Estado vazio. Navega para o Edit. |
| `resources/js/Pages/Cedec/Prefeituras/Index.vue` | Orquestra: recebe as props do contrato, traduz eventos em reload parcial, monta a paginacao. |
| `resources/js/Support/moduleIcons.js` | Uma linha, so se a fase 2 nao tiver registrado `prefeituras`. |
| `tests/e2e/cedec-prefeituras-responsivo.spec.js` | Verificacao. **Nao entra no commit.** |

---

### Task 1: Molecula PrefeituraStatCards

**Files:**
- Create: `resources/js/Components/Molecules/Cedec/PrefeituraStatCards.vue`
- Modify (condicional, ver Step 1): `resources/js/Support/moduleIcons.js`

**Interfaces:**

- Consumes (da fase 2, ja existentes):
  - prop Inertia `estatisticas: { total: int, sem_email: int, sem_telefone: int, sem_foto: int }` da pagina `Cedec/Prefeituras/Index`, produzida por `App\Modules\Cedec\Services\CedecPrefeituraService::estatisticas(): array{total,sem_email,sem_telefone,sem_foto}`.
  - `resources/js/Components/Molecules/Statistics/StatCardsGrid.vue` — props `colunas: Number (2|3|4|5, default 4)`, `espacoInferior: Boolean (default true)`; slot default.
  - `resources/js/Components/Molecules/Statistics/StatCard.vue` — props `title: String (required)`, `value: Number|String (required)`, `icon: Object|Function`, `variant: 'info'|'success'|'warning'|'danger'`, `subtitle: String`, `formatNumber: Boolean`, `clickable: Boolean`; emit `click` (so dispara quando `clickable`).
  - `resources/js/Support/moduleIcons.js` — `moduleIcon(modulo: string): string|null`, `MODULE_ICONS`, `ICONS`.

- Produces (usado pela Task 4):
  - Componente `PrefeituraStatCards`, prop `estatisticas: Object`, emit `filter` com payload `'sem_email' | 'sem_telefone' | 'sem_foto'`.

- [ ] **Step 1: Garantir o icone do modulo no registry**

O `PageHeader` da Task 4 pede `moduleIcon('prefeituras')`. O contrato manda registrar
`prefeituras: apartment`; `apartment` ja esta importado e ja esta em `ICONS`, mas pode nao
estar em `MODULE_ICONS`. Verifique:

```bash
grep -n "prefeituras" SDC/resources/js/Support/moduleIcons.js
```

Se nao houver saida, a fase 2 nao registrou. Abra
`resources/js/Support/moduleIcons.js` e, dentro de `MODULE_ICONS`, logo apos a linha
`orgaos: officeBuilding,`, insira exatamente:

```js
  prefeituras: apartment,
```

Se o grep ja acusar a linha, nao mexa no arquivo.

- [ ] **Step 2: Criar a molecula**

Crie `resources/js/Components/Molecules/Cedec/PrefeituraStatCards.vue` com este conteudo
completo:

```vue
<template>
  <!--
    Molecula: so exibe numero e emite intencao de filtro. Nao navega, nao le
    estado global e nao conhece rota -- quem traduz `filter` em visita ao
    servidor e a pagina (contrato de camada da secao 8 do contrato de
    interfaces).

    `espaco-inferior=false` porque a raiz da pagina usa `space-y-6` (Forma B da
    regra 2 de "03 - Layout"). Com a `mb-6` propria do grid somando ao
    `space-y-6` do pai, o primeiro intervalo ficaria em 48px contra os 24px de
    todos os outros -- foi exatamente o defeito do TdapPageHeader.
  -->
  <StatCardsGrid :espaco-inferior="false">
    <!--
      O card de total NAO leva `clickable`: nao existe filtro "todos os
      municipios" atras dele. Card clicavel que nao filtra nada ensina o
      usuario a clicar e nao acontecer nada. Quem limpa a pendencia e o botao
      Limpar da secao de filtros.
    -->
    <StatCard
      title="Municípios"
      :value="estatisticas.total"
      subtitle="Cadastro estadual de prefeituras"
      variant="info"
      :icon="BuildingOffice2Icon"
    />

    <StatCard
      title="Sem e-mail"
      :value="estatisticas.sem_email"
      subtitle="Filtrar pendência"
      variant="danger"
      :icon="EnvelopeIcon"
      clickable
      @click="$emit('filter', 'sem_email')"
    />

    <StatCard
      title="Sem telefone"
      :value="estatisticas.sem_telefone"
      subtitle="Filtrar pendência"
      variant="warning"
      :icon="PhoneIcon"
      clickable
      @click="$emit('filter', 'sem_telefone')"
    />

    <StatCard
      title="Sem foto"
      :value="estatisticas.sem_foto"
      subtitle="Filtrar pendência"
      variant="warning"
      :icon="PhotoIcon"
      clickable
      @click="$emit('filter', 'sem_foto')"
    />
  </StatCardsGrid>
</template>

<script setup>
import { BuildingOffice2Icon, EnvelopeIcon, PhoneIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineProps({
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, sem_email: 0, sem_telefone: 0, sem_foto: 0 }),
  },
});

/** Payload: 'sem_email' | 'sem_telefone' | 'sem_foto'. */
defineEmits(['filter']);
</script>
```

- [ ] **Step 3: Verificar que compila**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro. Um import errado de Heroicon aparece aqui como
`"X" is not exported by node_modules/@heroicons/vue/...`.

Nao commitar ainda — Task 4 fecha o commit da fase (regra de ouro 12: commit atomico,
nao um commit por arquivo).

---

### Task 2: Organismo PrefeituraFiltersSection

**Files:**
- Create: `resources/js/Components/Organisms/Cedec/PrefeituraFiltersSection.vue`

**Interfaces:**

- Consumes (da fase 2 e do projeto):
  - props Inertia da pagina `Cedec/Prefeituras/Index`: `filtros: { busca: ?string, redec_id: ?int, macrorregiao: ?string, pendencia: ?string }`, `redecs: Array<{value:number,label:string}>`, `macrorregioes: Array<{value:string,label:string}>` (esta ultima vem de `App\Modules\Cedec\Enums\Macrorregiao::opcoes()`).
  - `resources/js/Components/Molecules/CollapsibleSection.vue` — props `namespace: String (required)`, `sectionId: String (required)`, `title: String (required)`, `subtitle: String`, `icon: Object|Function`, `tom: 'info'|'success'|'warning'|'danger'|'neutro'`, `statusText: String`, `expandidoPorPadrao: Boolean (default true)`; slots default e `icon`. Persiste o estado em `localStorage` sob a chave `${namespace}-sections-state` via `Composables/core/useCollapsibleSection`.

- Produces (usado pela Task 4):
  - Componente `PrefeituraFiltersSection`, props `filters: Object`, `redecs: Array`, `macrorregioes: Array`.
  - emit `apply` com payload `{ busca: string, redec_id: number|'', macrorregiao: string }`.
  - emit `clear` sem payload.

- [ ] **Step 1: Criar o organismo**

Crie `resources/js/Components/Organisms/Cedec/PrefeituraFiltersSection.vue` com este
conteudo completo:

```vue
<template>
  <!--
    `namespace="cedec"` e obrigatorio: o estado de secao vive em
    `localStorage['cedec-sections-state']`. Sem namespace proprio, a preferencia
    desta tela disputaria registro com a de outro modulo.

    Recolhido por padrao: aberto, o painel empurra a lista para fora da primeira
    dobra e a tela abre mostrando formulario em vez de dado. O `status-text` diz
    quantos filtros estao valendo, entao recolher nao esconde informacao.
  -->
  <CollapsibleSection
    namespace="cedec"
    section-id="prefeituras-filtros"
    title="Filtros de pesquisa"
    :icon="FunnelIcon"
    tom="neutro"
    :status-text="resumoAtivos"
    :expandido-por-padrao="false"
  >
    <form class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="aplicar">
      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Município</span>
        <input
          v-model="local.busca"
          type="text"
          placeholder="Nome ou parte do nome"
          :class="INPUT"
        >
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">REDEC</span>
        <!--
          `String(r.value)` no option e no normalizar(): o select devolve
          exatamente o que esta no atributo, e um id numerico vindo do servidor
          nunca casaria com a string do `v-model` -- a opcao correta apareceria
          como "nao selecionada" depois de recarregar com o filtro aplicado.
          A conversao de volta para Number acontece em paraFiltros().
        -->
        <select v-model="local.redec_id" :class="INPUT">
          <option value="">Todas</option>
          <option v-for="r in redecs" :key="r.value" :value="String(r.value)">{{ r.label }}</option>
        </select>
      </label>

      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Macrorregião</span>
        <select v-model="local.macrorregiao" :class="INPUT">
          <option value="">Todas</option>
          <option v-for="m in macrorregioes" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
      </label>

      <div class="flex items-end justify-end gap-3 sm:col-span-2 lg:col-span-3">
        <!--
          `type="button"` e obrigatorio: dentro de <form>, botao sem type e
          submit, e Limpar acabaria pesquisando.
        -->
        <button type="button" :class="BOTAO_SECUNDARIO" @click="limpar">
          Limpar
        </button>
        <button type="submit" :class="BOTAO_PRIMARIO">
          Pesquisar
        </button>
      </div>
    </form>
  </CollapsibleSection>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { FunnelIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';

/**
 * Organismo de filtros. Nao navega e nao conhece rota: emite `apply` com o
 * objeto de filtros e a pagina decide como pedir ao servidor.
 */
const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  redecs: { type: Array, default: () => [] },
  macrorregioes: { type: Array, default: () => [] },
});

const emit = defineEmits(['apply', 'clear']);

// h-10 = 40px, o alvo de toque minimo da regra 8 de "04 - Responsividade".
const INPUT = 'h-10 w-full rounded-md border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
const BOTAO_SECUNDARIO = 'h-10 rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700';
const BOTAO_PRIMARIO = 'h-10 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';

const VAZIO = { busca: '', redec_id: '', macrorregiao: '' };

const local = reactive({ ...VAZIO, ...normalizar(props.filters) });

/**
 * O servidor devolve os filtros que aplicou. Ressincronizar e o que mantem o
 * formulario coerente quando o filtro veio de um stat card, e nao daqui.
 */
watch(
  () => props.filters,
  (novos) => Object.assign(local, VAZIO, normalizar(novos)),
  { deep: true },
);

function normalizar(filters) {
  const f = filters ?? {};

  return {
    busca: f.busca ?? '',
    redec_id: f.redec_id === undefined || f.redec_id === null || f.redec_id === '' ? '' : String(f.redec_id),
    macrorregiao: f.macrorregiao ?? '',
  };
}

/**
 * Quantos filtros estao valendo, incluindo a pendencia escolhida por stat card
 * -- que nao tem campo aqui. Sem contar a pendencia, a lista apareceria
 * recortada com o cabecalho dizendo "Nenhum filtro aplicado".
 */
const resumoAtivos = computed(() => {
  const doFormulario = Object.values(local).filter((valor) => valor !== '').length;
  const daPendencia = props.filters?.pendencia ? 1 : 0;
  const ativos = doFormulario + daPendencia;

  if (ativos === 0) return 'Nenhum filtro aplicado';

  return ativos === 1 ? '1 filtro aplicado' : `${ativos} filtros aplicados`;
});

function paraFiltros() {
  return {
    busca: local.busca.trim(),
    redec_id: local.redec_id === '' ? '' : Number(local.redec_id),
    macrorregiao: local.macrorregiao,
  };
}

function aplicar() {
  emit('apply', paraFiltros());
}

function limpar() {
  Object.assign(local, VAZIO);
  emit('clear');
}
</script>
```

- [ ] **Step 2: Verificar que compila**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro.

Nao commitar ainda.

---

### Task 3: Organismo PrefeituraTable

**Files:**
- Create: `resources/js/Components/Organisms/Cedec/PrefeituraTable.vue`

**Interfaces:**

- Consumes (da fase 2 e do projeto):
  - Linha de `App\Modules\Cedec\Resources\PrefeituraListaResource`: `{ municipio_id: int, municipio_nome: string, codigo_ibge: string, redec: ?string, prefeito_nome: ?string, email_prefeitura: ?string, tel_prefeitura: ?string, tem_foto: bool }`.
  - Rota nomeada `cedec.prefeituras.edit` (registrada na fase 2, binding implicito por `App\Models\Municipio`; o parametro e o `municipio_id`).
  - `resources/js/Composables/useMobile.js` — reexporta `useMobile()` de `Composables/mobile/useMobile`, que devolve `{ isMobile, isTablet, isDesktop, screenWidth }`. `isDesktop` e `matchMedia('(min-width: 1024px)')`, ou seja, o corte em `lg`.
  - `resources/js/Components/Molecules/ListEmptyState.vue` — props `icon: Object|Function`, `title: String`, `helper: String`.
  - `resources/js/Components/Atoms/Button/ActionButton.vue` — no modo unico usa `action`, `module`, `resource`, `allowed`, `showLabel`, `size`, `tooltipText`, `label`. Com `module="cedec"`, `resource="prefeituras"` e `action="edit"` ele consulta `can('cedec.prefeituras.edit')`; `:allowed="false"` esconde antes de consultar o RBAC.
  - Utilitarios de CSS ja existentes em `resources/css/utilities/table-sticky.css`: `.table-actions-head`, `.table-actions-cell`, `.table-row-solid`.

- Produces (usado pela Task 4):
  - Componente `PrefeituraTable`, props `prefeituras: Array`, `podeEditar: Boolean`. Sem emits: a navegacao para o Edit acontece dentro do organismo, como em `Organisms/Cisterna/BeneficiariosTable.vue`.

- [ ] **Step 1: Criar o organismo**

Crie `resources/js/Components/Organisms/Cedec/PrefeituraTable.vue` com este conteudo
completo:

```vue
<template>
  <!--
    Tabela a partir de `lg`; abaixo disso, BLOCO (regra 9 de
    "04 - Responsividade"). O corte e o mesmo da sidebar e do resto do sistema:
    `useMobile().isDesktop` le matchMedia `(min-width: 1024px)`, a MESMA medida
    das media queries do Tailwind. Decidir por `window.innerWidth` divergiria da
    largura da barra de rolagem justamente na borda.

    Nao se usa aqui o `Organisms/Table/ResponsiveTable.vue`: ele corta em `md`
    (768px) e carrega `<style scoped>` -- as duas coisas que esta fase nao pode
    ter.
  -->
  <div
    v-if="isDesktop"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead class="bg-slate-50 dark:bg-slate-900/50">
          <tr>
            <th scope="col" :class="TH">Município</th>
            <th scope="col" :class="TH">REDEC</th>
            <th scope="col" :class="TH">Prefeito</th>
            <th scope="col" :class="TH">E-mail institucional</th>
            <th scope="col" :class="TH">Telefone</th>
            <th scope="col" :class="TH">Foto</th>
            <th scope="col" class="table-actions-head w-20 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              Ações
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr
            v-for="p in prefeituras"
            :key="p.municipio_id"
            class="table-row-solid transition-colors"
          >
            <td :class="TD_FORTE">{{ p.municipio_nome }}</td>
            <td :class="TD">{{ p.redec ?? '—' }}</td>
            <td :class="TD">{{ p.prefeito_nome ?? '—' }}</td>

            <!--
              O e-mail institucional passa de 40 caracteres em boa parte das 853
              linhas e e ele que empurrava a tabela. Receita de tres partes da
              regra 2 de "04 - Responsividade": container com `min-w-0` e
              largura maxima, `overflow-x-auto` nele, e `w-max` no filho -- sem
              o `w-max` o span se comprime em vez de rolar. A celula rola dentro
              de si; a pagina, nao.
            -->
            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">
              <div class="min-w-0 max-w-[18rem] overflow-x-auto">
                <span class="block w-max whitespace-nowrap">{{ p.email_prefeitura ?? '—' }}</span>
              </div>
            </td>

            <td :class="TD">{{ p.tel_prefeitura ?? '—' }}</td>

            <td :class="TD">
              <span :class="p.tem_foto ? PILULA_SIM : PILULA_NAO">
                {{ p.tem_foto ? 'Sim' : 'Não' }}
              </span>
            </td>

            <!-- Coluna fixa no canto direito. Depende de .table-row-solid na
                 <tr> para o fundo opaco. -->
            <td class="table-actions-cell w-20 whitespace-nowrap px-3 py-2 text-right">
              <div class="flex items-center justify-end">
                <ActionButton
                  action="edit"
                  module="cedec"
                  resource="prefeituras"
                  :allowed="podeEditar"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar dados da prefeitura"
                  @click="editar(p.municipio_id)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="prefeituras.length === 0">
            <td colspan="7" class="px-3 py-10">
              <ListEmptyState
                :icon="BuildingOffice2Icon"
                title="Nenhum município encontrado"
                helper="Ajuste a busca, a REDEC ou a macrorregião, ou use Limpar para ver a lista inteira."
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Abaixo de `lg`: um bloco por municipio. Titulo, pares rotulo/valor e a
       acao no pe -- o mesmo desenho de card do RAT, que e a referencia. -->
  <div v-else class="space-y-3">
    <article
      v-for="p in prefeituras"
      :key="p.municipio_id"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <header class="flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ p.municipio_nome }}
          </h3>
          <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
            REDEC: {{ p.redec ?? '—' }}
          </p>
        </div>
        <span :class="[p.tem_foto ? PILULA_SIM : PILULA_NAO, 'shrink-0']">
          {{ p.tem_foto ? 'Com foto' : 'Sem foto' }}
        </span>
      </header>

      <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
        <div class="min-w-0">
          <dt :class="DT">Prefeito</dt>
          <dd :class="DD" class="truncate">{{ p.prefeito_nome ?? '—' }}</dd>
        </div>
        <div class="min-w-0">
          <dt :class="DT">Telefone</dt>
          <dd :class="DD" class="truncate">{{ p.tel_prefeitura ?? '—' }}</dd>
        </div>
        <div class="col-span-2 min-w-0">
          <dt :class="DT">E-mail institucional</dt>
          <!-- Mesma receita da celula da tabela: em 375px o e-mail e o unico
               campo que nao cabe, e ele rola dentro do proprio bloco. -->
          <dd class="min-w-0 overflow-x-auto">
            <span class="block w-max whitespace-nowrap text-sm text-slate-700 dark:text-slate-200">
              {{ p.email_prefeitura ?? '—' }}
            </span>
          </dd>
        </div>
      </dl>

      <footer class="mt-3 flex justify-end border-t border-slate-200 pt-3 dark:border-slate-700/50">
        <!--
          No bloco o rotulo FICA: aqui nao ha coluna disputando largura, e um
          icone solto no pe do card e adivinhacao.
        -->
        <ActionButton
          action="edit"
          module="cedec"
          resource="prefeituras"
          label="Editar"
          :allowed="podeEditar"
          size="sm"
          tooltip-text="Editar dados da prefeitura"
          @click="editar(p.municipio_id)"
        />
      </footer>
    </article>

    <div
      v-if="prefeituras.length === 0"
      class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <ListEmptyState
        :icon="BuildingOffice2Icon"
        title="Nenhum município encontrado"
        helper="Ajuste a busca, a REDEC ou a macrorregião, ou use Limpar para ver a lista inteira."
      />
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import { useMobile } from '@/Composables/useMobile';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

/**
 * Organismo: concentra a interacao da listagem. Navegar para o Edit acontece
 * aqui, no mesmo padrao de `Organisms/Cisterna/BeneficiariosTable.vue` -- a
 * pagina nao precisa repassar um evento que so tem um destino possivel.
 */
const props = defineProps({
  prefeituras: { type: Array, default: () => [] },
  podeEditar: { type: Boolean, default: false },
});

const { isDesktop } = useMobile();

const TH = 'px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-100';
const DT = 'text-xs font-medium text-slate-500 dark:text-slate-400';
const DD = 'text-sm text-slate-700 dark:text-slate-200';

/*
  Classes literais, nunca interpoladas: o Tailwind varre `resources/js/**\/*.vue`
  em busca de literais. Uma string montada em runtime (ou vinda de enum PHP) nao
  seria detectada e a cor nao existiria no CSS final.
*/
const PILULA_SIM = 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
const PILULA_NAO = 'inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 dark:bg-slate-700/50 dark:text-slate-400';

/**
 * O binding da rota e por Municipio, nao por Prefeitura: a CEDEC navega pelos
 * 853 municipios, inclusive os que ainda nao tem linha em
 * `compdec_prefeituras`.
 */
function editar(municipioId) {
  router.visit(route('cedec.prefeituras.edit', municipioId));
}
</script>
```

Atencao ao escrever o comentario do bloco `PILULA_*`: no arquivo real ele deve conter
`resources/js/**/*.vue` sem a barra invertida (a barra existe apenas neste plano para nao
fechar o comentario de bloco).

- [ ] **Step 2: Verificar que compila**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro.

Nao commitar ainda.

---

### Task 4: Pagina Cedec/Prefeituras/Index e commit da fase

**Files:**
- Create: `resources/js/Pages/Cedec/Prefeituras/Index.vue`

**Interfaces:**

- Consumes (da fase 2 e das tasks anteriores):
  - Rota nomeada `cedec.prefeituras.index` (GET `/cedec/prefeituras`, `can:cedec.prefeituras.view`), servida por `App\Modules\Cedec\Controllers\PrefeituraController::index(Request $request): \Inertia\Response`, renderizando a pagina `Cedec/Prefeituras/Index`.
  - Props Inertia da pagina, exatamente como a secao 8 do contrato descreve:
    - `prefeituras: { data: Array<linha do PrefeituraListaResource>, current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int }` — ACHATADO, sem `meta`.
    - `estatisticas: { total: int, sem_email: int, sem_telefone: int, sem_foto: int }` — prop closure, cara: e ela que o reload parcial protege.
    - `filtros: { busca: ?string, redec_id: ?int, macrorregiao: ?string, pendencia: ?string }`
    - `redecs: Array<{value:number,label:string}>`
    - `macrorregioes: Array<{value:string,label:string}>`
  - `App\Modules\Cedec\DTOs\PrefeituraFiltroDTO::fromRequest()` le as chaves `busca`, `redec_id`, `macrorregiao`, `pendencia` da query string; `pendencia` aceita `'sem_email'`, `'sem_telefone'`, `'sem_foto'` ou nulo.
  - `PrefeituraStatCards` (Task 1), `PrefeituraFiltersSection` (Task 2), `PrefeituraTable` (Task 3).
  - `resources/js/Components/Organisms/PageHeader.vue` — props `title` (required), `description`, `icon`, `iconImage`, `variant: 'default'|'gradient'`, `iconClass`, `espacoInferior: Boolean (default true)`; slot `actions`.
  - `resources/js/Components/Molecules/Navigation/Pagination.vue` — prop unica `pagination: Object` cujas chaves sao ACHATADAS (`current_page`, `last_page`, `per_page`, `total`); emit `page-change` com o numero da pagina.
  - `resources/js/Composables/usePermissions.js` — `usePermissions()` devolve `{ can, canAny, ... }`; `can(slug: string): boolean`.
  - `resources/js/Support/moduleIcons.js` — `moduleIcon('prefeituras')`.

- Produces:
  - Pagina Inertia `Cedec/Prefeituras/Index` funcionando. Nada nesta fase e consumido por fases posteriores alem do proprio caminho do arquivo (a fase 5 acrescenta o botao de relatorios no slot `#actions`).

- [ ] **Step 1: Criar a pagina**

Crie `resources/js/Pages/Cedec/Prefeituras/Index.vue` com este conteudo completo:

```vue
<template>
  <Head title="Cadastro de Prefeituras" />

  <!--
    Forma B da regra 2 de "03 - Layout": `w-full space-y-6 pb-8`, SEM `p-*`
    nem `px-*`. A calha horizontal e do `<main>` do AuthenticatedLayout
    (`px-4 sm:px-6 lg:px-8`); repetir aqui deixaria o modulo 48px mais estreito
    que o resto do sistema em `lg`.

    Como o ritmo vem do `space-y-6` do pai, todo filho com margem propria
    desliga a sua -- e o que fazem `:espaco-inferior="false"` no PageHeader e o
    mesmo, ja embutido, no PrefeituraStatCards.
  -->
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      title="Cadastro de Prefeituras"
      description="Contatos institucionais das prefeituras de Minas Gerais, mantidos pela CEDEC estadual."
      :icon-image="moduleIcon('prefeituras') ?? ''"
      :icon="BuildingOffice2Icon"
      variant="gradient"
      :espaco-inferior="false"
    />
    <!--
      Sem slot #actions nesta fase, de proposito: o unico botao candidato seria
      "Relatórios de contato", cuja pagina (`Cedec/Contatos/Index`) so nasce na
      fase 5. Botao apontando para pagina inexistente e link morto em producao.
    -->

    <PrefeituraStatCards :estatisticas="estatisticas" @filter="filtrarPorPendencia" />

    <PrefeituraFiltersSection
      :filters="filtros"
      :redecs="redecs"
      :macrorregioes="macrorregioes"
      @apply="aplicar"
      @clear="limpar"
    />

    <!--
      Lista e paginacao num bloco so: o `Pagination` ja traz `mt-4` proprio, e
      solto dentro do `space-y-6` os dois espacos somariam 40px onde o resto da
      pagina usa 24px.
    -->
    <div>
      <PrefeituraTable
        :prefeituras="prefeituras.data ?? []"
        :pode-editar="can('cedec.prefeituras.edit')"
      />

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { moduleIcon } from '@/Support/moduleIcons';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PrefeituraStatCards from '@/Components/Molecules/Cedec/PrefeituraStatCards.vue';
import PrefeituraFiltersSection from '@/Components/Organisms/Cedec/PrefeituraFiltersSection.vue';
import PrefeituraTable from '@/Components/Organisms/Cedec/PrefeituraTable.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  prefeituras: {
    type: Object,
    default: () => ({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0, from: null, to: null }),
  },
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, sem_email: 0, sem_telefone: 0, sem_foto: 0 }),
  },
  filtros: { type: Object, default: () => ({}) },
  redecs: { type: Array, default: () => [] },
  macrorregioes: { type: Array, default: () => [] },
});

/**
 * O `Pagination` recebe UM objeto com as chaves achatadas. O backend da fase 2
 * ja entrega nesse formato (sem `meta` aninhado); este computed existe para
 * garantir os defaults quando a prop chega vazia numa visita parcial.
 */
const paginacao = computed(() => ({
  current_page: props.prefeituras?.current_page ?? 1,
  last_page: props.prefeituras?.last_page ?? 1,
  per_page: props.prefeituras?.per_page ?? 20,
  total: props.prefeituras?.total ?? 0,
  from: props.prefeituras?.from ?? null,
  to: props.prefeituras?.to ?? null,
}));

/**
 * Pesquisar preserva a pendencia escolhida no stat card: busca textual e
 * REFINAMENTO, nao troca de eixo.
 */
function aplicar(filtrosDoFormulario) {
  buscar({ ...filtrosDoFormulario, pendencia: props.filtros?.pendencia ?? '' });
}

/**
 * Filtro vindo de stat card SUBSTITUI o eixo -- clicar em "Sem e-mail" tem que
 * mostrar quem esta sem e-mail, nao a intersecao com a pendencia anterior. O
 * que sobrevive e o recorte territorial e a busca, que sao refinamento.
 */
function filtrarPorPendencia(pendencia) {
  buscar({
    busca: props.filtros?.busca ?? '',
    redec_id: props.filtros?.redec_id ?? '',
    macrorregiao: props.filtros?.macrorregiao ?? '',
    pendencia: pendencia ?? '',
  });
}

function limpar() {
  buscar({});
}

function irParaPagina(pagina) {
  buscar({ ...props.filtros, page: pagina });
}

/**
 * Reload PARCIAL: `estatisticas` fica FORA do `only`.
 *
 * No controller ela e uma closure, e closure de prop no Inertia e reavaliada em
 * TODA visita completa. Sem isto, os quatro contadores -- que agregam sobre as
 * 853 linhas de municipio com LEFT JOIN em `compdec_prefeituras` -- seriam
 * recalculados a cada troca de filtro e a cada pagina, sem precisar: eles medem
 * o cadastro inteiro, nao o resultado filtrado.
 *
 * `redecs` e `macrorregioes` tambem ficam de fora: sao listas fixas.
 */
function buscar(filtros) {
  router.get(route('cedec.prefeituras.index'), paraQuery(filtros), {
    only: ['prefeituras', 'filtros'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Tira o que esta vazio, para a URL nao encher de parametro sem valor -- e para
 * o `PrefeituraFiltroDTO` receber `null` em vez de string vazia.
 */
function paraQuery(filtros) {
  const query = {};

  Object.entries(filtros ?? {}).forEach(([chave, valor]) => {
    if (valor === undefined || valor === null || valor === '') return;

    query[chave] = valor;
  });

  return query;
}
</script>
```

- [ ] **Step 2: Verificar que compila**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro e o manifesto passa a listar
`resources/js/Pages/Cedec/Prefeituras/Index.vue`. Confirme:

```bash
grep -c "Cedec/Prefeituras/Index" SDC/public/build/manifest.json
```
Expected: `1` ou mais.

- [ ] **Step 3: Conferir que nao sobrou `<style scoped>` nem `p-*` na raiz**

Run:
```bash
grep -rn "style scoped" SDC/resources/js/Pages/Cedec SDC/resources/js/Components/Organisms/Cedec SDC/resources/js/Components/Molecules/Cedec
```
Expected: nenhuma saida.

Run:
```bash
grep -n "class=\"w-full space-y-6 pb-8\"" SDC/resources/js/Pages/Cedec/Prefeituras/Index.vue
```
Expected: uma linha. A raiz nao pode conter `p-4`, `p-6`, `px-*` nem `sm:p-*`.

- [ ] **Step 4: Commit da fase (unico, atomico)**

Os quatro arquivos entregam UMA mudanca — a tela de indice — e vao juntos (regra de
ouro 12). Se o Step 1 da Task 1 alterou `moduleIcons.js`, ele entra tambem.

```bash
cd SDC
git add resources/js/Pages/Cedec/Prefeituras/Index.vue \
        resources/js/Components/Organisms/Cedec/PrefeituraFiltersSection.vue \
        resources/js/Components/Organisms/Cedec/PrefeituraTable.vue \
        resources/js/Components/Molecules/Cedec/PrefeituraStatCards.vue
git add resources/js/Support/moduleIcons.js
git commit -m "✨ feat(cedec): indice de prefeituras com stat cards, filtros e tabela responsiva"
```

Se `moduleIcons.js` nao foi alterado, o `git add` dele e inofensivo (nada a adicionar).
**Nao incluir trailer de co-autor.**

---

### Task 5: Verificacao E2E de responsividade em 375px e 840px

**Files:**
- Create (temporario, NAO commitar): `tests/e2e/cedec-prefeituras-responsivo.spec.js`

**Interfaces:**

- Consumes:
  - `SDC/playwright.config.js` — `testDir: './tests/e2e'`, `baseURL: process.env.TEST_BASE_URL`, `ignoreHTTPSErrors: true`, projeto unico `chromium`.
  - `npm run test:e2e` = `dotenvx run -f .env -f tests/e2e/.env.test -- npx playwright test`. O `.env.test` cifrado fornece `TEST_BASE_URL`, `TEST_CPF` e `TEST_PASSWORD`; a decifra usa `tests/e2e/.env.keys`, que existe local e esta no `.gitignore`.
  - Tela de login (`resources/js/Pages/Auth/Login.vue`): campo `#cpf` (texto, mascara aplicada no `@input`), campo `#password`, botao de submit com o texto `Acessar Sistema`.
  - A pagina publicada na Task 4, em `/cedec/prefeituras`.
- Produces: nada de codigo. Produz a evidencia de que o excesso horizontal e 0.

- [ ] **Step 1: Escrever o spec de responsividade**

Crie `SDC/tests/e2e/cedec-prefeituras-responsivo.spec.js`:

```js
import { test, expect } from '@playwright/test';

/**
 * Excesso horizontal ZERO em 375px e 840px -- os dois pontos da regra 1 de
 * "04 - Responsividade". 840px e a faixa de tablet, onde mais defeito se
 * esconde: e o unico ponto em que a tabela ja virou bloco (corte em lg = 1024)
 * mas o cabecalho ainda esta em linha.
 */
const CPF = process.env.TEST_CPF;
const SENHA = process.env.TEST_PASSWORD;

async function entrar(page) {
  await page.goto('/login');
  await page.fill('#cpf', CPF);
  await page.fill('#password', SENHA);
  await page.getByRole('button', { name: 'Acessar Sistema' }).click();
  await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 30000 });
}

async function excessoHorizontal(page) {
  return page.evaluate(() => {
    const de = document.documentElement;

    return de.scrollWidth - de.clientWidth;
  });
}

const ESTREITOS = [
  { nome: '375px', width: 375, height: 812 },
  { nome: '840px', width: 840, height: 1024 },
];

for (const vp of ESTREITOS) {
  test(`indice de prefeituras nao transborda em ${vp.nome}`, async ({ page }) => {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await entrar(page);

    await page.goto('/cedec/prefeituras');
    await expect(page.getByRole('heading', { name: 'Cadastro de Prefeituras' })).toBeVisible();

    expect(await excessoHorizontal(page)).toBe(0);

    // Abaixo de lg a listagem e BLOCO: nenhuma <table> na tela.
    await expect(page.locator('table')).toHaveCount(0);

    // Quem transborda rola DENTRO de si. Registrado para inspecao no relatorio.
    const contidos = await page.evaluate(() => [...document.querySelectorAll('.overflow-x-auto')]
      .map((el) => ({
        classe: el.className.slice(0, 40),
        rola: el.scrollWidth > el.clientWidth,
      })));
    console.log(vp.nome, JSON.stringify(contidos));
  });
}

test('indice de prefeituras mostra a tabela a partir de lg', async ({ page }) => {
  await page.setViewportSize({ width: 1280, height: 900 });
  await entrar(page);

  await page.goto('/cedec/prefeituras');
  await expect(page.getByRole('heading', { name: 'Cadastro de Prefeituras' })).toBeVisible();
  await expect(page.locator('table')).toHaveCount(1);

  expect(await excessoHorizontal(page)).toBe(0);
});
```

- [ ] **Step 2: Rodar o spec**

Run: `cd SDC && npm run test:e2e -- tests/e2e/cedec-prefeituras-responsivo.spec.js`
Expected: 3 testes PASSANDO.

Se falhar em `375px` com excesso maior que 0, o culpado quase sempre e um elemento com
`whitespace-nowrap` sem ancestral que o contenha. Isole rodando no console do navegador:

```js
[...document.querySelectorAll('*')]
  .filter((el) => el.getBoundingClientRect().right > document.documentElement.clientWidth)
  .map((el) => el.className)
  .slice(0, 10);
```

Corrija aplicando a receita de tres partes (pai `min-w-0`, container
`min-w-0 overflow-x-auto`, filho `w-max`) no elemento acusado, e rode de novo.

Se o login falhar por `TEST_BASE_URL` inacessivel, suba o ambiente
(`npm run docker:up`) antes de repetir — o spec nao sobe servidor.

- [ ] **Step 3: Conferir o build final**

Run: `cd SDC && npm run build`
Expected: build conclui sem erro.

- [ ] **Step 4: Remover o spec e conferir a arvore limpa**

Regra de ouro 10: arquivo de teste criado durante o trabalho nao entra no commit.

```bash
cd SDC
rm tests/e2e/cedec-prefeituras-responsivo.spec.js
git status --short
```
Expected: nenhuma entrada relativa a `tests/e2e/` e nenhum arquivo da fase 3 pendente —
tudo ja foi para o commit da Task 4.

---

## Fora do escopo desta fase (nao implemente)

- `Pages/Cedec/Prefeituras/Edit.vue`, `PrefeituraFormSections`, `IndicadoresMunicipaisPanel`, foto e o refit de `Organisms/Compdec/PrefeituraForm.vue` — fase 4.
- `Pages/Cedec/Contatos/Index.vue`, `ContatoBloco`, `ContatoBlocosOutlook`, export CSV — fase 5.
- Qualquer alteracao em controller, service, resource, rota ou `config/permissions.php` — fase 2. Se algo faltar no backend, PARE e relate; nao contorne no front.
- Alterar `Molecules/Navigation/Pagination.vue`, `Organisms/PageHeader.vue`, `StatCard.vue` ou `CollapsibleSection.vue`. Se um deles nao atender, a regra do projeto e **adicionar prop no componente canonico**, nunca criar um paralelo — e isso e mudanca de escopo, que precisa ser combinada antes.

---

## Pontos que o executor precisa saber antes de comecar

1. **O botao Editar da tabela aponta para a fase 4.** A rota `cedec.prefeituras.edit` existe
   desde a fase 2, mas a pagina Inertia `Cedec/Prefeituras/Edit` so nasce na fase 4. Clicar
   nele antes disso devolve erro de componente nao encontrado. E esperado; o E2E da Task 5
   nao clica no botao. Nao invente uma pagina provisoria.
2. **`Pagination.vue` renderiza os numeros das paginas, alem das setas.** A regra 10 de
   "04 - Responsividade" diz "so as setas", mas o componente canonico atual desenha
   `visiblePages` mais dois `Button` com rotulo escondido abaixo de `md`. Cumprir a regra ao
   pe da letra exigiria mudar o componente compartilhado, o que afeta 20 consumidores — fora
   do escopo desta fase. Use o componente como esta e nao escreva paginacao propria.
3. **O card de total nao e clicavel.** A secao 5 de `.claude/skills/frontend/01 - Frontend`
   e o `PmdaStatisticsCards` fazem o card "Total" limpar o filtro. Aqui o contrato manda o
   contrario: "card sem filtro atras nao leva `clickable`". O contrato vence. Quem limpa a
   pendencia e o botao Limpar.
