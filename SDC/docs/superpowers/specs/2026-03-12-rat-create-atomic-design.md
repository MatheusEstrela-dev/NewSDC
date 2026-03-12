# RAT Create — Refatoração Atomic Design

**Data:** 2026-03-12
**Módulo:** RAT (Relatório de Ação Técnica)
**Escopo:** Método CREATE (e base reutilizável por EDIT) — camada Vue (frontend only)

---

## Problema

O método CREATE do módulo RAT viola o Atomic Design em três pontos:

1. `RatCreate.vue` (Page) acumula 12 handlers inline que deveriam estar no composable `useRat` — 215 linhas fazendo papel de Page + Template.
2. `RatForm.vue` (Organism) contém footer de ações, toggle bruto sem abstração e seções anônimas inline — mistura Organism + Molecule + Atom.
3. Camada Template inexistente — a estrutura Header + Tabs está partida entre a Page e o Organism.

---

## Solução — Abordagem 2 (Media)

Separação clara de camadas sem reorganizar pastas globais nem tocar nos Tabs 2-6.

Os novos componentes `RatFormLayout` e `RatDadosGeraisForm` serão imediatamente reutilizáveis por `RatEdit.vue` (estrutura idêntica), evitando divergência futura.

---

## Composable Autoritativo

O composable utilizado pelas Pages é **`@/Composables/useRat`** (`resources/js/composables/useRat.js`).

Este arquivo exporta: `rat`, `recursos`, `envolvidos`, `vistoria`, `historico`, `anexos`, `tabs`, `salvarRat`, `salvarRascunho`, `finalizarRat`, `cancelarRat`.

Os handlers de array (`handleAddRecurso`, `handleRemoveRecurso`, etc.) que hoje estão inline na Page serão movidos para dentro deste composable como funções exportadas.

O arquivo `/composables/rat/useRat.js` é um stub secundário — não utilizado pelas Pages, não modificado nesta refatoração.

---

## Árvore de Componentes

```
Pages/Rat/RatCreate.vue                     Page (~50 linhas)
  └── AuthenticatedLayout                   Layout existente
       └── RatFormLayout.vue                NEW Template
            ├── RatHeader.vue               Organism (inalterado)
            ├── RatTabs.vue                 Organism (inalterado)
            └── slot #default:
                 Tab 1: RatDadosGeraisForm.vue   NEW Organism
                   ├── RatAtendimentoSection.vue   NEW Section
                   │    └── FormField (datetime x3)
                   ├── RatCommunicationSection.vue  inalterado
                   ├── RatNaturezaSection.vue        NEW Section
                   │    ├── FormSelect (COBRADE)
                   │    └── FormField (nome operação)
                   ├── RatConfigSection.vue          NEW Section
                   │    ├── ToggleField.vue           NEW Molecule
                   │    │    └── ToggleInput.vue      NEW Atom
                   │    └── FormField (unidade readonly)
                   ├── RatLocationSection.vue        inalterado
                   ├── RatAddressSection.vue         inalterado
                   └── RatFormActions.vue            NEW Molecule
                        ├── Button "Salvar"          Atom existente
                        └── Button "Finalizar"       Atom existente
                 Tabs 2-6: inalterados
```

---

## Fluxo de Dados

**Regra:** props descem, eventos sobem. Nenhuma Section acessa `useRat` diretamente.

```
RatCreate (Page) — único que conhece useRat()
  │
  ├─props──► RatFormLayout [rat, tabConfig, activeTab, viewOnly, isCreate, lastUpdate]
  │           emit: @tab-change → Page
  │           RatFormLayout repassa para RatHeader: rat, viewOnly, isCreate, lastUpdate
  │           RatFormLayout repassa para RatTabs: tabConfig, activeTab
  │
  └─slot──► RatDadosGeraisForm [rat, viewOnly]
             emits:
               @save-draft        → payload: { dadosGerais, comunicacao, local, endereco }
               @finalize          → mesmo payload
               @update:tem-vistoria → Boolean
               @update:formData   → payload completo (para handleSaveFromSubTab dos subtabs)
             (slot content é owned pela Page — emits chegam direto à Page, não ao Layout)
             │
             ├─v-model──► RatAtendimentoSection  (localData.dadosGerais)
             ├─v-model──► RatCommunicationSection (localData.comunicacao)
             ├─v-model──► RatNaturezaSection      (localData.dadosGerais)
             ├─v-model──► RatConfigSection        (localData.dadosGerais — ver abaixo)
             ├─v-model──► RatLocationSection      (localData.local)
             ├─v-model──► RatAddressSection       (localData.endereco)
             └─props──►  RatFormActions           (viewOnly, loading=false)
                          emit: @save-draft, @finalize → RatDadosGeraisForm → Page
```

### Convenção de emit Vue

`defineEmits` usa camelCase: `'update:formData'`, `'update:temVistoria'`.
Templates usam kebab-case: `@update:form-data`, `@update:tem-vistoria`.
Vue normaliza automaticamente — seguir este padrão em todos os componentes novos.

### RatConfigSection — interface exata

```
Props:
  modelValue: Object  // dadosGerais completo (tem_vistoria dentro)
  unidade: String     // readonly, ex: 'COMPDEC - Municipio Modelo/MG'

Emits:
  update:modelValue   // Object dadosGerais atualizado com tem_vistoria toggled
```

`RatDadosGeraisForm` interpreta a mudança e re-emite `update:temVistoria` (Boolean) para o Page controlar a visibilidade da aba Vistoria.

### RatFormActions — prop `loading`

`loading` é sempre `false` nesta iteração — Inertia não expõe flag reativa de loading por componente neste fluxo. A prop existe para extensão futura. O implementador não precisa buscar fonte de `loading`.

---

## Inventário de Arquivos

### Novos (7)

| Arquivo | Camada | Props | Emits |
|---|---|---|---|
| `Atoms/Input/ToggleInput.vue` | Átomo | `modelValue` (Boolean) | `update:modelValue` |
| `Molecules/Form/ToggleField.vue` | Molécula | `modelValue`, `label`, `description`, `icon` | `update:modelValue` |
| `Molecules/Rat/RatFormActions.vue` | Molécula | `viewOnly`, `loading=false` | `save-draft`, `finalize` |
| `Rat/Templates/RatFormLayout.vue` | Template | `rat`, `tabConfig`, `activeTab`, `viewOnly`, `isCreate`, `lastUpdate` | `tab-change` |
| `Rat/Sections/RatAtendimentoSection.vue` | Section | `modelValue` (dadosGerais Object) | `update:modelValue` |
| `Rat/Sections/RatNaturezaSection.vue` | Section | `modelValue` (dadosGerais Object) | `update:modelValue` |
| `Rat/Sections/RatConfigSection.vue` | Section | `modelValue` (dadosGerais Object), `unidade` (String) | `update:modelValue` |

### Modificados (2)

| Arquivo | Mudança |
|---|---|
| `RatForm.vue` → `RatDadosGeraisForm.vue` | Remove seções inline e footer; importa 3 novos Sections + RatFormActions. Mantém localData, watch, emits. |
| `Pages/Rat/RatCreate.vue` | Remove 12 handlers inline; handlers de array migram para `useRat`; usa RatFormLayout via Template. Resultado: ~50 linhas. |

### Modificado indireto (1)

| Arquivo | Mudança |
|---|---|
| `composables/useRat.js` | Adiciona funções de array: `adicionarRecurso`, `removerRecurso`, `atualizarRecurso`, `adicionarEnvolvido`, etc. Interface pública existente inalterada. |

### Inalterados (15+)

RatTabs, RatHeader, RatCommunicationSection, RatLocationSection, RatAddressSection, RatCollapsibleSection, RatResources, RatInvolved, RatInspection, RatHistory, RatAttachments, todos os controllers PHP, RatEdit.vue (será refatorado em iteração separada usando os mesmos componentes).

---

## Estratégia de Implementação

Ordem bottom-up — cada passo compilável independentemente:

```
Passo 1  Criar ToggleInput.vue
Passo 2  Criar ToggleField.vue + RatFormActions.vue
Passo 3  Criar RatAtendimentoSection, RatNaturezaSection, RatConfigSection
Passo 4  Criar RatDadosGeraisForm.vue (RatForm refatorado)
Passo 5  Criar RatFormLayout.vue
Passo 6  Mover handlers de array para useRat.js
Passo 7  Refatorar RatCreate.vue
Passo 8  just npm-build
```

**Salvaguardas:**
- `RatForm.vue` original existe em paralelo até o Passo 4 ser validado
- Tabs 2-6 não são tocadas
- `useRat` recebe novos handlers mas retorno existente permanece estável

---

## Critérios de Sucesso

- `RatCreate.vue` com no máximo 60 linhas
- Nenhum handler inline de array na Page
- Nenhum `<button>` bruto no Organism (substituído por `RatFormActions` + `ToggleField`)
- `just npm-build` sem erros
- Funcionalidade de criar RAT (salvar rascunho + finalizar) operacional
