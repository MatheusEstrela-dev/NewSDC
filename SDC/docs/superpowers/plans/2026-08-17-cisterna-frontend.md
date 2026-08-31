# Plano de acao: frontend do modulo Cisterna

> Continuacao de `2026-08-10-cisterna-modulo-backend-etl.md`, cujas 19 tasks estao concluidas.
> Este documento cobre **somente** a camada Vue/Inertia.

## 1. Estado atual, medido

O backend esta completo e com a carga do legado no banco:

| | |
|---|---|
| Testes do modulo | 211 verdes, 616 asserts |
| Beneficiarios migrados | 8.096 (7.580 ativos) |
| Vistorias | 2.129 (791 fornecedor, 680 compdec, 658 cedec) |
| Itens conferidos | 27.677 |
| Comunidades / OS / lotes | 840 / 7 / 3 |

**E o frontend esta em zero.** As 11 paginas Inertia que os controllers referenciam nao existem:

```
Cisterna/Beneficiarios/{Index,Create,Show,Edit}
Cisterna/Comunidades/Index
Cisterna/Lotes/Index
Cisterna/Notificacoes/Index
Cisterna/OrdensServico/Index
Cisterna/Vistorias/{Index,Show}
Cisterna/QrCode/Ficha
```

> **Consequencia imediata:** `/cisternas/beneficiarios` responde 200 no servidor e **quebra no navegador**, porque o Inertia nao resolve o componente. As 4 paginas do scaffold foram removidas na Task 19 e nao serviam de base -- modelavam um dominio inventado (codigo, capacidade_litros, tipo comunitaria|individual|escolar) que nao existe no legado.

### 1.1 Dois bloqueios que nao sao de frontend

1. **`cedec_municipio` esta vazia** (0 linhas). `Municipio::habilitadosCisterna()` faz join nela, entao **todo select de municipio vai vir vazio** e nenhum cadastro novo podera ser criado pela interface. Resolver com `legado:importar-cedec-municipio` **antes** de testar os formularios.
2. **Nenhum arquivo de midia foi migrado** (`media` do Cisterna = 0). Os arquivos do legado nao estao nesta maquina e as fotos do imovel sempre foram link do Google Drive (30.574 URLs de arquivo individual, sem pasta raiz). Qualquer galeria de fotos vai renderizar vazia -- o desenho tem que prever isso como estado normal, nao como erro.

## 2. Inventario do legado

32 views Blade, **9.105 linhas**, em `sdc/resources/views/ajuda/cisterna/`.

### 2.1 Mapeamento view -> pagina

| View do legado | Linhas | Destino no NewSDC |
|---|---|---|
| `menu.blade.php` | 270 | **nao vira pagina** -> stat cards de `Beneficiarios/Index` (ver D1) |
| `index.blade.php` | 762 | `Beneficiarios/Index` |
| `create.blade.php` | 556 | `Beneficiarios/Create` |
| `edit.blade.php` | 565 | `Beneficiarios/Edit` |
| `view.blade.php` | 264 | `Beneficiarios/Show` |
| `analise.blade.php` | 81 | acao dentro de `Beneficiarios/Show` (modal), nao pagina |
| `imagens.blade.php` | 537 | organismo de galeria em `Beneficiarios/Show` |
| `comunidade/{index,create,edit}` | 410 | `Comunidades/Index` + modais (D3) |
| `lotes/{index,create,edit}` | 261 | `Lotes/Index` + modais (D3) |
| `ordem_servico/{index,create,edit,_form}` | 299 | `OrdensServico/Index` + modais (D3) |
| `notificacoes/{index,create,edit}` | 183 | `Notificacoes/Index` + modais (D3) |
| `relatorio_formularios.blade.php` | 1.459 | `Vistorias/Index` (as tres etapas) |
| `relatorio_formulario_cedec.blade.php` | 600 | formulario da etapa CEDEC |
| `edit_relatorio_formulario_cedec.blade.php` | 616 | idem, modo edicao (D4) |
| `edit_relatorio_formulario_fornecedor.blade.php` | 505 | formulario da etapa fornecedor |
| `edit_relatorio_formulario_compdec.blade.php` | 357 | formulario da etapa COMPDEC |
| `relatorio_formulario_visualizar.blade.php` | 324 | `Vistorias/Show` |
| `relatorio_formulario_compdec_visualizar.blade.php` | 362 | idem |
| `show_relatorio_formulario_cedec.blade.php` | 212 | idem |
| `qrcode.blade.php` + `qrcode_layout` | 95 | `QrCode/Ficha` |
| `qrcodes_pdf.blade.php` | 34 | **nao portavel** (D7) |
| `relatorios.blade.php` | 33 | rota quebrada no legado; descartar |

**1.459 + 616 + 600 + 505 + 357 = 3.537 linhas so de formulario de relatorio** — 39% do legado. E o centro de gravidade do trabalho, nao a listagem.

## 3. Decisoes de arquitetura

**D1. O `menu.blade.php` vira stat cards, nao pagina.**
O menu legado ja e o padrao de "card = filtro": 11 contadores que linkam para `cisterna/index?status=N`. A convencao obrigatoria do projeto (skill de frontend, secao 5) diz que stat cards de pagina de indice **devem** disparar o filtro correspondente. Entao o hub desaparece e seus contadores viram os cards do proprio `Beneficiarios/Index`, o que remove um clique de todo fluxo.

O backend **ja entrega exatamente** esses numeros em `indicadores()`:

| Card do legado | Prop do backend |
|---|---|
| Quantidade Beneficiarios Registrados | `indicadores.total` |
| Quantidade Municipios Registrados | `indicadores.municipios` |
| Registros Aprovados / em Edicao / Reprovados / Ressalva | `indicadores.por_analise[*]` |
| Envio para Instalacao / Instalados | `indicadores.por_obra[*]` |
| Validado Fornecedor / Compdec / Cedec | `indicadores.com_vistoria_*` |

Reaproveitar `Molecules/Statistics/StatCard.vue` com `clickable`, e `PmdaStatisticsCards.vue` + `Pages/Pmda/Index.vue` como implementacao de referencia. **Sem anel de marcacao** no card ativo.

**D2. Atomic Design, com os niveis do projeto.**
Criar `Atoms/Cisterna/`, `Molecules/Cisterna/`, `Organisms/Cisterna/`, `Templates/Cisterna/`, `Pages/Cisterna/`. Atomos e moleculas sao burros (props down, events up); organismos concentram a interacao local; a pagina orquestra e distribui props do Inertia.

**D3. CRUD de apoio em modal, nao em pagina.**
Comunidades, lotes, OS e notificacoes tinham `create` e `edit` como paginas inteiras no legado (11 views para 4 entidades). As rotas do NewSDC ja foram desenhadas sem `create`/`edit`: existem so `index`, `store`, `update`, `destroy`. Logo, o formulario e modal sobre o index. Isso elimina 7 paginas e 6 navegacoes.

**D4. Um formulario por etapa de vistoria, em modo duplo.**
O legado tinha view separada para preencher e para editar cada etapa (`relatorio_formulario_cedec` + `edit_relatorio_formulario_cedec` = 1.216 linhas quase identicas). Um unico organismo por etapa, com prop `modo: 'criar' | 'editar' | 'ler'`, corta essa duplicacao. As rotas confirmam: existe `vistorias.store` e `vistorias.update`, sem rota de formulario.

**D5. A vistoria vive dentro do beneficiario.**
`Vistorias/Index` recebe `beneficiario`, `vistorias`, `etapa_disponivel` e `itens`. A prop `etapa_disponivel` e o que governa a UI: ela diz qual das tres etapas pode ser preenchida agora, e a cadeia e sequencial (fornecedor -> compdec -> cedec). O botao de nova vistoria so aparece para essa etapa.

**D6. Galeria de fotos precisa de estado vazio de primeira classe.**
Nao e caso de erro: hoje **toda** galeria renderiza vazia (media = 0). Onde o legado tinha foto, mostrar o estado vazio com o link do Drive quando houver, lido de `custom_properties.origem_legado`. Reaproveitar `Molecules/ListEmptyState.vue`.

**D7. O PDF de folhas de QR Code nao sera portado nesta fase.**
Nao existe biblioteca de PDF no NewSDC. Era assim que se imprimiam as cartelas de adesivo. A rota `cisternas.qrcode.pdf-em-lote` existe no backend, mas a geracao depende de decisao de infraestrutura (qual lib) que nao e de frontend. **Perda de funcionalidade conhecida, ja registrada nas notas.**

**D8. Tailwind nao escaneia `app/**/*.php`.**
Classe de cor que venha de enum PHP (`getColorClass()`) so entra no CSS se a mesma classe existir em algum `.vue`. Ao montar badges de `SituacaoAnalise` e `SituacaoObra`, usar as familias ja comuns no front (blue/amber/emerald/red/indigo/slate) ou acrescentar ao `safelist`.

## 4. Defeitos do legado a nao reproduzir

| # | Defeito | Tratamento |
|---|---|---|
| F1 | No `menu.blade.php`, "Aprovados Ressalva" e "Envio para Instalacao" apontam **os dois** para `status=3` -- copia e cola. Um dos cards levava a lista errada. | Cards ligados aos indicadores certos: ressalva em `por_analise`, envio em `por_obra`. |
| F2 | A listagem tinha um "Total de Registros" solto em texto, sem os cards. | Vira card clicavel de `Total`, que limpa o filtro. |
| F3 | Acao em massa sem escopo territorial: um COMPDEC alcancava beneficiario de outro municipio. Corrigido no backend na Task 8. | A UI nao precisa fazer nada, mas **nao** deve oferecer selecao fora do escopo do perfil. |
| F4 | `relatorios.blade.php` aponta para rota que nao existe. | Descartado. |
| F5 | Ranqueamento tinha checkbox de filtro mas nenhum calculo no legado. | `ranqueamento_ordem` e coluna importada e **apenas ordenavel**. Nada de botao "calcular". |

## 5. Componentes a criar

### Atomos e moleculas
- `Atoms/Cisterna/SituacaoAnaliseBadge.vue`, `SituacaoObraBadge.vue` (D8)
- `Atoms/Cisterna/EtapaVistoriaBadge.vue`
- `Molecules/Cisterna/ItemConferidoRow.vue` — item do checklist: conferido, quantidade, unidade, detalhes
- `Molecules/Cisterna/CoordenadaField.vue` — lat/long com aviso de fora de faixa
- `Molecules/Cisterna/AtendimentoPipaFieldset.vue` — os 5 responsaveis + descricao de "outros"

### Organismos
- `Organisms/Cisterna/CisternaStatisticsCards.vue` (D1)
- `Organisms/Cisterna/BeneficiarioFiltersSection.vue` — nome, CPF, num. instalacao, municipio, comunidade, lote, situacao de analise, situacao de obra, pipa, ranqueamento
- `Organisms/Cisterna/BeneficiariosTable.vue` — colunas do legado + selecao em massa
- `Organisms/Cisterna/AcaoEmMassaBar.vue`
- `Organisms/Cisterna/BeneficiarioForm.vue` — o formulario largo (54 campos), em secoes
- `Organisms/Cisterna/FotosImovelGallery.vue` (D6)
- `Organisms/Cisterna/VistoriaFornecedorForm.vue`, `VistoriaCompdecForm.vue`, `VistoriaCedecForm.vue` (D4)
- `Organisms/Cisterna/VistoriaTimeline.vue` — as tres etapas e o que falta
- `Organisms/Cisterna/{Comunidade,Lote,OrdemServico,Notificacao}Modal.vue` (D3)
- `Organisms/Cisterna/AnaliseModal.vue` — aprovar/reprovar/ressalva com observacao

### Templates
- `Templates/Cisterna/CisternaIndexTemplate.vue` — cards + filtros + tabela + paginacao
- `Templates/Cisterna/CisternaFormTemplate.vue` — formulario em secoes com navegacao lateral
- `Templates/Cisterna/CisternaDetailTemplate.vue` — cabecalho + abas

## 6. Fases

| Fase | Escopo | Portao de saida |
|---|---|---|
| **1 — Fundacao** | Badges, template de indice, `Beneficiarios/Index` com cards-filtro, filtros e tabela | A rota principal abre e filtra; cards disparam filtro |
| **2 — Cadastro** | `Beneficiarios/{Create,Edit,Show}`, galeria, modal de analise | Cadastro completo pela interface, com os 3 perfis |
| **3 — Vistorias** | `Vistorias/{Index,Show}`, os 3 formularios de etapa, timeline, checklist | Cadeia sequencial das 3 etapas funcionando |
| **4 — Apoio** | `Comunidades`, `Lotes`, `OrdensServico`, `Notificacoes` (index + modais) | CRUD de apoio completo |
| **5 — QR Code** | `QrCode/Ficha` e download individual | Ficha publica abre pelo numero de instalacao |

**A fase 1 depende de `legado:importar-cedec-municipio` ter rodado**, senao o filtro de municipio abre vazio e nao da para validar nada.

## 7. Criterios de verificacao

1. As 11 paginas resolvem, sem erro de componente nao encontrado no Inertia
2. `npm run build` passa sem import nao resolvido
3. Todo stat card de indice dispara o filtro correspondente; `Total` limpa (convencao obrigatoria)
4. Nenhum `<style>` novo que nao seja para componente de terceiros; Tailwind utilitario
5. Atomos e moleculas sem chamada de API e sem acesso a estado global
6. Os tres perfis verificados na interface: CEDEC ve todos os municipios habilitados, COMPDEC so o proprio, fornecedor so obra em envio ou instalada
7. Estado vazio tratado em toda listagem e galeria -- hoje a midia e sempre vazia
8. Nenhuma classe de cor vinda de PHP sem par no front ou no safelist
9. Teste Inertia por pagina (`AssertableInertia`), com `withoutVite()` e `inertia.testing.ensure_pages_exist => false`
10. Nenhuma referencia remanescente as rotas do scaffold

## 8. Pendencias herdadas que a interface precisa comunicar

Nao sao bugs a corrigir no frontend, sao verdades do dado migrado que a tela nao pode esconder:

- **Fotos do imovel:** 30.574 links do Google Drive, nenhum arquivo. A tela mostra o link, nao a imagem.
- **`created_by` nulo** nos 8.096 importados: o cruzamento com os usuarios do NewSDC deu zero. A coluna "cadastrado por" fica vazia para dado do legado.
- **9 beneficiarios nao importados** por conflito de CPF ou CPF truncado, e **3 comunidades** com municipio literal `"Municipio"`. A area precisa de uma tela ou export para resolver -- hoje isso vive so no `cisterna_etl_log`.
- **516 duplicados** ficam fora da lista ativa por padrao. O filtro precisa permitir ve-los.
