# Modulo Notas Tecnicas (NewSDC) — plano por fases

## Contexto

A equipe da CEDEC precisa de um lugar unico no sistema para consultar Notas Tecnicas e POPs
(fluxos e processos) em PDF. Hoje os PDFs do sistema abrem com `window.open` em outra aba e nao
existe nenhum repositorio de procedimentos. O objetivo: entrar no modulo **Notas Tecnicas** pelo
menu "Modulos de Gestao", clicar numa nota e o PDF abrir **dentro do proprio site**, no leitor
nativo do navegador (sumario, zoom, busca, impressao, download — o mesmo leitor do print do
Edge), com os metadados da nota ao lado (estilo coluna direita da tela de Prefeitura), versoes
anteriores acessiveis e confirmacao de leitura ("Li e estou ciente").

Inspiracao: workspace do IBM Lotus Notes (arvore de pastas | lista | preview | metadados).

## Decisoes (minhas, para voce conferir)

1. **Leitor = `<iframe>` do PDF servido inline** pela propria aplicacao. Nada de pdf.js: o leitor
   nativo do Chrome/Edge ja entrega sumario, zoom, busca, impressao e download.
   - `X-Frame-Options: SAMEORIGIN` e `frame-ancestors 'self'` (`app/Http/Middleware/SecurityHeaders.php`)
     ja permitem iframe da mesma origem.
   - **Armadilha:** o CSP global tem `object-src 'none'`, e o Chromium bloqueia o leitor de PDF
     quando a *resposta do PDF* carrega esse CSP. A rota de stream precisa sair do CSP global
     (ou mandar o proprio CSP sem `object-src 'none'`). Verificar no navegador na Fase 1.
2. **Mobile (< lg, via `useMobile`)**: celular nao renderiza PDF em iframe direito. Segue a regra
   14 da skill de responsividade — clicar na nota abre o PDF em outra aba; a tela mostra lista +
   metadados empilhados.
3. **Nomes em portugues**, como o resto do repo: modulo `App\Modules\NotasTecnicas`, rota
   `/notas-tecnicas`, nome de rota `notas-tecnicas.*`, tabelas com prefixo `nota_tecnica_`.
4. **Codigo da nota gerado pelo sistema**: `NT-AAAA-NNN` sequencial por ano (lock na transacao).
5. **Status**: `rascunho` | `publicada` | `revogada` (Enum). Leitor comum so ve `publicada` e
   `revogada` (revogada aparece marcada como obsoleta, filtro proprio).
6. **Versao nova = novo PDF** + changelog. A versao corrente e a ultima publicada; as antigas
   ficam acessiveis pela lista expansivel. Confirmacao de leitura e **por versao** (versao nova
   volta a ficar "nao lida").
7. **Busca**: Fases 1-3 buscam em codigo/titulo/resumo (`pg_trgm` + `unaccent`, ja instalados).
   A busca **dentro do PDF** (tsvector) vem na Fase 4, porque exige `poppler-utils` na imagem
   da fila.
8. **Favoritos ficam fora** do escopo inicial. "Recentes" sai das leituras do proprio usuario.
9. Armazenamento no padrao Storage direto (`Demandas\Services\DemandaAnexoService.php`), nao
   MediaLibrary: precisamos de controle do caminho por versao e do stream.

## Arquitetura de tela (Atomic Design, padrao do NewSDC)

```
Pages/NotasTecnicas/Index.vue                (orquestra: props Inertia, router.get, selecao)
 └ Templates/NotasTecnicas/NotasTecnicasWorkspaceTemplate.vue   (slots: arvore | lista | leitor | meta)
    ├ PageHeader (canonico)  +  NotaTecnicaStatCards (StatCard clickable = filtro rapido)
    ├ Organisms/NotasTecnicas/NotaTecnicaCategoriaArvore.vue
    │   └ Molecules/NotasTecnicas/NotaTecnicaCategoriaItem.vue (recursivo, emite select)
    ├ Organisms/NotasTecnicas/NotaTecnicaLista.vue (SearchInput + itens + Pagination)
    │   ├ Molecules/NotasTecnicas/NotaTecnicaListaItem.vue (codigo, titulo, badge, "nao lida")
    │   └ Molecules/NotasTecnicas/NotaTecnicaVersaoItem.vue (v2.1, v2.0... expansivel)
    ├ Organisms/NotasTecnicas/NotaTecnicaLeitor.vue (iframe; vazio = ListEmptyState)
    └ Organisms/NotasTecnicas/NotaTecnicaMetadadosPainel.vue (CollapsibleSection + CampoDeLeitura,
        responsaveis, vigencia, changelog, telas relacionadas, botao "Li e estou ciente")
Atoms reusados: Badge/StatusBadge, ActionButton, PermissionButton, ButtonIcon, SearchInput
Molecules reusadas: CollapsibleSection, CampoDeLeitura, ListEmptyState, Pagination, DropZone
```

- Raiz da pagina: `w-full space-y-6 pb-8`, filhos com `:espaco-inferior="false"`, sem `p-*`.
- Workspace em `lg+`: grid `[16rem_22rem_1fr_20rem]`, altura `calc(100dvh - topo)`, **`min-h-0`
  em toda a cadeia** (regra 17) e cada painel com seu `overflow-y-auto`.
- Arvore e painel de metadados retrateis; estado em `useStorage` do `@vueuse/core` (ja instalado).
- Nota selecionada vai na URL (`?nota=ID&versao=ID`) via `router.get(..., { only: ['selecionada'],
  preserveState, preserveScroll, replace })`: link compartilhavel e voltar do navegador funciona.
- Stat cards (filtro rapido): Total | Publicadas | Nao lidas por mim | Revogadas.

## Modelo de dados (uma migration consolidada)

`database/migrations/2026_11_xx_000000_create_nota_tecnica_tables.php`:

- `nota_tecnica_categorias`: id, parent_id (self, nullable), nome, slug, ordem, timestamps.
  Seed inicial: RAT, TDAP/PMDA, PAE, SCO, Administrativo.
- `notas_tecnicas`: id, codigo (unique), titulo, categoria_id, resumo (text), status,
  vigencia_inicio (date), elaborado_por (user_id), aprovado_por (user_id null),
  rotas_relacionadas (jsonb: nomes de rota Ziggy + rotulo), timestamps, softDeletes.
  Indices GIN trigram em `titulo`/`codigo` (padrao de `2026_08_20_110000_indices_trigram_da_busca_global.php`).
- `nota_tecnica_versoes`: id, nota_tecnica_id, numero (string "2.1"), arquivo_path, arquivo_nome,
  arquivo_tamanho, changelog, publicada_em, publicada_por, timestamps. Unique (nota, numero).
  Coluna `conteudo_texto` + `conteudo_busca tsvector` + GIN entram **nesta mesma migration** na
  Fase 4 (regra 9: consolidar).
- `nota_tecnica_leituras`: id, user_id, nota_tecnica_versao_id, lida_em. Unique (user, versao).

Migration de permissoes no padrao de `2026_09_25_120000_create_resgate_permissions.php`.

## Fases

### Fase 0 — Fundacao (backend, sem tela)
- `app/Modules/NotasTecnicas/`: `NotasTecnicasServiceProvider.php`, `Models/{NotaTecnica,
  NotaTecnicaCategoria,NotaTecnicaVersao,NotaTecnicaLeitura}.php`, `Enums/NotaTecnicaStatus.php`.
  Todo arquivo com `declare(strict_types=1)`.
- Provider em `config/app.php` (linhas ~228-250). **Sem** `loadRoutesFrom` (quebra middleware).
- Migration de tabelas + migration de permissoes + seeder de categorias.
- `config/permissions.php`: grupo do modulo com `notas-tecnicas.view`, `.create`, `.edit`,
  `.publicar`, `.delete`, `.leituras` (relatorio); seguir o formato exato de `cedec.prefeituras.*`
  (linhas ~488-497) e o `role_permissions`.
- Disco `notas_tecnicas` em `config/filesystems.php` via `$azureOrLocal`; container
  `sdc-notas-tecnicas` + `AZURE_STORAGE_CONTAINER_*` no `entrypoint.prod.sh` (o adapter nao cria
  container).
- `app/Policies/NotaTecnicaPolicy.php` (estende `BasePolicy`) mapeada no `AuthServiceProvider`.
- `routes/modules/notas-tecnicas.php` incluido com `require` no grupo auth de `routes/web.php`
  (mesmo lugar do cedec, linha ~167).
- Commit: `🗃️ db(notas-tecnicas): Fase 0 - tabelas, permissoes e disco`.

### Fase 1 — Leitura (MVP que voce descreveu)
- `Controllers/NotaTecnicaController@index`: arvore de categorias, lista paginada com filtros
  (categoria, status, busca, nao_lidas), stats e `selecionada` (nota + versoes + metadados).
- `Services/NotaTecnicaConsultaService.php` + `DTOs/NotaTecnicaFiltroDTO.php` (padrao
  `CedecPrefeituraService` / `PrefeituraFiltroDTO`), eager load com `->with()`.
- `Resources/NotaTecnicaListaResource.php`, `NotaTecnicaDetalheResource.php`.
- `Controllers/NotaTecnicaArquivoController@show`: `GET /notas-tecnicas/versoes/{versao}/pdf`,
  autoriza via policy, `Storage::disk('notas_tecnicas')->response(..., ['Content-Disposition' =>
  'inline'])`, sem `object-src 'none'` na resposta (ajuste em `SecurityHeaders` para essa rota).
  `?download=1` troca para `attachment`.
- Frontend: Template + Organisms + Molecules listados acima, `Pages/NotasTecnicas/Index.vue`,
  composable `Composables/notasTecnicas/useNotaTecnicaWorkspace.js` (selecao, paineis retrateis).
- Menu: `NavItem` em `Components/Sidebar.vue` (bloco ~183, `canSeeNotasTecnicas` ~1039,
  `_routes.hasNotasTecnicas` ~865) + `NavItem.vue` (icone) + `CommandPalette.vue` (~276) +
  `Support/moduleIcons.js` + mapa de cores em `Pages/Admin/Permissions/Permissions/Index.vue` (~336).
- Commit: `✨ feat(notas-tecnicas): Fase 1 - workspace de leitura com PDF embutido`.

### Fase 2 — Gestao (cadastrar, versionar, publicar)
- `Requests/{StoreNotaTecnicaRequest,UpdateNotaTecnicaRequest,StoreNotaTecnicaVersaoRequest}.php`
  (PDF `mimes:pdf`, `max` configuravel), DTOs com `fromRequest()`.
- `Services/NotaTecnicaService.php`: criar (gera codigo `NT-AAAA-NNN` com lock), editar,
  publicar, revogar, nova versao (grava arquivo, apaga se a transacao falhar — padrao do
  `DemandaAnexoService`), path saneado por `Support/Storage/AnexoPath.php`.
- Telas: `Pages/NotasTecnicas/{Create,Edit}.vue` com `Organisms/NotasTecnicas/
  NotaTecnicaFormSecoes.vue` (CollapsibleSection, sanfona no mobile) + `DropZone`;
  `Organisms/NotasTecnicas/NotaTecnicaNovaVersaoModal.vue`; publicar/revogar com
  `useConfirmacao` + `ConfirmDialog`.
- Gestao de categorias (CRUD simples) no proprio workspace, para quem tem `.edit`.
- Commit: `✨ feat(notas-tecnicas): Fase 2 - cadastro, versoes e publicacao`.

### Fase 3 — Ciencia de leitura
- `POST /notas-tecnicas/versoes/{versao}/ciencia` (idempotente, unique user+versao).
- Botao "Li e estou ciente" no painel de metadados (some depois, vira "Ciente em dd/mm/aaaa").
- Badge "Nao lida" na lista e stat card "Nao lidas" filtrando.
- Relatorio por versao (quem leu / quem falta) para `notas-tecnicas.leituras`, com exportacao
  via `useExport` existente.
- Commit: `✨ feat(notas-tecnicas): Fase 3 - confirmacao e relatorio de leitura`.

### Fase 4 — Busca dentro do PDF
- `poppler-utils` no `apk add` de `docker/Dockerfile.queue` (e da imagem swoole, se o job rodar
  la) + `spatie/pdf-to-text` no composer (instalar no container — vendor vive na imagem).
- `Jobs/ExtrairTextoNotaTecnicaJob.php` (padrao `EmitirCertificadosTreinamentoJob`: recebe id,
  `onQueue('low')`, timeout 600) disparado ao criar versao.
- Colunas `conteudo_texto` e `conteudo_busca tsvector` (`to_tsvector('portuguese',
  unaccent(...))`) + GIN, consolidadas na migration da Fase 0.
- Busca do workspace passa a combinar trigram (titulo/codigo) com `websearch_to_tsquery`
  (conteudo), indicando "encontrado no conteudo do PDF". Comando artisan para reindexar.
- Commit: `✨ feat(notas-tecnicas): Fase 4 - busca no conteudo do PDF`.

## Verificacao (cada fase)

- Testes Feature em `tests/Feature/NotasTecnicas/` (padrao `CedecPrefeituraControllerTest`:
  `DatabaseTransactions`, `Permission::firstOrCreate`, `AssertableInertia`, `Storage::fake`):
  permissao nega/permite, stream exige permissao e devolve `inline` com `application/pdf`,
  rascunho invisivel para leitor, codigo sequencial, ciencia idempotente. Rodar no host
  (`php artisan test --filter=NotaTecnica`) — **nao entram no commit** (regra 10).
- `npm run build` passa.
- Navegador (`/notas-tecnicas`): clicar numa nota abre o PDF no leitor nativo (sumario, zoom,
  busca, imprimir); confirmar no DevTools que nenhum CSP bloqueou o iframe; dark e light.
- Metrica de layout (skill frontend, 03): snippet contra `/rat`, `gaps` = 24, `left`/`width`
  iguais.
- Transbordo zero em 375px e 840px (`scrollWidth - clientWidth === 0`); em 375px clicar abre o
  PDF em outra aba.
- Apos mudar PHP no container: `octane:reload`; classe/modulo novo exige `composer dump-autoload`
  no container.

## Depois da aprovacao

Copiar este plano para `docs/superpowers/plans/2026-11-03-notas-tecnicas.md` no repo NewSDC
(padrao da skill specs) e trabalhar numa branch/worktree propria a partir do `dev`.
