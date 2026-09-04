# Modulo CEDEC — Cadastro Prefeitura (design)

Data: 2026-09-04
Origem: modulo `mod_cedec` dos legados `gestaocedec` e `sdc`
Destino: `NewSDC/SDC`, modulo novo `Cedec` + correcao de divida no `Compdec`

## 1. Problema

A CEDEC estadual mantem hoje, no legado `gestaocedec`, a tela "Cadastro Prefeitura":
busca de municipio, edicao dos dados da prefeitura e relatorios de contato. O NewSDC
tem metade da peca, mal encaixada, e o dado de prefeitura vive em tres lugares
divergentes no legado.

### 1.1 O que existe no legado

Entrada: `mod_cedec/backEnd/View/index/index.php` — quatro tiles, cada um atras de
`Usuario::getPermissao('cedec_permissao', <slug>)`: `cad_prefeitura`, `ger_demanda`,
`agua_doce`, `defesa_agora`. Este design cobre apenas o primeiro.

Fluxo do `controller=municipio`:

| Acao | Arquivo | Comportamento |
| --- | --- | --- |
| `index` | `View/municipio/index.php` | dois botoes: Dados Municipio, Relatorios |
| `buscar` | `View/municipio/buscar.php` | busca por nome com `LIKE`, `LIMIT 15`, tabela com lapis |
| `cadastrar` | `View/municipio/cadastrar.php` | formulario de 18 campos; macrorregiao e territorio em array PHP hardcoded (linhas 20-47) |
| `rel` | `View/municipio/relatorio/relbusca.php` | tres botoes; o de Telefones e `href="#"` e nunca foi implementado |
| `rel_email` | `View/municipio/relatorio/rel_email.php` | tabela de e-mails, exclui `id_municipio != 7221` |
| `rel_email_ca` | `View/municipio/relatorio/rel_email_ca.php` | e-mails concatenados com `;` em blocos de 50 — limite do Outlook da Cidade Administrativa |

Logica de dados em `core/classe/Classe.Municipio.php`: `dadosMunicipio()` (join
`cedec_meso` + `cedec_micro` + `cedec_prefeitura`), `alterar()`, `rel_email()`.

`mod_cedec/app/prefeitura/cadastro.php` e codigo morto: e um formulario de
Arquivamento de Oficio copiado que nunca foi ligado a rota nenhuma.

O legado `sdc` (Laravel) nao tem modulo CEDEC; tem apenas
`app/Models/Compdec/Prefeitura.php` apontando para `cedec_prefeitura`.

### 1.2 O banco legado (medido em `gestaocedec_local`, 161 tabelas)

Tres fontes para o mesmo dado:

| Fonte | Linhas | Campos de contato | Estado real |
| --- | --- | --- | --- |
| `cedec_municipio` | 854 | `email`, `tel`, `fax`, `tel_pref`, `cel_pref`, `prefeito`, `endereco`, `bairro`, `cep`, `aliquota_iss`, `resp_cob_iss`, `num_lei_iss`, `cobra_iss` | e a tabela que a tela do CEDEC edita. 716 e-mails institucionais, 854 prefeitos atuais, 366 telefones, 147 com `cobra_iss` |
| `cedec_prefeitura` | 854 | `tel1`, `tel2`, `cel1`, `cel2`, `fax`, `email`, `email2`, `email3`, `partido`, `fotoPref`, `prefeiro` | abandonada. 853 linhas preenchidas mas com e-mails `yahoo`/`hotmail`, `tel2 = "-"` e `prefeiro` de mandatos anteriores. Valor unico: 423 fotos em `fotoPref` |
| `com_comdec` | 854 | `email`, `email2`, `email3`, `fone_com1`, `fone_com2` | e da COMPDEC municipal, nao da prefeitura |

Divergencia medida: **764 dos 854 e-mails diferem** entre `cedec_municipio.email` e
`cedec_prefeitura.email`.

`cedec_telefone` existe e tem **zero linhas** — o relatorio de telefones nunca saiu do
`href="#"`.

`id_municipio = 7221` e a sentinela "MUNICIPIO TESTE", com `Codmundv = 0` e e-mail
invalido `prefeitura@prefeitura@gmail.com1`. Os 853 municipios reais tem `Codmundv`
preenchido, entao a ponte para `municipios.codigo_ibge` fecha em 100%.

Tabelas de referencia: `cedec_meso` (12), `cedec_micro` (66), `cedec_rpm` (19 REDECs),
`cedec_rpm_mun` (854), `cedec_municipio_ibge` (5565).

### 1.3 O que o NewSDC ja tem, e os tres defeitos

Existe: `municipios` (catalogo IBGE canonico, cache de tres camadas em
`app/Models/Municipio.php`), `cedec_municipio` (espelho do legado, ponte
`Codmundv = codigo_ibge`), e um CRUD de prefeitura completo no modulo Compdec —
`PrefeituraController`, `PrefeituraService`, `PrefeituraDTO`, `Prefeitura`,
`UpsertPrefeituraRequest`, `PrefeituraPolicy`, `PrefeituraForm.vue`,
`PrefeituraTab.vue`.

Defeitos:

1. **Escopo errado.** O CRUD e chaveado por orgao (`/orgaos/{orgao}/prefeitura`) — e a
   aba onde o municipio preenche o proprio cadastro. Nao existe a visao estadual das
   853 prefeituras que a CEDEC usa.
2. **Faltam os contatos institucionais.** `compdec_prefeituras` so tem `prefeito_*`.
   Nao tem `tel_prefeitura`, `fax_prefeitura` nem `email_prefeitura` — que e exatamente
   o campo que alimenta o relatorio de e-mails.
3. **O ETL esta quebrado.** `PrefeituraService::migrarLegado()` faz
   `SELECT c.prefeito_nome, c.prefeito_telefone, c.prefeito_celular, c.prefeito_email,
   c.inss_tem_cobranca, c.inss_aliquota, c.inss_lei_cobranca, c.inss_responsavel
   FROM com_comdec c`. Nenhuma dessas colunas existe em `com_comdec` (verificado no
   `information_schema`). A migracao estoura.

Divida de UI: `Components/Organisms/Compdec/PrefeituraForm.vue` importa `TextInput` e
`SelectInput` crus e carrega `<style scoped>` com `.form-section` / `.form-grid`
proprios. E a divergencia que `.claude/kernel.py` nomeia explicitamente na secao
"Identidade visual dos modulos (OBRIGATORIA)".

## 2. Decisoes

| Decisao | Escolha | Motivo |
| --- | --- | --- |
| Fonte de verdade | estender `compdec_prefeituras` | `municipio_id` ja e `unique`: uma prefeitura por municipio. A aba do Compdec e a tela estadual passam a ler a MESMA linha. `cedec_prefeitura` morre apos a carga |
| Onde mora a tela | modulo novo `Cedec` | publico distinto (CEDEC estadual vs municipio), espelha o modulo do legado e abre espaco para os outros tres tiles depois |
| Model | fica em `App\Modules\Compdec\Models\Prefeitura` | DDD como o repo pratica: model no modulo dono, consumido por import direto. Precedente: `Compdec\Models\Orgao` e importado por Pmda, PlanCon e AjudaHumanitaria. Nada vai para `App\Models` |
| Nome da tabela | segue `compdec_prefeituras` | renomear seria churn cosmetico sem ganho |
| Indicadores municipais | read-only na fase 1 | `populacao`, `pop_rural`, `area`, `macroregiao`, `territorio_desenv`, `distancia_bh`, `qtd_pipa` sao referencia IBGE/CEDEC e vivem em `municipios` / `cedec_municipio`. O formulario legado misturava dominios |
| ETL | corrigir neste escopo | sem ETL correto a tela nasce vazia ou com o dado podre de `cedec_prefeitura` |
| Entrega | spec + plano em 5 fases | cada fase verificavel e commitavel sozinha (regra de commits atomicos) |

**Capacidade removida, explicitamente:** a tela legada permite a CEDEC editar os
indicadores municipais. Na fase 1 eles ficam read-only, com a origem indicada em tela.
Se o uso real exigir edicao, entra como fase 6 e obriga a tornar
`ImportCedecMunicipioCommand` nao-destrutivo nesses campos.

## 3. Dados

Colunas novas em `compdec_prefeituras`, **consolidadas na migration de criacao**
`database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php` (regra de
ouro 9: consolidar na migration principal, nao criar `add_*`):

```
email_prefeitura      varchar(255)  nullable   -- institucional; alimenta o relatorio
email_prefeitura_2    varchar(255)  nullable
email_prefeitura_3    varchar(255)  nullable
tel_prefeitura        varchar(20)   nullable
tel_prefeitura_2      varchar(20)   nullable
fax_prefeitura        varchar(20)   nullable
prefeito_partido      varchar(60)   nullable
```

Indices: `municipio_id` unique ja existe. A listagem estadual ordena e filtra por nome
de municipio via join em `municipios`, que ja tem `index(['uf','nome'])`. Adicionar
indice em `email_prefeitura` nao se justifica — o filtro e por nulidade sobre 853
linhas.

`Prefeitura::$fillable` e `$casts` recebem as sete colunas. `PrefeituraDTO` recebe os
campos correspondentes em camelCase e o mapeamento em `fromRequest()` / `toArray()`.

## 4. Backend

```
app/Modules/Cedec/
  Controllers/PrefeituraController.php        index, edit, update, uploadFoto, removerFoto
  Controllers/ContatoRelatorioController.php  emails, outlook, telefones, export
  Services/CedecPrefeituraService.php         listagem paginada com filtros; upsert por municipio
  Services/ContatoRelatorioService.php        blocos de N, CSV, higienizacao
  Requests/UpdatePrefeituraRequest.php
  Resources/PrefeituraListaResource.php
  DTOs/PrefeituraFiltroDTO.php
  Enums/Macrorregiao.php
  Enums/TerritorioDesenvolvimento.php
  Console/ImportarPrefeiturasCommand.php
routes/modules/cedec.php
```

Os dois enums substituem os arrays PHP hardcoded de `cadastrar.php:20-47` (10
macrorregioes, 17 territorios de desenvolvimento).

`CedecPrefeituraService` opera por `municipio_id` — nao por orgao. `PrefeituraService`
do Compdec continua intacto para a aba municipal; os dois convergem na mesma linha de
`compdec_prefeituras` via `updateOrCreate(['municipio_id' => ...])`.

A foto reusa o que ja existe: `Prefeitura::MEDIA_FOTO_PREFEITO` com Spatie Media
Library, colecao `singleFile`, conversao `thumb` 200x200, disco `config('compdec.disk')`.
Nao ha logica nova de upload.

Rotas, prefixo `/cedec`:

```
GET    /cedec/prefeituras                  cedec.prefeituras.index
GET    /cedec/prefeituras/{municipio}/edit cedec.prefeituras.edit
PUT    /cedec/prefeituras/{municipio}      cedec.prefeituras.update
POST   /cedec/prefeituras/{municipio}/foto cedec.prefeituras.foto.upload
DELETE /cedec/prefeituras/{municipio}/foto cedec.prefeituras.foto.destroy
GET    /cedec/contatos                     cedec.contatos.index
GET    /cedec/contatos/export              cedec.contatos.export
```

O binding e por `Municipio`, nao por `Prefeitura`: a CEDEC navega pelos 853 municipios,
inclusive os que ainda nao tem linha de prefeitura.

## 5. Permissoes

Bloco novo em `config/permissions.php`, secao `modules`, espelhando os slugs do legado:

| Slug novo | Slug legado em `cedec_permissao` |
| --- | --- |
| `cedec.prefeituras.view` | `cad_prefeitura` |
| `cedec.prefeituras.edit` | `alterar_prefeitura` |
| `cedec.prefeituras.export` | (novo) |
| `cedec.contatos.view` | `relatorio` |

Em `role_permissions`: perfis CEDEC estaduais recebem os quatro. Perfis municipais e
COMPDEC nao recebem nenhum — continuam com `compdec.prefeitura.*` sobre a propria
linha. Item no `Sidebar.vue` atras de `cedec.prefeituras.view`, com
`moduleIcon('prefeituras')` apontando para `apartment.svg`, que ja esta no catalogo
`ICONS` e nao esta mapeado em `MODULE_ICONS`.

## 6. Frontend — Atomic Design

```
Pages/Cedec/Prefeituras/Index.vue              orquestra
Pages/Cedec/Prefeituras/Edit.vue               orquestra
Pages/Cedec/Contatos/Index.vue                 abas: e-mails, Outlook CA, telefones

Organisms/Cedec/PrefeituraFiltersSection.vue   sobre CollapsibleSection
Organisms/Cedec/PrefeituraTable.vue            tabela md+, BLOCO no mobile
Organisms/Cedec/PrefeituraFormSections.vue     sobre Molecules/Form/*
Organisms/Cedec/IndicadoresMunicipaisPanel.vue read-only, origem indicada
Organisms/Cedec/ContatoBlocosOutlook.vue
Molecules/Cedec/PrefeituraStatCards.vue        sobre StatCardsGrid + StatCard
Molecules/Cedec/ContatoBloco.vue               bloco de N contatos + copiar
```

Reuso obrigatorio, sem padrao visual paralelo: `Organisms/PageHeader.vue` com
`variant="gradient"` e `:icon-image="moduleIcon('prefeituras')"`;
`Molecules/Statistics/StatCard.vue` dentro de `StatCardsGrid.vue`, com **card servindo
de filtro rapido** (sem e-mail, sem telefone, sem foto, por REDEC);
`Molecules/CollapsibleSection.vue` com `namespace="cedec"`; `Molecules/Form/*`
(`FormField`, `FormSelect`, `ToggleField`, `FormActions`);
`Molecules/Navigation/Pagination.vue` com props achatadas (`current_page`, `last_page`,
`per_page`, `total`, `from`, `to`); `Molecules/ListEmptyState.vue`;
`Atoms/Button/ActionButton.vue`; `Organisms/ExportCsvModal.vue`; `useMobile`;
`useCopiarTexto`.

Unico componente genuinamente novo: `ContatoBloco` — nao existe equivalente para
"N contatos concatenados com `;` mais botao de copiar".

Contrato de camada: atomo e molecula nao chamam API nem estado global; organismo
concentra interacao; a pagina orquestra.

Regras duras de `.claude/skills/frontend/03 - Layout` e `04 - Responsividade`: a calha
horizontal e do `<main>`, a raiz da pagina nao leva `p-*`;
`scrollWidth - clientWidth === 0` em 375px e 840px; quem transborda rola dentro de si
(coluna de e-mail e o caso obvio); breakpoint por `useMobile` alinhado em `lg`; tabela
vira bloco no mobile; paginacao so com setas; dark mode por CLASSE, nunca
`prefers-color-scheme`; nenhum `<style scoped>` novo.

### 6.1 Correcao de divida inclusa

`Organisms/Compdec/PrefeituraForm.vue` e reescrito sobre `Molecules/Form/*` e
`CollapsibleSection`, com o `<style scoped>` deletado, e passa a ser compartilhado
entre a aba do Compdec e o Edit do Cedec. Um formulario, dois donos — o mesmo caminho
que o kernel prescreve depois do estrago de `RatPageHeader` e
`RatCollapsibleSection`.

Armadilha ja paga a respeitar: `SelectInput` le `value`/`id` e `label`/`name`/`text`.
O backend que manda `nome` (municipios) precisa ser mapeado para `{value, label}`.
Atributo solto (`inputmode`, `maxlength`) passado a `FormField` cai na div raiz e nao
chega ao input — precisa ser prop declarada e repassada.

## 7. ETL — correcao de `migrarLegado()`

Origem correta e precedencia por campo:

| Destino em `compdec_prefeituras` | Origem | Fallback |
| --- | --- | --- |
| `prefeito_nome` | `cedec_municipio.prefeito` | nenhum. Nunca `cedec_prefeitura.prefeiro`, que esta no mandato anterior |
| `prefeito_telefone` | `cedec_municipio.tel_pref` | — |
| `prefeito_celular` | `cedec_municipio.cel_pref` | — |
| `prefeito_partido` | `cedec_prefeitura.partido` | — |
| `email_prefeitura` | `cedec_municipio.email` | `cedec_prefeitura.email` se vazio |
| `email_prefeitura_2` / `_3` | `cedec_prefeitura.email2` / `email3` | — |
| `tel_prefeitura` | `cedec_municipio.tel` | `cedec_prefeitura.tel1` se vazio |
| `tel_prefeitura_2` | `cedec_prefeitura.tel2` | — |
| `fax_prefeitura` | `cedec_municipio.fax` | `cedec_prefeitura.fax` se vazio |
| `endereco`, `bairro`, `cep`, `latitude`, `longitude` | `cedec_municipio` | — |
| `inss_tem_cobranca` | `cedec_municipio.cobra_iss` | — |
| `inss_aliquota` | `cedec_municipio.aliquota_iss` | — |
| `inss_lei_cobranca` | `cedec_municipio.num_lei_iss` | — |
| `inss_responsavel` | `cedec_municipio.resp_cob_iss` | — |
| `foto_prefeito` (media) | `cedec_prefeitura.fotoPref` | — |

Ponte para o NewSDC: `cedec_municipio.Codmundv = municipios.codigo_ibge` — o mesmo
padrao que `ImportCedecMunicipioCommand` documenta. Exclui `id_municipio = 7221`.

Higienizacao: `trim` em tudo; e-mail para minusculas; `"-"`, `""` e string so de
espacos viram `null`; telefone normalizado; e-mail sintaticamente invalido vai para
`null` e gera linha em `compdec_etl_log` com `acao = 'skipped'`.

Mantem `compdec_etl_log` e a flag `--dry-run` que o servico ja tem. Command:
`php artisan cedec:importar-prefeituras`.

## 8. Relatorios de contato

Uma pagina com tres abas, servida por `ContatoRelatorioService`:

- **E-mails**: tabela municipio + e-mail institucional, com contagem de preenchidos.
- **Outlook CA**: e-mails concatenados com `;` em blocos de tamanho configuravel,
  default 50, porque e o limite de destinatarios por envio do Outlook da Cidade
  Administrativa. Cada bloco tem botao de copiar via `useCopiarTexto`. O legado ja
  fazia isso, so nao dava como copiar.
- **Telefones**: o `href="#"` que nunca foi implementado. Sai de
  `tel_prefeitura`, `tel_prefeitura_2`, `prefeito_telefone`, `prefeito_celular`,
  `fax_prefeitura`, com blocos e copiar iguais.

Export CSV das tres abas via `ExportCsvModal`, atras de `cedec.prefeituras.export` —
esse slug cobre toda exportacao do modulo, tanto da listagem quanto dos relatorios,
para nao multiplicar slug de export.

## 9. Verificacao

Feature (Pest, no container `newsdc_frankenphp_local`):

- listagem: filtro por nome, por REDEC, por "sem e-mail", paginacao;
- autorizacao: 403 sem `cedec.prefeituras.view`; 403 no update sem `.edit`;
- update valido; update invalido (CEP fora do formato, e-mail malformado, latitude fora de faixa);
- **fronteira dos blocos**: 50, 51, 100 e 101 contatos geram 1, 2, 2 e 3 blocos;
- CSV: cabecalho e contagem de linhas;
- foto: mime rejeitado, tamanho acima do limite rejeitado, upload e remocao felizes;
- ETL: `--dry-run` nao escreve; fixtures sujas (`tel2 = "-"`, e-mail com espaco a
  esquerda, `Codmundv` sem par em `municipios`, sentinela 7221) caem nos ramos certos e
  aparecem em `compdec_etl_log`.

E2E: `scrollWidth - clientWidth === 0` no Index e no Edit em 375px e 840px.

Comandos: `php artisan test --filter=Cedec`, `npm run build`, `php -l` nos arquivos
tocados.

## 10. Fases

| Fase | Conteudo | Verificacao |
| --- | --- | --- |
| 1 | Colunas consolidadas na migration, `Prefeitura` e `PrefeituraDTO` atualizados, ETL corrigido, command de importacao | teste de ETL com fixtures sujas; `--dry-run` |
| 2 | Modulo `Cedec` backend: service, controller, request, resource, DTO de filtro, enums, rotas, permissoes, sidebar | teste de autorizacao e de listagem |
| 3 | `Pages/Cedec/Prefeituras/Index.vue` + organismos de filtro, tabela e stat cards | E2E 375/840; card como filtro |
| 4 | `Edit.vue`, `PrefeituraFormSections`, painel read-only de indicadores, foto, e refit do `PrefeituraForm.vue` do Compdec | teste de update e de foto; aba do Compdec segue funcionando |
| 5 | Relatorios: tres abas, blocos, copiar, export CSV | teste de fronteira dos blocos e do CSV |

## 11. Fora de escopo

Os outros tres tiles do modulo CEDEC legado — Gerenciador de Demanda, Agua Doce Agora
e Defesa Civil Agora (`cedec_def_agora`, 8852 linhas) — nao entram. O modulo `Cedec`
nasce preparado para receber cada um como fase propria.

Edicao dos indicadores municipais fica para uma fase 6, se o uso real exigir.
