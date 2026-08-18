# Release — Modulo Cisterna: migracao do legado

**Branch:** `feat/cisterna-modulo-backend-etl`
**Periodo:** 14 a 18 de agosto de 2026
**Escopo:** modulo completo -- dominio, carga do legado e interface.

| | |
|---|---|
| Commits | 55, sem merge |
| Arquivos | 181 alterados |
| Linhas | +32.130 / -1.937 |
| Testes | 241 verdes, 1.045 asserts |
| Registros migrados | 11.396 documentos do legado -> 39.696 linhas de dominio |
| Paginas de interface | 11 |

Distribuicao dos commits: 18 `feat`, 13 `fix`, 12 `docs`, 5 `config`, 2 `style`, 2 `security`, 2 `db`, 1 `remove`.

---

## 1. O que foi entregue

O modulo saiu de um **scaffold com dominio inventado** para o dominio real do legado.

O scaffold modelava `codigo`, `capacidade_litros` e `tipo` (comunitaria/individual/escolar) — nada disso existe no sistema legado. O legado e **cadastro de beneficiario mais fiscalizacao de instalacao em tres etapas**, e e isso que foi implementado.

### 1.1 Dominio

8 tabelas novas, mais 2 efemeras de ETL:

| Tabela | Papel |
|---|---|
| `cisterna_beneficiarios` | o cadastro, 54 campos |
| `cisterna_comunidades` | comunidade por municipio |
| `cisterna_lotes` / `cisterna_ordens_servico` | agrupamento de instalacao |
| `cisterna_atendimentos_pipa` | os 5 responsaveis, normalizados em linhas |
| `cisterna_vistorias` | fiscalizacao, uma linha por etapa |
| `cisterna_itens_conferidos` | checklist de 13 itens, polimorfico |
| `cisterna_notificacoes` | notificacao de fiscalizacao, polimorfica |
| `cisterna_legado_raw` / `cisterna_etl_log` | area de pouso jsonb e auditoria da carga |

Especificos do Postgres em uso: unique parcial, `COUNT(*) FILTER`, GIN `jsonb_path_ops`, CHECK constraints e sequence dedicada para o numero de instalacao.

### 1.2 Aplicacao

- 6 enums de dominio, com `doLegado()` para absorver a grafia livre do legado
- 8 models, 9 services, 2 observers, 7 controllers, 7 resources, 6 policies
- Escopo territorial por perfil num unico ponto: CEDEC ve todos os municipios habilitados, COMPDEC so o proprio, fornecedor so obra em envio ou instalada
- ETL em duas etapas (extracao para jsonb, refino para o dominio), idempotente por `legacy_id`
- QR Code via `endroid/qr-code`, export CSV de 39 colunas

---

## 2. Carga do legado: numeros medidos

**11.396 documentos processados, 12 erros.** Todos os 12 dependem de decisao da area, nenhum e defeito de codigo.

| Tabela de dominio | Linhas |
|---|---|
| `cisterna_beneficiarios` | **8.096** (7.580 ativos, 516 duplicados) |
| `cisterna_itens_conferidos` | **27.677** |
| `cisterna_atendimentos_pipa` | 2.904 |
| `cisterna_vistorias` | 2.129 (791 fornecedor, 680 compdec, 658 cedec) |
| `cisterna_comunidades` | 840 |
| `cisterna_notificacoes` | 7 |
| `cisterna_ordens_servico` | 7 |
| `cisterna_lotes` | 3 |

Rodar o ETL duas vezes seguidas nao duplica nada: a segunda passada faz 11.102 `updated` e **zero** `inserted`.

### 2.1 Os 12 nao importados

| Caso | Qtd | Por que |
|---|---|---|
| Mesmo CPF em pessoas diferentes | 5 | erro de digitacao na origem. Marcar como duplicata apagaria um beneficiario real da lista ativa |
| CPF truncado pela mascara do formulario | 4 | `048.793.606-0_`, `0752515600`, `Preencher` — falta digito, irrecuperavel |
| Comunidade com municipio literal `"Municipio"` | 3 | placeholder de formulario quebrado, sem `codmundv` |

---

## 3. Defeitos do legado corrigidos na migracao

20 defeitos foram catalogados no spec. Os de maior impacto:

**Sem UNIQUE de CPF.** A origem tem **492 CPFs repetidos em 1.003 linhas** — a garantia era um `count()` em PHP antes do insert, que falha sob concorrencia. Agora ha unique parcial (um ativo por CPF), e a resolucao respeita a decisao humana: em 195 grupos o proprio legado ja havia marcado qual cadastro vale, e essa marcacao vence a regra automatica.

**Sem UNIQUE de `(beneficiario, etapa)` na vistoria.** O legado nao prevenia double-submit: **65 relatorios de fornecedor e 17 de CEDEC sao reenvio do mesmo formulario**. A deduplicacao mantem a linha mais completa.

**Comunidade casada so por nome.** Misturava municipios. Agora a chave e o par `(municipio, nome)` — 58 nomes de comunidade convivem legitimamente em municipios distintos.

**Acao em massa sem escopo territorial.** Um usuario COMPDEC alcancava beneficiario de outro municipio. Buraco de autorizacao fechado com policy dedicada e teste que prova o alcance.

**Numero de instalacao alocado sem atomicidade.** Passou a usar sequence do Postgres, com a sequence alinhada ao maior numero importado (834) ao fim da carga.

---

## 4. Achados que so o dado real revelou

Estes nao estavam no plano. Cada um foi medido antes de corrigir.

**Coordenada em 21 formatos de texto livre.** A coluna era `varchar(150)`. **127 linhas gravaram sem separador decimal** (`-16393269` = `-16.393269`): estouravam `numeric(10,7)` e **derrubavam o INSERT inteiro**, perdendo o cadastro completo por causa de um campo. Outras 1.039 tinham sufixo de mascara (`-05.033800_`) e eram descartadas em silencio. Um parser dedicado levou o aproveitamento de **6.810 para 7.993** coordenadas, e o mesmo problema existia nas vistorias (11 seriam perdidas inteiras).

**`iconv('ASCII//TRANSLIT')` nao resolve acento sozinho nesta plataforma.** Medido no host e no container: `Cerâmica` sai como `Cer^amica` (o diacritico vira caractere separado) e, diante do U+FFFD dos 67 `PROPRIA` truncados, o retorno e `false`. Sem tratar isso, 434 linhas de cobertura ceramica nao casariam com o enum.

**Municipio acentuado nao casava no fallback por nome.** O legado grava `PINTOPOLIS`, o catalogo grava `Pintopolis` com acento — um `LOWER(nome) = ?` falha em todo municipio acentuado, que e a maioria.

**As colunas `img_*` sao legenda, nao caminho de arquivo.** Os valores sao `0`, `-`, `FRENTE`, `.`. O conteudo real esta nas colunas `_lk`, que sao **100% link do Google Drive ou vazio**.

**Mapa de itens perdia as 4 pecas de PVC.** `rel_fornecedor` so tem 9 colunas `_opcao`; te, joelho, luva e cap tem apenas quantidade. Sem o fallback por quantidade, **3.308 registros de item desapareciam**.

---

## 5. Pendencias que exigem decisao da area

| # | Pendencia | Impacto |
|---|---|---|
| 1 | **`cedec_municipio` esta vazia** (0 linhas) | `Municipio::habilitadosCisterna()` faz join nela: **todo select de municipio das telas fica em branco** e nao da para criar cadastro pela interface. Resolver com `legado:importar-cedec-municipio` |
| 2 | **Fotos do imovel nao sao migraveis** | 30.574 URLs do Google Drive, todas de arquivo individual — **nao existe link de pasta raiz no dado**. Recuperar exige acesso a conta dona dos arquivos e varredura por ID via API |
| 3 | **Arquivos do legado fora desta maquina** | `media` do Cisterna = 0. Os 5.264 casos estao registrados no `cisterna_etl_log` com o caminho ou o link de origem |
| 4 | **PDF de folhas de QR Code nao portado** | Nao existe biblioteca de PDF no NewSDC. Era assim que se imprimiam as cartelas de adesivo. **Perda de funcionalidade conhecida** |
| 5 | **`created_by` nulo** nos 8.096 importados | O cruzamento por CPF e por email entre os 43 usuarios do legado e os 55 do NewSDC deu **zero**. A trilha de acoes nao notifica ninguem para dado do legado ate uma reconciliacao |
| 6 | **Export mudou de xlsx para CSV** | Nao ha `maatwebsite/excel` no projeto |
| 7 | **9 beneficiarios e 3 comunidades pendentes** | Ver 2.1. Vivem so no `cisterna_etl_log`; a area precisa de tela ou export para resolver |
| 8 | **`sinc_cisterna_relatorio_cedec` nao portada** | 2 linhas, checklist de 26 itens, sem codigo que a use no legado |

---

## 6. Verificacao

**12 dos 13 criterios do spec atendidos.** O criterio 10 (`at_cisterna` populado) esta bloqueado pela pendencia 1 — depende de rodar o import, nao de codigo.

- Suite do modulo: **241 testes verdes**, 1.045 asserts
- Pint limpo no diretorio do modulo
- Zero referencia remanescente a `TipoCisterna`, `StatusCisterna`, `CisternaPolicy`, `CisternaDTO` ou a tabela `cisternas`
- Estrutura no padrao do projeto: `Requests/` e `Resources/` na raiz do modulo, policies em `app/Policies/`, sem `Http/` nem `Exports/`
- Build do Vite passa, com as 11 paginas no manifest
- Guardas contra as falhas silenciosas encontradas: teste de contrato de props,
  teste de ausencia do wrapper `data` em colecao aninhada, e teste de que a ficha
  publica nao trafega dado pessoal. As tres classes de defeito respondiam 200 no
  servidor e nao apareciam em log.

### 6.1 Duas ressalvas honestas

**O `--dry-run` nao valida a etapa do COMPDEC.** Ela se liga a vistoria do fornecedor, que o dry-run nao escreve, entao **680 de 858 linhas acusam erro por construcao**. O numero nao deve ser lido como defeito de dado.

**Nao houve verificacao em navegador.** Os testes cobrem servidor e contrato de
props; o comportamento visual e de interacao nao foi observado por falta de
ferramenta de browser na sessao. O fluxo de preencher vistoria pelo cliente nunca
rodou ponta a ponta -- ele estava inalcancavel ate a correcao do contrato de props.

**A suite completa do projeto tem 1 erro e 5 falhas fora deste modulo** (`Pae`, `AjudaHumanitaria`, `PlanCon`). Sao **pre-existentes** e todas tem a mesma causa: os testes leem dado pre-existente do banco em vez de semear o proprio, e `ajuda_h_estoque_saldos` e `planos_contingencia` estao com 0 linhas. E a mesma fragilidade que obrigou a corrigir 7 testes deste modulo durante a carga.

---

## 7. Interface

As 11 paginas Inertia do modulo, em cinco fases:

| Fase | Entrega |
|---|---|
| 1 | `Beneficiarios/Index` -- stat cards como filtro, filtros e tabela |
| 2 | `Beneficiarios/{Create,Edit,Show}` -- formulario de 45 campos em 8 secoes, galeria e comprovantes |
| 3 | `Vistorias/{Index,Show}` -- cadeia das tres etapas em timeline e checklist de 13 itens |
| 4 | `Comunidades`, `Lotes`, `OrdensServico`, `Notificacoes` -- index com formulario em modal |
| 5 | `QrCode/Ficha` -- consulta publica, sem login |

### 7.1 Decisoes que reduziram o legado

O legado tinha **32 views Blade, 9.105 linhas**. Tres decisoes cortaram a maior parte da duplicacao:

- **O `menu.blade.php` deixou de existir.** Era pagina separada com 11 contadores linkando para `index?status=N` -- exatamente o padrao de "card e filtro" que o projeto exige em pagina de indice. Os contadores viraram os cards do proprio indice, e o backend ja entregava esses numeros em `indicadores()`.
- **CRUD de apoio em modal.** Comunidades, lotes, OS e notificacoes tinham `create` e `edit` como paginas inteiras: 11 views para 4 entidades, ja divergentes entre si. Viraram 4 telas com modal.
- **Um formulario por etapa de vistoria, em modo duplo.** Cada etapa tinha view para preencher e outra para editar; so a da CEDEC somava 1.216 linhas quase identicas.

### 7.2 Identidade visual

O modulo usa a casca compartilhada: `Organisms/PageHeader` com a arte do modulo, `Molecules/Statistics/StatCard` como filtro rapido, `Molecules/Form/*` nos campos, `Molecules/Table/TableActions` na coluna de acoes e `Organisms/ExportCsvModal` na exportacao.

Duas pecas nasceram genericas para nao repetir divergencia existente:

- `Molecules/CollapsibleSection` -- a versao sem acoplamento do `Rat/Sections/RatCollapsibleSection`, que depende de CSS carregado sob demanda e renderiza sem estilo fora do RAT.
- `Composables/core/useCollapsibleSection` -- namespaced, porque o do RAT fixa a chave de storage e tem caso especial hardcoded.

### 7.3 Defeitos que o code review encontrou

Tres defeitos que respondiam 200 no servidor e nao deixavam rastro em log:

1. **Prop que nunca chegava.** O Vue converte kebab-case para camelCase, mas nao snake_case. Paginas declarando `etapaDisponivel` recebiam `undefined` de `etapa_disponivel`: nenhuma etapa ficava liberada e o botao de preencher vistoria nunca aparecia. Guardado pelo `ContratoDePropsTest`, que compara o que o controller manda com o que o `.vue` declara.
2. **Campo sobrescrito por metodo do form.** O Inertia define `data()` depois de espalhar os campos, entao o campo `data` do lote virava a funcao e a data nao salvava. Provado em execucao: `typeof form.data === 'function'` e `payload.data === undefined`.
3. **Acao com slug inexistente.** A acao `check` do `TableActions` consulta `{modulo}.{recurso}.validar`, que nao esta no `config/permissions.php` -- e o `ActionButton` consulta o RBAC mesmo com `allowed=true`. Responder notificacao era inalcancavel.

## 8. Ferramentas de apoio criadas

| Script | Para que |
|---|---|
| `scripts/test-host.sh` | roda a suite no host contra o Postgres do container, encapsulando 5 armadilhas de ambiente |
| `scripts/artisan-host.sh` | roda artisan no host; a pior armadilha e `bootstrap/cache/config.php`, recriado com `host=db` a cada restart do container |
| `scripts/refresh-container.sh` | faz o container reconhecer classe criada ou removida (`app/` e bind-mount, `vendor/` vive na imagem) |

## 9. Documentos

| Arquivo | Conteudo |
|---|---|
| `docs/superpowers/specs/2026-08-10-cisterna-migracao-legado-design.md` | 25 decisoes de arquitetura, 20 defeitos do legado, ERD |
| `docs/superpowers/plans/2026-08-10-cisterna-modulo-backend-etl.md` | as 19 tasks do backend |
| `docs/superpowers/plans/2026-08-17-cisterna-frontend.md` | as 5 fases do frontend |
| `docs/superpowers/notas/2026-08-10-cisterna-ddl-legado.md` | pendencias da area e verificacao final |
| `docs/superpowers/notas/2026-08-14-cisterna-arquitetura-db-implantada.md` | ERD gerado do banco real |
