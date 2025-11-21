# Estrutura Modular do Dashboard

## 📁 Organização de Arquivos

O Dashboard foi completamente modularizado seguindo os princípios SOLID e boas práticas de desenvolvimento:

```
resources/
├── js/
│   ├── Pages/
│   │   └── Dashboard.vue              # Componente principal (orquestrador)
│   ├── Components/
│   │   ├── Dashboard/
│   │   │   ├── MetricsCard.vue        # Card de métricas
│   │   │   ├── PmdaTable.vue          # Tabela de PMDA
│   │   │   ├── Timeline.vue           # Timeline de histórico
│   │   │   └── DashboardModal.vue     # Modal específico do dashboard
│   │   └── Icons/
│   │       ├── ArrowRightIcon.vue
│   │       ├── DocumentTextIcon.vue
│   │       ├── EyeIcon.vue
│   │       ├── FunnelIcon.vue
│   │       ├── PencilIcon.vue
│   │       ├── ClockIcon.vue
│   │       ├── CheckIcon.vue
│   │       ├── CheckBadgeIcon.vue
│   │       └── XMarkIcon.vue
│   ├── composables/
│   │   ├── useDashboard.js            # Composable principal
│   │   ├── useModal.js                # Gerenciamento de modais
│   │   └── useNavigation.js           # Gerenciamento de navegação
│   └── utils/
│       ├── statusColors.js            # Utilitários de cores de status
│       └── dateFormatter.js           # Formatação de datas
└── css/
    └── pages/
        └── dashboard/
            └── dashboard.css          # Estilos específicos do dashboard
```

## 🎯 Princípios SOLID Aplicados

### Single Responsibility Principle (SRP)
Cada arquivo tem uma única responsabilidade:
- **Components**: Apenas apresentação visual
- **Composables**: Apenas lógica de negócio
- **Utils**: Apenas funções utilitárias puras
- **CSS**: Apenas estilização

### Open/Closed Principle (OCP)
- Componentes são extensíveis via props
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

## 📦 Componentes

### 1. `Dashboard.vue` - Orquestrador Principal
- **Responsabilidade**: Coordenar componentes e composables
- **Dependências**: Layout, Components, Composables
- **Props**: Nenhuma (recebe dados via composable)

### 2. `MetricsCard.vue` - Card de Métricas
- **Responsabilidade**: Exibir uma métrica individual
- **Props**:
  - `metric` (Object): Dados da métrica
  - `showTrend` (Boolean): Mostrar tendência
  - `trend` (String): Texto da tendência
  - `showAction` (Boolean): Mostrar botão de ação
- **Emits**: `view-details`

### 3. `PmdaTable.vue` - Tabela de PMDA
- **Responsabilidade**: Exibir tabela de processos PMDA
- **Props**:
  - `title` (String): Título da tabela
  - `subtitle` (String): Subtítulo
  - `items` (Array): Dados da tabela
  - `showFilters` (Boolean): Mostrar botão de filtros
  - `showActions` (Boolean): Mostrar coluna de ações
  - `showFooter` (Boolean): Mostrar rodapé
- **Emits**: `filter`, `view-item`, `view-all`

### 4. `Timeline.vue` - Timeline de Histórico
- **Responsabilidade**: Exibir timeline de movimentações
- **Props**:
  - `title` (String): Título da timeline
  - `items` (Array): Itens do histórico
- **Emits**: `view-item`

### 5. `DashboardModal.vue` - Modal do Dashboard
- **Responsabilidade**: Exibir modal com detalhes
- **Props**:
  - `isOpen` (Boolean): Estado do modal
  - `title` (String): Título do modal
  - `data` (Object/Array/String): Dados a exibir
- **Emits**: `close`, `view-process`

## 🔧 Composables

### 1. `useDashboard.js` - Composable Principal
- **Responsabilidade**: Orquestrar lógica do dashboard
- **Retorna**:
  - State: `metrics`, `pmdaEmAnalise`, `historico`, `currentYear`
  - Composables: `modal`, `navigation`
  - Methods: `openDetails`, `fetchDashboardData`, `refreshMetrics`

### 2. `useModal.js` - Gerenciamento de Modal
- **Responsabilidade**: Gerenciar estado de modais
- **Retorna**:
  - State: `isOpen`, `title`, `data`
  - Methods: `open`, `close`, `toggle`

### 3. `useNavigation.js` - Gerenciamento de Navegação
- **Responsabilidade**: Gerenciar navegação e menu mobile
- **Retorna**:
  - State: `activeMenu`, `isMobileMenuOpen`, `windowWidth`, `openSubMenus`, `isMobile`
  - Methods: `setActive`, `toggleSubMenu`, `openMobileMenu`, `closeMobileMenu`, `toggleMobileMenu`

## 🛠️ Utils

### 1. `statusColors.js`
- **Funções**:
  - `getStatusColor(status)`: Retorna classes CSS para status
  - `getStatusBadgeColor(status)`: Retorna cor do badge

### 2. `dateFormatter.js`
- **Funções**:
  - `formatRelativeDate(date)`: Formata data relativa ("Há 2 horas")
  - `formatDate(date)`: Formata data (DD/MM/YYYY)
  - `formatDateTime(date)`: Formata data e hora

## 🎨 Estilos

### `dashboard.css`
- Variáveis CSS para temas
- Transições Vue (slide-fade, fade, slide-in)
- Scrollbar personalizada
- Animações e efeitos
- Classes utilitárias

## 📊 Fluxo de Dados

```
Dashboard.vue
    ↓
useDashboard() (composable)
    ↓
├── useModal() → Gerencia estado do modal
├── useNavigation() → Gerencia navegação
├── metrics → Dados das métricas
├── pmdaEmAnalise → Dados da tabela
└── historico → Dados da timeline
    ↓
Components (MetricsCard, PmdaTable, Timeline)
    ↓
Utils (statusColors, dateFormatter)
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

## 🔄 Próximos Passos

### Melhorias Sugeridas
- [ ] Adicionar testes unitários para composables
- [ ] Adicionar testes de componentes
- [ ] Implementar integração com API real
- [ ] Adicionar loading states
- [ ] Implementar error handling
- [ ] Adicionar paginação na tabela
- [ ] Implementar filtros avançados
- [ ] Adicionar exportação de dados
- [ ] Implementar cache de dados
- [ ] Adicionar real-time updates

## 📚 Referências

- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Inertia.js](https://inertiajs.com/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Component Design Patterns](https://vuejs.org/guide/components/props.html)

---

**Última atualização**: 2025-11-20

