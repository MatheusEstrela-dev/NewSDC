# 🚀 Skill de Backend DDD - Defesa Civil

Uma Skill de alta performance para agentes Claude trabalharem com autonomia total no desenvolvimento, debugging e refatoração de backend Laravel com DDD e Clean Architecture.

---

## 📋 Conteúdo da Skill

```
defesa-civil-backend-ddd/
├── SKILL.md                              # Documento principal da skill
└── references/                           # Guias de referência
    ├── repository-pattern-guide.md       # Padrão de Repositories
    ├── value-objects-guide.md            # ValueObjects Imutáveis
    ├── use-case-pattern.md               # Use Cases e Orquestração
    ├── domain-entities.md                # Entidades de Negócio Ricas
    ├── debugging-guide.md                # Metodologia de Debugging
    └── cheat-sheet.md                    # Referência Rápida
```

---

## 🎯 O que Esta Skill Permite

### ✅ Desenvolvimento Autônomo
- Criar novos módulos DDD completos
- Implementar features seguindo Clean Architecture
- Gerar código boilerplate correto
- Estruturar Entidades, Repositories, UseCases, DTOs

### ✅ Debugging Eficiente
- Metodologia de trace inverso (Request → Entidade)
- Ferramentas: Debugbar, Logs, Tinker
- Diagnóstico de N+1 queries
- Resolução de ValidationException, ConstraintViolation, etc

### ✅ Refatoração com Confiança
- Refatore Controllers para Clean Architecture
- Simplifique usando ValueObjects
- Mantenha testes passando
- Preserve backward compatibility

### ✅ Otimização
- Eager loading para eliminar N+1
- Cache estratégico
- Processamento assíncrono com filas
- Identificação de bottlenecks

---

## 📖 Como Usar

### Instalação

1. **Copie a pasta** `defesa-civil-backend-ddd/` para seu diretório de skills:
   ```bash
   cp -r defesa-civil-backend-ddd ~/.claude/skills/
   # ou
   cp -r defesa-civil-backend-ddd /mnt/skills/user/
   ```

2. **Reinicie Claude** para detectar a nova skill

3. **Verifique** se a skill aparece em seus skills disponíveis

---

### Trigger da Skill

A skill se ativa automaticamente quando você mencionar:

#### 🔴 Palavras-chave que ATIVAM:
- "debugar" / "debug" / "erro" / "problema"
- "refatorar" / "refactoring"
- "implementar" / "criar" / "nova feature"
- "UseCase" / "Repository" / "Entity" / "DTO"
- "Clean Architecture" / "DDD"
- "N+1 queries" / "performance"
- "testes" / "teste automatizado"
- "Laravel backend" / "backend development"
- Nomes de módulos: "Rat", "Pae", "Demandas", etc

#### 🟢 Exemplos de Prompts:

**Debugging:**
```
Quando tento criar uma RAT, recebo erro "Call to Undefined Method".
O erro aparece em RatRepository. Debugue e corrija.
```

**Implementação:**
```
Implemente um novo UseCase chamado "AssignRatToUserUseCase" no módulo Rat.
Deve verificar permissões, validar estado, e atribuir o usuário.
Inclua testes e siga a arquitetura DDD.
```

**Refatoração:**
```
Refatore o Controller RatController para seguir Clean Architecture.
Use UseCases, crie DTOs, elimine lógica do Controller.
```

**Otimização:**
```
A página de Rats está lenta ao listar 1000 registros.
Identifique queries N+1 e implemente eager loading + cache.
```

---

## 📚 Estrutura de Referências

### 1. **cheat-sheet.md** ⚡ (Comece aqui!)
Referência rápida com exemplos práticos de todos os padrões.

### 2. **repository-pattern-guide.md** 📦
- Interface vs Implementação
- Conversão Entidade ↔ Modelo Eloquent
- Filtering, Pagination, Eager Loading
- Testes

### 3. **value-objects-guide.md** 💎
- ValueObjects enum-like (RatPriority, RatStatus)
- ValueObjects com lógica (DateRange, Email)
- Serialização em JSON
- Validação rigorosa

### 4. **use-case-pattern.md** 🎬
- Anatomia de um UseCase
- Create, Read, Update, Delete UseCases
- Transações e eventos
- Tratamento de exceções

### 5. **domain-entities.md** 👑
- Entidades ricas com lógica
- Imutabilidade de identidade
- Métodos comportamentais
- Auditoria e histórico

### 6. **debugging-guide.md** 🔍
- Metodologia de debugging
- Ferramentas (Debugbar, Logs, Tinker)
- Cenários comuns e soluções
- Performance profiling

---

## 🏗️ Arquitetura (Resumida)

```
Request (HTTP)
    ↓
FormRequest (Validação de Formato)
    ↓
Controller (Thin - Delega)
    ↓
DTO (Transfer Object)
    ↓
UseCase (Orquestração)
    ↓
Repository (Persistência)
    ↓
Entidade (Lógica de Negócio Pura)
    ↓
Resource (Serialização)
    ↓
JSON Response
```

### Módulos do Seu Projeto
- **Rat** - Registro de Ações e Tarefas
- **Pae** - Plano de Ação do Escritório
- **Demandas** - Gestão de Demandas
- **Decretacoes** - Documentação
- **Ajuda_Humanitaria** - Ajuda Humanitária
- **Tdap** - Treinamento
- **Treinamento** - Programas
- **Plantao** - Gestão de Plantões
- **BI** - Business Intelligence
- **Integracoes** - Integrações Externas

---

## 💡 Exemplos de Uso

### Exemplo 1: Criar Novo UseCase

**Seu prompt:**
```
Crie um UseCase para "AssignRatToUserUseCase" no módulo Rat.
Deve validar se a RAT pode ser atribuída (não completa),
se o usuário existe, e então atribuir.
Inclua testes com Pest.
```

**O agente irá:**
1. Ler a estrutura existente
2. Criar a classe `AssignRatToUseCase` em `app/Modules/Rat/Application/UseCases/`
3. Criar DTO `AssignRatDTO`
4. Implementar validações de negócio
5. Injetar Repository e UseCase corretos
6. Gerar testes com assertions apropriadas
7. Mostrar o resultado pronto para usar

---

### Exemplo 2: Debugar Erro N+1

**Seu prompt:**
```
A página de listagem de Rats está muito lenta (10+ segundos).
Há 500 registros. Debugue e otimize.
```

**O agente irá:**
1. Ativar Debugbar para inspecionar queries
2. Identificar queries N+1 (1 query + N queries)
3. Localizar em qual Repository/UseCase ocorre
4. Implementar eager loading com `.with()`
5. Adicionar cache se apropriado
6. Validar que queries caíram de 500+ para 2-3
7. Mostrar antes/depois

---

### Exemplo 3: Refatorar Controller

**Seu prompt:**
```
Refatore o Controller RatController para Clean Architecture.
Atualmente tem muita lógica de negócio misturada.
Crie UseCases, DTOs, remova lógica do Controller.
```

**O agente irá:**
1. Ler o Controller existente
2. Extrair ações em UseCases separados (Create, Update, Delete, etc)
3. Criar DTOs para transferência de dados
4. Injetar UseCases no Controller
5. Deixar Controller com ~10 linhas cada método
6. Escrever testes para validar refatoração
7. Garantir sem quebra de funcionalidade

---

## 🔗 Relacionado

Se o problema é de **permissões / ACL / roles**:
→ Use a skill `defesa-civil-acl-debug` (já instalada no seu projeto)

---

## 🛠️ Stack Que Você Usa

- **Backend**: Laravel 12, PHP 8.3+
- **ORM**: Eloquent
- **Autenticação**: Sanctum
- **ACL**: Spatie Laravel-Permission
- **API**: Inertia.js
- **Frontend**: Vue 3 + Atomic Design
- **Banco**: MySQL
- **Cache/Filas**: Redis
- **Development**: Docker, Vite, Pint

---

## 📋 Quando Ativar a Skill

### ✅ ATIVE para:
```
"Debugue este erro de N+1"
"Implemente um novo módulo DDD"
"Refatore este Controller"
"Otimize a performance desta query"
"Crie testes para este UseCase"
"Qual é o padrão correto para..."
"Como debugar em Laravel"
"Como estruturar um Repository"
```

### ❌ NÃO ative para:
```
"Por que meu botão não aparece?" (Frontend - outra skill)
"Usuário não tem permissão" (ACL - use defesa-civil-acl-debug)
"JWT não funciona" (Segurança - outro contexto)
```

---

## 🚀 Começar Agora

1. **Primeira leitura**: `references/cheat-sheet.md` (5 min)
2. **Primeiro prompt**: "Crie um novo módulo DDD para [X]"
3. **O agente fará**: Estrutura completa + testes
4. **Você adapta**: Específicos do seu negócio
5. **Próximas vezes**: Use a skill com confiança!

---

## 📞 Suporte

Se algo não funcionar:

1. **Verifique a estrutura** - Está em `app/Modules/{Nome}/`?
2. **Leia a referência** - Existe um guide para isso em `references/`?
3. **Use o cheat-sheet** - Tem um exemplo em `cheat-sheet.md`?
4. **Debugue com Tinker**:
   ```bash
   php artisan tinker
   > Seu código aqui
   ```

---

## 📝 Notas

- A skill é **100% agnóstica a negócio** - Funciona para qualquer módulo
- O agente tem **autonomia total** para debugar, refatorar e implementar
- Tudo segue **SOLID + Clean Architecture + DDD**
- Testes são **esperados e recomendados**
- Code review ainda é importante!

---

## ✨ Recursos Principais

| Recurso | Localização |
|---------|------------|
| Padrão Request-DTO-Controller-UseCase | SKILL.md + cheat-sheet.md |
| Exemplos de Repository | repository-pattern-guide.md |
| ValueObjects tipados | value-objects-guide.md |
| Use Cases orquestradores | use-case-pattern.md |
| Entidades ricas | domain-entities.md |
| Debugging passo-a-passo | debugging-guide.md |
| Referência rápida | cheat-sheet.md |

---

## 🎓 Próximos Passos

1. **Instale a skill**
2. **Leia `cheat-sheet.md`** - Aprenda padrões
3. **Use um prompt** - Ative a skill
4. **Explore as referências** - Conforme necessário
5. **Trabalhe com autonomia** - O agente está pronto!

---

**Skill criada com ❤️ para máxima autonomia do agente.**

Boa sorte com seu desenvolvimento! 🚀
