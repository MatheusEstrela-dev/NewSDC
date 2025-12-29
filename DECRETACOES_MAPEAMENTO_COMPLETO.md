# Mapeamento Completo: Sistema de Decretações (Reconhecimento de Desastres)

## 1. Visão Geral do Domínio

### 1.1 Conceituação do Negócio

O **Sistema de Decretações** (também chamado de **Reconhecimento de Desastres**) é o módulo central e mais crítico do SDC para o processo burocrático-legal de formalização de desastres em Minas Gerais.

#### Contexto de Defesa Civil
Quando um município sofre um desastre (enchente, deslizamento, seca, etc.), ele pode decretar:
- **SE (Situação de Emergência)**: Capacidade de resposta parcialmente comprometida
- **ECP (Estado de Calamidade Pública)**: Capacidade totalmente comprometida, precisa de ajuda estadual/federal

Este processo segue um fluxo legal rigoroso que envolve:
1. **Município** cria o decreto e preenche o FIDE (Formulário de Informações do Desastre)
2. **Coordenadoria Regional (Redec)** verifica a documentação
3. **Estado (CEDEC/DAT)** analisa e pode reconhecer
4. **União (Defesa Civil Nacional)** pode reconhecer para liberar recursos federais

### 1.2 Objetivo do Sistema

O sistema de Decretações gerencia todo o ciclo de vida do processo de reconhecimento:
- Registro de novos processos de desastre
- Documentação de danos (humanos, materiais, econômicos)
- Acompanhamento de status e prazos
- Integração com sistemas externos (S2iD, Hexagon)
- Geração de relatórios e estatísticas para tomada de decisão

---

## 2. Modelo de Dados (Arquitetura de Entidades)

### 2.1 Diagrama de Entidades Principais

```
EntradaProcesso (Processo de Reconhecimento)
├── DecretoMunicipio (N:N com Município)
│   └── Municipio
├── EntradaCategoriaDesastre (Categoria por Município)
│   ├── DesastreCategoria (ex: Danos Humanos, Materiais)
│   └── EntradaDesastre (Dados preenchidos)
│       └── DesastreItemCampo (Campo específico)
│           └── DesastreItem (ex: Mortos, Feridos)
│               └── DesastreGrupo (Agrupador de categorias)
├── EntradaDecreto (Informações do Decreto)
│   └── DecretoCategoria
└── EntradaProcessoLog (Auditoria)
```

### 2.2 Descrição Detalhada das Entidades

#### **EntradaProcesso** (Tabela: `dec_entrada_processos`)
**Papel**: Agregado raiz do processo de reconhecimento

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador único |
| data_entrada | date | Data de entrada do processo na CEDEC |
| data_ocorrencia_desastre | date | Data que o desastre ocorreu |
| processo | string | Tipo: "ESTADUAL" ou "MUNICIPAL" |
| analista | string | Nome do analista responsável |
| n_protocolo_fide | string | Número do protocolo no sistema S2iD |
| decreto_municipal | string | Número do decreto municipal |
| tipo_desastre_id | bigint | FK para classificação COBRADE |
| tipo_desastre | string | Descrição textual do tipo |
| data_decreto_municipal | date | Data do decreto do município |
| data_publicacao_mg | date | Data de publicação no Diário Oficial MG |
| prazo_vigencia | integer | Prazo em dias (90, 120, 180) |
| reconhecimento | string | **Status do processo** (ver seção 3.1) |
| reconhecimento_decreto_n_data | string | Número e data do decreto de reconhecimento |
| data_publicacao_diario | string | Data publicação do reconhecimento |
| portaria_reconhecimento_fed | string | Portaria federal de reconhecimento |
| portaria_diario_oficial | string | Publicação no DOU |
| reconhecimento_federal | string | Status reconhecimento federal |
| observacoes | text | Observações gerais |
| processo_inserido_sei | string | Número do processo no SEI |
| created_by | string | Usuário que criou |

**Atributos Calculados** (Appends):
- `data_vencimento`: data_publicacao_mg + prazo_vigencia dias
- `dias_restantes`: dias até o vencimento
- `tipo_desastre_nome`: Nome do desastre baseado no COBRADE
- `tipo_desastre_cobrade`: Código COBRADE

---

#### **DecretoMunicipio** (Tabela: `dec_decreto_municipios`)
**Papel**: Relacionamento N:N entre EntradaProcesso e Município

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| entrada_processos_id | bigint | FK para EntradaProcesso |
| municipio_id | bigint | FK para Município |
| n_protocolo_fide | string | Protocolo FIDE específico do município |

**Regras**:
- Processo MUNICIPAL: 1 município
- Processo ESTADUAL: N municípios (enchentes regionais, por exemplo)

---

#### **DesastreCategoria** (Tabela: `dec_desastre_categorias`)
**Papel**: Categorias de dados do desastre (template)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| titulo | string | Ex: "DANOS HUMANOS", "DANOS MATERIAIS" |
| informacao | text | Informações auxiliares |
| descricao | text | Descrição detalhada |

**Categorias Padrão**:
1. DANOS HUMANOS
2. DANOS MATERIAIS
3. PREJUÍZOS ECONÔMICOS PÚBLICOS
4. PREJUÍZOS ECONÔMICOS PRIVADOS

---

#### **DesastreItem** (Tabela: `dec_desastre_items`)
**Papel**: Itens dentro de cada categoria

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| categoria_id | bigint | FK para DesastreCategoria |
| titulo | string | Ex: "Mortos", "Feridos", "Desabrigados" |
| observacao | text | Observações do item |

**Exemplos de Items da Categoria "DANOS HUMANOS"**:
- ID 1: Mortos
- ID 2: Feridos
- ID 3: Enfermos
- ID 4: Desabrigados
- ID 5: Desalojados
- ID 6: Desaparecidos
- ID 7: Outros afetados

---

#### **DesastreItemCampo** (Tabela: `dec_desastre_item_campos`)
**Papel**: Campos específicos de cada item (formulário dinâmico)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| desastre_item_id | bigint | FK para DesastreItem |
| tipo | string | Tipo do campo: "text", "number", "currency" |
| titulo | string | Label do campo: "Quantidade", "Valor (R$)" |

**Exemplo**:
- Item "Mortos" pode ter campo "Quantidade" (tipo: number)
- Item "Casas Destruídas" pode ter:
  - Campo "Quantidades destruídas" (tipo: number)
  - Campo "Valor (R$)" (tipo: currency)

---

#### **EntradaCategoriaDesastre** (Tabela: `dec_entrada_categoria_desastres`)
**Papel**: Instância de uma categoria para um município específico em um processo

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| entrada_processo_id | bigint | FK para EntradaProcesso |
| municipio_id | bigint | FK para Município |
| categoria_id | bigint | FK para DesastreCategoria |
| descricao | text | Descrição específica desta categoria |

---

#### **EntradaDesastre** (Tabela: `dec_entrada_desastres`)
**Papel**: Valores preenchidos pelo usuário (dados efetivos)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| entrada_processo_id | bigint | FK para EntradaProcesso |
| municipio_id | bigint | FK para Município |
| entrada_categoria_desastre_id | bigint | FK para EntradaCategoriaDesastre |
| item_id | bigint | FK para DesastreItem |
| item_campo_id | bigint | FK para DesastreItemCampo |
| campo_titulo | string | Título do campo (cache) |
| valor | string | Valor preenchido pelo usuário |

**Características**:
- Soft Deletes habilitado
- Auditoria com LogsModelChanges trait
- Armazena dados de forma EAV (Entity-Attribute-Value)

---

#### **EntradaDecreto** (Tabela: `dec_entrada_decretos`)
**Papel**: Informações complementares do decreto

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| entrada_processos_id | bigint | FK para EntradaProcesso |
| decreto_categoria_id | bigint | FK para DecretoCategoria |
| observacao | string | Observações específicas |

---

#### **EntradaProcessoLog** (Tabela: `dec_entrada_processo_logs`)
**Papel**: Auditoria completa de mudanças

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | bigint | Identificador |
| entrada_processo_id | bigint | FK para EntradaProcesso |
| entrada_processo_data | json | Snapshot completo do processo |
| action | string | "created", "updated", "deleted" |
| created_at | timestamp | Data da mudança |

---

### 2.3 Relacionamentos Críticos

```php
// EntradaProcesso
$processo->municipios // hasManyThrough DecretoMunicipio
$processo->decretoMunicipios // hasMany DecretoMunicipio
$processo->desastres // hasMany EntradaCategoriaDesastre
$processo->logs // hasMany EntradaProcessoLog

// EntradaCategoriaDesastre
$categoria->municipio // belongsTo Municipio
$categoria->categoria // belongsTo DesastreCategoria
$categoria->entradaItems // hasMany EntradaDesastre
$categoria->entradaProcesso // belongsTo EntradaProcesso

// EntradaDesastre
$entrada->municipio // belongsTo Municipio
$entrada->item // belongsTo DesastreItem
$entrada->itemCampo // belongsTo DesastreItemCampo
$entrada->entradaCategoria // belongsTo EntradaCategoriaDesastre
```

---

## 3. Fluxos de Trabalho

### 3.1 Máquina de Estados do Processo

O campo `reconhecimento` segue esta máquina de estados:

```
[Registro] → [Aguardando assinatura clube CEDEC]
           → [Aguardando assinatura Diretor da DAT]
           → [Aguardando Análise do Estado]
           → [Em análise pelo Estado]
           → [Aguardando ajustes do município]
           → [Aguardando Nota Jurídica]
           → [Reconhecido pelo Estado / Aguardando análise da União]
           → [Reconhecido pelo Estado e pela União]
           → [Enviado para Publicação]
           → [Envio Direto para União]
           → [Reconhecido somente pela União]
           → [Reconhecido somente pelo Estado]
           → [Não reconhecido pelo Estado]
           → [Não reconhecido pela União]
           → [Não reconhecido pelo Estado e União]
```

### 3.2 Fluxo de Criação de Processo

**Controller**: `EntradaProcessosController@create`

1. **Validação** (StoreEntradaProcessoRequest)
   - Tipo do Desastre (COBRADE) obrigatório
   - Origem (ESTADUAL/MUNICIPAL) obrigatória
   - Município(s) obrigatório(s)
   - Status do Processo obrigatório

2. **Transação DB**
   ```php
   DB::beginTransaction()
   - Cria EntradaProcesso
   - Sincroniza Municípios (syncMunicipalities)
   - Sincroniza Informações do Decreto (syncInformacoesDecreto)
   DB::commit()
   ```

3. **Pós-Criação**
   - Atualiza Hexagon Service (integração externa)
   - Redireciona para formulário de desastres

4. **Redirect**
   - Redireciona para `formDesastres` para preenchimento de danos

### 3.3 Fluxo de Preenchimento de Dados de Desastre

**Controller**: `EntradaProcessosController@formDesastres`

1. **Carregamento de Dados**
   - Carrega municípios com dados de desastre existentes
   - Carrega categorias, items e campos dinamicamente
   - Carrega valores já preenchidos (modo edição)

2. **Apresentação**
   - Interface organizada por municípios
   - Cada município tem suas categorias
   - Cada categoria tem seus items e campos

3. **Submissão** (`updateDesastres`)
   - Recebe JSON com todos os dados
   - `DesastreDataService::processDesastresData`
   - Atualiza/cria registros em EntradaDesastre

### 3.4 Fluxo de Edição

**Controller**: `EntradaProcessosController@edit`

1. Carrega processo existente
2. Carrega municípios relacionados
3. Carrega informações do decreto
4. `prepareProcessoForEdit` enriquece com dados COBRADE
5. Apresenta formulário preenchido

### 3.5 Fluxo de Listagem e Filtros

**Controller**: `EntradaProcessosController@index`

1. **Filtros Disponíveis** (EntradaProcessoFilter):
   - Busca textual
   - Data de entrada (período)
   - Tipo de processo (ESTADUAL/MUNICIPAL)
   - Status (reconhecimento)
   - Analista
   - Tipo de desastre (COBRADE)
   - Município
   - Protocolo FIDE
   - Status de vigência (vigente/vencido)

2. **Processamento**
   - `EntradaProcessoService::getFilteredProcessos`
   - Paginação: 15 por página
   - Cálculo de totais de desastres por entrada

3. **Exportação**
   - Parâmetro `?xlsx`: exporta Excel
   - Parâmetro `?json`: retorna JSON
   - Inclui totais agregados

---

## 4. Características do Frontend (Sistema Legado)

### 4.1 Tecnologias Utilizadas

- **Framework**: Laravel Blade (server-side rendering)
- **JavaScript**: Vue.js 2.x (inline components)
- **CSS**: Bootstrap 5 + CSS customizado
- **Formulários**: Dinâmicos baseados em tabelas HTML

### 4.2 Páginas Principais

#### A. Index (`decretacoes/index.blade.php`)

**Características**:
- Cards com estatísticas filtráveis
- Sistema de filtros colapsável
- Lista paginada de processos
- Badges coloridos para status
- Hover cards com informações detalhadas
- Ações: Ver, Editar, Excluir, Exportar

**Componentes Visuais**:
```css
.modern-card       // Cards com gradiente e sombra
.filter-section    // Seção de filtros colapsável
.filter-badge      // Tags de filtros ativos
.action-icon       // Ícones de ação (editar, excluir)
.hover-modal       // Modal flutuante ao passar mouse
.stat-bar-btn      // Botões de estatística clicáveis
```

**Filtros Avançados**:
- Busca textual (processo, analista, município)
- Período de entrada
- Tipo de processo (ESTADUAL/MUNICIPAL)
- Status do reconhecimento
- Analista responsável
- Tipo de desastre (select com COBRADE)
- Município específico
- Protocolo FIDE
- Status de vigência (vigente/vencido/próximo ao vencimento)

#### B. Formulário de Processo (`formularios/entrada_processos.blade.php`)

**Características**:
- Layout em tabela (approach legado)
- Vue.js para interatividade
- Select dinâmico de municípios
- Cálculo automático de Redec
- Validação client-side

**Campos Principais**:
1. **Tipo do Desastre** (select com COBRADE)
2. **COBRADE** (readonly, calculado)
3. **Data de Entrada**
4. **Data de Ocorrência**
5. **Origem** (ESTADUAL/MUNICIPAL)
   - Se MUNICIPAL: 1 município
   - Se ESTADUAL: N municípios (multi-select com tags)
6. **Redec** (calculado automaticamente)
7. **Status do Processo** (select com estados)
8. **Analista**
9. **Protocolo FIDE**
10. **Decreto Municipal** (número)
11. **Data Decreto Municipal**
12. **Data Publicação MG**
13. **Prazo Vigência** (dias)
14. **Reconhecimento Estadual** (dados)
15. **Reconhecimento Federal** (dados)
16. **Observações**
17. **Processo SEI**

**Comportamentos Dinâmicos**:
```javascript
// Vue.js component inline
watch: {
  'formdata.processo': function(newVal) {
    // Muda interface se MUNICIPAL/ESTADUAL
  },
  'selectedTipoDesastreId': function(newVal) {
    // Atualiza COBRADE automaticamente
  }
}
```

#### C. Formulário de Desastres (`formularios/entrada_desastres.blade.php`)

**Características**:
- Formulário dinâmico baseado em categorias
- Organizado por município
- Cada município tem todas as categorias
- Campos gerados dinamicamente do banco

**Estrutura**:
```
Para cada Município:
  Para cada DesastreGrupo:
    Para cada DesastreCategoria:
      Para cada DesastreItem:
        Para cada DesastreItemCampo:
          <input/> ou <textarea/> com valor
```

**Exemplo Visual**:
```
Município: Belo Horizonte

[DANOS HUMANOS]
  Mortos
    Quantidade: [____]
  Feridos
    Quantidade: [____]
  Desabrigados
    Quantidade: [____]
  ...

[DANOS MATERIAIS]
  Casas
    Quantidades destruídas: [____]
    Quantidades danificadas: [____]
    Valor (R$): [____]
  ...

[PREJUÍZOS ECONÔMICOS PÚBLICOS]
  Agricultura
    Área afetada (ha): [____]
    Valor do prejuízo (R$): [____]
  ...
```

### 4.3 Recursos de UX

#### Indicadores Visuais
- **Cores de Status**:
  - Azul: Registro
  - Amarelo: Aguardando análise
  - Verde: Reconhecido
  - Vermelho: Não reconhecido

- **Alertas de Prazo**:
  - Verde: Mais de 30 dias
  - Amarelo: 15-30 dias
  - Vermelho: Menos de 15 dias

#### Feedback em Tempo Real
- Validação de formulário ao digitar
- Mensagens de sucesso/erro com toast/alert
- Loading spinners em operações assíncronas

---

## 5. Integrações e Serviços Externos

### 5.1 Sistema Hexagon

**Finalidade**: Sistema de gestão de emergências

**Endpoint**:
```
POST http://ComOnCallQA/HxGN.DecretoAPI/CEDECDecretos/api/Decretos
```

**Payload** (HexagonDecretoResource):
```json
{
  "GeocodigoIbge": "3106200",
  "Municipio": "Belo Horizonte",
  "Mesorregiao": "Metropolitana de BH",
  "Redec": "01",
  "NumeroProtocoloFide": "12345",
  "DataPublicacao": "2025-01-15",
  "PrazoVigencia": 90
}
```

**Trigger**:
- Ao criar processo
- Ao atualizar processo (se campos obrigatórios preenchidos)

**Validação Antes de Enviar** (hasRequiredDataForHexagon):
- `n_protocolo_fide` preenchido
- `data_publicacao_mg` preenchida
- `prazo_vigencia` preenchido
- Município com `Codmundv`, `nome`, `macroregiao`, `rpm`

### 5.2 Sistema S2iD (Nacional)

**Finalidade**: Sistema integrado de informações sobre desastres

**Integração**: Manual (via protocolo FIDE)
- Município cadastra desastre no S2iD
- Recebe número de protocolo FIDE
- Insere protocolo no sistema SDC

### 5.3 SEI (Sistema Eletrônico de Informações)

**Integração**: Manual
- Processo físico/digital tramita no SEI
- Número do processo SEI registrado no campo `processo_inserido_sei`

### 5.4 Sistema Legado de Ajuda Humanitária

**Tabelas**: `aju_h_pedido_pedid`, `aju_h_pedido_itens`

**Função**: Relacionar decretos com pedidos de ajuda

**Query** (`getPedidoAhData`):
```sql
SELECT
  descricao_item,
  tp_item,
  SUM(qtd) as total_qtd
FROM aju_h_pedido_pedid
JOIN aju_h_pedido_itens ON ...
WHERE num_decreto = ?
GROUP BY descricao_item, tp_item
```

---

## 6. Funcionalidades Avançadas

### 6.1 Cálculo de Totais de Desastres

**Service**: `EntradaProcessoService::getTotalDesastresCountFromEntradas`

**Funcionalidade**:
- Agrega valores de EntradaDesastre por categoria
- Suporta tipos: `number`, `currency`
- Gera dois níveis:
  - Totais gerais por processo
  - Totais por município

**Exemplo de Output**:
```php
$processo->desastre_totals = [
  'DANOS HUMANOS' => [
    'Mortos' => 5,
    'Feridos' => 12,
    'Desabrigados' => 150
  ],
  'DANOS MATERIAIS' => [
    'Quantidades destruídas' => 45,
    'Valor (R$)' => '1.500.000,00'
  ]
]

$processo->desastre_totals_por_municipio = [
  'Belo Horizonte' => [
    'DANOS HUMANOS' => [...],
    ...
  ],
  'Contagem' => [...]
]
```

### 6.2 Exportação para Power BI

**Endpoint**: `getNormalizedDataForPowerBI`

**Características**:
- Estrutura flat (1 linha = 1 município + 1 processo)
- Inclui dados geoespaciais (lat/long)
- Inclui totalizadores calculados
- Suporta filtros
- Opção `include_deleted` para análise histórica

**Campos Exportados** (43 campos):
```
- Dados do Processo: id, protocolo, data_registro, status
- Dados do Município: nome, codigo_ibge, macroregiao, lat/long
- Dados do Desastre: cobrade, tipo_desastre, data_fato
- Danos Humanos: obitos, feridos, desabrigados, desalojados
- Danos Materiais: danificadas, destruidas, valor
- Prejuízos: publicos_valor, privados_valor
- Prazos: data_vencimento, dias_restantes
```

### 6.3 Sistema de Auditoria

**Trait**: `LogsModelChanges`

**Tabela**: `dec_entrada_processo_logs`

**Eventos Capturados**:
- `created`: Snapshot completo ao criar
- `updated`: Snapshot do estado após update
- `deleted`: Snapshot antes de soft delete

**Uso**:
```php
EntradaProcesso::boot()
static::created(fn($m) => $m->logChange('created'))
static::updated(fn($m) => $m->logChange('updated'))
static::deleted(fn($m) => $m->logChange('deleted'))
```

---

## 7. Análise de Pontos de Melhoria

### 7.1 Problemas Identificados no Sistema Legado

#### A. Arquitetura de Dados
- **EAV Excessivo**: EntradaDesastre usa padrão EAV que dificulta queries
- **Falta de Validação**: Campos de valor são strings, permite inconsistências
- **Denormalização**: Alguns dados duplicados (campo_titulo em EntradaDesastre)

#### B. Frontend
- **Tabelas HTML**: Layout de formulário ultrapassado
- **Vue.js Inline**: Componentes não reutilizáveis
- **Sem Responsividade**: Formulários grandes em mobile
- **Sem Validação Visual**: Faltam indicadores de campos obrigatórios claros

#### C. UX
- **Formulário Longo**: Muitos campos de uma vez
- **Sem Wizard**: Não há divisão em etapas
- **Falta de Feedback**: Salvamento sem indicador claro
- **Sem Autosave**: Perde dados se sair sem salvar

#### D. Integrações
- **Hexagon Hardcoded**: URL e credenciais no código
- **Sem Retry**: Falha de integração não tenta novamente
- **Sem Queue**: Integrações síncronas travam o usuário

---

## 8. Planejamento para Migração ao NewSDC

### 8.1 Arquitetura Proposta (DDD + CQRS)

```
SDC/app/Modules/Decretacoes/
├── Domain/
│   ├── Entities/
│   │   ├── Processo.php (Aggregate Root)
│   │   ├── ProcessoMunicipio.php
│   │   ├── DadosDesastre.php (Value Object)
│   │   ├── DanosHumanos.php (Value Object)
│   │   └── StatusProcesso.php (Enum)
│   ├── Events/
│   │   ├── ProcessoCriado.php
│   │   ├── ProcessoAtualizado.php
│   │   ├── ProcessoReconhecido.php
│   │   └── PrazoProximoVencimento.php
│   ├── Repositories/
│   │   └── ProcessoRepositoryInterface.php
│   └── ValueObjects/
│       ├── ProtocoloFIDE.php
│       ├── COBRADE.php
│       └── PrazoVigencia.php
│
├── Application/
│   ├── UseCases/
│   │   ├── CriarProcessoUseCase.php
│   │   ├── AtualizarProcessoUseCase.php
│   │   ├── PreencherDadosDesastreUseCase.php
│   │   ├── ReconhecerProcessoUseCase.php
│   │   └── ExportarProcessosUseCase.php
│   ├── DTOs/
│   │   ├── CriarProcessoDTO.php
│   │   └── DadosDesastreDTO.php
│   └── Queries/
│       ├── ListarProcessosQuery.php
│       └── ObterEstatisticasQuery.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── EloquentProcessoRepository.php
│   │   └── Migrations/
│   ├── ExternalServices/
│   │   ├── HexagonClient.php
│   │   └── S2iDClient.php
│   └── Jobs/
│       ├── SincronizarHexagonJob.php
│       └── NotificarPrazoVencimentoJob.php
│
└── Presentation/
    ├── Http/
    │   ├── Controllers/
    │   │   ├── ProcessoCreateController.php
    │   │   ├── ProcessoUpdateController.php
    │   │   ├── ProcessoShowController.php
    │   │   └── ProcessoIndexController.php
    │   ├── Requests/
    │   │   ├── StoreProcessoRequest.php
    │   │   └── UpdateDadosDesastreRequest.php
    │   └── Resources/
    │       └── ProcessoResource.php
    └── Console/
        └── Commands/
            └── VerificarPrazosCommand.php
```

### 8.2 Modelo de Dados Otimizado

#### Mudanças Propostas:

1. **Tabela `processos`** (ex-dec_entrada_processos)
   - Adicionar: `status` (enum mais restrito)
   - Adicionar: `tipo_decreto` (SE/ECP)
   - Adicionar: `geom` (PostGIS - área afetada)
   - Separar datas em timestamps

2. **Tabela `processo_danos_humanos`**
   - Desnormalizar dados humanos (colunas dedicadas):
     ```sql
     CREATE TABLE processo_danos_humanos (
       id BIGINT PRIMARY KEY,
       processo_id BIGINT,
       municipio_id BIGINT,
       obitos INT DEFAULT 0,
       feridos INT DEFAULT 0,
       desabrigados INT DEFAULT 0,
       desalojados INT DEFAULT 0,
       desaparecidos INT DEFAULT 0,
       outros_afetados INT DEFAULT 0,
       ...
     )
     ```

3. **Tabela `processo_danos_materiais`**
   - Colunas dedicadas para tipos comuns
   ```sql
   casas_destruidas INT,
   casas_danificadas INT,
   valor_danos DECIMAL(15,2)
   ```

4. **Tabela `processo_prejuizos`**
   - `tipo` (PUBLICO/PRIVADO)
   - `categoria` (AGRICULTURA, INDUSTRIA, etc)
   - `valor` (DECIMAL)

**Vantagens**:
- Queries mais rápidas (sem JOINs complexos)
- Validação em nível de banco
- Facilita agregações para BI

### 8.3 Frontend Moderno (Vue 3 + Inertia.js)

#### A. Stack Tecnológica
- **Framework**: Vue 3 (Composition API)
- **Comunicação**: Inertia.js
- **UI**: TailwindCSS + HeadlessUI
- **Formulários**: VeeValidate + Yup
- **Estado**: Pinia
- **Mapas**: Leaflet/Mapbox

#### B. Componentes Principais

```
resources/js/Pages/Decretacoes/
├── ProcessoIndex.vue
├── ProcessoCreate/
│   ├── ProcessoCreateTemplate.vue
│   ├── Steps/
│   │   ├── Step1DadosBasicos.vue
│   │   ├── Step2Municipios.vue
│   │   ├── Step3LocalizacaoMapa.vue
│   │   ├── Step4DanosHumanos.vue
│   │   ├── Step5DanosMateriais.vue
│   │   ├── Step6Prejuizos.vue
│   │   └── Step7Revisao.vue
│   └── ProcessoCreateWizard.vue
└── ProcessoShow.vue

resources/js/Components/Organisms/Decretacoes/
├── ProcessoCard.vue
├── ProcessoTable.vue
├── ProcessoTimeline.vue (Status visual)
├── ProcessoMapView.vue
├── DanosHumanosForm.vue
├── DanosMateriaisForm.vue
└── ProcessoFilters.vue

resources/js/Components/Molecules/Decretacoes/
├── COBRADESelect.vue
├── MunicipioMultiSelect.vue
├── StatusBadge.vue
├── PrazoIndicator.vue
└── ProtocoloFIDEInput.vue
```

#### C. Wizard Pattern (Multi-Step Form)

```vue
<template>
  <WizardContainer>
    <WizardStep :step="1" title="Dados Básicos">
      <DadosBasicosForm v-model="form.dados" />
    </WizardStep>

    <WizardStep :step="2" title="Municípios">
      <MunicipiosSelector
        v-model="form.municipios"
        :tipo="form.dados.tipo_processo"
      />
    </WizardStep>

    <WizardStep :step="3" title="Localização">
      <MapaDesastre
        v-model:geom="form.area_afetada"
        :municipios="form.municipios"
      />
    </WizardStep>

    <WizardStep :step="4" title="Danos Humanos">
      <DanosHumanosGrid
        v-model="form.danos_humanos"
        :municipios="form.municipios"
      />
    </WizardStep>

    <!-- ... outros steps ... -->

    <WizardStep :step="7" title="Revisão">
      <ProcessoRevisao :data="form" @submit="handleSubmit" />
    </WizardStep>
  </WizardContainer>
</template>
```

**Benefícios**:
- Reduz sobrecarga cognitiva
- Permite validação por etapa
- Melhor em mobile
- Autosave entre etapas

#### D. Features de UX

1. **Timeline de Status**
```vue
<ProcessoTimeline :status="processo.status">
  <TimelineItem
    status="completed"
    label="Registro"
    date="01/12/2024"
  />
  <TimelineItem
    status="active"
    label="Em análise"
  />
  <TimelineItem
    status="pending"
    label="Reconhecimento"
  />
</ProcessoTimeline>
```

2. **Mapa Interativo**
- Desenhar polígono da área afetada
- Validar que área está dentro do município
- Calcular área afetada automaticamente
- Visualizar municípios selecionados

3. **Validação em Tempo Real**
```javascript
import { useForm } from 'vee-validate'
import * as yup from 'yup'

const schema = yup.object({
  tipo_desastre_id: yup.number().required('Tipo de desastre obrigatório'),
  processo: yup.string().oneOf(['ESTADUAL', 'MUNICIPAL']),
  municipios: yup.array().min(1, 'Selecione ao menos um município'),
  danos_humanos: yup.object({
    obitos: yup.number().min(0).max(1000000)
  })
})
```

4. **Autosave**
```javascript
const { form, isDirty } = useProcessoForm()

watchDebounced(
  form,
  async (newForm) => {
    if (isDirty) {
      await saveDraft(newForm)
      showToast('Rascunho salvo automaticamente')
    }
  },
  { debounce: 2000, deep: true }
)
```

### 8.4 Melhorias de Backend

#### A. Eventos de Domínio

```php
// Domain/Events/ProcessoCriado.php
class ProcessoCriado
{
    public function __construct(
        public Processo $processo
    ) {}
}

// Listeners
class NotificarRedecListener
{
    public function handle(ProcessoCriado $event)
    {
        $redec = $event->processo->municipios->first()->redec;
        Mail::to($redec->email)->send(new NovoProcessoMail($event->processo));
    }
}

class SincronizarHexagonListener
{
    public function handle(ProcessoCriado $event)
    {
        SincronizarHexagonJob::dispatch($event->processo);
    }
}
```

#### B. Jobs Assíncronos

```php
// Infrastructure/Jobs/SincronizarHexagonJob.php
class SincronizarHexagonJob implements ShouldQueue
{
    use Queueable, Dispatchable;

    public function __construct(
        public Processo $processo
    ) {}

    public function handle(HexagonClient $hexagon)
    {
        $hexagon->sincronizar($this->processo);
    }

    public function failed(Throwable $exception)
    {
        Log::error('Falha ao sincronizar Hexagon', [
            'processo_id' => $this->processo->id,
            'error' => $exception->getMessage()
        ]);

        // Tentar novamente em 1 hora
        $this->release(3600);
    }
}
```

#### C. CQRS - Separação de Leitura e Escrita

```php
// Write Model (Commands)
class CriarProcessoUseCase
{
    public function execute(CriarProcessoDTO $dto): Processo
    {
        DB::beginTransaction();

        $processo = Processo::create([...]);
        $processo->adicionarMunicipios($dto->municipios);

        event(new ProcessoCriado($processo));

        DB::commit();
        return $processo;
    }
}

// Read Model (Queries)
class ListarProcessosQuery
{
    public function execute(FiltrosDTO $filtros): Collection
    {
        return ProcessoReadModel::query()
            ->filtrar($filtros)
            ->comMunicipios()
            ->comTotalizadores()
            ->paginate(15);
    }
}

// Tabela otimizada para leitura
class ProcessoReadModel extends Model
{
    protected $table = 'processos_read_model';

    // Campos desnormalizados para performance
    protected $casts = [
        'municipios_nomes' => 'array',
        'totais_danos' => 'array',
        'status_timeline' => 'array'
    ];
}
```

### 8.5 Integrações Robustas

#### A. Cliente Hexagon

```php
// Infrastructure/ExternalServices/HexagonClient.php
class HexagonClient
{
    public function __construct(
        private HttpClient $http,
        private string $baseUrl,
        private string $apiKey
    ) {}

    public function sincronizar(Processo $processo): HexagonResponse
    {
        $payload = HexagonMapper::toPayload($processo);

        return $this->http
            ->retry(3, 1000) // 3 tentativas, intervalo 1s
            ->timeout(30)
            ->withToken($this->apiKey)
            ->post("{$this->baseUrl}/api/Decretos", $payload)
            ->throw()
            ->json();
    }
}

// Config
// config/integrations.php
return [
    'hexagon' => [
        'base_url' => env('HEXAGON_BASE_URL'),
        'api_key' => env('HEXAGON_API_KEY'),
        'timeout' => 30,
        'retry_times' => 3
    ]
];
```

### 8.6 Monitoramento e Alertas

```php
// Console/Commands/VerificarPrazosCommand.php
class VerificarPrazosCommand extends Command
{
    protected $signature = 'decretacoes:verificar-prazos';

    public function handle()
    {
        $processosProximosVencer = Processo::query()
            ->where('status', 'RECONHECIDO')
            ->whereRaw('DATEDIFF(data_vencimento, CURDATE()) BETWEEN 1 AND 15')
            ->get();

        foreach ($processosProximosVencer as $processo) {
            event(new PrazoProximoVencimento($processo));
        }

        $this->info("Verificados {$processosProximosVencer->count()} processos");
    }
}

// Listener
class NotificarPrazoProximoListener
{
    public function handle(PrazoProximoVencimento $event)
    {
        $processo = $event->processo;

        Mail::to($processo->analista->email)
            ->send(new PrazoVencendoMail($processo));
    }
}

// Scheduler (app/Console/Kernel.php)
$schedule->command('decretacoes:verificar-prazos')->daily();
```

---

## 9. Cronograma de Migração (Sugestão)

### Fase 1: Preparação (2 semanas)
- [ ] Criar módulo Decretacoes no NewSDC
- [ ] Implementar migrations
- [ ] Criar seeders de categorias/items/campos
- [ ] Configurar repositórios e interfaces

### Fase 2: Domain Layer (2 semanas)
- [ ] Implementar entidades (Processo, DadosDesastre)
- [ ] Criar Value Objects (COBRADE, ProtocoloFIDE)
- [ ] Implementar eventos de domínio
- [ ] Criar repositórios

### Fase 3: Application Layer (2 semanas)
- [ ] Implementar Use Cases (Criar, Atualizar, etc)
- [ ] Criar DTOs
- [ ] Implementar queries (CQRS read model)
- [ ] Testes unitários de use cases

### Fase 4: Infrastructure (1 semana)
- [ ] Implementar EloquentProcessoRepository
- [ ] Criar HexagonClient
- [ ] Implementar Jobs (sincronização)
- [ ] Configurar eventos e listeners

### Fase 5: Frontend - Componentes Base (2 semanas)
- [ ] Criar componentes atômicos (inputs, selects)
- [ ] Criar COBRADESelect
- [ ] Criar MunicipioMultiSelect
- [ ] Criar componentes de mapa

### Fase 6: Frontend - Páginas (3 semanas)
- [ ] ProcessoIndex (listagem + filtros)
- [ ] ProcessoCreate (wizard multi-step)
- [ ] ProcessoShow (visualização)
- [ ] ProcessoEdit
- [ ] Testes E2E

### Fase 7: Integrações (1 semana)
- [ ] Testar integração Hexagon
- [ ] Implementar retry logic
- [ ] Criar dashboard de monitoramento

### Fase 8: Migração de Dados (1 semana)
- [ ] Script de migração do legado
- [ ] Validação de integridade
- [ ] Testes com dados reais

### Fase 9: Homologação (1 semana)
- [ ] Testes com usuários
- [ ] Ajustes de UX
- [ ] Performance tuning

### Fase 10: Deploy (1 semana)
- [ ] Deploy gradual (feature flag)
- [ ] Treinamento de usuários
- [ ] Monitoramento intensivo
- [ ] Suporte 24/7

---

## 10. Considerações Finais

### Pontos Críticos de Atenção

1. **Integridade de Dados**
   - Sistema está em produção com dados críticos
   - Migração deve preservar histórico completo
   - Auditoria deve ser mantida

2. **Performance**
   - Queries de totalização são pesadas
   - Considerar cache (Redis) para dashboards
   - Read models para consultas frequentes

3. **Segurança**
   - Processos contêm dados sensíveis
   - Implementar RBAC granular
   - Logs de acesso detalhados

4. **Usabilidade**
   - Sistema usado por diferentes perfis
   - Interface deve ser intuitiva
   - Mobile-first para analistas em campo

5. **Escalabilidade**
   - Sistema pode crescer significativamente
   - Jobs assíncronos para operações pesadas
   - Sharding de banco se necessário

### Melhorias Futuras Possíveis

- [ ] OCR para digitalização de documentos
- [ ] IA para sugerir valores baseado em histórico
- [ ] Integração com sensores IoT (pluviômetros)
- [ ] App mobile nativo
- [ ] API pública para municípios
- [ ] Chatbot para dúvidas frequentes
- [ ] Dashboard em tempo real (WebSockets)

---

**Documento gerado em**: 2025-12-27
**Versão**: 1.0
**Autor**: Mapeamento baseado no código-fonte do sistema legado SDC
