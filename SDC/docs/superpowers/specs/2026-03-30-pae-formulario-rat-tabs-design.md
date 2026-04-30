# Design: Refatoração das Abas do Formulário PAE (RAT)

**Data:** 2026-03-30
**Projeto:** NewSDC — `C:\Users\x24679188\Documents\Github\NewSDC`
**Escopo:** Frontend Vue 3 + Inertia + Tailwind (Atomic Design + DDD)

---

## Contexto

O módulo PAE no NewSDC possui a página `Pae.vue` com 4 abas principais (Formulário PAE / Histórico / CCPAE / Empreendedor). A aba "Formulário PAE" é renderizada pelo componente `PaeForm.vue`, que atualmente exibe apenas dados gerais do empreendimento.

O objetivo é refatorar o `PaeForm.vue` para incorporar o fluxo completo de criação do RAT (Relatório de Análise Técnica), com 4 sub-abas, salvamento parcial por aba via Inertia, e dados pré-populados a partir do empreendimento selecionado.

O design segue 100% o sistema de design existente do projeto (dark/light mode, tokens Tailwind, componentes `PaeCard`, `FormField`, `FormSelect`). Sem fachada roxa ou elementos visuais externos ao design system.

---

## Arquitetura de Componentes

### Novos arquivos

```
resources/js/Components/Pae/
  PaeFormTabs.vue               ← Molecule: navegação interna das 4 sub-abas
  PaeFormInfoGerais.vue         ← Organism: sub-aba 1 — Informações Gerais
  PaeFormObjetivoContexto.vue   ← Organism: sub-aba 2 — Objetivo e Contexto
  PaeFormApontamentos.vue       ← Organism: sub-aba 3 — Apontamentos Técnicos
  PaeFormConclusao.vue          ← Organism: sub-aba 4 — Conclusão

resources/js/composables/pae/
  usePaeFormulario.js           ← Composable: state + saves parciais via Inertia
```

### Arquivos modificados

```
resources/js/Components/Pae/
  PaeForm.vue                   ← Refatorado: orquestrador das sub-abas (~60 linhas)
```

### Arquivos sem mudança

```
Pages/Pae.vue
Components/Pae/PaeTabs.vue
Components/Pae/PaeHeader.vue
Components/Pae/PaeCard.vue
Components/Pae/FormField.vue
Components/Pae/FormSelect.vue
composables/pae/usePae.js
```

---

## Fluxo de Dados

```
Pae.vue
  props: { empreendimento, ... }
  └── PaeForm.vue
        recebe: empreendimento (Object)
        instancia: usePaeFormulario(empreendimento)
        └── PaeFormTabs.vue
              controla: activeSubTab (ref interno)
        └── PaeFormInfoGerais.vue
              recebe: formData.infoGerais, municipios, saving
              emite: @save
        └── PaeFormObjetivoContexto.vue
              recebe: formData.objetivoContexto, saving
              emite: @save
        └── PaeFormApontamentos.vue
              recebe: formData.apontamentos, saving
              emite: @save, @add-item, @remove-item, @add-sub, @remove-sub
        └── PaeFormConclusao.vue
              recebe: formData.conclusao, saving
              emite: @save, @finalizar, @add-item, @remove-item, @add-sub, @remove-sub
```

---

## Composable: `usePaeFormulario.js`

### State

```js
formData: {
  infoGerais: {
    barragem, municipio_id, coordenador_pae, email,
    coordenador_mun_def_civ, coordenador_mun_compdec,
    empreendedor_res, metodo_construtivo, numero_zas, nivel_emergencia
  },
  objetivoContexto: {
    objetivo,       // texto default da Resolução GMG n. 48.078/2020
    contextualizacao // texto default CCPAE / Resolução GMG n. 83/2024
  },
  apontamentos: [{ id, text, children: [{ id, text }] }],
  conclusao:    [{ id, text, children: [{ id, text }] }]
}
saving: false  // estado de loading compartilhado
```

### Props recebidas pelo `PaeForm.vue`

```js
props: {
  empreendimento: Object,   // dados do empreendimento para pré-população
  municipios: Object,       // { id: nome } para o select de município (via Inertia)
  formulario: Object|null,  // dados já salvos do RAT, se existir (para edição)
}
```

O `Pae.vue` repassa `municipios` e `formulario` que chegam do controller Laravel via Inertia.

### Inicialização (pré-população)

Os campos são inicializados com dados do empreendimento recebido como argumento:
- `barragem` ← `empreendimento.nome`
- `municipio_id` ← `empreendimento.municipio_id`
- `coordenador_pae` ← `empreendimento.coordenador`
- `email` ← `empreendimento.email_coord`
- `empreendedor_res` ← `empreendimento.empreendedor?.nome`
- `metodo_construtivo` ← `empreendimento.m_construcao`
- `numero_zas` ← `empreendimento.pop_zas`
- Demais campos: vazios (preenchimento manual)

### Métodos de save (Inertia `router.put`)

| Método | Rota |
|---|---|
| `saveInfoGerais()` | `PUT /pae/formulario/{id}/infogerais` |
| `saveObjetivoContexto()` | `PUT /pae/formulario/{id}/objetivo` |
| `saveApontamentos()` | `PUT /pae/formulario/{id}/aptecnico` |
| `saveConclusao()` | `PUT /pae/formulario/{id}/conclusao` |
| `finalizarRelatorio()` | `PUT /pae/formulario/{id}/finalizar` |

Todos os saves: `saving = true` no início, `saving = false` no `onFinish`.

### Métodos de lista dinâmica (Apontamentos e Conclusão)

```js
addItem(section)              // adiciona item ao array
removeItem(section, index)    // remove item (mínimo 1)
addSubItem(section, index)    // adiciona sub-item ao item
removeSubItem(section, itemIndex, subIndex)
```

`section` é `'apontamentos'` ou `'conclusao'` — mesma lógica reutilizada.

---

## Componentes: Especificação Visual

### `PaeFormTabs.vue`

Segue o mesmo padrão visual do `PaeTabs.vue` existente:
- Container: `bg-slate-100 dark:bg-slate-800/30 rounded-xl p-1.5 mb-6`
- Nav: `flex gap-1 overflow-x-auto`
- Aba inativa: `text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white`
- Aba ativa: `text-blue-400 bg-blue-500/10 border-b-2 border-blue-400`
- Ícones Lucide: `file-text` / `book-open` / `clipboard-check` / `check-circle`

Props: `activeTab: Number`, `tabs: Array`
Emits: `tab-change`

### `PaeFormInfoGerais.vue`

Usa `PaeCard.vue` com título "1. Informações Gerais do Relatório":
- Grid 2 colunas (`grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6`)
- Campos: `FormField` para texto/email, `FormSelect` para selects
- Botão salvar alinhado à direita: `bg-blue-600 hover:bg-blue-500`, disabled durante saving

Selects:
- `metodo_construtivo`: Jusante / Montante / Etapa única / Linha de Centro
- `nivel_emergencia`: Sem Emergência (0) / Alerta (1) / Nível 1 (2) / Nível 2 (3) / Nível 3 (4)
- `municipio_id`: lista passada via prop `municipios`

### `PaeFormObjetivoContexto.vue`

Dois `PaeCard.vue` empilhados:
- Card "2. Objetivo": textarea rows=6, full-width
- Card "3. Contextualização": textarea rows=8, full-width
- Textareas: `w-full bg-transparent border border-slate-300 dark:border-slate-700 rounded-lg p-4 text-slate-900 dark:text-slate-100 resize-y`
- Botão salvar no rodapé

### `PaeFormApontamentos.vue`

Título "4. Apontamentos Técnicos Observados":
- Lista de cards por item: `bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4`
- Badge numerado item principal: `bg-blue-600 text-white w-7 h-7 rounded-md flex items-center justify-center font-bold`
- Badge sub-item: `bg-cyan-600 text-white px-2 py-0.5 rounded text-xs font-bold`
- Textarea item: rows=3, `FormField` estilizado
- Textarea sub-item: rows=2, menor
- Botão remover item/sub-item: `text-red-500/50 hover:text-red-500`
- Botão add sub-item: `text-blue-400 hover:text-blue-300 text-sm flex items-center gap-1`
- Botão add item: `w-full border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-blue-500/50 rounded-xl py-4`

### `PaeFormConclusao.vue`

Igual a `PaeFormApontamentos.vue` com:
- Título "5. Conclusão"
- Badge item principal: `bg-green-600`
- Badge sub-item: `bg-teal-600`
- Botão extra "Finalizar Relatório": `bg-green-600 hover:bg-green-500`

---

## Campos: Valores Default dos Textos

**Objetivo (pré-preenchido):**
> "Analisar os requisitos necessários para a aprovação da Segunda Seção do Plano de Ação de Emergência, relativos à competência do órgão Estadual de Proteção e Defesa Civil, expressa no Decreto Estadual n. 48.078, de 05 de novembro de 2020 e notificar o empreendedor sobre as inconsistências observadas para devida correção."

**Contextualização (pré-preenchido):**
> "O PAE é analisado conforme a Resolução GMG n. 83/2024, além da legislação estadual e federal vigentes. Após a sua aprovação, será emitido o Certificado de Conformidade do Plano de Ação de Emergência (CCPAE) pelo Coordenador Estadual de Defesa Civil de Minas Gerais.\n\nA emissão do CCPAE está vinculada à análise de um cenário hipotético, no qual os detalhes específicos serão descritos em um relatório relacionado à estrutura analisada. A barragem poderá ser vistoriada a qualquer tempo pelos órgãos fiscalizadores federais e estaduais e caso sejam constatadas irregularidades previstas em legislação, o CCPAE poderá ser revogado."

---

## Responsividade

- Grid das abas: `overflow-x-auto` com `whitespace-nowrap` nos labels
- Grid de campos: 1 coluna em mobile, 2 colunas em `sm:`
- Textareas: `resize-y`, full-width em todos os breakpoints
- Botões de salvar: `flex justify-end`, empilham em mobile se necessário

---

## Fora de Escopo

- Backend Laravel (rotas, controllers, requests) — não faz parte deste spec
- Aba "Histórico", "CCPAE", "Empreendedor" — sem mudança
- `Pae.vue`, `PaeTabs.vue`, `PaeHeader.vue` — sem mudança
- Geração de PDF do RAT
- Validação client-side avançada (apenas o save via Inertia com erros de volta)
