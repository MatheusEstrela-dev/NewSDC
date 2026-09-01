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
| Modais: rolagem de fundo e altura | `Modal.vue`, `useBloqueioDeRolagem` | bloqueio contado; painel limitado a `calc(100dvh-5rem)` |
| Tira de abas que nao rolava | 3 modais de historico | assinatura copiada em Cisterna, PAE e PMDA |
| Abas de modulo | `ModuleTabs` + 5 modulos | um desenho no lugar de cinco |
| Acoes do cabecalho | `ActionButton` + 28 arquivos | so a primaria e Analises levam nome |
| Mapa de sismos | `MapaSismos.vue` | mesmo isolamento do INMET; media query no fim do arquivo |
| Ultimas tabelas | AH pedidos, AH transferencias, COMPDEC orgaos | medidas antes: 769px, 8 colunas e 744px em ~326px de espaco |
| Tabelas rolando de lado — 16 paginas | TDAP (11), Cisterna (5) | `ResponsiveTable`; excesso 0px medido em 375px nas 13 restantes |
| Sanfona no formulario longo | `Composables/rat/useCollapsible.js` | uma secao aberta abaixo de md; as 2 secoes cruas viraram colapsaveis |
| Acoes no pe do card | `Molecules/Table/TableMobileCard.vue` | saiu do cabecalho, onde disputava espaco com o titulo |
| Botao Voltar duplicado | 7 paginas do TDAP | a trilha ja tem o BackButton; sobrava um segundo na mesma dobra |

---

## Pendente

Nada aberto. A varredura de 2026-09-01 mediu **27 rotas de modulo em 375px** com
`scrollWidth - clientWidth`: excesso **0 em todas**.

Restou uma tabela visivel no mobile por decisao, nao por divida: `/inmet` mede 336px num
espaco de 360px -- **cabe**, e converter em card sem necessidade so aumentaria a rolagem
vertical de 57 linhas.

---

## Como validar qualquer item

```js
// em 375px e 840px, na pagina alterada
const de = document.documentElement;
console.log({ viewport: de.clientWidth, excesso: de.scrollWidth - de.clientWidth });
```

`excesso` tem que ser **0**. Comparar sempre com `/rat` no mesmo viewport.

Para gesto de rolagem, use `page.mouse.wheel` do Playwright -- `new WheelEvent(...)` e evento
sintetico, nao dispara rolagem nativa e da falso negativo (regra 18).
