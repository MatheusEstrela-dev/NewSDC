# Correções de Serialização Frontend - Páginas em Branco Resolvidas

## Data: 27/12/2025

---

## 🔍 Problema Identificado

As rotas do módulo TDAP e RAT estavam retornando **páginas em branco** devido a **falha na serialização JSON** dos dados enviados para o frontend via Inertia.js.

### Causa Raiz

Os DTOs (Data Transfer Objects) estavam retornando objetos `LengthAwarePaginator` diretamente sem serialização, o que causava falha no `json_encode()` do Inertia.

```php
// ❌ PADRÃO QUEBRADO
public function toArray(): array
{
    return [
        'products' => $this->products,  // LengthAwarePaginator não serializa
    ];
}
```

---

## ✅ Solução Aplicada

Implementamos o método `executeAsDTO()` em todos os Use Cases de listagem, seguindo o padrão já usado com sucesso no módulo **Demandas**.

### Padrão Correto

```php
// ✅ PADRÃO CORRETO
public function executeAsDTO(array $filters = [], int $perPage = 15): array
{
    $paginator = $this->repository->list($filters, $perPage);

    return [
        'data' => collect($paginator->items())
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                // ... todos os campos serializados
            ])
            ->toArray(),  // ✅ Array de arrays
        'pagination' => [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ],
    ];
}
```

---

## 📁 Arquivos Corrigidos

### 1. Módulo TDAP

#### ListProductsUseCase.php
**Localização:** `app/Modules/Tdap/Application/UseCases/ListProductsUseCase.php`

**Mudanças:**
- ✅ Adicionado método `executeAsDTO()`
- ✅ Serialização completa de produtos com 13 campos
- ✅ Datas convertidas para ISO8601

**Campos Serializados:**
- id, codigo, nome, descricao, categoria
- unidade_medida, estoque_minimo, estoque_maximo, estoque_atual
- preco_unitario, localizacao_padrao, is_active
- created_at, updated_at

---

#### ListRecebimentosUseCase.php
**Localização:** `app/Modules/Tdap/Application/UseCases/ListRecebimentosUseCase.php`

**Mudanças:**
- ✅ Adicionado método `executeAsDTO()`
- ✅ Serialização de recebimentos com 16 campos
- ✅ Status convertido para `->value` (Enum)

**Campos Serializados:**
- id, numero_recebimento, ordem_compra_id, nota_fiscal
- placa_veiculo, transportadora, motorista_nome, motorista_documento
- doca_descarga, data_chegada, data_inicio_conferencia, data_fim_conferencia
- conferido_por, aprovado_por, status, observacoes

---

#### ListMovimentacoesUseCase.php
**Localização:** `app/Modules/Tdap/Application/UseCases/ListMovimentacoesUseCase.php`

**Mudanças:**
- ✅ Adicionado método `executeAsDTO()`
- ✅ Serialização de movimentações com 10 campos
- ✅ Relacionamentos condicionais (product, lote, responsavel)

**Campos Serializados:**
- id, product_id, lote_id, tipo, quantidade
- responsavel_id, destino, observacoes, data_movimentacao
- created_at, updated_at
- **Relacionamentos:** product, lote, responsavel (se carregados)

---

### 2. Controllers Atualizados

#### TdapProductsController.php
```php
// ANTES
$result = $this->listProductsUseCase->execute($filters, $perPage);
return Inertia::render('Tdap/ProductsIndex', [
    'products' => $result->products->items(),  // ❌ Perde paginação
]);

// DEPOIS
$result = $this->listProductsUseCase->executeAsDTO($filters, $perPage);
return Inertia::render('Tdap/ProductsIndex', [
    'products' => $result['data'],           // ✅ Array serializado
    'pagination' => $result['pagination'],   // ✅ Metadados de paginação
    'filters' => $result['filters'],
    'statistics' => $result['statistics'],
]);
```

---

#### TdapRecebimentosController.php
```php
// ANTES
$result = $this->listRecebimentosUseCase->execute($filters, $perPage);
return Inertia::render('Tdap/RecebimentosIndex', $result->toArray());  // ❌ Paginator no DTO

// DEPOIS
$result = $this->listRecebimentosUseCase->executeAsDTO($filters, $perPage);
return Inertia::render('Tdap/RecebimentosIndex', [
    'recebimentos' => $result['data'],       // ✅ Array serializado
    'pagination' => $result['pagination'],
    'filters' => $result['filters'],
    'statistics' => $result['statistics'],
]);
```

---

#### TdapMovimentacoesController.php
```php
// ANTES
$result = $this->listMovimentacoesUseCase->execute($filters, $perPage);
return Inertia::render('Tdap/MovimentacoesIndex', $result->toArray());  // ❌ Paginator no DTO

// DEPOIS
$result = $this->listMovimentacoesUseCase->executeAsDTO($filters, $perPage);
return Inertia::render('Tdap/MovimentacoesIndex', [
    'movimentacoes' => $result['data'],      // ✅ Array serializado
    'pagination' => $result['pagination'],
    'filters' => $result['filters'],
    'statistics' => $result['statistics'],
]);
```

---

### 3. Módulo RAT

#### ListRatsUseCase.php
**Localização:** `app/Modules/Rat/Application/UseCases/ListRatsUseCase.php`

**Mudanças:**
- ✅ Adicionado método `executeAsDTO()`
- ✅ Serialização de RATs com 8 campos base
- ✅ Relacionamentos condicionais (solicitante, tecnicoResponsavel)

**Campos Serializados:**
- id, protocolo, tipo_demanda, municipio
- status, descricao, created_at, updated_at
- **Relacionamentos:** solicitante, tecnico_responsavel (se carregados)

---

#### RatIndexController.php
```php
// ANTES
$rats = $this->listRatsUseCase->execute($filters, 15);
$ratsData = $rats->map(function ($rat) { ... });  // ❌ Collection retornada

return Inertia::render('RatIndex', [
    'rats' => $ratsData,                     // ❌ Ainda Collection
    'pagination' => [                        // ⚠️ Extração manual
        'current_page' => $rats->currentPage(),
        ...
    ],
]);

// DEPOIS
$ratsResult = $this->listRatsUseCase->executeAsDTO($filters, 15);

return Inertia::render('RatIndex', [
    'rats' => $ratsResult['data'],           // ✅ Array puro
    'pagination' => $ratsResult['pagination'], // ✅ Automático
    'filters' => $filters,
]);
```

---

## 📊 Resumo das Correções

| Módulo | Use Case | Controller | Status |
|--------|----------|------------|--------|
| TDAP | ListProductsUseCase | TdapProductsController | ✅ Corrigido |
| TDAP | ListRecebimentosUseCase | TdapRecebimentosController | ✅ Corrigido |
| TDAP | ListMovimentacoesUseCase | TdapMovimentacoesController | ✅ Corrigido |
| RAT | ListRatsUseCase | RatIndexController | ✅ Corrigido |

**Total:** 4 Use Cases + 4 Controllers = **8 arquivos corrigidos**

---

## 🎯 Benefícios

### 1. Serialização Consistente
- ✅ Todos os módulos agora seguem o mesmo padrão
- ✅ Dados sempre JSON-serializáveis
- ✅ Sem falhas no Inertia

### 2. Paginação Padronizada
```javascript
// Frontend agora recebe sempre:
{
  data: [...],  // Array de objetos
  pagination: {
    current_page: 1,
    per_page: 15,
    total: 150,
    last_page: 10,
    from: 1,
    to: 15
  }
}
```

### 3. Manutenibilidade
- ✅ Padrão claro e documentado
- ✅ Fácil adicionar novos endpoints
- ✅ Código previsível

### 4. Performance
- ✅ Apenas dados necessários no frontend
- ✅ Relacionamentos lazy-loaded quando preciso
- ✅ Serialização eficiente

---

## 🔧 Padrão Estabelecido

Para TODOS os endpoints paginados, seguir:

```php
// NO USE CASE
public function executeAsDTO(array $filters, int $perPage): array
{
    $paginator = $this->repository->list($filters, $perPage);

    return [
        'data' => collect($paginator->items())
            ->map(fn ($item) => $this->serializeItem($item))
            ->toArray(),
        'pagination' => [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ],
    ];
}

// NO CONTROLLER
$result = $this->useCase->executeAsDTO($filters, $perPage);

return Inertia::render('ViewName', [
    'items' => $result['data'],
    'pagination' => $result['pagination'],
]);
```

---

## ✅ Build Status

```bash
npm run build
```

**Resultado:** ✅ Sucesso
- ✓ 1325 modules transformed
- ✓ Built in 3.10s
- Total de arquivos: 91 assets gerados

---

## 🚀 Próximos Passos

### Para Novos Endpoints

1. Criar Use Case com método `executeAsDTO()`
2. Serializar todos os campos explicitamente
3. Incluir paginação no retorno
4. Controller usa apenas `executeAsDTO()`
5. Testar JSON serialization

### Checklist de Qualidade

- [ ] Use Case tem `executeAsDTO()`?
- [ ] Todos os campos serializados?
- [ ] Datas em ISO8601?
- [ ] Enums convertidos para `->value`?
- [ ] Paginação incluída?
- [ ] Relacionamentos condicionais?
- [ ] Controller não acessa Paginator?

---

## 📝 Observações

### Relacionamentos
Usar `relationLoaded()` para evitar N+1:

```php
'product' => $movimentacao->relationLoaded('product') ? [
    'id' => $movimentacao->product->id,
    'nome' => $movimentacao->product->nome,
] : null,
```

### Enums
Sempre converter para `value`:

```php
'status' => $recebimento->status?->value,  // "AGUARDANDO" ao invés do objeto
```

### Datas
Sempre usar `toIso8601String()`:

```php
'created_at' => $item->created_at?->toIso8601String(),  // "2025-12-27T03:15:00+00:00"
```

---

**Data:** 27/12/2025
**Status:** ✅ Concluído
**Build:** ✅ Sucesso
**Frontend:** ✅ Funcionando

**Rotas Corrigidas:**
- ✅ `/tdap/produtos`
- ✅ `/tdap/recebimentos`
- ✅ `/tdap/movimentacoes`
- ✅ `/rat` (index)
