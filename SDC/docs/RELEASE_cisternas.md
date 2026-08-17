# Release — Modulo Cisterna: migracao do legado

**Branch:** `feat/cisterna-modulo-backend-etl`
**Periodo:** 14 a 17 de agosto de 2026
**Escopo:** backend completo e carga do legado. **Frontend nao incluido** (ver secao 7).

| | |
|---|---|
| Commits | 45, sem merge |
| Arquivos | 143 alterados |
| Linhas | +27.326 / -1.937 |
| Testes | 211 verdes, 616 asserts |
| Registros migrados | 11.396 documentos do legado -> 39.696 linhas de dominio |

Distribuicao dos commits: 15 `feat`, 11 `docs`, 10 `fix`, 5 `config`, 2 `db`, 1 `security`, 1 `remove`.

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

- Suite do modulo: **211 testes verdes**
- Pint limpo no diretorio do modulo
- Zero referencia remanescente a `TipoCisterna`, `StatusCisterna`, `CisternaPolicy`, `CisternaDTO` ou a tabela `cisternas`
- Estrutura no padrao do projeto: `Requests/` e `Resources/` na raiz do modulo, policies em `app/Policies/`, sem `Http/` nem `Exports/`
- Build do Vite passa

### 6.1 Duas ressalvas honestas

**O `--dry-run` nao valida a etapa do COMPDEC.** Ela se liga a vistoria do fornecedor, que o dry-run nao escreve, entao **680 de 858 linhas acusam erro por construcao**. O numero nao deve ser lido como defeito de dado.

**A suite completa do projeto tem 1 erro e 5 falhas fora deste modulo** (`Pae`, `AjudaHumanitaria`, `PlanCon`). Sao **pre-existentes** e todas tem a mesma causa: os testes leem dado pre-existente do banco em vez de semear o proprio, e `ajuda_h_estoque_saldos` e `planos_contingencia` estao com 0 linhas. E a mesma fragilidade que obrigou a corrigir 7 testes deste modulo durante a carga.

---

## 7. O que NAO esta nesta release

**O frontend.** As 11 paginas Inertia que os controllers referenciam **nao existem**:

```
Cisterna/Beneficiarios/{Index,Create,Show,Edit}
Cisterna/Comunidades/Index      Cisterna/Lotes/Index
Cisterna/Notificacoes/Index     Cisterna/OrdensServico/Index
Cisterna/Vistorias/{Index,Show} Cisterna/QrCode/Ficha
```

Consequencia: **`/cisternas/beneficiarios` responde 200 no servidor e quebra no navegador.** As 4 paginas do scaffold foram removidas por modelarem o dominio inventado.

Plano em `docs/superpowers/plans/2026-08-17-cisterna-frontend.md`: 32 views do legado (9.105 linhas) mapeadas para as 11 paginas, em 5 fases.

---

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
