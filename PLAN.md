# Plano: Corrigir Erro de Import e Validar Widgets Drag & Drop no Dashboard

## Diagnóstico do Erro

O erro no screenshot é:
```
[plugin:vite:import-analysis] Failed to resolve import "vuedraggable" from "resources/js/Pages/Dashboard.vue"
```

**Causa raiz**: O `Dashboard.vue` no container Docker ainda usa o import antigo `import draggable from 'vuedraggable'`, mas no código local (editado) já foi alterado para `import draggable from '@/lib/vuedraggable-src/vuedraggable.js'`.

### Estado atual verificado:
- ✅ `vuedraggable@4.1.0` está no `package.json` e instalado em `node_modules/`
- ✅ `sortablejs` está instalado (dependência transitiva)
- ✅ Cópia local do source em `resources/js/lib/vuedraggable-src/` existe e é válida
- ✅ `vite.config.js` tem alias `@` → `resources/js` configurado
- ✅ Todos os 8 widgets existem e são componentes Vue válidos:
  - `DashboardMetricCard.vue`, `BarChartWidget.vue`, `DonutChartWidget.vue`
  - `RadarChartWidget.vue`, `TrendChartWidget.vue`, `SparklinesWidget.vue`
  - `PmdaListWidget.vue`, `TimelineWidget.vue`
- ✅ Todos os ícones (`HomeIcon`, `CheckCircleIcon`, `ClockIcon`, `PencilSquareIcon`) existem
- ✅ `PageHeader.vue` existe

## Plano de Ação

### Passo 1 — Simplificar o import do vuedraggable
Trocar o import local por import direto do pacote npm (que JÁ está instalado):

```js
// DE (atual - via cópia local):
import draggable from '@/lib/vuedraggable-src/vuedraggable.js';

// PARA (direto do npm - mais limpo e manutenível):
import draggable from 'vuedraggable';
```

**Justificativa**: O pacote `vuedraggable@4.1.0` já está no `package.json` e `node_modules/`. Usar a cópia local é redundante e propenso a erros de resolução. O import direto é o padrão recomendado.

> Se `vuedraggable` falhar no Vite (bug conhecido com vue-next), usaremos o import alternativo: `import draggable from 'vuedraggable/src/vuedraggable'`

### Passo 2 — Garantir que node_modules está sincronizado no container
Executar `npm install` dentro do container Docker para garantir que o `vuedraggable` e `sortablejs` estão instalados.

### Passo 3 — Remover a cópia local desnecessária
Após confirmar que o import direto funciona:
- Remover `/resources/js/lib/vuedraggable-src/` (cópia desnecessária do source)
- Remover `/resources/js/lib/vuedraggable.umd.min.js` (backup desnecessário)

### Passo 4 — Limpar comentários desnecessários no Dashboard.vue
Remover os comentários de decisão (linhas 58-63) que são anotações de desenvolvimento e não deveriam estar no código final:

```js
// Estas linhas serão removidas:
// Precisarei criar este se não existir, ou usar um genérico aqui
// Nota: Vou criar um DashboardMetricCard.vue inline...
// Decisão: Vou criar um `MetricWidget.vue` simples agora também?
// Melhor: Como já tenho os outros...
// Vou usar um componente placeholder "MetricWidget" aqui...
```

### Passo 5 — Reiniciar o Vite dev server
Reiniciar o servidor Vite para que as mudanças sejam capturadas corretamente pelo HMR.

## Resultado Esperado
- Dashboard carrega sem erros
- Widgets renderizam corretamente no grid 12-colunas
- Drag & drop funciona via handles (ícone de arrastar)
- Ghost card com estilo visual durante arraste
