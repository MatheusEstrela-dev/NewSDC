# Guia de Referência: Domain-Driven Design (DDD) para LLMs

Este documento estabelece uma base de conhecimento padronizada sobre Domain-Driven Design (DDD), baseada na obra de Eric Evans. Ele foi estruturado pedagogicamente com regras claras, separação de conceitos e mapeamento de antipadrões para servir de contexto técnico de alta fidelidade.

---

## 1. Filosofia Fundamental do DDD

O DDD **não** é uma arquitetura de software, um framework ou uma metodologia ágil. É uma **abordagem de modelagem de software** para sistemas complexos, baseada em duas premissas:
1. O design do software deve ser estritamente alinhado com o modelo de negócio (Domínio).
2. A complexidade do canal de comunicação entre técnicos e especialistas de negócio deve ser eliminada.

---

## 2. Design Estratégico (Strategic Design)

O Design Estratégico foca na macroarquitetura, na organização das equipes e na divisão do ecossistema de software.

### 2.1. Domínio e Subdomínios
O **Domínio** é o modelo geral do negócio (ex: Logística, Finanças, E-commerce). Ele é dividido em três tipos de subdomínios:
* **Subdomínio Core (Coração):** O diferencial competitivo da empresa. É onde o código deve ser mais customizado, escalável e proprietário.
* **Subdomínio de Suporte (Supporting):** Essencial para o negócio operar, mas não gera diferencial competitivo direta (ex: Catálogo de produtos). Geralmente desenvolvido internamente de forma simples.
* **Subdomínio Genérico (Generic):** Problemas comuns que qualquer empresa possui (ex: Autenticação, Gateway de Pagamento). Deve-se preferir comprar soluções prontas (SaaS) ou bibliotecas open-source.

### 2.2. Contexto Delimitado (Bounded Context)
É a fronteira conceitual onde um modelo de domínio específico se aplica. Dentro de um *Bounded Context*, todas as palavras têm um significado único.
* *Exemplo:* A palavra `Produto` no contexto de *Vendas* possui preço e taxas. No contexto de *Entrega*, o mesmo `Produto` possui peso, altura e dimensões. Tentar criar uma única classe `Produto` para ambos os cenários quebra o DDD.



### 2.3. Linguagem Ubíqua (Ubiquitous Language)
É o idioma comum compartilhado por desenvolvedores e *Domain Experts* (especialistas do negócio). 
* **Regra de Ouro:** Os termos usados nas reuniões de negócio devem ser exatamente os mesmos termos usados nas classes, métodos, tabelas e variáveis do código.

### 2.4. Mapa de Contexto (Context Map)
Define como múltiplos *Bounded Contexts* se integram e se comunicam:
* **Shared Kernel (Núcleo Compartilhado):** Dois contextos compartilham o mesmo código/banco de dados (Alto acoplamento).
* **Customer-Supplier (Cliente-Fornecedor):** O fornecedor (*Upstream*) dita o ritmo, mas precisa atender às demandas do cliente (*Downstream*).
* **Conformist (Conformista):** O contexto *Downstream* se adapta totalmente ao modelo do *Upstream*, sem poder de barganha.
* **Anti-Corruption Layer (Camada Anticorrupção - ACL):** Uma camada de tradução que isola o modelo limpo de um contexto contra a semântica poluída de outro contexto externo ou legado.
* **Open Host Service (OHS):** O contexto expõe seus serviços de forma pública e estável (ex: uma API REST estruturada).

---

## 3. Design Tático (Tactical Design)

O Design Tático fornece os blocos de construção para implementar as regras de negócio dentro de um único *Bounded Context*.

### 3.1. Entidades (Entities)
Objetos que possuem uma identidade única e contínua ao longo do tempo. O que os define não são seus atributos, mas o seu ID.
* *Exemplo:* Um `Usuario` com ID `123` continua sendo o mesmo usuário mesmo se mudar de nome ou e-mail.

### 3.2. Objetos de Valor (Value Objects - VOs)
Objetos que descrevem características ou propriedades do domínio, mas **não possuem identidade única**. Eles são definidos estritamente pelo valor de seus atributos.
* **Imutabilidade:** Um VO nunca muda de estado. Se precisar alterá-lo, substitua a instância inteira.
* **Validação:** A validação deve ocorrer no momento da criação (construtor). Não existe VO em estado inválido.
* *Exemplo:* `Endereço`, `Dinheiro`, `CPF`, `Email`.

### 3.3. Agregados e Raiz de Agregado (Aggregates & Aggregate Roots)
Um Agregado é um grupo de Entidades e Objetos de Valor que são tratados como uma única unidade de modificação de dados.
* **Raiz do Agregado:** É a entidade principal através da qual o mundo externo interage com o agregado. Ela garante a consistência de todas as regras de negócio internas (*Invariants*).
* **Regra de Persistência:** Repositórios salvam e buscam apenas a Raiz do Agregado. Entidades internas não possuem repositórios próprios.
* **Regra de Referência:** Um agregado só pode referenciar outro através do ID da sua Raiz.

### 3.4. Serviços de Domínio (Domain Services)
Lógicas ou processos importantes do negócio que não pertencem naturalmente a nenhuma Entidade ou Objeto de Valor específico. São *stateless* (não guardam estado).
* *Exemplo:* Uma calculadora de taxas complexa que precisa cruzar dados de múltiplos agregados.

### 3.5. Eventos de Domínio (Domain Events)
Algo que aconteceu no passado do domínio e que é relevante para o negócio. Sempre nomeado no passado (ex: `PedidoPago`, `ClienteRegistrado`). Usado para desacoplar sistemas e aplicar consistência eventual.

### 3.6. Repositórios (Repositories)
Abstrações que simulam uma coleção na memória para gerenciar o ciclo de vida dos Agregados (salvar, atualizar, buscar). A interface fica no Domínio; a implementação técnica fica na Infraestrutura.

---

## 4. Arquitetura em Camadas (Layered Architecture)

O DDD sugere o isolamento do Domínio através de uma separação clara de responsabilidades:



1.  **Camada de Interface/Apresentação:** Controllers REST, interfaces gráficas, CLI. Recebe a requisição e exibe a resposta.
2.  **Camada de Aplicação:** Orquestra os fluxos de trabalho. Busca o Agregado no repositório, executa a ação e salva. **Não possui lógica de negócio**.
3.  **Camada de Domínio:** O coração do software. Contém as Entidades, VOs, Agregados, Serviços de Domínio e Interfaces dos Repositórios. É puramente isolada de tecnologia.
4.  **Camada de Infraestrutura:** Detalhes técnicos e de comunicação com o mundo externo (implementação do Banco de Dados/ORM, envio de e-mails, filas de mensageria).

---

## 5. Diretrizes de Prompt para Geração de Código DDD (Anti-patterns a Evitar)

Ao instruir uma LLM a escrever códigos alinhados ao DDD, force o cumprimento das seguintes regras:

1.  **Banir o Modelo Anêmico:** Classes de domínio não devem conter apenas `getters` e `setters`. O domínio deve ter comportamento expressivo.
    * *Errado (Anêmico):* `pedido.setStatus("Aprovado");`
    * *Certo (DDD):* `pedido.aprovar(motivo);` (onde as validações ocorrem dentro do método).
2.  **Impedir Tipos Primitivos Absolutos (Primitive Obsession):** Evitar o uso excessivo de `string` ou `double` para conceitos de negócio. Exigir Objetos de Valor.
    * *Errado:* `string email;`
    * *Certo:* `Email email;` (Classe com validação de formato Regex interna).
3.  **Isolamento Tecnológico:** A camada de domínio não pode herdar classes de ORMs (como Hibernate, Entity Framework, Prisma) ou anotações de frameworks web. O Domínio deve ser código puro (POJO/POCO).