# Backlog — Responsividade mobile web

Levantado durante a validacao no celular (ngrok) em 2026-08-31. Cada item traz o que foi
medido, para virar tarefa sem redescobrir o diagnostico.

Regras que governam estes itens: `.claude/skills/frontend/04 - Responsividade/04 - responsividade.md`.

---

## Feito

| item | onde | nota |
|---|---|---|
| Transbordo horizontal da trilha | `NavigationHeader`, `BreadcrumbTrail` | `excesso 0px` em 375px, medido; a trilha rola dentro de si |
| Drawer torto na faixa de tablet | `Sidebar.styles.css`, `NavItem.vue` | bloco de 768-1023px removido; `inset 0px` medido em 840px |
| Voltar ia para o Inicio | `useBreadcrumb.js` | crumbs intermediarios resolvem rota propria |
| Paginacao com rotulo | `Molecules/Navigation/Pagination.vue` | so setas (`ButtonIcon`), 20 consumidores de uma vez |
| Dark mode pelo SO | 5 componentes, 20 blocos | `prefers-color-scheme` -> `:global(html.dark)` |
| Busca global no celular | `TopBar.vue` | icone nu -> campo de 149x40px com dica |
| Calendario da escala | `EscalaCalendario.vue` | 24h rolaveis, toggle Dia/Semana, clique lanca vaga |
| Impresso em tela pequena | `Organisms/Print/BasePrintModal.vue` | no mobile abre em outra aba; sem modal apertado |
| Mapa sobre a sidebar | `Pages/Inmet/MapaInmet.vue` | `isolation: isolate` no wrapper; altura em `vh` no mobile |

---

## Pendente

### 1. Tabelas que ainda rolam de lado no telefone — 16 paginas

**Prioridade alta.** E o defeito que mais engana: a coluna de acoes flutua sobre conteudo
cortado, e o usuario nao sabe de qual registro sao os botoes que esta tocando.

Medido em 2026-08-31: `grep -c "isMobile\|md:hidden\|hidden md:"` = **0** em todas.

| modulo | paginas |
|---|---|
| TDAP | `Atas/Index`, `Atas/Show`, `Caminhoes/Index`, `Cronogramas/Index`, `Cronogramas/Show`, `Historicos/Index`, `Lotes/Index`, `Prestadores/Index`, `Processos/Index`, `Viagens/Pendentes`, `Vistorias/Index` |
| Cisterna | `Beneficiarios/Index`, `Comunidades/Index`, `Lotes/Index`, `Notificacoes/Index`, `OrdensServico/Index` |

**Receita:** Forma B da regra 9 — `Organisms/Table/ResponsiveTable.vue` com
`Molecules/Table/TableMobileCard.vue`. Os dois componentes existem, estao corretos e **nunca
foram usados por ninguem** (eram codigo morto ate a correcao de tema desta rodada). Nao ha
componente novo a escrever: falta declarar `mobileFields` e o slot `#mobile-actions` por
pagina.

Comecar por `Cisterna/Beneficiarios/Index.vue`, que e a que o usuario reportou, e usa-la como
implementacao de referencia para as outras 15.

### 2. Auditoria de modais e popups em tela pequena

**Pedido explicito, ainda nao medido.** Verificar em 375px:

- `Components/Modal.vue` (base de todos): largura, altura maxima, rolagem interna
- modais de formulario longo (`ViaturaFormModal`, `EscalaVagaModal`, `MovimentacaoModal`,
  `EncerrarTurnoModal`, `AceitarPassagemModal`)
- `Dropdown.vue` com `mobileFullWidth` — conferir se todos os consumidores passam a prop
- popups do Leaflet no INMET (marcador clicado)

Criterio: nada corta, nada rola a pagina de fundo, botao de acao sempre alcancavel sem zoom.

### 3. INMET: reatividade alem do z-index

O `isolation` e a altura em `vh` resolveram o pior. Ainda vale medir em 375px:

- filtros e seletor de modo acima do mapa
- tabela de estacoes, se houver, cai na divida do item 1
- os cards de estatistica fora do mapa

---

## Como validar qualquer item

```js
// em 375px e 840px, na pagina alterada
const de = document.documentElement;
console.log({ viewport: de.clientWidth, excesso: de.scrollWidth - de.clientWidth });
```

`excesso` tem que ser **0**. Comparar sempre com `/rat` no mesmo viewport.
