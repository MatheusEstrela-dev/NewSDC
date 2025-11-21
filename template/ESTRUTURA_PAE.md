# Estrutura Modular do PAE

## 📁 Organização de Arquivos

O módulo PAE (Plano de Ação de Emergência) foi completamente modularizado seguindo os princípios SOLID e boas práticas:

```
resources/
├── js/
│   ├── Pages/
│   │   └── Pae.vue                    # Componente principal (orquestrador)
│   ├── Components/
│   │   ├── Pae/
│   │   │   ├── PaeHeader.vue          # Cabeçalho com título e nível de emergência
│   │   │   ├── PaeBreadcrumb.vue      # Navegação breadcrumb
│   │   │   ├── PaeTabs.vue            # Sistema de abas
│   │   │   ├── PaeForm.vue            # Formulário PAE (Aba 1)
│   │   │   ├── PaeHistory.vue         # Histórico de eventos (Aba 2)
│   │   │   ├── PaeCommittee.vue       # CCPAE - Comitê (Aba 3)
│   │   │   ├── PaeEntrepreneur.vue    # Dados do Empreendedor (Aba 4)
│   │   │   ├── PaeCard.vue            # Card genérico reutilizável
│   │   │   ├── PaeDocumentsCard.vue   # Card de documentos
│   │   │   ├── PaeActionsCard.vue    # Card de ações
│   │   │   ├── FormField.vue          # Campo de formulário
│   │   │   └── FormSelect.vue          # Select de formulário
│   │   └── Icons/
│   │       ├── PlusIcon.vue
│   │       ├── UsersIcon.vue
│   │       ├── ExclamationTriangleIcon.vue
│   │       ├── CheckCircleIcon.vue
│   │       ├── BuildingOfficeIcon.vue
│   │       ├── UploadIcon.vue
│   │       ├── DownloadIcon.vue
│   │       ├── SaveIcon.vue
│   │       ├── ChevronRightIcon.vue
│   │       ├── ArrowLeftIcon.vue
│   │       ├── MapIcon.vue
│   │       └── DocumentIcon.vue
│   ├── composables/
│   │   ├── usePae.js                  # Composable principal
│   │   ├── useTabs.js                 # Gerenciamento de abas
│   │   └── useDocuments.js            # Gerenciamento de documentos
│   └── utils/
│       ├── eventColors.js             # Cores de eventos
│       ├── roleColors.js              # Cores de funções/roles
│       ├── fileTypes.js               # Tipos de arquivos
│       └── dateFormatter.js           # Formatação de datas
└── css/
    └── pages/
        └── pae/
            └── pae.css                # Estilos específicos do PAE
```

## 🎯 Princípios SOLID Aplicados

### Single Responsibility Principle (SRP)
- **PaeForm.vue**: Apenas formulário PAE
- **PaeHistory.vue**: Apenas histórico
- **PaeCommittee.vue**: Apenas comitê
- **useTabs.js**: Apenas gerenciamento de abas
- **useDocuments.js**: Apenas gerenciamento de documentos

### Open/Closed Principle (OCP)
- Componentes extensíveis via props
- Composables podem ser estendidos sem modificar código existente
- Utils são funções puras, fáceis de testar e estender

### Liskov Substitution Principle (LSP)
- Componentes seguem contratos consistentes via props
- Composables retornam interfaces consistentes

### Interface Segregation Principle (ISP)
- Props específicas para cada componente
- Composables focados em responsabilidades específicas

### Dependency Inversion Principle (DIP)
- Componentes dependem de abstrações (props, emits)
- Composables são injetados, não instanciados diretamente

## 📦 Componentes Principais

### 1. `Pae.vue` - Orquestrador Principal
- **Responsabilidade**: Coordenar componentes e composables
- **Props do Inertia**:
  - `empreendimento`: Dados do empreendimento
  - `historyEvents`: Histórico de eventos
  - `committeeMembers`: Membros do comitê
  - `empreendedor`: Dados do empreendedor
  - `documents`: Documentos anexados
  - `atas`: Atas de reuniões
  - `lastUpdate`: Última atualização

### 2. `PaeForm.vue` - Formulário PAE
- **Responsabilidade**: Exibir e editar dados do PAE
- **Props**:
  - `empreendimento`: Dados do empreendimento
  - `documents`: Lista de documentos
- **Emits**: `save`, `save-draft`, `archive`, `upload`, `remove`

### 3. `PaeHistory.vue` - Histórico de Eventos
- **Responsabilidade**: Exibir timeline de eventos
- **Props**:
  - `events`: Array de eventos
- **Emits**: `filter-change`, `view-event`

### 4. `PaeCommittee.vue` - CCPAE
- **Responsabilidade**: Gerenciar membros e atas do comitê
- **Props**:
  - `members`: Membros do comitê
  - `atas`: Atas de reuniões
- **Emits**: `add-member`, `add-meeting`, `view-ata`

### 5. `PaeEntrepreneur.vue` - Dados do Empreendedor
- **Responsabilidade**: Exibir e editar dados do empreendedor
- **Props**:
  - `empreendedor`: Dados do empreendedor
- **Emits**: `save`

## 🔧 Composables

### 1. `usePae.js` - Composable Principal
- **Responsabilidade**: Orquestrar lógica do PAE
- **Retorna**:
  - State: `empreendimento`, `historyEvents`, `committeeMembers`, `empreendedor`
  - Composables: `tabs`, `documents`, `modal`
  - Methods: `savePae`, `saveDraft`, `archiveEmpreendimento`, etc.

### 2. `useTabs.js` - Gerenciamento de Abas
- **Responsabilidade**: Gerenciar sistema de abas
- **Retorna**:
  - State: `activeTab`
  - Methods: `setActiveTab`, `isActive`, `getTabClass`

### 3. `useDocuments.js` - Gerenciamento de Documentos
- **Responsabilidade**: Gerenciar upload e lista de documentos
- **Retorna**:
  - State: `documents`, `uploading`, `uploadProgress`, `uploadError`
  - Methods: `addDocument`, `removeDocument`, `uploadDocuments`

## 🛠️ Utils

### 1. `eventColors.js`
- `getEventColorClass(type)`: Retorna classes CSS para tipo de evento
- `getEventIcon(type)`: Retorna nome do componente de ícone

### 2. `roleColors.js`
- `getRoleClass(role)`: Retorna classes CSS para função/role
- `getRoleBadgeColor(role)`: Retorna cor do badge

### 3. `fileTypes.js`
- `getFileTypeInfo(filename)`: Retorna informações do tipo de arquivo
- `isAllowedFileType(filename)`: Valida tipo de arquivo
- `formatFileSize(bytes)`: Formata tamanho de arquivo

### 4. `dateFormatter.js`
- `formatRelativeDate(date)`: Formata data relativa
- `formatDate(date)`: Formata data (DD/MM/YYYY)
- `formatDateTime(date)`: Formata data e hora

## 🎨 Estilos

### `pae.css`
- Variáveis CSS para temas
- Estilos de formulários (`.form-input`, `.form-select`)
- Scrollbar personalizada
- Animações (fade-in, fade-in-up)
- Classes utilitárias específicas do PAE

## 📊 Fluxo de Dados

```
Pae.vue (orquestrador)
    ↓
usePae() (composable)
    ↓
├── useTabs() → Gerencia abas
├── useDocuments() → Gerencia documentos
├── useModal() → Gerencia modais
├── empreendimento → Dados do empreendimento
├── historyEvents → Histórico
├── committeeMembers → Membros do comitê
└── empreendedor → Dados do empreendedor
    ↓
Components (PaeForm, PaeHistory, PaeCommittee, PaeEntrepreneur)
    ↓
Utils (eventColors, roleColors, fileTypes, dateFormatter)
```

## ✅ Benefícios da Modularização

### Manutenibilidade
- Código organizado e fácil de encontrar
- Mudanças isoladas em componentes específicos
- Fácil adicionar novas funcionalidades

### Testabilidade
- Componentes testáveis isoladamente
- Composables testáveis sem dependências de UI
- Utils são funções puras, fáceis de testar

### Reutilização
- Componentes reutilizáveis em outras páginas
- Composables reutilizáveis em diferentes contextos
- Utils reutilizáveis em todo o projeto

### Performance
- Lazy loading de componentes
- Code splitting automático
- CSS otimizado e modular

### Escalabilidade
- Fácil adicionar novos componentes
- Fácil adicionar novos composables
- Estrutura preparada para crescimento

## 🔄 Integração com Dashboard

O PAE será acessível através da sidebar do Dashboard como um módulo:

```vue
<!-- No Dashboard ou Layout -->
<NavLink :href="route('pae.index')">Gestão de PAE</NavLink>
```

## 📝 Rotas Necessárias

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pae', [PaeController::class, 'index'])->name('pae.index');
    Route::get('/pae/{id}', [PaeController::class, 'show'])->name('pae.show');
    Route::put('/pae/{id}', [PaeController::class, 'update'])->name('pae.update');
    Route::post('/pae/{id}/documents', [PaeController::class, 'uploadDocuments'])->name('pae.documents.upload');
    // ... outras rotas
});
```

## 🚀 Próximos Passos

### Backend:
- [ ] Criar PaeController
- [ ] Criar modelos (Empreendimento, Pae, Historico, etc.)
- [ ] Implementar migrations
- [ ] Implementar validações

### Frontend:
- [ ] Adicionar loading states
- [ ] Implementar error handling
- [ ] Adicionar validações de formulário
- [ ] Implementar preview de documentos
- [ ] Adicionar filtros avançados no histórico

### Testes:
- [ ] Testes unitários para composables
- [ ] Testes de componentes
- [ ] Testes de integração

---

**Última atualização**: 2025-11-20

