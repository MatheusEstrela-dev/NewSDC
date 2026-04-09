# Design: Edicao de Dados de Desastres — NewSDC

**Data:** 2026-04-08
**Branch:** dev2 (worktree: Feat/decretacoes)
**Modulo:** Decretacoes

---

## Objetivo

Implementar a tela de edicao de dados de desastres no NewSDC com paridade funcional completa ao legado SDC, mantendo o UX do NewSDC (Tailwind dark mode, design system proprio) e a abordagem Atomic Design.

---

## Contexto

### Legado (sdc.mg.gov.br)
- Blade + Vue 3 inline
- Formulario com blocos retrateis por municipio > categoria > desastre
- Badges de total (soma de campos number/currency) por desastre
- Mascaras de currency (BRL) e number no keyup e no load
- Campo Protocolo FIDE com mascara MG-F-XXXXXXX-XXXXX-XXXXXXXX
- Tipos de campo: `radio`, `select`, `currency`, `number`, `textarea`, `text`
- Submit envia apenas dados alterados (diff)

### NewSDC atual
- Vue 3 + Inertia + Tailwind + Atomic Design
- `ProcessoDesastresEditTemplate.vue` existe mas incompleto
- Falta: accordions por desastre, badges de total, mascaras, radio, select, textarea
- Submit via `useForm` do Inertia (envia tudo — idempotente pelo backend)

---

## Decisoes de Design

| Questao | Decisao |
|---------|---------|
| Submit | `form.post()` do Inertia — sem diff, backend usa `updateOrCreate` |
| Select options | Hardcoded no frontend por `campo.titulo` (igual ao legado) |
| Migration | Nenhuma — `tipo` e `valor` ja aceitam todos os tipos |
| Diff-only | Nao — mantido padrao NewSDC |

---

## Arquitetura — Atomic Design

### Atoms (novos)

**`Atoms/Input/CurrencyInput.vue`**
- Props: `modelValue: String`, `label: String`, `disabled: Boolean`
- Emits: `update:modelValue`
- Logica: strip non-digits → /100 → `formatCurrency` no keyup; `formatOnLoad` no mounted
- Renderiza: input com prefixo `R$` usando classes do design system

**`Atoms/Input/ProtocoloFideInput.vue`**
- Props: `modelValue: String`
- Emits: `update:modelValue`
- Logica: mascara `MG-F-XXXXXXX-XXXXX-XXXXXXXX`, max 20 digitos numericos
- Hint text: "Formato esperado: MG-F-XXXXXXX-XXXXX-XXXXXXXX"

### Composable (novo)

**`composables/ui/useDesastreMask.js`**
- `formatCurrency(value: number): string` — ex: `1234.56` → `"1.234,56"`
- `formatNumber(value: string): string` — ex: `"1234"` → `"1.234"`
- `formatOnLoad(municipios: Array): void` — itera todos os campos currency/number e aplica formatacao
- Sem estado reativo. Funcoes puras exportadas.

### Molecules (novas)

**`Molecules/Decretacoes/DesastreCampoField.vue`**
- Props: `campo: Object`, `itemId: Number`, `municipioId: Number`
- Emits: `update:valor`
- Switch em `campo.tipo`:
  - `radio` → `RadioInput` com `name=radio-item-${itemId}-${municipioId}`, `value=campo.id`
  - `select` → `SelectInput` com options derivadas de `selectOptionsMap[campo.titulo]`
  - `currency` → `CurrencyInput`
  - `number` → `TextInput` com keyup `formatNumber`
  - `textarea` → `<textarea>` nativo com classes Tailwind
  - default → `TextInput`
- `selectOptionsMap` (constante interna):
  ```js
  {
    'Populacao do municipio atingida': ['0 a 5%', '5 a 10%', '10 a 20%', 'Mais de 20%'],
    'Area atingida': ['Ate 40%', 'Mais de 40%'],
  }
  ```

**`Molecules/Decretacoes/DesastreTotalBadge.vue`**
- Props: `desastre: Object`
- Computed `totals`: itera `desastre.items[].campos[]`
  - `currency`: acumula valor numerico, formata como BRL
  - `number`: acumula inteiro, formata com separador de milhar
  - Exibe apenas tipos `number` e `currency`
- Renderiza: um `<Badge>` por `campo.titulo` com valor somado
- Atualiza reativamente conforme o usuario digita (watch deep em desastre)

### Organisms (novos)

**`Organisms/Decretacoes/DesastreAccordion.vue`**
- Props: `desastre: Object`, `mIndex: Number`, `cIndex: Number`, `dIndex: Number`
- Emits: `update:desastre`
- Estado: `isExpanded: ref(false)`
- Header: titulo do desastre + `<DesastreTotalBadge>` + chevron rotacionado
- Body:
  - Informacao do desastre (`desastre.informacao`) em texto muted
  - `<textarea>` para `desastre.descricao`
  - Tabela: coluna Item (titulo + observacao) | coluna Campos (`<DesastreCampoField>` por campo)

**`Organisms/Decretacoes/MunicipioDesastreSection.vue`**
- Props: `municipio: Object`, `mIndex: Number`
- Emits: `update:municipio`
- Estado: `isExpanded: ref(mIndex === 0)` — primeiro aberto por padrao
- Header: nome do municipio + protocolo resumido + chevron
- Body:
  - `<ProtocoloFideInput>` vinculado a `municipio.n_protocolo_fide`
  - Loop de categorias:
    - Header da categoria (titulo com icone — nao colapsavel)
    - Loop de `<DesastreAccordion>` por desastre

### Template (modificado)

**`Templates/Decretacoes/ProcessoDesastresEditTemplate.vue`**
- Responsabilidade: orquestra componentes, gerencia estado local, dispara submit
- Estado: `localMunicipios = ref([...props.municipios])`; `watch` deep para sincronizar
- Renderiza:
  1. Header da pagina
  2. Resumo do processo (protocolo, tipo, cobrade, status)
  3. Loop `<MunicipioDesastreSection>` com `v-model`
  4. Empty state se sem municipios
  5. Botoes: Cancelar | Salvar Alteracoes (com spinner enquanto `form.processing`)
- Submit: `form.municipios = localMunicipios.value` → `emit('submit')`

---

## Backend — Delta Minimo

### `DTO/CampoData.php` — `getFormattedValue()`

Adicionar casos ao switch:
```php
case 'radio':
    return (int) $this->valor;   // 0 ou 1

case 'select':
    return (string) $this->valor; // valor string direto sem transformacao
```

### `Requests/DesastreDataRequest.php`

Relaxar validacao do valor para aceitar strings:
```php
'municipios.*.categorias.*.desastres.*.items.*.campos.*.valor' => 'nullable',
```

---

## Modelo de Dados

### Tabelas de definicao (somente leitura)

```
dec_desastre_grupos
  id | titulo | numero

dec_desastre_categorias
  id | titulo | informacao | descricao | desastre_grupo_id

dec_desastre_items
  id | titulo | observacao | categoria_id

dec_desastre_item_campos
  id | titulo | tipo | desastre_item_id
  -- tipo: 'number' | 'currency' | 'radio' | 'select' | 'text' | 'textarea'
```

### Tabelas de entrada (gravadas ao salvar)

```
dec_decreto_municipios
  id | entrada_processos_id | municipio_id | n_protocolo_fide | updated_at

dec_entrada_categoria_desastres
  id | municipio_id | categoria_id | entrada_processo_id | descricao | deleted_at

dec_entrada_desastres
  id | municipio_id | item_id | item_campo_id | entrada_processo_id
   | entrada_categoria_desastre_id | campo_titulo | valor | deleted_at
```

### Fluxo de escrita

```
form.post() → ProcessoDesastresEdit.vue → handleSubmit()
  → DesastreDataRequest (validacao)
  → DesastreSubmissionDTO::fromArray()
  → DesastreDataService::processDesastresData()
      → por municipio: upsert dec_decreto_municipios (protocolo FIDE)
      → por desastre:  upsert dec_entrada_categoria_desastres (descricao)
      → por campo:     upsert dec_entrada_desastres (valor)
                       deduplicacao: mantém mais recente com valor != 0
                       sync updated_at do municipio
```

**Nenhuma migration necessaria.** `tipo` e `valor` ja aceitam todos os valores necessarios.

---

## Arquivos Afetados

| Arquivo | Acao |
|---------|------|
| `Atoms/Input/CurrencyInput.vue` | Criar |
| `Atoms/Input/ProtocoloFideInput.vue` | Criar |
| `composables/ui/useDesastreMask.js` | Criar |
| `Molecules/Decretacoes/DesastreCampoField.vue` | Criar |
| `Molecules/Decretacoes/DesastreTotalBadge.vue` | Criar |
| `Organisms/Decretacoes/DesastreAccordion.vue` | Criar |
| `Organisms/Decretacoes/MunicipioDesastreSection.vue` | Criar |
| `Templates/Decretacoes/ProcessoDesastresEditTemplate.vue` | Modificar |
| `app/Modules/Decretacoes/DTO/CampoData.php` | Modificar |
| `app/Modules/Decretacoes/Requests/DesastreDataRequest.php` | Modificar |

---

## Criterios de Aceitacao

- [ ] Municipios colapsaveis, primeiro aberto por padrao
- [ ] Protocolo FIDE com mascara MG-F-XXXXXXX-XXXXX-XXXXXXXX
- [ ] Categorias com header fixo (nao colapsavel)
- [ ] Desastres colapsaveis com badges de total reativos
- [ ] Textarea para descricao por desastre
- [ ] Suporte a todos os tipos: radio, select, currency, number, text, textarea
- [ ] Mascaras aplicadas no load e no keyup (currency e number)
- [ ] Select com opcoes corretas por campo.titulo
- [ ] Submit via Inertia form.post() sem regressao
- [ ] UX NewSDC preservado (dark mode, Tailwind, design system)
- [ ] Atomic Design respeitado (sem logica de negocio em atoms)
