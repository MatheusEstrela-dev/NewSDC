# PlanCon Module Implementation Plan

## Overview
Create a new "PlanCon" (Plano de Contingencia) module following the existing DDD architecture, Atomic Design patterns, and Design System of the NewSDC project.

## Module Purpose
Dashboard for managing and visualizing Contingency Plans (Planos de Contingencia) for Minas Gerais municipalities, featuring:
- Interactive pie charts showing plan coverage statistics
- Links to filter municipalities with/without plans
- Status visualization of plans in the system

---

## Phase 1: Backend Module Structure (DDD)

### 1.1 Create Module Directory Structure
**Location:** `app/Modules/PlanCon/`

```
PlanCon/
├── Application/
│   ├── UseCases/
│   │   ├── ListPlanConStatsUseCase.php
│   │   ├── ListMunicipiosComPlanoUseCase.php
│   │   └── ListMunicipiosSemPlanoUseCase.php
│   └── DTOs/
│       ├── PlanConStatsDTO.php
│       └── MunicipioDTO.php
├── Domain/
│   ├── Entities/
│   │   └── PlanoContingencia.php
│   ├── ValueObjects/
│   │   └── SituacaoPlano.php (Regular/Irregular)
│   └── Repositories/
│       └── PlanoContingenciaRepositoryInterface.php
├── Infrastructure/
│   └── Persistence/
│       └── EloquentPlanoContingenciaRepository.php
├── Presentation/
│   └── Http/
│       └── Controllers/
│           ├── PlanConIndexController.php
│           ├── PlanConStatsController.php
│           ├── MunicipiosComPlanoController.php
│           └── MunicipiosSemPlanoController.php
└── PlanConServiceProvider.php
```

### 1.2 Database Considerations
- Verify existing tables: `planos_contingencia`, `municipios`
- Required data:
  - Total municipalities in MG
  - Municipalities with active contingency plans
  - Plan status (Regular/Irregular)

### 1.3 Routes Configuration
**File:** `routes/modules/plancon.php`

```php
Route::prefix('plancon')->name('plancon.')->middleware(['auth', 'can:plancon.view'])->group(function () {
    Route::get('/', PlanConIndexController::class)->name('index');
    Route::get('/stats', PlanConStatsController::class)->name('stats');
    Route::get('/municipios/com-plano', MunicipiosComPlanoController::class)->name('municipios.com');
    Route::get('/municipios/sem-plano', MunicipiosSemPlanoController::class)->name('municipios.sem');
});
```

---

## Phase 2: Frontend - Atomic Design Components

### 2.1 Atoms (Base Components)
**Location:** `resources/js/Components/Atoms/`

No new Atoms needed - will reuse existing:
- `Button.vue`
- `Card.vue`
- `Badge.vue`

### 2.2 Molecules (Component Combinations)
**Location:** `resources/js/Components/Molecules/PlanCon/`

#### 2.2.1 PlanConStatCard.vue
- Purpose: Display quick stats (total municipalities, plans, etc.)
- Props: `title`, `value`, `percentage`, `trend`, `color`
- Uses existing `StatCard.vue` pattern

#### 2.2.2 PlanConLinkCard.vue
- Purpose: Clickable cards for "Lista de Municipios com/sem Plano"
- Props: `title`, `icon`, `href`, `variant` (with-plan/without-plan)
- Styled with icon on left, text with highlight color

### 2.3 Organisms (Complex Components)
**Location:** `resources/js/Components/Organisms/PlanCon/`

#### 2.3.1 PlanConPieChart.vue
- Purpose: Interactive pie chart for statistics
- Props: `data`, `title`, `colors`
- Based on `DonutChartWidget.vue` pattern (custom SVG)
- Features:
  - Animated segments
  - Percentage labels
  - Legend with color coding
  - Hover effects
  - Dark mode support

#### 2.3.2 PlanConDashboardGrid.vue
- Purpose: Grid layout for dashboard widgets
- Contains: Link cards + Pie charts
- Responsive: `grid-cols-1 lg:grid-cols-2`

### 2.4 Templates
**Location:** `resources/js/Templates/PlanCon/`

#### 2.4.1 PlanConIndexTemplate.vue
- Main dashboard template
- Layout:
  - Header with PageHeader component
  - Link cards section (2 cards)
  - Charts section (2 pie charts side by side)

### 2.5 Pages
**Location:** `resources/js/Pages/PlanCon/`

#### 2.5.1 PlanConIndex.vue
- Main dashboard page
- Uses Inertia.js for data hydration
- Receives stats data from backend

---

## Phase 3: Dashboard Implementation Details

### 3.1 Visual Specifications (Based on Screenshot)

#### Link Cards Section
| Card | Text | Icon | Style |
|------|------|------|-------|
| 1 | Lista de Municipios **com** Plano de Contingencia | List/Document icon | "com" in orange/amber |
| 2 | Lista de Municipios **sem** Plano de Contingencia | List/Document icon | "sem" in orange/amber |

#### Pie Chart 1: "Plano de Contingencia Municipios Mineiros"
| Segment | Color | Label |
|---------|-------|-------|
| Municipios com plano | Blue (#3b82f6) | 85.5% |
| Municipios Sem Plano | Orange (#f97316) | 14.5% |

#### Pie Chart 2: "Situacao dos Planos Inseridos no Sistema"
| Segment | Color | Label |
|---------|-------|-------|
| Planos em Situacao Regular | Blue (#3b82f6) | 97.9% |
| Planos em Situacao Irregular | Orange (#f97316) | 2.1% |

### 3.2 Chart Configuration (ApexCharts Donut)
```javascript
const chartOptions = {
  chart: {
    type: 'donut',
    background: 'transparent',
    animations: { enabled: true, easing: 'easeinout', speed: 800 }
  },
  colors: ['#3b82f6', '#f97316'],
  labels: ['Com Plano', 'Sem Plano'],
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: { show: true, label: 'Total', formatter: () => '85.5%' }
        }
      }
    }
  },
  legend: {
    position: 'right',
    labels: { colors: '#94a3b8' }
  },
  dataLabels: {
    enabled: true,
    formatter: (val) => `${val.toFixed(1)}%`
  },
  stroke: { show: false },
  tooltip: { theme: 'dark' }
};
```

---

## Phase 4: Composables

### 4.1 usePlanCon.js
**Location:** `resources/js/Composables/usePlanCon.js`

```javascript
// Provides:
// - planConStats (reactive stats data)
// - isLoading
// - refreshStats()
// - chartConfigs (computed chart options)
```

---

## Phase 5: Implementation Order

### Step 1: Backend Foundation
1. [ ] Create `PlanConServiceProvider.php`
2. [ ] Create DTOs: `PlanConStatsDTO.php`, `MunicipioDTO.php`
3. [ ] Create Repository Interface and Implementation
4. [ ] Create UseCases
5. [ ] Create Controllers (single-action)
6. [ ] Register routes in `routes/modules/plancon.php`
7. [ ] Register provider in `config/app.php`

### Step 2: Frontend Components
1. [ ] Create `PlanConStatCard.vue` (Molecule)
2. [ ] Create `PlanConLinkCard.vue` (Molecule)
3. [ ] Create `PlanConPieChart.vue` (Organism)
4. [ ] Create `PlanConDashboardGrid.vue` (Organism)
5. [ ] Create `usePlanCon.js` (Composable)

### Step 3: Page Assembly
1. [ ] Create `PlanConIndexTemplate.vue` (Template)
2. [ ] Create `PlanConIndex.vue` (Page)
3. [ ] Connect to backend via Inertia

### Step 4: Integration
1. [ ] Add sidebar navigation item
2. [ ] Add permissions if needed
3. [ ] Test responsive layout
4. [ ] Test dark mode compatibility

---

## File Checklist

### Backend Files
- [ ] `app/Modules/PlanCon/PlanConServiceProvider.php`
- [ ] `app/Modules/PlanCon/Application/DTOs/PlanConStatsDTO.php`
- [ ] `app/Modules/PlanCon/Application/DTOs/MunicipioDTO.php`
- [ ] `app/Modules/PlanCon/Application/UseCases/ListPlanConStatsUseCase.php`
- [ ] `app/Modules/PlanCon/Application/UseCases/ListMunicipiosComPlanoUseCase.php`
- [ ] `app/Modules/PlanCon/Application/UseCases/ListMunicipiosSemPlanoUseCase.php`
- [ ] `app/Modules/PlanCon/Domain/Entities/PlanoContingencia.php`
- [ ] `app/Modules/PlanCon/Domain/ValueObjects/SituacaoPlano.php`
- [ ] `app/Modules/PlanCon/Domain/Repositories/PlanoContingenciaRepositoryInterface.php`
- [ ] `app/Modules/PlanCon/Infrastructure/Persistence/EloquentPlanoContingenciaRepository.php`
- [ ] `app/Modules/PlanCon/Presentation/Http/Controllers/PlanConIndexController.php`
- [ ] `app/Modules/PlanCon/Presentation/Http/Controllers/PlanConStatsController.php`
- [ ] `app/Modules/PlanCon/Presentation/Http/Controllers/MunicipiosComPlanoController.php`
- [ ] `app/Modules/PlanCon/Presentation/Http/Controllers/MunicipiosSemPlanoController.php`
- [ ] `routes/modules/plancon.php`

### Frontend Files
- [ ] `resources/js/Components/Molecules/PlanCon/PlanConStatCard.vue`
- [ ] `resources/js/Components/Molecules/PlanCon/PlanConLinkCard.vue`
- [ ] `resources/js/Components/Organisms/PlanCon/PlanConPieChart.vue`
- [ ] `resources/js/Components/Organisms/PlanCon/PlanConDashboardGrid.vue`
- [ ] `resources/js/Composables/usePlanCon.js`
- [ ] `resources/js/Templates/PlanCon/PlanConIndexTemplate.vue`
- [ ] `resources/js/Pages/PlanCon/PlanConIndex.vue`

---

## Design Tokens Reference

### Colors
- Primary Blue: `#3b82f6` / `bg-blue-500`
- Orange Accent: `#f97316` / `bg-orange-500`
- Success Green: `#10b981` / `bg-emerald-500`
- Dark Background: `#1e293b` / `bg-slate-800`
- Card Background: `#334155` / `bg-slate-700`
- Text Primary: `#f8fafc` / `text-slate-50`
- Text Secondary: `#94a3b8` / `text-slate-400`

### Spacing
- Card padding: `p-6`
- Section gap: `gap-6` or `gap-8`
- Chart container: `min-h-[300px]`

### Typography
- Chart title: `text-sm font-medium text-slate-300`
- Legend text: `text-xs text-slate-400`
- Percentage display: `text-lg font-semibold`

---

## Notes
- Follow existing patterns from `DonutChartWidget.vue` for pie chart implementation
- Reuse `PageHeader.vue` for consistent header styling
- All controllers should be invokable (single-action)
- Use constructor injection for all dependencies
- DTOs should use `readonly` properties (PHP 8.3+)
