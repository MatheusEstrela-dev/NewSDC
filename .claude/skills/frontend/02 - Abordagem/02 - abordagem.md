tomic Design foi criado em 2013 por Brad Frost e se baseia na química para criar uma metodologia em que os componentes são aplicados ao design de interfaces.

Aprendemos nas aulas de química que toda matéria é composta por átomos e essas unidades atômicas se unem para formar moléculas, que por sua vez se combinam em organismos mais complexos para criar toda a matéria existente no universo.

Da mesma forma, as interfaces são feitas de componentes menores e podemos quebrá-las em blocos de construção fundamentais e trabalhar a partir daí. Essa é a essência do Atomic Design.

Divisão
O Atomic Design é divido em em cinco componentes que trabalham juntos com o intuito de criar interfaces hierárquicas. Cada um dos cinco estágios desempenha um papel fundamental no design de interface. São eles:

Átomos
Moléculas
Organismos
Templates
Páginas
Átomos
Press enter or click to view image in full size

São blocos de construção básicos da matéria que formam a interface.

Get Jessica Araujo’s stories in your inbox
Join Medium for free to get updates from this writer.

Enter your email
Subscribe

Remember me for faster sign in

Exemplo: um label isolado é um átomo, um input é um átomo, um button é um átomo…

Moléculas
Press enter or click to view image in full size

Grupos simples de elementos da interface do usuário que funcionam juntos como uma unidade.

Exemplo: o label, o input e o button, juntos, formam uma molécula.

Organismos
Press enter or click to view image in full size

Conjuntos de moléculas que funcionam juntas como uma unidade. Uma molécula com outra molécula (ou mais), formam um organismo

Exemplo: um header de um site.

Templates
Press enter or click to view image in full size

Saindo um pouco dos termos da química, os templates são objetos no nível de página, onde colocamos componentes em um layout formando a estrutura de página.

Páginas
Press enter or click to view image in full size

É o resultado final, exatamente como o template, só que completa de informações reais. As páginas são o nível de fidelidade mais alto e, por serem as mais tangíveis, normalmente é onde a maioria das pessoas envolvidas em seu processo de criação, passa grande parte do tempo. É em torno dela, também, que gira a maioria das avaliações.

Conclusão
O Atomic Design é uma metodologia que auxilia na criação do Design System, ajudando a criar um design sistemático. O conceito nos permite criar projetos com consistência e escalabilidade, entendendo cada etapa da construção.

A metodologia nos permite ver o design de interface dividido em seus elementos atômicos e também nos permite ver como esses elementos se unem para formar nossas UIs finais.

Fonte:

Atomic Design Methodology | Atomic Design by Brad Frost
My search for a methodology to craft interface design systems led me to look for inspiration in other fields and…
atomicdesign.bradfrost.com


1. O Fluxo de Dados e Responsabilidades (Data Flow)
A regra de ouro desta arquitetura é: Dados descem (Props Down), Eventos sobem (Events Up) na camada base, mas a complexidade é injetada estrategicamente nos níveis mais altos.

Nível 1 e 2: Átomos e Moléculas (Dumb Components)

Como trabalham: São cegos, surdos e mudos em relação ao negócio. Eles não sabem em qual página estão. Recebem dados puramente via propriedades (props) e comunicam qualquer interação do utilizador emitindo eventos (emits ou defineModel).

O que NÃO fazem: Não chamam APIs, não acedem ao Pinia/Vuex, não usam localStorage.

Nível 3: Organismos (Smart Components)

Como trabalham: São os "gestores" locais. Eles pegam nas Moléculas burras e dão-lhes inteligência. Um organismo pode injetar dependências (serviços de API) e ligar-se ao estado global (Pinia).

Exemplo: Uma TabelaDeUtilizadores (Organismo) recebe o evento de "página seguinte" da Paginacao (Molécula) e executa internamente a chamada à API para buscar mais dados.

Nível 4: Templates (Gestores de Espaço)

Como trabalham: São apenas matrizes de layout. Utilizam slots (<slot name="header">, <slot name="sidebar">) para definir onde as peças vão encaixar. Garantem que a responsividade (CSS Grid/Flexbox) não suja a lógica de negócios das páginas.

Nível 5: Páginas (Orquestradores de Domínio)

Como trabalham: A página é o ponto de entrada da rota (/dashboard). Ela detém o ciclo de vida da vista (ex: onMounted). A página fornece as dependências (Injeção de Dependências) e distribui os dados principais para os Organismos e Templates.

2. Anatomia de um Fluxo na Prática (Exemplo: Carrinho de Compras)
Vamos visualizar como os componentes trabalham juntos num cenário real: Adicionar um produto ao carrinho.

Plaintext
[PAGE] ProductDetailsPage.vue
  │ (Busca os dados do produto na API e injeta no Template)
  │
  ├── [TEMPLATE] BaseLayout.vue
  │     │ (Garante que o Header fica no topo e o Produto no centro)
  │     │
  │     ├── [ORGANISM] TopNavbar.vue
  │     │     │ (Ouve o Pinia para saber quantos itens estão no carrinho)
  │     │     └── [ATOM] CartBadge.vue (Mostra o número '1')
  │     │
  │     └── [ORGANISM] ProductBuySection.vue
  │           │ (Recebe os dados do produto via Prop da Page)
  │           │
  │           ├── [MOLECULE] QuantitySelector.vue
  │           │     ├── [ATOM] AppButton.vue (Botão '+')
  │           │     └── [ATOM] AppInput.vue  (Mostra a quantidade)
  │           │
  │           └── [ATOM] AppButton.vue (Botão "Adicionar")
A Ação Passo-a-Passo:

O utilizador clica no Átomo AppButton ("Adicionar").

O Átomo emite um evento @click para cima.

O Organismo ProductBuySection capta o clique. Ele tem acesso ao Service ou ao estado global (Pinia). Ele dispara a ação: cartStore.addToCart(produto, quantidade).

O estado global é atualizado.

O Organismo TopNavbar, que está reativamente ligado ao Pinia, percebe a mudança e atualiza a prop do Átomo CartBadge.

3. O Segredo da Arquitetura Limpa (Clean Architecture no Frontend)
Para que essa arquitetura não desmorone quando o projeto cresce (ex: quando tens 50 páginas e 200 componentes), usamos o princípio da Inversão de Dependência.

Em vez de o teu Organismo importar o Axios diretamente para fazer um fetch, ele "pede" à Página que lhe forneça essa ferramenta. No Vue, fazemos isso com provide e inject.

Por que isso é poderoso?

Testes: Ao testar o Organismo, não precisas de ligar a internet ou o backend. Injetas um serviço "falso" (mock) e testas a interface.

Reutilização: O mesmo Organismo FormularioDeContacto pode ser usado no Módulo de Vendas (enviando para a API de Vendas) e no Módulo de Suporte (enviando para a API de Suporte). Apenas mudas a injeção que a Página lhe dá.

4. Como os Módulos de Domínio conversam? (Event-Driven)
Se estás a usar a separação por Domínios (DDD) que sugeri antes (ex: Módulo de Pagamento e Módulo de Notificação), eles não se devem importar um ao outro.

Eles comunicam através de um Event Bus (ou via Pinia Actions):

O Organismo do Módulo de Pagamentos finaliza a compra e grita para a aplicação: "Evento: CompraAprovada { id: 123 }".

O Organismo do Módulo de Notificação, que estava apenas à escuta (listener), ouve esse evento e dispara um alerta de sucesso verde (Toast) no ecrã.

Resumo da Ópera:
Átomos e Moléculas garantem que tudo é bonito e consistente (UI/UX). Organismos contêm as regras de interação locais. Templates arrumam a casa. Páginas gerem as rotas e os dados pesados. E a injeção de dependências garante que ninguém sabe demais, mantendo o código altamente manutenível.