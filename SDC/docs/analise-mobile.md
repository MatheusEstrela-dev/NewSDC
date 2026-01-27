# Análise Mobile - Sistema NewSDC

**Data:** 2025-01-25
**Objetivo:** Identificar e corrigir problemas de responsividade mobile em todos os módulos do sistema

---

## Arquitetura Mobile Estabelecida

### Componentes Base
- **useMobile.js**: Composable para detectar breakpoints (mobile < 768px)
- **ResponsiveTable.vue**: Organism que exibe tabela em desktop e cards em mobile
- **TableMobileCard.vue**: Molecule que renderiza dados em formato de card para mobile
- **HamburgerButton.vue**: Atom para botão de menu mobile
- **Cards específicos**: PaeProtocoloCard, ProcessoCard, BeneficiarioCard, TreinamentoCard

### Breakpoint Padrão
```javascript
MOBILE_BREAKPOINT = 768px  // < 768px = mobile
TABLET_BREAKPOINT = 1024px // 768px - 1024px = tablet
```

---

## Status dos Módulos

### ✅ Dashboard (Gerais) - PARCIALMENTE CORRETO
**Localização:** `Pages/Dashboard.vue`

**Status:**
- Cards de estatísticas: ✅ Responsivo (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`)
- Tabela PMDA em Análise: ❌ Tabela HTML pura sem responsividade mobile
- Timeline: ✅ Funciona bem em mobile

**Problemas:**
- Tabela "PMDA em Análise" (linhas 44-74) não é responsiva, vai ter scroll horizontal no mobile

**Solução:**
- Substituir tabela por ResponsiveTable ou criar cards mobile específicos

---

### ❌ Protocolos PAE - NECESSITA CORREÇÃO
**Localização:** `Pages/PaeProtocolosIndex.vue` → `Templates/Pae/PaeProtocolosIndexTemplate.vue`

**Status:**
- Header: ✅ Funciona
- Toggle Grade/Tabela: ⚠️  Visível no mobile (pode ser ocultado em mobile, mantendo apenas grade)
- Stats Cards: ✅ Responsivo
- Filtros: ✅ Responsivo
- **Grade**: ✅ Funciona (`grid-cols-1 lg:grid-cols-3`)
- **Tabela**: ❌ Não usa ResponsiveTable

**Problemas:**
1. `PaeProtocolosTable.vue` é uma tabela HTML pura
2. No mobile, mostra scroll horizontal
3. Toggle Grade/Tabela ocupa espaço no mobile desnecessariamente

**Solução:**
- Implementar ResponsiveTable em PaeProtocolosTable.vue
- Ocultar toggle em mobile (< 768px), forçar visualização em grade
- Criar mobileFields para os cards

---

### ❌ Decretações (Processos) - NECESSITA CORREÇÃO
**Localização:** `Pages/Decretacoes/ProcessoIndex.vue` → `Templates/Decretacoes/ProcessoIndexTemplate.vue`

**Status:**
- Header: ✅ Funciona
- Toggle Grade/Tabela: ⚠️  Visível no mobile
- Stats Cards: ✅ Responsivo
- Filtros: ✅ Responsivo
- **Grade**: ✅ Funciona (ProcessoGrid com cards)
- **Tabela**: ❌ Usa `overflow-x-auto` e `min-w-[640px]`

**Problemas:**
1. `ProcessoTable.vue` (linha 19): `min-w-[640px]` força scroll horizontal
2. Usa `hidden sm:table-cell` e `hidden md:table-cell` para ocultar colunas, mas ainda tem scroll
3. Toggle visível no mobile

**Solução:**
- Implementar ResponsiveTable em ProcessoTable.vue
- Ocultar toggle em mobile, forçar grade
- Usar TableMobileCard com campos apropriados

---

### ❌ RAT - NECESSITA CORREÇÃO
**Localização:** `Pages/RatIndex.vue` → `Templates/Rat/RatIndexTemplate.vue`

**Status:**
- Header: ✅ Funciona
- Stats Cards: ✅ Responsivo
- Filtros: ✅ Responsivo
- **Tabela**: ❌ Apenas visualização tabela, usa `overflow-x-auto` e `table-fixed`

**Problemas:**
1. `RatTable.vue` (linha 26): tabela com `table-fixed` e `overflow-x-auto`
2. Não tem visualização em grade
3. Não usa ResponsiveTable

**Solução:**
- Implementar ResponsiveTable em RatTable.vue
- Criar RatCard.vue para visualização mobile
- Definir mobileFields apropriados

---

### ⚠️  Treinamento - A VERIFICAR
**Localização:** `Pages/Treinamento/TreinamentoIndex.vue` → `Templates/Treinamento/TreinamentoIndexTemplate.vue`

**Status:**
- Tem toggle Grade/Tabela (visível no código)
- Já tem TreinamentoCard.vue criado
- Precisa verificar se a tabela usa ResponsiveTable

---

### ⚠️  Ajuda Humanitária (Beneficiários) - A VERIFICAR
**Localização:** `Pages/AjudaHumanitaria/Beneficiarios/BeneficiarioIndex.vue`

**Status:**
- Já tem BeneficiarioCard.vue criado
- Já tem BeneficiarioGrid.vue
- Precisa verificar se tem tabela e se usa ResponsiveTable

---

### ⚠️  COMPDEC (Órgãos) - A VERIFICAR
**Localização:** `Pages/Compdec/OrgaosIndex.vue`

**Status:**
- Precisa verificar estrutura de visualização

---

### ⚠️  Demandas - A VERIFICAR
**Localização:** `Pages/Demandas/DemandasIndex.vue` → `Templates/Demandas/DemandasIndexTemplate.vue`

**Status:**
- Precisa verificar estrutura de visualização
- Já tem DemandasList.vue (lista ou tabela?)

---

### ⚠️  TDAP - A VERIFICAR
**Localização:**
- `Pages/Tdap/Dashboard.vue`
- `Pages/Tdap/ProductsIndex.vue`
- `Pages/Tdap/MovimentacoesIndex.vue`
- `Pages/Tdap/RecebimentosIndex.vue`

**Status:**
- Precisa verificar todas as páginas do módulo TDAP

---

### ⚠️  Admin/Permissions - A VERIFICAR
**Localização:**
- `Pages/Admin/Permissions/Users/Index.vue`
- `Pages/Admin/Permissions/Roles/Index.vue`
- `Pages/Admin/Permissions/Permissions/Index.vue`

**Status:**
- Precisa verificar tabelas de usuários, cargos e permissões

---

## Padrão de Correção

### Para módulos com toggle Grade/Tabela:

1. **Ocultar toggle em mobile** (< 768px)
   ```vue
   <div class="hidden md:flex items-center gap-1 ...">
     <!-- Toggle Grade/Tabela -->
   </div>
   ```

2. **Forçar visualização em grade no mobile**
   ```vue
   <PaeProtocolosGrid
     v-if="viewMode === 'grid' || isMobile"
     ...
   />

   <PaeProtocolosTable
     v-else
     ...
   />
   ```

3. **Implementar ResponsiveTable na tabela**
   ```vue
   <ResponsiveTable
     :items="items"
     :mobile-fields="mobileFields"
     :get-item-title="(item) => item.title"
     :get-item-subtitle="(item) => item.subtitle"
   >
     <template #table>
       <!-- Tabela tradicional para desktop -->
     </template>

     <template #mobile-actions="{ item }">
       <!-- Ações para cards mobile -->
     </template>
   </ResponsiveTable>
   ```

### Para módulos apenas com tabela:

1. **Implementar ResponsiveTable**
2. **Criar mobileFields apropriados**
3. **Definir funções getItemTitle e getItemSubtitle**

---

## Checklist de Implementação

### Prioridade Alta (Confirmados com problemas)
- [ ] Dashboard - Tabela PMDA em Análise
- [ ] Protocolos PAE - Tabela
- [ ] Decretações - Tabela
- [ ] RAT - Tabela

### Prioridade Média (A verificar)
- [ ] Treinamento - Verificar tabela
- [ ] Ajuda Humanitária - Verificar estrutura
- [ ] COMPDEC - Verificar estrutura
- [ ] Demandas - Verificar estrutura
- [ ] TDAP Dashboard - Verificar estrutura
- [ ] TDAP Products - Verificar estrutura
- [ ] TDAP Movimentações - Verificar estrutura
- [ ] TDAP Recebimentos - Verificar estrutura

### Prioridade Baixa (Admin)
- [ ] Admin Users - Verificar tabela
- [ ] Admin Roles - Verificar tabela
- [ ] Admin Permissions - Verificar tabela

---

## Princípios de Design Mobile

1. **Mobile First**: Priorizar visualização mobile
2. **Sem scroll horizontal**: Nunca usar `overflow-x-auto` como solução
3. **Cards > Tabelas**: Em mobile, sempre preferir cards
4. **Atomic Design**: Seguir a hierarquia Atoms → Molecules → Organisms → Templates
5. **DRY**: Reutilizar ResponsiveTable e TableMobileCard
6. **Progressive Disclosure**: Mostrar informações essenciais, esconder detalhes em ações
7. **Touch-friendly**: Botões e áreas clicáveis com tamanho mínimo de 44x44px
