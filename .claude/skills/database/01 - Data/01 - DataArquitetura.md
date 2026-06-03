# Arquitetura de Banco de Dados: Schema, Vetores e Otimizações

Este documento detalha a arquitetura do banco de dados PostgreSQL do projeto NewSDC, com foco nas customizações, extensões e padrões de design utilizados.

---

## 1. Arquitetura de Conexões (Multi-Database)

A configuração em `config/database.php` revela uma estratégia sofisticada de múltiplas conexões, em vez de uma única conexão monolítica. Isso serve para isolar cargas de trabalho e otimizar a performance.

- **`pgsql` (Padrão):** A conexão principal usada pelas requisições web da aplicação.
- **`pgsql_webhook`:** Uma conexão dedicada para processar jobs de webhooks. Isso é crucial para que um pico de webhooks externos não esgote o pool de conexões da aplicação principal, garantindo a estabilidade do sistema.
- **`pgsql_read` (Réplica de Leitura / IA):** Conexão que aponta para uma réplica de leitura do banco de dados ou para um banco de dados especializado em IA (`sdc_ai`). Usado para consultas pesadas que não precisam dos dados mais recentes em tempo real, como dashboards de BI e, principalmente, cargas de trabalho de IA.
- **`legacy`:** Conexão para integração e migração de um banco de dados MySQL legado.

**Benefício:** Essa arquitetura previne que uma carga de trabalho (ex: um webhook em massa) impacte negativamente a performance de outra (ex: a navegação do usuário no painel).

---

## 2. Provisionamento de Extensões via Migrations

O projeto adota a prática recomendada de habilitar as extensões do PostgreSQL diretamente nos arquivos de migração. Isso garante que o ambiente seja configurado de forma automática e consistente em qualquer máquina.

As seguintes extensões são habilitadas:

- **`vector`:** Habilitada em `..._create_dec_embeddings_table.php`. Essencial para machine learning e buscas de similaridade (ver seção 3).
- **`citus`:** Habilitada em `..._enable_postgres_extensions.php`. A extensão Citus transforma o PostgreSQL em um banco de dados distribuído, permitindo escalar horizontalmente. **Esta é uma decisão arquitetural de grande impacto**, indicando que o sistema foi projetado para suportar volumes de dados massivos.
- **`pg_trgm`:** Habilitada em `..._enable_pg_extensions.php`. Usada para otimizar buscas de texto por similaridade (fuzzy search) através de trigramas. É muito mais eficiente que um `LIKE '%search%'`.

---

## 3. Deep Dive: `pgvector` para Inteligência Artificial

O coração da estratégia de IA no banco de dados reside na migration `2026_04_27_000001_create_dec_embeddings_table.php`.

```php
// ..._create_dec_embeddings_table.php

// 1. Habilita a extensão
$conn->statement('CREATE EXTENSION IF NOT EXISTS vector');

// 2. Cria a tabela com a coluna de vetor
Schema::connection('pgsql_read')->create('dec_embeddings', function (Blueprint $table) {
    // ... colunas
    $table->text('content_hash');
    $table->text('model'); // ex: text-embedding-ada-002
    // ...
});
DB::connection('pgsql_read')->statement(<<<'SQL'
    ALTER TABLE dec_embeddings
    ADD COLUMN  embedding   vector(768),
    ADD COLUMN  token_count integer
SQL);

// 3. Cria o índice otimizado para busca de similaridade
DB::connection('pgsql_read')->statement(<<<'SQL'
    CREATE INDEX ON dec_embeddings
    USING ivfflat (embedding vector_cosine_ops)
    WITH (lists = 100);
SQL);

```

### Análise:
- **Tabela `dec_embeddings`:** Esta tabela é criada na conexão `pgsql_read`, isolando a carga de trabalho de IA. Ela armazena os embeddings gerados por um modelo de IA.
- **Coluna `embedding vector(768)`:** Define uma coluna chamada `embedding` que armazena um vetor de 768 dimensões. Isso é usado para guardar a representação numérica de um texto, permitindo buscas semânticas.
- **Índice `ivfflat` com `vector_cosine_ops`:** Esta é a otimização chave. Em vez de comparar um vetor de busca com todos os outros na tabela (o que seria lento), este índice permite encontrar os vetores mais próximos (usando a métrica de similaridade por cosseno) de forma extremamente rápida. É a tecnologia que potencializa funcionalidades como "encontrar documentos similares".

---

## 4. Tratamento de Dados Geoespaciais

Uma análise completa das migrations revelou que, apesar do uso do PostgreSQL, a extensão **PostGIS não é utilizada**.

- **Armazenamento:** Dados de Latitude e Longitude são armazenados em colunas numéricas padrão, como `decimal` ou `float`. Exemplo na tabela `municipios`:
    ```php
    $table->decimal('latitude', 10, 8)->nullable();
    $table->decimal('longitude', 11, 8)->nullable();
    ```
- **Implicação:** O projeto não necessita (atualmente) de consultas geoespaciais complexas no nível do banco de dados (ex: "encontrar todos os RATs num raio de 5km de um ponto"). As operações com coordenadas são provavelmente realizadas na camada da aplicação (PHP/backend).

---

## 5. Visão Geral do Schema e Relacionamentos

- **Tabelas de Negócio:** A lista de migrations mostra uma estrutura de dados rica, com tabelas para os principais domínios da aplicação: `rats`, `pae_protocolos`, `dec_processo`, `users`, `compdec_orgaos`, `treinamentos`, `estoques`, `plantoes`, etc.
- **Segurança:** A migration `..._create_permission_tables.php` cria as tabelas do pacote `spatie/laravel-permission` (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`), que formam a base do sistema de controle de acesso.
- **Soft Deletes:** Muitas tabelas usam o `softDeletes()`, uma prática comum em Laravel para não destruir dados permanentemente, permitindo a recuperação e auditoria.
- **Otimizações de Performance:** Além dos índices de `pgvector`, múltiplas migrations (`...add_performance_indexes.php`) adicionam índices (`btree`, `gin`) a colunas frequentemente consultadas, demonstrando um foco contínuo na otimização de queries.
