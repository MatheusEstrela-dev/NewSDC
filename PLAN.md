# Plano: Renderizar Dados Corretos em Detalhes da Decretacao

## Problema Identificado

O modal "Detalhes da Decretacao" exibe **N/A** e **valores zerados** porque:

1. O modal recebe dados da **lista paginada** (formato compacto) ao inves dos dados completos
2. `ProcessoGrid.vue` passa o objeto `processo` diretamente da lista para o modal
3. Os dados completos (totais, pedidos_ah, municipios com detalhes) **NAO estao presentes** nos dados da lista

### Fluxo Atual (Problematico)
```
Lista (ProcessosIndexResource) -> processo parcial -> DecretacaoDetailModal -> N/A
```

### Fluxo Correto (Solucao)
```
Lista -> clique -> fetch /api/v1/decretacoes/{id} -> dados completos -> DecretacaoDetailModal -> dados reais
```

---

## Arquivos a Modificar

### Backend (PHP)

| Arquivo | Acao |
|---------|------|
| `app/Modules/Decretacoes/Services/ProcessoQueryService.php` | Adicionar metodo para carregar totais e pedidos_ah |
| `app/Modules/Decretacoes/Resources/ProcessoResource.php` | Incluir totais e pedidos_ah na resposta |
| `app/Http/Controllers/Api/V1/Decretacoes/DecretacoesApiController.php` | Garantir que show() retorna dados completos |

### Frontend (Vue)

| Arquivo | Acao |
|---------|------|
| `resources/js/Components/Organisms/Decretacoes/ProcessoGrid.vue` | Modificar openDetailModal para fazer fetch da API |
| `resources/js/Components/Organisms/Decretacoes/Details/DecretacaoDetailModal.vue` | Adicionar loading state e receber dados da API |
| `resources/js/Components/Organisms/Decretacoes/Details/tabs/TabTotaisDesastres.vue` | Ajustar binding de dados |
| `resources/js/Components/Organisms/Decretacoes/Details/tabs/TabPedidoAH.vue` | Ajustar binding de dados |

---

## Etapas de Implementacao

### Etapa 1: Backend - Carregar Totais de Desastres

Modificar `ProcessoQueryService.php` para calcular e retornar os totais:

```php
public function getProcessoWithFullData(int $id): array
{
    $processo = Processo::with(['municipios', 'desastres'])->findOrFail($id);

    // Carregar totais de desastres
    $totais = $this->getTotalDesastresCountFromEntradas($id);

    // Carregar pedidos de ajuda humanitaria
    $pedidosAh = $this->getPedidoAhData($processo->decreto_municipal);

    return [
        'processo' => $processo,
        'totais' => $totais,
        'pedidos_ah' => $pedidosAh
    ];
}
```

### Etapa 2: Backend - Atualizar ProcessoResource

Incluir os novos campos na resposta da API:

```php
public function toArray($request): array
{
    return [
        // ... campos existentes ...
        'totais' => $this->totais ?? $this->calculateTotais(),
        'pedidos_ah' => $this->pedidos_ah ?? [],
    ];
}
```

### Etapa 3: Frontend - Fetch de Dados no Modal

Modificar `ProcessoGrid.vue`:

```javascript
const openDetailModal = async (processo) => {
    loadingDetail.value = true;
    showDetailModal.value = true;
    selectedProcesso.value = processo; // dados parciais primeiro

    try {
        const response = await axios.get(`/api/v1/decretacoes/${processo.id}`);
        selectedProcesso.value = response.data.data; // dados completos
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
    } finally {
        loadingDetail.value = false;
    }
};
```

### Etapa 4: Frontend - Loading State no Modal

Adicionar skeleton/loading no `DecretacaoDetailModal.vue` enquanto busca dados.

---

## Estrutura de Dados Esperada

### Totais de Desastres (processo.totais)
```json
{
    "geral": {
        "danos_humanos": {
            "total": 15,
            "desabrigados": 10,
            "desalojados": 5
        },
        "danos_materiais": {
            "quantidade": 8,
            "valor": 150000.00
        },
        "prejuizos_publicos": {
            "valor": 500000.00
        },
        "prejuizos_privados": {
            "valor": 200000.00
        }
    },
    "por_municipio": [...]
}
```

### Pedidos AH (processo.pedidos_ah)
```json
[
    {
        "id": 1,
        "numero": "AH-2026-001",
        "tipo": "Kit higiene",
        "quantidade": 50,
        "status": "aprovado",
        "data_solicitacao": "2026-03-01"
    }
]
```

---

## Verificacao de Integridade

- [ ] Endpoint `/api/v1/decretacoes/{id}` retorna totais e pedidos_ah
- [ ] Modal exibe loading enquanto busca dados
- [ ] TabTotaisDesastres renderiza dados corretamente
- [ ] TabPedidoAH renderiza lista de pedidos
- [ ] TabInformacoes exibe municipios com detalhes
- [ ] TabDadosDecreto exibe datas e vigencia

---

## Risco e Mitigacao

| Risco | Mitigacao |
|-------|-----------|
| Performance (muitos dados) | Carregar apenas quando necessario (lazy load) |
| Dados nulos | Usar optional chaining e valores default |
| Cache desatualizado | Sempre buscar dados frescos da API |

---

## Ordem de Execucao

1. **Backend primeiro**: Garantir que a API retorna os dados corretos
2. **Testar endpoint**: Verificar resposta com Postman/curl
3. **Frontend**: Implementar fetch e loading state
4. **Teste e2e**: Abrir modal e verificar dados
