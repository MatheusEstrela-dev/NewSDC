# Plano de Implementacao - Pagina de Criacao de Reconhecimento de Desastres

## Objetivo
Criar frontend Vue para cadastro de novo processo de Reconhecimento de Desastres no modulo Decretacoes, seguindo padroes Atomic Design e DRY.

---

## Analise da Estrutura Atual

### Componentes Existentes Reutilizaveis
- `@/Components/Atoms/Input/TextInput.vue` - input de texto
- `@/Components/Atoms/Input/SelectInput.vue` - dropdown
- `@/Components/Atoms/Input/DateInput.vue` - seletor de data
- `@/Components/Atoms/Button/Button.vue` - botoes
- `@/Components/Molecules/Form/FormField.vue` - campo com label e erro
- `@/Components/Molecules/Form/FormSelect.vue` - select com label e erro
- `@/Layouts/AuthenticatedLayout.vue` - layout autenticado

### Estrutura de Decretacoes Existente
```
Pages/Decretacoes/
  ProcessoIndex.vue (listagem)
  ProcessoShow.vue (visualizacao)
  --> ProcessoCreate.vue (A CRIAR)

Templates/Decretacoes/
  ProcessoIndexTemplate.vue
  --> ProcessoCreateTemplate.vue (A CRIAR)

Components/Organisms/Decretacoes/
  ProcessoGrid.vue
  ProcessoFilters.vue
  --> ProcessoForm.vue (A CRIAR)
```

---

## Arquivos a Criar

### 1. Page - ProcessoCreate.vue
**Caminho:** `SDC/resources/js/Pages/Decretacoes/ProcessoCreate.vue`

**Responsabilidade:**
- Receber props do backend (options de selects)
- Instanciar useForm do Inertia
- Delegar renderizacao ao Template
- Tratar submit e navegacao

### 2. Template - ProcessoCreateTemplate.vue
**Caminho:** `SDC/resources/js/Templates/Decretacoes/ProcessoCreateTemplate.vue`

**Responsabilidade:**
- Renderizar layout visual da pagina
- Integrar PageHeader, ProcessoForm
- Gerenciar estado de loading/submitting

### 3. Organism - ProcessoForm.vue
**Caminho:** `SDC/resources/js/Components/Organisms/Decretacoes/ProcessoForm.vue`

**Responsabilidade:**
- Formulario completo com todos os campos
- Organizar campos em secoes logicas
- Calcular campos derivados (dias restantes, protocolo FIDE)
- Emitir submit com dados

---

## Estrutura do Formulario

### Secao 1: Identificacao do Processo
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| Tipo de Desastre | select | Sim | FormSelect |
| Cobrade | select | Sim | FormSelect |
| Origem | select (Municipal/Estadual) | Sim | FormSelect |
| Municipio | select | Sim | FormSelect |
| Redec | select | Sim | FormSelect |
| Situacao de Anormalidade | radio (N1/SE) | Sim | RadioGroup (criar) |

### Secao 2: Datas e Prazos
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| Data de Entrada do Processo | date | Sim | FormDateField |
| Data de Ocorrencia do Desastre | date | Sim | FormDateField |
| Data Vencimento do Decreto | date | Sim | FormDateField |
| Dias Restantes da Vigencia | number | Computed | FormField (readonly) |

### Secao 3: Status e Responsavel
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| Status Do Processo | select | Sim | FormSelect |
| Analista Responsavel | select | Nao | FormSelect |
| N Protocolo FIDE | text | Auto-gerado | FormField (readonly) |

### Secao 4: Decreto Municipal
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| N Decreto Municipal | text | Nao | FormField |
| Data do Decreto Municipal | date | Nao | FormDateField |
| Data Publicacao do Decreto Municipal | date | Nao | FormDateField |
| Prazo Vigencia Decreto | number | Nao | FormField |

### Secao 5: Reconhecimento Estadual
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| N Decreto Estadual | text | Nao | FormField |
| Data do Decreto Estadual | date | Nao | FormDateField |
| N Edicao DOMG | text | Nao | FormField |
| Data Publicacao DOMG | date | Nao | FormDateField |

### Secao 6: Reconhecimento Federal
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| N Portaria Federal | text | Nao | FormField |
| Data Portaria Federal | date | Nao | FormDateField |
| N Edicao DOU | text | Nao | FormField |
| Data Publicacao DOU | date | Nao | FormDateField |

### Secao 7: Informacoes Adicionais
| Campo | Tipo | Obrigatorio | Componente |
|-------|------|-------------|------------|
| N Processo SEI | text | Sim | FormField |
| Observacoes | textarea | Nao | FormTextarea (criar) |

---

## Componentes Novos Necessarios

### 1. Molecule - FormDateField.vue
**Caminho:** `SDC/resources/js/Components/Molecules/Form/FormDateField.vue`
- Wrapper de DateInput com Label e erro
- Similar ao FormField existente

### 2. Molecule - FormTextarea.vue
**Caminho:** `SDC/resources/js/Components/Molecules/Form/FormTextarea.vue`
- Textarea com Label e erro
- Props: modelValue, label, placeholder, rows, required, error

### 3. Atom - RadioInput.vue
**Caminho:** `SDC/resources/js/Components/Atoms/Input/RadioInput.vue`
- Radio button individual

### 4. Molecule - RadioGroup.vue
**Caminho:** `SDC/resources/js/Components/Molecules/Form/RadioGroup.vue`
- Grupo de radio buttons com label
- Props: modelValue, options, name, label, required

### 5. Organism - FormSection.vue
**Caminho:** `SDC/resources/js/Components/Organisms/FormSection.vue`
- Secao de formulario com titulo
- Util para organizar campos em grupos visuais

---

## Logica de Negocio no Frontend

### 1. Calculo de Dias Restantes
```javascript
const diasRestantes = computed(() => {
  if (!form.data_vencimento_decreto) return null;
  const hoje = new Date();
  const vencimento = new Date(form.data_vencimento_decreto);
  const diff = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
  return diff;
});
```

### 2. Geracao de Protocolo FIDE
```javascript
const protocoloFide = computed(() => {
  const tipo = form.origem === 'municipal' ? 'F' : 'F';
  const codigoMunicipio = form.municipio_id ? getMunicipioCode(form.municipio_id) : 'XXXX';
  const ano = new Date().getFullYear();
  const sequencia = 'XXXX'; // Vira do backend
  return `MG-${tipo}-${codigoMunicipio}-${ano}-${sequencia}`;
});
```

### 3. Validacao de Datas
- Data de Ocorrencia <= Data de Entrada
- Data Vencimento > Data Entrada

---

## Ordem de Implementacao

1. **Atoms** (se nao existir)
   - RadioInput.vue

2. **Molecules** (novos)
   - FormDateField.vue
   - FormTextarea.vue
   - RadioGroup.vue

3. **Organisms**
   - FormSection.vue
   - ProcessoForm.vue

4. **Templates**
   - ProcessoCreateTemplate.vue

5. **Pages**
   - ProcessoCreate.vue

---

## Dependencias de Props (Backend)

O backend precisara fornecer estas opcoes via Inertia props:
```php
return Inertia::render('Decretacoes/ProcessoCreate', [
    'tiposDesastre' => TipoDesastre::all(),
    'cobrades' => Cobrade::all(),
    'municipios' => Municipio::all(),
    'redecs' => Redec::all(),
    'statusProcesso' => StatusProcesso::cases(),
    'analistas' => User::analistas()->get(),
]);
```

---

## Rotas Necessarias

```php
// routes/modules/decretacoes.php
Route::get('/decretacoes/create', [ProcessoCreateController::class, 'create'])
    ->name('decretacoes.create');

Route::post('/decretacoes', [ProcessoStoreController::class, 'store'])
    ->name('decretacoes.store');
```

---

## Estimativa de Arquivos

| Tipo | Quantidade | Arquivos |
|------|------------|----------|
| Page | 1 | ProcessoCreate.vue |
| Template | 1 | ProcessoCreateTemplate.vue |
| Organism | 2 | ProcessoForm.vue, FormSection.vue |
| Molecule | 3 | FormDateField.vue, FormTextarea.vue, RadioGroup.vue |
| Atom | 1 | RadioInput.vue |
| **Total** | **8** | |

---

## Checklist Pre-Implementacao

- [x] Verificar componentes reutilizaveis existentes
- [x] Definir estrutura de campos do formulario
- [x] Identificar componentes novos necessarios
- [x] Mapear logica de negocio frontend
- [ ] **Aguardando aprovacao do plano**

---

## Observacoes

1. **Somente Frontend** - Conforme solicitado, este plano cobre apenas a implementacao frontend. O backend sera feito posteriormente.

2. **Padroes Seguidos**:
   - Atomic Design (Atoms -> Molecules -> Organisms -> Templates -> Pages)
   - DRY (reutilizacao de componentes existentes)
   - Clean Code (separacao de responsabilidades)

3. **Mobile-First** - O formulario sera responsivo, empilhando campos em mobile.
