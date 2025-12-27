# Módulo TDAP - Gestão de Depósito

## Visão Geral

O módulo TDAP (Termo de Depósito e Assistência Permanente) é um sistema completo de gestão de depósito desenvolvido seguindo os princípios de **Domain-Driven Design (DDD)** e **Atomic Design** para o frontend.

Este módulo foi projetado para gerenciar estoques de itens assistenciais como:
- **Cestas Básicas** (produtos perecíveis com validade)
- **Kits de Limpeza** (produtos químicos com restrições de armazenamento)
- **Colchões** (produtos volumétricos)
- **Outros materiais** de assistência social

## Características Principais

### 1. Gestão de Produtos
- Cadastro de produtos com tipos específicos
- Controle de composição (Kitting) para produtos compostos
- Definição de grupos de risco para segregação de armazenamento
- Estratégias de armazenamento configuráveis (FIFO, FEFO, LIFO)
- Controle de estoque mínimo e máximo
- Volumetria e peso unitário para planejamento de capacidade

### 2. Recebimento de Materiais (Modal TDPA)
- Registro completo de dados de transporte (placa, motorista, transportadora)
- Vinculação com ordens de compra
- Conferência física item a item
- Registro de avarias com upload de fotos
- Validação de validades para produtos perecíveis
- Workflow de aprovação (Pendente → Em Conferência → Conferido → Aprovado → Finalizado)

### 3. Controle de Lotes
- Rastreabilidade completa por lote
- Controle de data de fabricação e validade
- Localização física no depósito
- Alertas automáticos para lotes vencidos ou próximos do vencimento
- Estratégia FEFO para produtos perecíveis (primeiro a vencer, primeiro a sair)

### 4. Movimentações de Estoque
- Tipos de movimentação: Entrada, Saída, Transferência, Ajuste, Devolução
- Seleção automática de lotes baseada na estratégia (FIFO/FEFO)
- Validação de estoque disponível
- Bloqueio de saída de lotes vencidos
- Histórico completo de movimentações por produto
- Rastreabilidade de origem e destino

### 5. Dashboard e Alertas
- Visão geral do depósito
- Alertas de estoque baixo
- Alertas de lotes vencidos
- Alertas de lotes próximos ao vencimento
- Recebimentos pendentes de conferência
- Estatísticas de movimentações

## Arquitetura

### Backend - Domain-Driven Design (DDD)

O módulo está estruturado em camadas bem definidas:

```
app/Modules/Tdap/
├── Application/              # Camada de Aplicação
│   ├── DTOs/                # Data Transfer Objects
│   │   ├── ProductListDTO.php
│   │   ├── RecebimentoListDTO.php
│   │   ├── EstoqueDTO.php
│   │   └── MovimentacaoStatisticsDTO.php
│   └── UseCases/            # Casos de Uso
│       ├── ListProductsUseCase.php
│       ├── CreateProductUseCase.php
│       ├── CreateRecebimentoUseCase.php
│       ├── ProcessarRecebimentoUseCase.php
│       ├── CreateSaidaEstoqueUseCase.php
│       └── GetEstoqueUseCase.php
│
├── Domain/                   # Camada de Domínio
│   ├── Entities/            # Entidades de Negócio
│   │   ├── Product.php
│   │   ├── ProductLote.php
│   │   ├── ProductComposition.php
│   │   ├── Recebimento.php
│   │   ├── RecebimentoItem.php
│   │   └── Movimentacao.php
│   ├── ValueObjects/        # Objetos de Valor
│   │   ├── ProductType.php
│   │   ├── MovimentacaoType.php
│   │   ├── RecebimentoStatus.php
│   │   └── StorageStrategy.php
│   └── Repositories/        # Interfaces de Repositório
│       ├── ProductRepositoryInterface.php
│       ├── ProductLoteRepositoryInterface.php
│       ├── RecebimentoRepositoryInterface.php
│       └── MovimentacaoRepositoryInterface.php
│
├── Infrastructure/           # Camada de Infraestrutura
│   └── Persistence/         # Implementações de Repositórios
│       ├── EloquentProductRepository.php
│       ├── EloquentProductLoteRepository.php
│       ├── EloquentRecebimentoRepository.php
│       └── EloquentMovimentacaoRepository.php
│
├── Presentation/            # Camada de Apresentação
│   └── Http/
│       └── Controllers/
│           ├── TdapDashboardController.php
│           ├── TdapProductsController.php
│           ├── TdapRecebimentosController.php
│           └── TdapMovimentacoesController.php
│
└── TdapServiceProvider.php  # Service Provider do Módulo
```

### Frontend - Atomic Design

```
resources/js/
├── Components/
│   ├── Atoms/Tdap/              # Componentes Atômicos
│   │   ├── ProductTypeBadge.vue
│   │   ├── MovimentacaoTypeBadge.vue
│   │   ├── RecebimentoStatusBadge.vue
│   │   └── EstoqueIndicator.vue
│   │
│   ├── Molecules/Tdap/          # Componentes Moleculares
│   │   └── TdapStatCard.vue
│   │
│   └── Organisms/Tdap/          # Componentes Orgânicos
│       └── TdapRecebimentoModal.vue
│
├── Templates/Tdap/              # Templates de Página
│   └── TdapDashboardTemplate.vue
│
└── Pages/Tdap/                  # Páginas Inertia
    └── Dashboard.vue
```

## Banco de Dados

### Tabelas Principais

#### 1. tdap_products
Armazena os produtos do depósito.

**Campos principais:**
- `codigo` - Código único do produto
- `nome` - Nome do produto
- `tipo` - Enum: cesta_basica, kit_limpeza, colchao, outros
- `eh_composto` - Boolean indicando se é um kit
- `volume_unitario_m3` - Volume para cálculo de capacidade
- `peso_unitario_kg` - Peso para planejamento logístico
- `estoque_minimo` - Gatilho para ressuprimento
- `estoque_maximo` - Limite de capacidade
- `estrategia_armazenamento` - Enum: fifo, fefo, lifo
- `grupo_risco` - ALIMENTO, QUIMICO, GERAL (para segregação)
- `dias_alerta_validade` - Dias de antecedência para alertar

#### 2. tdap_product_lotes
Controla lotes individuais de cada produto.

**Campos principais:**
- `product_id` - FK para tdap_products
- `numero_lote` - Número do lote
- `data_entrada` - Data de entrada no depósito
- `data_fabricacao` - Data de fabricação (opcional)
- `data_validade` - Data de validade (obrigatório para perecíveis)
- `quantidade_inicial` - Quantidade recebida
- `quantidade_atual` - Quantidade disponível
- `localizacao` - Localização física (prateleira, pallet)

#### 3. tdap_product_compositions
Composição de produtos (Kitting).

**Campos principais:**
- `product_composto_id` - FK para produto final (ex: Cesta Básica)
- `product_componente_id` - FK para componente (ex: Arroz)
- `quantidade` - Quantidade do componente no kit
- `unidade_medida` - unidade, kg, litro, etc.

#### 4. tdap_recebimentos
Registro de recebimentos de materiais.

**Campos principais:**
- `numero_recebimento` - Número único gerado automaticamente
- `ordem_compra_id` - FK opcional para ordem de compra
- `nota_fiscal` - Número da nota fiscal
- `placa_veiculo` - Placa do veículo
- `transportadora` - Nome da transportadora
- `motorista_nome` - Nome do motorista
- `motorista_documento` - RG/CPF do motorista
- `doca_descarga` - Doca utilizada
- `data_chegada` - Data/hora de chegada
- `data_inicio_conferencia` - Data/hora início da conferência
- `data_fim_conferencia` - Data/hora fim da conferência
- `conferido_por` - FK para user que conferiu
- `aprovado_por` - FK para user que aprovou
- `status` - Enum: pendente, em_conferencia, conferido, aprovado, rejeitado, finalizado

#### 5. tdap_recebimento_itens
Itens de cada recebimento.

**Campos principais:**
- `recebimento_id` - FK para tdap_recebimentos
- `product_id` - FK para tdap_products
- `quantidade_nota` - Quantidade na nota fiscal
- `quantidade_conferida` - Quantidade conferida fisicamente
- `numero_lote` - Lote do produto
- `data_fabricacao` - Data de fabricação
- `data_validade` - Data de validade
- `tem_avaria` - Boolean
- `tipo_avaria` - Tipo: molhado, rasgado, vazamento, etc.
- `quantidade_avariada` - Quantidade com avaria
- `foto_avaria` - Path para foto da avaria

#### 6. tdap_movimentacoes
Histórico de todas as movimentações.

**Campos principais:**
- `numero_movimentacao` - Número único (ENT-YYYYMM-XXXX, SAI-YYYYMM-XXXX, etc.)
- `tipo` - Enum: entrada, saida, transferencia, ajuste, devolucao
- `product_id` - FK para tdap_products
- `lote_id` - FK para tdap_product_lotes
- `quantidade` - Quantidade movimentada
- `data_movimentacao` - Data/hora da movimentação
- `origem` - Origem da movimentação
- `destino` - Destino da movimentação
- `solicitante_id` - FK para user solicitante
- `responsavel_id` - FK para user responsável
- `documento_referencia` - NF, OC, etc.

## Conceitos de Negócio Implementados

### 1. Kitting (Composição de Produtos)
Cestas básicas e kits são tratados como produtos compostos. O sistema permite:
- Definir um produto como composto (`eh_composto = true`)
- Cadastrar os componentes do kit na tabela `tdap_product_compositions`
- Controlar estoque do kit fechado OU dos componentes individuais

### 2. Estratégias de Armazenamento

#### FIFO (First In, First Out)
- Padrão para produtos não perecíveis
- O lote mais antigo sai primeiro
- Baseado em `data_entrada ASC`

#### FEFO (First Expire, First Out)
- **Obrigatório para produtos perecíveis** (Cestas Básicas, Kit Limpeza)
- O lote com validade mais próxima sai primeiro
- Previne vencimento de produtos
- Baseado em `data_validade ASC, data_entrada ASC`

#### LIFO (Last In, First Out)
- Último a entrar, primeiro a sair
- Pouco utilizado, mas disponível
- Baseado em `data_entrada DESC`

### 3. Segregação de Armazenamento
O sistema implementa controle de incompatibilidades:

**Grupos de Risco:**
- **ALIMENTO** - Produtos alimentícios (Cesta Básica)
- **QUIMICO** - Produtos de limpeza (Kit Limpeza)
- **GERAL** - Outros produtos (Colchões, etc.)

**Regra de Negócio:**
```php
// ALIMENTO não pode compartilhar localização com QUIMICO
$produto->podeCompartilharLocalCom($outroProduto); // false
```

### 4. Volumetria e Capacidade
O sistema calcula:
- Volume total ocupado: `volume_unitario_m3 × quantidade`
- Peso total: `peso_unitario_kg × quantidade`
- Permite planejamento de capacidade em m³, não apenas quantidade

### 5. Ponto de Ressuprimento Dinâmico
Não espera o estoque zerar:
- Define `estoque_minimo` baseado em:
  - Saída média por dia
  - Lead time de compra
  - Estoque de segurança
- Exemplo: Se saída média = 100/dia e lead time = 5 dias, `estoque_minimo = 600`
- Alerta dispara quando `quantidade_atual <= estoque_minimo`

### 6. Rastreabilidade de Lote
Cada saída vincula:
- `id_produto` - Qual produto saiu
- `id_lote` - De qual lote específico
- `solicitante_id` - Quem solicitou
- `destino` - Para onde foi
- `documento_referencia` - Nota fiscal, protocolo, etc.

**Benefício:** Em caso de recall, sabe-se exatamente quem recebeu aquele lote.

## Regras de Validação

### Recebimento de Produtos Perecíveis
```php
// Validade deve ser maior que X meses (configurável)
if ($item->data_validade < now()->addMonths(3)) {
    // ALERTA: Produto com validade curta
}
```

### Recebimento de Químicos
```php
// Verificar vazamentos
if ($item->tem_avaria && $item->tipo_avaria === 'vazamento') {
    // BLOQUEAR entrada para evitar contaminação
}
```

### Saída de Estoque
```php
// Verificar se lote está vencido
if ($lote->isVencido()) {
    throw new \DomainException("Lote vencido");
}

// Verificar estoque suficiente
$estoqueTotal = $lotes->sum('quantidade_atual');
if ($estoqueTotal < $quantidadeNecessaria) {
    throw new \DomainException("Estoque insuficiente");
}
```

## Instalação e Configuração

### 1. Registrar o Service Provider

Adicionar em `config/app.php`:
```php
'providers' => [
    // ...
    App\Modules\Tdap\TdapServiceProvider::class,
],
```

OU em `bootstrap/providers.php` (Laravel 11+):
```php
return [
    // ...
    App\Modules\Tdap\TdapServiceProvider::class,
];
```

### 2. Executar Migrations

```bash
php artisan migrate
```

Isto criará as tabelas:
- `tdap_products`
- `tdap_product_lotes`
- `tdap_product_compositions`
- `tdap_recebimentos`
- `tdap_recebimento_itens`
- `tdap_movimentacoes`

### 3. Configurar Permissões

O módulo utiliza gates do Laravel. Configurar em `AuthServiceProvider`:

```php
Gate::define('tdap.products.create', fn($user) => $user->hasRole('admin'));
Gate::define('tdap.recebimentos.create', fn($user) => $user->hasRole('almoxarife'));
Gate::define('tdap.recebimentos.processar', fn($user) => $user->hasRole('gestor'));
Gate::define('tdap.movimentacoes.create', fn($user) => $user->hasRole('almoxarife'));
Gate::define('tdap.admin', fn($user) => $user->hasRole('admin'));
```

### 4. Publicar Assets

```bash
npm run build
```

## Uso

### Dashboard
Acesse: `/tdap`

Visualiza:
- Estatísticas gerais
- Alertas de estoque baixo
- Alertas de lotes vencidos
- Alertas de lotes próximos ao vencimento
- Recebimentos pendentes

### Produtos
Acesse: `/tdap/produtos`

Funcionalidades:
- Listar produtos
- Filtrar por tipo, grupo de risco
- Visualizar estoque atual por produto
- Criar novos produtos

### Recebimentos
Acesse: `/tdap/recebimentos`

**Fluxo de Recebimento:**

1. **Criar Recebimento** (Modal TDPA)
   - Aba 1: Dados do Transporte (placa, motorista, transportadora)
   - Aba 2: Documentação (NF, OC)
   - Aba 3: Conferência Física (itens, lotes, validades, avarias)

2. **Conferir** (Status: Pendente → Em Conferência → Conferido)
   - Validar quantidades
   - Registrar divergências
   - Fotografar avarias

3. **Aprovar** (Status: Conferido → Aprovado)
   - Revisar conferência
   - Aprovar ou rejeitar

4. **Processar** (Status: Aprovado → Finalizado)
   - Cria lotes automaticamente
   - Gera movimentações de entrada
   - Atualiza estoque

### Movimentações
Acesse: `/tdap/movimentacoes`

**Criar Saída de Estoque:**
```php
POST /tdap/movimentacoes/saida
{
  "product_id": 1,
  "quantidade": 50,
  "destino": "Distribuição Comunidade XYZ",
  "responsavel_id": 1,
  "observacoes": "Entrega assistencial"
}
```

O sistema automaticamente:
- Seleciona lotes baseado na estratégia FEFO/FIFO
- Valida se há estoque suficiente
- Bloqueia lotes vencidos
- Baixa quantidade dos lotes
- Gera movimentações com rastreabilidade

## Testes

### Cenários de Teste Recomendados

#### 1. Teste de FEFO para Cestas Básicas
- Criar produto tipo "cesta_basica"
- Adicionar 3 lotes com validades diferentes:
  - Lote A: validade 30/01/2026
  - Lote B: validade 15/01/2026
  - Lote C: validade 28/02/2026
- Criar saída de 10 unidades
- **Esperado:** Sistema deve usar Lote B primeiro (menor validade)

#### 2. Teste de Bloqueio de Lote Vencido
- Criar lote com validade no passado
- Tentar criar saída
- **Esperado:** Erro "Lote vencido"

#### 3. Teste de Incompatibilidade de Armazenamento
- Criar Cesta Básica (ALIMENTO) na localização "Prateleira A-1"
- Tentar alocar Kit Limpeza (QUIMICO) na mesma localização
- **Esperado:** Sistema deve alertar sobre incompatibilidade

#### 4. Teste de Workflow de Recebimento
- Criar recebimento com status PENDENTE
- Iniciar conferência → EM_CONFERENCIA
- Finalizar conferência → CONFERIDO
- Aprovar → APROVADO
- Processar → FINALIZADO (cria lotes e movimentações)

## API Reference

### Endpoints Principais

```
GET    /tdap                                  # Dashboard
GET    /tdap/produtos                         # Lista produtos
POST   /tdap/produtos                         # Cria produto
GET    /tdap/produtos/{id}/estoque            # Estoque do produto

GET    /tdap/recebimentos                     # Lista recebimentos
POST   /tdap/recebimentos                     # Cria recebimento
GET    /tdap/recebimentos/{id}                # Detalhes do recebimento
POST   /tdap/recebimentos/{id}/processar      # Processa recebimento

GET    /tdap/movimentacoes                    # Lista movimentações
POST   /tdap/movimentacoes/saida              # Cria saída
GET    /tdap/movimentacoes/produto/{id}/historico  # Histórico
```

## Melhorias Futuras

1. **Sistema de Reservas**
   - Reservar estoque para solicitações futuras
   - Evitar overselling

2. **Endereçamento Inteligente**
   - Sugerir melhor localização baseado em:
     - Grupo de risco
     - Volumetria
     - Rotatividade

3. **Inventário**
   - Contagem física periódica
   - Ajustes de inventário
   - Acuracidade de estoque

4. **Relatórios**
   - Giro de estoque
   - Acuracidade
   - Curva ABC
   - Movimentações por período

5. **Integração WMS**
   - Leitura de código de barras
   - Picking guiado
   - Endereçamento por radiofrequência

6. **Notificações**
   - Email/SMS para alertas de estoque baixo
   - Alertas de vencimento
   - Notificações de recebimentos pendentes

## Suporte

Para dúvidas ou problemas, consulte:
- Documentação do Laravel: https://laravel.com/docs
- Documentação do Inertia.js: https://inertiajs.com
- Issues no repositório do projeto

---

**Desenvolvido com:**
- Laravel 12
- Vue 3 (Composition API)
- Inertia.js
- Tailwind CSS
- Domain-Driven Design (DDD)
- Atomic Design Pattern

**Data de Criação:** 26/01/2025

**Versão:** 1.0.0
