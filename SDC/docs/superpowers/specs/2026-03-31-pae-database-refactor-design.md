# PAE Formulário — Refatoração do Banco de Dados

**Data:** 2026-03-31
**Status:** Aprovado
**Escopo:** Módulo PAE — formulário RAT (4 abas)

---

## Problema

O banco atual tem gaps que fazem campos do frontend serem descartados silenciosamente:

1. `pae_forms` não tem `municipio_id` — campo do dropdown da Aba 1 é perdido no DTO
2. `pae_forms` não tem `pae_empnto_id` — formulário flutua sem vínculo com o empreendimento
3. `protocolo_id` é string referenciando `pae_protocolos.num_protocolo` — FK suja, deveria ser integer
4. `pae_form_itens` usa coluna `categoria` para misturar apontamentos e conclusões — queries carregam tudo sempre, status hardcoded como 'CONFORME'
5. Controller `show()` não passa `empreendimento` ao Inertia — prop nunca populada no frontend

---

## Abordagem

**Opção B — Separar itens por tipo + corrigir campos faltantes.**

Adicionar colunas faltantes em `pae_forms`, substituir `pae_form_itens` por duas tabelas dedicadas (`pae_form_apontamentos` e `pae_form_conclusao`). DTOs e service atualizados por seção.

---

## Mapeamento Frontend → Banco

### Aba 1 — Informações Gerais → `pae_forms`

| Frontend (`infoGerais`)     | Coluna DB                 | Situação atual     |
|-----------------------------|---------------------------|--------------------|
| `barragem`                  | `barragem_nome`           | OK                 |
| `municipio_id`              | `municipio_id` (FK)       | **FALTANDO**       |
| `coordenador_pae`           | `coord_pae_nome`          | OK                 |
| `email`                     | `coord_pae_email`         | OK                 |
| `coordenador_mun_def_civ`   | `coord_mun_def_civ`       | OK                 |
| `coordenador_mun_compdec`   | `coord_mun_compdec`       | OK                 |
| `empreendedor_res`          | `emp_responsavel_nome`    | OK                 |
| `metodo_construtivo`        | `metodo_construtivo`      | OK                 |
| `numero_zas`                | `num_zas`                 | OK                 |
| `nivel_emergencia`          | `nivel_emergencia`        | OK                 |
| `pae_empnto_id` (implícito) | `pae_empnto_id` (FK)      | **FALTANDO**       |

### Aba 2 — Objetivo e Contexto → `pae_forms`

| Frontend (`objetivoContexto`) | Coluna DB       | Situação atual |
|-------------------------------|-----------------|----------------|
| `objetivo`                    | `objetivo`      | OK             |
| `contextualizacao`            | `contexto`      | OK (alias)     |

### Aba 3 — Apontamentos Técnicos → `pae_form_apontamentos` (nova)

| Frontend                      | Coluna DB    | Situação atual                  |
|-------------------------------|--------------|---------------------------------|
| `items[].text`                | `conteudo`   | OK (via pae_form_itens)         |
| `items[].children[].text`     | `conteudo`   | OK (via parent_id)              |
| ordem implícita (array index) | `ordem`      | OK                              |
| status                        | `status`     | Hardcoded 'CONFORME' — inativo  |

### Aba 4 — Conclusão → `pae_form_conclusao` (nova)

Mesma estrutura de apontamentos.

---

## Estrutura de Banco

### Alterações em `pae_forms`

```
- DROP  protocolo_id VARCHAR(50) + fk_forms_protocolo
+ ADD   pae_protocolo_id  BIGINT UNSIGNED NULL  FK → pae_protocolos.id
+ ADD   municipio_id      BIGINT UNSIGNED NULL  FK → municipios.id
+ ADD   pae_empnto_id     BIGINT UNSIGNED NULL  FK → pae_empntos.id
```

### Nova tabela `pae_form_apontamentos`

```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
pae_form_id     BIGINT UNSIGNED NOT NULL  FK → pae_forms.id CASCADE DELETE
parent_id       BIGINT UNSIGNED NULL      FK → pae_form_apontamentos.id CASCADE DELETE
status          VARCHAR(50)  DEFAULT 'CONFORME'
ordem           INT          DEFAULT 0
conteudo        TEXT         NULL
updated_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

INDEX idx_apontamentos_form    (pae_form_id)
INDEX idx_apontamentos_parent  (parent_id)
```

### Nova tabela `pae_form_conclusao`

Mesma estrutura de `pae_form_apontamentos` (self-referenciando `pae_form_conclusao.id`).

### `pae_form_itens`

Tabela mantida no banco (sem drop) para segurança, mas o código para de usá-la.

---

## Arquivos Modificados

### Migrations (edição direta — regra consolidação)

- `2026_02_12_130001_create_pae_forms.php` — remover `protocolo_id`, adicionar `pae_protocolo_id`, `municipio_id`, `pae_empnto_id`
- `2026_02_12_130429_create_pae_form_itens_table.php` — substituir criação de `pae_form_itens` pelas duas novas tabelas

### Models

- `app/Modules/Pae/Models/PaeForm.php` — atualizar `$fillable`, trocar relação `itens()` por `apontamentos()` e `conclusao()`, atualizar `protocolo()`
- `app/Modules/Pae/Models/PaeFormItem.php` → **substituído** por:
  - `app/Modules/Pae/Models/PaeFormApontamento.php`
  - `app/Modules/Pae/Models/PaeFormConclusaoItem.php`

### DTOs

- `app/Modules/Pae/DTOs/PaeFormInfoGeraisDTO.php` — adicionar `municipioId`, `paeEmpntoId`; mapear no `toArray()`

### Service

- `app/Modules/Pae/Services/PaeFormularioService.php`:
  - `create()` — incluir `municipio_id`, `pae_empnto_id`
  - `syncItens()` → dividido em `syncApontamentos()` e `syncConclusao()`
  - `updateApontamentos()` usa `PaeFormApontamento`
  - `updateConclusao()` usa `PaeFormConclusaoItem`
  - `formatForView()` carrega via `$form->apontamentos` e `$form->conclusao`

### Controller

- `app/Modules/Pae/Controllers/PaeFormularioController.php`:
  - `validateInfoGerais()` — adicionar `municipio_id` e `pae_empnto_id`
  - `show()` — passar `empreendimento` ao Inertia

### Frontend

- `resources/js/composables/pae/usePaeFormulario.js`:
  - Adicionar `pae_empnto_id` em `infoGerais` (preenchido via `empreendimento?.id`)

---

## Regras de Negócio

- `pae_empnto_id` é preenchido no `store()` (criação) a partir do prop `empreendimento` recebido via Inertia. Não é campo editável pelo usuário.
- `pae_protocolo_id` permanece nullable — o formulário pode existir sem protocolo vinculado.
- `municipio_id` é nullable — o analista pode salvar info gerais sem selecionar município.
- Status dos itens (`CONFORME`) mantido como default — funcionalidade de status por item fica para iteração futura.
- `PaeFormItem` (arquivo) pode ser deletado após confirmar que não há outras referências no código.

---

## Fora de Escopo

- UI de status por item (conforme/não conforme) — não alterado nesta iteração
- Criação de novo formulário vinculado a protocolo via `PaeProtocoloController`
- Impressão/exportação do relatório
