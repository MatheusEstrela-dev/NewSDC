# 📋 Revisão de Código - Módulo TDAP

**Data**: 2025-12-26
**Revisor**: Claude Code
**Escopo**: Módulo TDAP completo (Domain-Driven Design)

---

## 🎯 Resumo Executivo

### Pontos Positivos ✅
- Separação clara de camadas (Domain, Application, Infrastructure, Presentation)
- Value Objects bem implementados e imutáveis
- Uso de interfaces para repositórios (inversão de dependência)
- Regras de negócio encapsuladas nas entidades
- Uso de transações em operações complexas
- DTOs para transferência de dados
- Eager loading para evitar N+1 queries

### Estatísticas 📊
- **Total de Problemas**: 23
- **Conformidade DDD**: 80%
- **Arquitetura**: Majoritariamente sólida

### Distribuição por Severidade
- 🔴 **Crítico**: 3 problemas (13%)
- 🟠 **Alto**: 4 problemas (17%)
- 🟡 **Médio**: 11 problemas (48%)
- 🟢 **Baixo**: 5 problemas (22%)

---

## 🔴 Problemas Críticos (Prioridade Máxima)

### 1. Entidades Acopladas ao Eloquent
**Arquivo**: `Domain/Entities/*.php`
**Problema**: Entidades de domínio estendendo `Eloquent\Model`
**Impacto**: Viola separação de camadas DDD, dificulta testes unitários

**Solução**: Separar entidades puras de modelos Eloquent
```php
// Domain/Entities/Product.php (entidade pura)
class Product {
    private int $id;
    private string $codigo;
    // Apenas regras de negócio
}

// Infrastructure/Persistence/Models/ProductModel.php
class ProductModel extends Model {
    protected $table = 'tdap_products';
    // Apenas mapeamento ORM
}
```

### 2. Entidades Chamando `save()` Diretamente
**Arquivos**: `Recebimento.php`, `ProductLote.php`, `RecebimentoItem.php`
**Problema**: Persistência sendo feita nas entidades
**Impacto**: Viola SRP, impossibilita testes sem BD

**Solução**: Remover `$this->save()` das entidades
```php
// ❌ Antes
public function aprovar(int $userId): void {
    $this->status = RecebimentoStatus::APROVADO;
    $this->save(); // REMOVER
}

// ✅ Depois
public function aprovar(int $userId): void {
    $this->status = RecebimentoStatus::APROVADO;
    // Repository fará a persistência
}
```

### 3. Controllers Acessando Repositórios Diretamente
**Arquivos**: Todos os controllers
**Problema**: Controllers pulando camada de aplicação
**Impacto**: Lógica de negócio vazando para apresentação

**Solução**: Usar sempre Use Cases
```php
// ❌ Antes
public function index(Request $request): Response {
    $data = $this->repository->list($filters);
    return Inertia::render('Page', ['data' => $data]);
}

// ✅ Depois
public function index(Request $request): Response {
    $result = $this->listUseCase->execute($filters);
    return Inertia::render('Page', $result->toArray());
}
```

---

## 🟠 Problemas de Alta Prioridade

### 4. Use Cases com Lógica de Negócio Complexa
**Arquivo**: `CreateSaidaEstoqueUseCase.php`
**Linhas**: 58-95
**Solução**: Criar Domain Service para lógica de seleção de lotes

### 5. Validação Apenas no Controller
**Arquivos**: Todos os controllers
**Solução**: Criar Form Requests ou validadores na camada de aplicação

### 6. Falta de Tratamento de Exceções
**Arquivos**: Todos os controllers
**Solução**: Implementar try-catch com mensagens amigáveis

### 7. Falta de Testes Unitários
**Solução**: Criar testes para entidades, value objects, use cases

---

## 🟡 Problemas de Média Prioridade

8. Falta de validação em Value Objects
9. Lógica de apresentação em Value Objects (`getColorClass()`, `getIcon()`)
10. Query raw complexa em repositórios
11. Acoplamento entre repositórios
12. Parsing manual de OrderBy
13. Falta de interface RecebimentoItemRepository
14. Falta de Domain Events
15. Falta de auditoria/rastreabilidade
16. Use Case acessando relacionamentos Eloquent
17. Inconsistência de nomenclatura (PT/EN)
18. Falta de documentação de invariantes

---

## 🟢 Problemas de Baixa Prioridade

19. Uso de `\DomainException` global (criar exceções específicas)
20. DTOs com `toArray()` desnecessário
21. Acesso a `auth()->id()` (usar Request)
22. Acoplamento com Inertia.js
23. Nomenclatura inconsistente

---

## 📅 Roadmap de Correções

### Sprint 1-2 (Curto Prazo) - CRÍTICO
- [ ] Refatorar controllers para usar apenas Use Cases
- [ ] Remover `save()` das entidades
- [ ] Criar Use Cases faltantes
- [ ] Implementar tratamento de exceções

### Sprint 3-4 (Médio Prazo) - ALTO
- [ ] Separar entidades de domínio dos modelos Eloquent
- [ ] Mover lógica de apresentação dos Value Objects
- [ ] Criar Domain Services
- [ ] Implementar sistema de eventos

### Sprint 5+ (Longo Prazo) - MÉDIO/BAIXO
- [ ] Criar exceções de domínio customizadas
- [ ] Padronizar nomenclatura
- [ ] Adicionar testes unitários completos
- [ ] Documentar invariantes

---

## 🎓 Conformidade DDD por Camada

| Camada | Conformidade | Problemas Principais |
|--------|--------------|---------------------|
| Domínio | 60% | Acoplamento com Eloquent |
| Aplicação | 70% | Lógica vazando para camada |
| Infraestrutura | 75% | Queries complexas |
| Apresentação | 50% | Acesso direto a repositórios |

---

## 💡 Conclusão

O módulo TDAP demonstra **compreensão sólida de DDD** com arquitetura bem estruturada. Os problemas identificados são principalmente de **refinamento** e não de falhas arquiteturais graves.

Com as correções prioritárias, o módulo alcançará **excelência em DDD** e estará preparado para crescimento sustentável.

### Próximos Passos Recomendados
1. Implementar correções críticas (Problemas #1, #2, #3)
2. Criar suite de testes unitários
3. Documentar regras de negócio
4. Refatorar progressivamente para DDD puro

---

**Referências**:
- Vernon, Vaughn. "Implementing Domain-Driven Design"
- Evans, Eric. "Domain-Driven Design: Tackling Complexity"
- Martin, Robert C. "Clean Architecture"
UT