# Plano CORRIGIDO: Módulo Ajuda Humanitária

**Data**: 2025-12-28
**Base**: Análise da estrutura REAL do projeto SDC

---

## 🔄 Principais Mudanças vs Plano Original

### ❌ REMOVER do Plano Original

1. **Pasta Organisms/** como separada
   ```diff
   - SDC/resources/js/Components/Organisms/AjudaHumanitaria/
   -   BeneficiarioStatsCards.vue
   -   BeneficiarioFilters.vue
   -   BeneficiarioGrid.vue
   ```
   **Motivo**: Não existe no projeto. Componentes complexos vão em Templates/

2. **Total de "Organisms"**: Era 29 componentes
   ```diff
   - FASE 5: Criar Organisms frontend (29 componentes complexos)
   ```
   **Motivo**: Vai ser mesclado com Templates

### ✅ ADICIONAR ao Plano

1. **DTOs na Application Layer**
   ```diff
   + SDC/app/Modules/AjudaHumanitaria/Application/DTOs/
   +   BeneficiarioListDTO.php
   +   AbrigoListDTO.php
   +   DashboardStatisticsDTO.php
   +   EstoqueStatusDTO.php
   +   DoacaoStatisticsDTO.php
   ```
   **Motivo**: TDAP e Demandas usam DTOs

2. **Atoms específicos do módulo**
   ```diff
   + SDC/resources/js/Components/Atoms/AjudaHumanitaria/
   +   StatusBeneficiarioBadge.vue
   +   SituacaoVulnerabilidadeBadge.vue
   +   StatusAbrigoBadge.vue
   +   TipoDoacaoBadge.vue
   +   TipoAuxilioBadge.vue
   +   EstoqueNivelBadge.vue
   ```
   **Motivo**: Seguir padrão do TDAP (Atoms/Tdap/)

---

## 📂 Estrutura Frontend CORRIGIDA

### Atoms (Componentes Atômicos Específicos)

**Localização**: `SDC/resources/js/Components/Atoms/AjudaHumanitaria/`

```
Atoms/AjudaHumanitaria/
  ├── StatusBeneficiarioBadge.vue       ← Badge de status
  ├── SituacaoVulnerabilidadeBadge.vue  ← Badge de situação
  ├── StatusAbrigoBadge.vue             ← Badge de status abrigo
  ├── TipoDoacaoBadge.vue               ← Badge tipo doação
  ├── StatusDoacaoBadge.vue             ← Badge status doação
  ├── TipoAuxilioBadge.vue              ← Badge tipo auxílio
  ├── StatusAuxilioBadge.vue            ← Badge status auxílio
  ├── EstoqueNivelBadge.vue             ← Badge nível estoque
  └── CategoriaProdutoBadge.vue         ← Badge categoria
```

**Total Atoms**: 9 componentes

---

### Molecules (Componentes Médios)

**Localização**: `SDC/resources/js/Components/Molecules/AjudaHumanitaria/`

```
Molecules/AjudaHumanitaria/
  ├── BeneficiarioCard.vue              ← Card de beneficiário
  ├── AbrigoCard.vue                    ← Card de abrigo
  ├── DoacaoCard.vue                    ← Card de doação
  ├── AuxilioCard.vue                   ← Card de auxílio
  ├── EstoqueCard.vue                   ← Card de estoque
  ├── OcupacaoProgressBar.vue           ← Barra de ocupação do abrigo
  ├── MembroFamiliaCard.vue             ← Card de membro família
  ├── FilterSection.vue                 ← Seção de filtros (pode reusar global)
  └── StatCard.vue                      ← Card de estatística (pode reusar global)
```

**Total Molecules**: ~9 componentes (alguns podem reusar globais)

---

### Templates (Componentes Complexos)

**Localização**: `SDC/resources/js/Templates/AjudaHumanitaria/`

**IMPORTANTE**: Templates fazem o papel de "Organisms" no projeto.

```
Templates/AjudaHumanitaria/
  ├── DashboardTemplate.vue
  │   ├── Contém: DashboardStatsCards
  │   ├── Contém: EstoqueAlerts
  │   └── Contém: Gráficos/Charts
  │
  ├── BeneficiarioIndexTemplate.vue
  │   ├── Contém: BeneficiarioStatsCards
  │   ├── Contém: BeneficiarioFilters
  │   ├── Contém: BeneficiarioGrid (lista de BeneficiarioCard)
  │   └── Contém: Paginação
  │
  ├── AbrigoIndexTemplate.vue
  │   ├── Contém: AbrigoStatsCards
  │   ├── Contém: AbrigoFilters
  │   ├── Contém: AbrigoGrid (lista de AbrigoCard)
  │   └── Contém: Paginação
  │
  ├── DoacaoIndexTemplate.vue
  │   ├── Contém: DoacaoStatsCards
  │   ├── Contém: DoacaoFilters
  │   └── Contém: DoacaoList
  │
  ├── AuxilioIndexTemplate.vue
  │   ├── Contém: AuxilioStatsCards
  │   ├── Contém: AuxilioFilters
  │   └── Contém: AuxilioList
  │
  └── EstoqueIndexTemplate.vue
      ├── Contém: EstoqueAlerts (crítico/baixo)
      ├── Contém: EstoqueFilters
      └── Contém: EstoqueGrid
```

**Total Templates**: 6 componentes (cada um com múltiplos sub-componentes internos)

---

### Pages (Páginas Inertia)

**Localização**: `SDC/resources/js/Pages/AjudaHumanitaria/`

```
Pages/AjudaHumanitaria/
  ├── Dashboard.vue                     ← Página do dashboard
  ├── Beneficiarios/
  │   ├── BeneficiarioIndex.vue        ← Lista de beneficiários
  │   ├── BeneficiarioShow.vue         ← Detalhes do beneficiário
  │   └── BeneficiarioForm.vue         ← Criar/Editar beneficiário
  ├── Abrigos/
  │   ├── AbrigoIndex.vue
  │   ├── AbrigoShow.vue
  │   └── AbrigoForm.vue
  ├── Doacoes/
  │   ├── DoacaoIndex.vue
  │   ├── DoacaoShow.vue
  │   └── DoacaoForm.vue
  ├── Auxilios/
  │   ├── AuxilioIndex.vue
  │   ├── AuxilioShow.vue
  │   └── AuxilioForm.vue
  ├── Estoque/
  │   ├── EstoqueIndex.vue
  │   └── MovimentacoesIndex.vue
  └── Relatorios/
      ├── RelatorioFinanceiro.vue
      ├── RelatorioAuxilios.vue
      └── RelatorioDoacoes.vue
```

**Total Pages**: 19 componentes

---

## 📋 Ordem de Implementação ATUALIZADA

### FASE 1: Backend Base (PRIORIDADE MÁXIMA)

**Passo 1-3**: ✅ JÁ FEITO
- Estrutura de diretórios
- 16 Value Objects (Enums)
- 11 Migrations

**Passo 4**: ⏭️ PULAR (executar depois quando ambiente estiver pronto)

**Passo 5**: 🔴 **FAZER AGORA - Entities/Models** (10 arquivos)

**ORDEM SUGERIDA** (do mais importante ao menos):
```
1. Beneficiario.php           ← PRIORIDADE 1 (bloqueante)
2. Abrigo.php                 ← PRIORIDADE 1
3. MembroFamilia.php          ← PRIORIDADE 2
4. Doacao.php                 ← PRIORIDADE 2
5. ItemDoacao.php             ← PRIORIDADE 3
6. Auxilio.php                ← PRIORIDADE 2
7. ItemAuxilio.php            ← PRIORIDADE 3
8. Estoque.php                ← PRIORIDADE 2
9. MovimentacaoEstoque.php    ← PRIORIDADE 3
10. MovimentacaoFinanceira.php ← PRIORIDADE 3
```

**Passo 6**: 🔴 **Repository Interfaces** (7 arquivos)
```
1. BeneficiarioRepositoryInterface.php   ← PRIORIDADE 1
2. AbrigoRepositoryInterface.php         ← PRIORIDADE 1
3. DoacaoRepositoryInterface.php         ← PRIORIDADE 2
4. AuxilioRepositoryInterface.php        ← PRIORIDADE 2
5. EstoqueRepositoryInterface.php        ← PRIORIDADE 2
6. MovimentacaoEstoqueRepositoryInterface.php
7. MovimentacaoFinanceiraRepositoryInterface.php
```

**Passo 7**: 🔴 **Repository Implementations** (7 arquivos)
```
1. EloquentBeneficiarioRepository.php   ← PRIORIDADE 1
2. EloquentAbrigoRepository.php         ← PRIORIDADE 1
3. EloquentDoacaoRepository.php
4. EloquentAuxilioRepository.php
5. EloquentEstoqueRepository.php
6. EloquentMovimentacaoEstoqueRepository.php
7. EloquentMovimentacaoFinanceiraRepository.php
```

**Passo 8**: 🔴 **NOVO - Criar DTOs** (5 arquivos)
```
Application/DTOs/
  1. BeneficiarioListDTO.php
  2. AbrigoListDTO.php
  3. DashboardStatisticsDTO.php
  4. EstoqueStatusDTO.php
  5. DoacaoStatisticsDTO.php
```

**Passo 9**: 🔴 **Service Provider**
```
AjudaHumanitariaServiceProvider.php
```

**Passo 10**: 🔴 **Registrar em config/app.php**

**✅ RESULTADO FASE 1**: Módulo funcional com backend completo

---

### FASE 2: Use Cases (37 use cases)

**MANTÉM IGUAL AO PLANO ORIGINAL**

Lista completa no plano aprovado.

---

### FASE 3: Controllers e Validação

**MANTÉM IGUAL AO PLANO ORIGINAL**

30 controllers + 7 form requests + rotas

---

### FASE 4: Atoms (CORRIGIDO)

**Passo 25**: 🟡 **Criar Atoms específicos em Atoms/AjudaHumanitaria/** (9 arquivos)

```
Components/Atoms/AjudaHumanitaria/
  1. StatusBeneficiarioBadge.vue
  2. SituacaoVulnerabilidadeBadge.vue
  3. StatusAbrigoBadge.vue
  4. TipoDoacaoBadge.vue
  5. StatusDoacaoBadge.vue
  6. TipoAuxilioBadge.vue
  7. StatusAuxilioBadge.vue
  8. EstoqueNivelBadge.vue
  9. CategoriaProdutoBadge.vue
```

---

### FASE 5: Molecules (CORRIGIDO)

**Passo 26**: 🟡 **Criar Molecules em Molecules/AjudaHumanitaria/** (~9 arquivos)

```
Components/Molecules/AjudaHumanitaria/
  1. BeneficiarioCard.vue
  2. AbrigoCard.vue
  3. DoacaoCard.vue
  4. AuxilioCard.vue
  5. EstoqueCard.vue
  6. OcupacaoProgressBar.vue
  7. MembroFamiliaCard.vue
  8. (Reusar FilterSection global)
  9. (Reusar StatCard global)
```

---

### FASE 6: Templates (SUBSTITUI "Organisms")

**Passo 27-32**: 🟡 **Criar Templates em Templates/AjudaHumanitaria/** (6 arquivos)

```
Templates/AjudaHumanitaria/
  1. DashboardTemplate.vue              ← Stats + Alerts + Charts
  2. BeneficiarioIndexTemplate.vue      ← Stats + Filters + Grid
  3. AbrigoIndexTemplate.vue            ← Stats + Filters + Grid
  4. DoacaoIndexTemplate.vue            ← Stats + Filters + List
  5. AuxilioIndexTemplate.vue           ← Stats + Filters + List
  6. EstoqueIndexTemplate.vue           ← Alerts + Filters + Grid
```

**CADA TEMPLATE CONTÉM**:
- Lógica de estado (refs, computed)
- Chamadas de API (Inertia)
- Composição de Molecules/Atoms
- Filtros, paginação, etc.

---

### FASE 7: Pages (IGUAL AO ORIGINAL)

**Passo 33-40**: 🟢 **Criar Pages** (19 arquivos)

```
Pages/AjudaHumanitaria/
  Dashboard.vue
  Beneficiarios/ (3 pages)
  Abrigos/ (3 pages)
  Doacoes/ (3 pages)
  Auxilios/ (3 pages)
  Estoque/ (2 pages)
  Relatorios/ (3 pages)
```

**CADA PAGE**:
- Recebe props do Inertia
- Passa para Template correspondente
- Mínima lógica (só routing)

---

### FASE 8-9: Integração e Avançado

**MANTÉM IGUAL AO PLANO ORIGINAL**

---

## 📊 Comparação de Totais

| Componente | Plano Original | Plano CORRIGIDO |
|-----------|----------------|-----------------|
| **Backend** |  |  |
| Entities | 10 | 10 ✅ |
| Repositories | 7 interfaces + 7 impl | 7 interfaces + 7 impl ✅ |
| **DTOs** | ❌ 0 | ✅ **5** 🆕 |
| Use Cases | 37 | 37 ✅ |
| Controllers | 30 | 30 ✅ |
| Service Provider | 1 | 1 ✅ |
| **Frontend** |  |  |
| **Atoms** | ❌ Não planejado | ✅ **9** 🆕 |
| Molecules | 17 | ~9 ⬇️ (alguns globais) |
| **Organisms** | ❌ 29 (ERRADO) | ❌ **0** (não existe!) |
| **Templates** | 6 | ✅ **6** (papel de Organisms) |
| Pages | 19 | 19 ✅ |
| **TOTAL FRONTEND** | 71 | ~43 ⬇️ (mais realista) |

**Redução**: De ~71 para ~43 componentes (mais próximo da realidade)

---

## ✅ Checklist de Validação

Antes de continuar implementação, garantir:

### Estrutura
- [ ] Atoms específicos em `Components/Atoms/AjudaHumanitaria/`
- [ ] Molecules específicos em `Components/Molecules/AjudaHumanitaria/`
- [ ] Templates (não Organisms!) em `Templates/AjudaHumanitaria/`
- [ ] Pages em `Pages/AjudaHumanitaria/`
- [ ] DTOs em `Application/DTOs/`

### Código
- [ ] Entities com relacionamentos completos
- [ ] Repositories com bind no Service Provider
- [ ] DTOs para endpoints principais
- [ ] Templates com lógica de composição

### Integração
- [ ] Service Provider registrado em config/app.php
- [ ] Rotas registradas em routes/web.php
- [ ] Sidebar atualizada
- [ ] Justfile com comandos `ajuda-migrate`, `ajuda-tables`, `ajuda-rollback`

---

## 📝 Notas Finais

**O que mudou**:
1. ✅ **Adicionados DTOs** (consistência com TDAP/Demandas)
2. ✅ **Adicionados Atoms específicos** (seguir padrão TDAP)
3. ❌ **Removido Organisms/** (não existe no projeto)
4. ✅ **Templates fazem papel de Organisms** (padrão do projeto)
5. ⬇️ **Redução de ~71 para ~43 componentes** frontend (mais realista)

**Próximos passos**:
1. Implementar **PRIORIDADE 1** do backend (Entities + Repositories + Provider)
2. Testar com `just ajuda-migrate`
3. Implementar frontend seguindo estrutura CORRIGIDA

---

**Aprovado**: Senior Developer
**Data**: 2025-12-28
