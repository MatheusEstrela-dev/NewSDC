# PAE — Numero do Protocolo no Header do Formulario

**Data:** 2026-04-13
**Status:** Aprovado

## Problema

Ao abrir a pagina do formulario PAE (`/pae/protocolo`), o numero do protocolo vinculado (ex: `10.04.2026.003`) nao e exibido em nenhum lugar da interface. O usuario perde o contexto de qual protocolo esta sendo editado.

Causas-raiz identificadas:
1. `PaeFormularioController::show` nao passa `protocolo` para a view Inertia
2. `PaeFormularioService::formatForView` nao inclui `pae_protocolo_id` no array retornado
3. `PaeHeader.vue` nao tem prop para o numero do protocolo
4. `PaeBreadcrumb.vue` exibe apenas "PAE" sem contexto do protocolo atual
5. `PaeProtocoloController::store` redireciona para o index (lista) em vez de ir direto ao formulario apos criar o protocolo

## Solucao — Opcao B (Badge + Breadcrumb)

Estender os componentes e controllers existentes para propagar o `num_protocolo` ate o cabecalho da pagina. Nenhum componente novo e criado.

## Arquitetura

### Fluxo de dados

```
PaeProtocoloController::store
  └── redirect -> pae.index?protocolo_id={id}

PaeFormularioController::show
  ├── resolve $formulario (existente)
  ├── resolve $protocolo a partir de:
  │     - query string ?protocolo_id=X, ou
  │     - $formulario['pae_protocolo_id'] (quando formulario ja vinculado)
  └── Inertia::render('Pae', [..., 'protocolo' => $protocolo])

PaeFormularioService::formatForView
  └── inclui 'pae_protocolo_id' => $form->pae_protocolo_id no retorno
```

### Estrutura de dados de `$protocolo` passado para a view

```php
[
    'id'            => int,
    'num_protocolo' => string,  // ex: "10.04.2026.003"
    'status'        => string,  // valor do enum PaeProtocoloStatus
]
```

Quando nao ha protocolo vinculado, o valor e `null`. Todos os componentes tratam `null` mantendo comportamento atual.

## Componentes Alterados

### `PaeFormularioController.php`

- Apos resolver `$formulario`, resolver `$protocolo` conforme logica acima
- Passar `protocolo` no array do `Inertia::render`

### `PaeFormularioService.php`

- Em `formatForView`, adicionar `'pae_protocolo_id' => $form->pae_protocolo_id` ao array retornado

### `PaeProtocoloController.php`

- Metodo `store`: alterar redirect de `pae.protocolos.index` para:
  ```php
  redirect()->route('pae.index', ['protocolo_id' => $protocolo->id])
  ```

### `Pae.vue`

- Adicionar prop `protocolo: { type: Object, default: null }`
- Repassar `protocolo` para `PaeHeader` e `PaeBreadcrumb`

### `PaeHeader.vue`

- Adicionar prop `protocolo: { type: Object, default: null }`
- Quando `protocolo` presente, exibir acima do titulo:
  - Numero: `#{{ protocolo.num_protocolo }}`
  - Badge de status com cor correspondente ao enum
- Quando `protocolo` e `null`: comportamento atual inalterado

**Layout resultante:**
```
[Protocolo #10.04.2026.003]  [badge: EM ANALISE]
Ficha do Empreendimento
Nome do Empreendimento    [Nivel Emergencia 2]
                          Ultima Atualizacao: ...
```

### `PaeBreadcrumb.vue`

- Adicionar prop `protocolo: { type: Object, default: null }`
- Quando `protocolo` presente, breadcrumb vira:
  ```
  PAE > Protocolos > #10.04.2026.003
         (link)       (texto ativo)
  ```
  O link "Protocolos" aponta para `route('pae.protocolos.index')`
- Quando `protocolo` e `null`: comportamento atual (`PAE`) mantido

## Restricoes e Invariantes

- Prop `protocolo` e sempre opcional em todos os componentes
- Nenhuma alteracao de rota e necessaria
- Nenhum componente novo criado — apenas extensao dos existentes
- O padrao Atomic Design e mantido: dados fluem de `Pae.vue` (Page) para `PaeHeader` e `PaeBreadcrumb` (Components)
- Sem regressao nos fluxos que nao passam `protocolo_id`

## Arquivos Impactados

| Arquivo | Caminho | Tipo |
|---|---|---|
| `PaeFormularioController.php` | `app/Modules/Pae/Controllers/` | Backend |
| `PaeFormularioService.php` | `app/Modules/Pae/Services/` | Backend |
| `PaeProtocoloController.php` | `app/Modules/Pae/Controllers/` | Backend |
| `Pae.vue` | `resources/js/Pages/` | Page |
| `PaeHeader.vue` | `resources/js/Components/Pae/` | Component |
| `PaeBreadcrumb.vue` | `resources/js/Components/Pae/` | Component |
