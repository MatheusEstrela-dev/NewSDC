
Search
Write
Sign up

Sign in



Desenvolvendo com Paixão

1


1


2

Top highlight

O que é SOLID: O guia completo para você entender os 5 princípios da POO
João Roberto da Paixão
João Roberto da Paixão

Follow
10 min read
·
Jan 6, 2019
7.6K


47


SOLID são cinco princípios da programação orientada a objetos que facilitam no desenvolvimento de softwares, tornando-os fáceis de manter e estender. Esses princípios podem ser aplicados a qualquer linguagem de POO.

Press enter or click to view image in full size

O que é SOLID?
SOLID é um acrônimo criado por Michael Feathers, após observar que cinco princípios da orientação a objetos e design de código — Criados por Robert C. Martin (a.k.a. Uncle Bob) e abordados no artigo The Principles of OOD — poderiam se encaixar nesta palavra.

S.O.L.I.D: Os 5 princípios da POO
S — Single Responsiblity Principle (Princípio da responsabilidade única)
O — Open-Closed Principle (Princípio Aberto-Fechado)
L — Liskov Substitution Principle (Princípio da substituição de Liskov)
I — Interface Segregation Principle (Princípio da Segregação da Interface)
D — Dependency Inversion Principle (Princípio da inversão da dependência)
Esses princípios ajudam o programador a escrever códigos mais limpos, separando responsabilidades, diminuindo acoplamentos, facilitando na refatoração e estimulando o reaproveitamento do código.

1. SRP — Single Responsibility Principle:
Princípio da Responsabilidade Única — Uma classe deve ter um, e somente um, motivo para mudar.

Esse princípio declara que uma classe deve ser especializada em um único assunto e possuir apenas uma responsabilidade dentro do software, ou seja, a classe deve ter uma única tarefa ou ação para executar.

Quando estamos aprendendo programação orientada a objetos, sem sabermos, damos a uma classe mais de uma responsabilidade e acabamos criando classes que fazem de tudo — God Class*. Num primeiro momento isso pode parecer eficiente, mas como as responsabilidades acabam se misturando, quando há necessidade de realizar alterações nessa classe, será difícil modificar uma dessas responsabilidades sem comprometer as outras. Toda alteração acaba sendo introduzida com um certo nível de incerteza em nosso sistema — principalmente se não existirem testes automatizados!

*God Class — Classe Deus: Na programação orientada a objetos, é uma classe que sabe demais ou faz demais.

Nota: Os exemplos desse artigo foram escritos usando a linguagem PHP, porém, são facilmente compreendidos por qualquer pessoa que já teve contato com programação orientada a objetos, independente da linguagem.

Exemplo prático do SRP:

A classe Order viola o SRP porque realiza 3 tipos distintos de tarefas. Além de lidar com as informações do pedido, ela também é responsável pela exibição e manipulação dos dados. Lembre-se, o princípio da responsabilidade única preza que uma classe deve ter um, e somente um, motivo para mudar.

A violação do Single Responsibility Principle pode gerar alguns problemas, sendo eles:

Falta de coesão — uma classe não deve assumir responsabilidades que não são suas;
Alto acoplamento — Mais responsabilidades geram um maior nível de dependências, deixando o sistema engessado e frágil para alterações;
Dificuldades na implementação de testes automatizados — É difícil de “mockar” esse tipo de classe;
Dificuldades para reaproveitar o código;
Aplicando o SRP na classe Order, podemos refatorar o código da seguinte forma:


Perceba no exemplo acima que agora temos 3 classes, cada uma cuidando da sua responsabilidade.

O princípio da responsabilidade única não se limita somente a classes, ele também pode ser aplicado em métodos e funções, ou seja, tudo que é responsável por executar uma ação, deve ser responsável por apenas aquilo que se propõe a fazer.


Considero o SRP um dos princípios mais importantes, ele acaba sendo a base para outros princípios e padrões porque aborda temas como acoplamento e coesão, características que todo código orientado a objetos deveria ter.

Aplicando esse princípio, automaticamente você estará escrevendo um código mais limpo e de fácil manutenção! Se você tem interesse nesse assunto, recomendo a leitura das boas práticas para escrever códigos impecáveis.

2. OCP — Open-Closed Principle:
Princípio Aberto-Fechado — Objetos ou entidades devem estar abertos para extensão, mas fechados para modificação, ou seja, quando novos comportamentos e recursos precisam ser adicionados no software, devemos estender e não alterar o código fonte original.

Exemplo prático do OCP:

Em um sistema hipotético de RH, temos duas classes que representam os contratos de trabalhos dos funcionários de uma pequena empresa, contratados e estágiários. Além de uma classe para processar a folha de pagamento.


A classe FolhaDePagamento precisa verificar o funcionário para aplicar a regra de negócio correta na hora do pagamento. Supondo que a empresa cresceu e resolveu trabalhar com funcionários PJ, obviamente seria necessário modificar essa classe! Sendo assim, estaríamos quebrando o princípio Open-Closed do SOLID.

Qual o problema de se alterar a classe FolhaDePagamento?

Não seria mais fácil apenas acrescentar mais um IF e verificar o novo tipo de funcionário PJ aplicando as respectivas regras? Sim, e provavelmente essa seria a solução que programadores menos experientes iriam fazer. Mas, esse é exatamente o problema! Alterar uma classe já existente para adicionar um novo comportamento, corremos um sério risco de introduzir bugs em algo que já estava funcionando.

Lembre-se: OCP preza que uma classe deve estar fechada para alteração e aberta para extensão.

Como adicionamos um novo comportamento sem alterar o código fonte já existente?

O guru Uncle Bob resumiu a solução em uma frase:

Separate extensible behavior behind an interface, and flip the dependencies.

Em tradução direta, seria:

Separe o comportamento extensível por trás de uma interface e inverta as dependências.

O que devemos fazer é concentrar nos aspectos essências do contexto, abstraindo-os para uma interface. Se as abstrações são bem definidas, logo o software estará aberto para extensão.

Aplicando OCP na prática

Voltando para o nosso exemplo, podemos concluir que o contexto que estamos lidando é a remuneração dos contratos de trabalho, aplicando as premissas de se isolar o comportamento extensível atrás de uma interface, podemos criar uma interface com o nome Remuneravel contendo o método remuneracao(), e fazer com que nossas classes de contrato de trabalho implementem essa interface. Além disso, iremos colocar as regras de calculo de remuneração para suas respectivas classes, dentro do método remuneracao(), fazendo com que a classe FolhaDePagamento dependa somente da interface Remuneravel que iremos criar.

Veja o código refatorado abaixo:


Agora a classe FolhaDePagamento não precisa mais saber quais métodos chamar para calcular. Ela será capaz de calcular o pagamento corretamente de qualquer novo tipo de funcionário que seja criado no futuro (ContratoPJ) — desde que ele implemente a interface Remuneravel — sem qualquer necessidade de alteração do seu código fonte. Dessa forma, acabamos de implementar o princípio de Aberto-Fechado do SOLID em nosso código!

Open-Closed Principle também é base para o padrão de projeto Strategy — Falerei desse padrão em um próximo artigo. Particularmente esse é o princípio que eu mais admiro, a sua principal vantagem é a facilidade na adição de novos requisitos, diminuindo as chances de introduzir novos bugs — ou bugs de menor expressão — pois o novo comportamento fica isolado, e o que estava funcionando provavelmente continuara funcionando.

3. LSP— Liskov Substitution Principle:
Princípio da substituição de Liskov — Uma classe derivada deve ser substituível por sua classe base.

O princípio da substituição de Liskov foi introduzido por Barbara Liskov em sua conferência “Data abstraction” em 1987. A definição formal de Liskov diz que:

Se para cada objeto o1 do tipo S há um objeto o2 do tipo T de forma que, para todos os programas P definidos em termos de T, o comportamento de P é inalterado quando o1 é substituído por o2 então S é um subtipo de T

Um exemplo mais simples e de fácil compreensão dessa definição. Seria:

se S é um subtipo de T, então os objetos do tipo T, em um programa, podem ser substituídos pelos objetos de tipo S sem que seja necessário alterar as propriedades deste programa. — Wikipedia.

Para facilitar o entendimento, veja esse princípio na prática neste simples exemplo:


Estamos passando como parâmetro tanto a classe pai como a classe derivada e o código continua funcionando da forma esperada.

Get João Roberto da Paixão’s stories in your inbox
Join Medium for free to get updates from this writer.

Enter your email
Subscribe
Exemplos de violação do LSP:

Sobrescrever/implementar um método que não faz nada;
Lançar uma exceção inesperada;
Retornar valores de tipos diferentes da classe base;

Para não violar o Liskov Substitution Principle, além de estruturar muito bem as suas abstrações, em alguns casos, você precisara usar a injeção de dependência e também usar outros princípios do SOLID, como por exemplo, o Open-Closed Principle e o Interface Segregation Principle — será abordado no próximo tópico.

Seguir o LSP nos permite usar o polimorfismo com mais confiança. Podemos chamar nossas classes derivadas referindo-se à sua classe base sem preocupações com resultados inesperados.

4. ISP — Interface Segregation Principle:
Princípio da Segregação da Interface — Uma classe não deve ser forçada a implementar interfaces e métodos que não irão utilizar.

Esse princípio basicamente diz que é melhor criar interfaces mais específicas ao invés de termos uma única interface genérica.

Vamos ver o ISP na prática através de códigos:

Em um cenário fictício para criação de um game de animais, teremos algumas aves que serão tratadas como personagens dentro do jogo. Sendo assim, criaremos uma interface Aves para abstrair o comportamento desses animais, depois faremos que nossas classes implementem essa interface, veja:


Percebam que ao criar a interface Aves, atribuímos comportamentos genéricos e isso acabou forçando a classe Pinguim a implementar o método setAltitude()do qual ela não deveria ter, pois pinguins não voam! Dessa forma, estamos violando o Interface Segregation Principle — E o LSP também!

Para resolver esse problema, devemos criar interfaces mais específicas, veja:


No exemplo acima, retiramos o método setAltitude() da interface Aves e adicionamos em uma interface derivada AvesQueVoam. Isso nos permitiu isolar os comportamentos das aves de maneira correta dentro do jogo, respeitando o princípio da segregação das interfaces.

Poderíamos melhorar ainda mais esse exemplo, criando uma interface Renderizavel pra abstrair esse comportamento, mas o foco aqui é explicar o conceito e não desenvolver o game, então vamos para o próximo princípio.

5. DIP — Dependency Inversion Principle:
Princípio da Inversão de Dependência — Dependa de abstrações e não de implementações.

De acordo com Uncle Bob, esse princípio pode ser definido da seguinte forma:

1. Módulos de alto nível não devem depender de módulos de baixo nível. Ambos devem depender da abstração.

2. Abstrações não devem depender de detalhes. Detalhes devem depender de abstrações.

No contexto da programação orientada a objetos, é comum que as pessoas confundam a Inversão de Dependência com a Injeção de Dependência, porém são coisas distintas, mas que relacionam entre si com um proposito em comum, deixar o código desacoplado.

Importante: Inversão de Dependência não é igual a Injeção de Dependência, fique ciente disso! A Inversão de Dependência é um princípio (Conceito) e a Injeção de Dependência é um padrão de projeto (Design Pattern).

Vamos entender tudo isso na prática através de exemplos:


Para recuperar a senha, a classe PasswordReminder, precisa conectar na base de dados, por tanto, ela cria um instancia da classe MySQLConnection em seu método construtor para realizar as respectivas operações.

Nesse pequeno trecho de código temos um alto nível de acoplamento, isso acontece porque a classe PasswordReminder tem a responsabilidade de criar uma instância da classe MySQLConnection! Se quiséssemos reaproveitar essa classe em outro sistema, teriamos obrigatoriamente de levar a classe MySQLConnection junto, portanto, temos um forte acoplamento aqui.

Para resolver esse problema de acoplamento, podemos refatorar nosso código da seguinte forma. Veja:


Com o código refatorado, a criação do objeto MySQLConnection deixa de ser uma responsabilidade da classe PasswordReminder, a classe de conexão com o banco de dados virou uma dependência que deve ser injetada via método construtor. Olha o que apareceu para nós: Injeção de Dependência!

Apesar de termos usado a injeção de dependência para melhorar o nível de acoplamento do nosso código, ele ainda viola o princípio da inversão de dependência! — lembre-se, um não é igual ao outro.

Além de violar o DIP, se você prestar atenção na forma que o exemplo foi codificado irá perceber que ele também viola o Open-Closed Principle. Por exemplo, se precisarmos alterar o banco de dados de MySQL para Oracle teríamos que editar a classe PasswordReminder.

Por que nosso exemplo refatorado viola o Dependency Inversion Principle?

Porque estamos dependendo de uma implementação e não de uma abstração, simples assim.

De acordo com a definição do DIP, um módulo de alto nível não deve depender de módulos de baixo nível, ambos devem depender da abstração. Então, a primeira coisa que precisamos fazer é identificar no nosso código qual é o módulo de alto nível e qual é o módulo de baixo nível. Módulo de alto nível é um módulo que depende de outros módulos.

No nosso exemplo ,PasswordReminder depende da classe MySQLConnection. Sendo assim, PasswordReminder é o módulo de alto nível e MySQLConnection é o módulo de baixo nível. Mas, MySQLConnection é uma implementação e não uma abstração!

Como refatoramos nosso exemplo para utilizar o DIP?

Se tratando de POO, você já ouviu aquela frase:

“Programe para uma interface e não para uma implementação.”

Pois bem, é exatamente o que iremos fazer, criar uma interface!

interface DBConnectionInterface
{
    public function connect();
}
Agora, vamos refatorar nosso exemplo fazendo que nossos módulos de alto e baixo nível dependam da abstração proposta pela interface que acabamos de criar. Veja:


Perfeito! Agora a classe PasswordReminder não tem a mínima ideia de qual banco de dados a aplicação irá utilizar. Dessa forma, não estamos mais violando o DIP, ambas as classes estão desacopladas e dependendo de uma abstração. Além disso, estamos favorecendo a reusabilidade do código e como “bônus” também estamos respeitando o SRP e o OCP.

Conclusão
A sistemática dos princípios SOLID tornam o software mais robusto, escalável e flexível, deixando-o tolerante a mudanças, facilitando a implementação de novos requisitos para a evolução e manutenção do sistema.

Levando em consideração algumas experiências vividas ao longo da minha história no mundo da tecnologia, acredito que os princípios SOLID, juntamente com algumas técnicas e boas praticas de Clean Code, são fatores essenciais que todos os desenvolvedores deveriam aplicar em seus códigos.

Pode ser um pouco assustador no início usar todos esses princípios — nem sempre conseguiremos aplicar todos em nosso projeto — mas com a prática e constância, aos poucos vamos adquirindo a experiência necessária para escrever códigos cada vez mais maduros, os princípios SOLID servem como guias pra isso.

Obrigado por ler! Se você gostou do artigo, dê um clap 👏.

Referências:
http://butunclebob.com/ArticleS.UncleBob.PrinciplesOfOod
https://en.wikipedia.org/wiki/SOLID
https://laracasts.com/series/solid-principles-in-php
https://www.tomdalling.com/blog/software-design/solid-class-design-the-liskov-substitution-principle
Artigos que talvez você possa se interessar:
#1 — Clean Code: O que é? Porque usar?
Clean Code é uma filosofia de desenvolvimento cuja o principal objetivo é aplicar técnicas simples que visam facilitar…
medium.com

#2 — Clean Code: Boas práticas para escrever códigos impecáveis!
Na primeira parte desse artigo eu falei um pouquinho sobre o que é Clean Code e qual o real impacto de escrever código…
medium.com

EditorConfig : Padronizando a codificação de arquivos entre diferentes editores e IDEs.
EditorConfig é um projeto open-source que facilita a adoção e padronização de um estilo de código para vários editores…
medium.com

O que é UUID? Porque usá-lo?
UUID — (do inglês Universally Unique IDentifier )
medium.com

Ainda não me conhecia? Então chegou a hora: Olá, meu nome é João Roberto, muito prazer!
Primeiros passos: Um pouco da minha história!
Conversor de café em código. Aspirante a empreendedor, apaixonado por tecnologia e investimentos. Um cara tranquilo e…
medium.com

João Roberto P. Borges - Personal Site
João Roberto P. Borges, analista de sistemas e desenvolvedor.
joaorobertopb.com

Solid
PHP
7.6K


47


Desenvolvendo com Paixão
Published in Desenvolvendo com Paixão
633 followers
·
Last published Jan 12, 2021
Um eterno aprendiz que acredita fielmente que o desenvolvimento de software e pessoal são habilidades que devem ser realizadas com Paixão!


Follow
João Roberto da Paixão
Written by João Roberto da Paixão
1.4K followers
·
221 following
Eterno aprendiz que compartilha aquilo que aprende. Apaixonado por tecnologia, desenvolvimento pessoal e investimentos. Pai de família, um cara tranquilo =) !


Follow
Responses (47)

Write a response

What are your thoughts?

Cancel
Respond
Wagner Araújo
Wagner Araújo

Jan 6, 2019


Que show, todo mundo tinha que ler isso, obrigado por compartilhar mano.
24

Reply

Felipe Chierice
Felipe Chierice

Mar 31, 2021


Show hein cara, estou lendo o Clean Archtecture do tiozão Bob e confesso que na parte que ele explica o SOLID eu tive dificuldades para compreender algumas coisas, mas seu texto cleareou 100% todos os princípios para mim! Vlw cara!
13

Reply

Jadir J. Silva Junior
Jadir J. Silva Junior

Jan 12, 2022


Uma classe deve ter um, e somente um, motivo para mudar.

the final version of the SRP is:
"A module should be responsible to one, and only one, actor." (Robert C. Martin)
Ficaria em português alog como:
Um modulo deve ser responsavel por um, e apenas um, ator
Ator no caso seria um grupo especifico
5


1 reply

Reply

See all responses
More from João Roberto da Paixão and Desenvolvendo com Paixão
Ngrok: do Localhost para o Mundo
Desenvolvendo com Paixão
In

Desenvolvendo com Paixão

by

João Roberto da Paixão

Ngrok: do Localhost para o Mundo
O Ngrok expõe serviços que estão sendo executados localmente (do seu computador) para a Internet, de forma rápida e segura.
Jan 3, 2021
665
8
#2 — Clean Code: Boas práticas para escrever códigos impecáveis!
Desenvolvendo com Paixão
In

Desenvolvendo com Paixão

by

João Roberto da Paixão

#2 — Clean Code: Boas práticas para escrever códigos impecáveis!
Na primeira parte desse artigo eu falei um pouquinho sobre o que é Clean Code e qual o real impacto de escrever código limpo no mundo do…
Oct 8, 2018
882
5
PHP CS Fixer: Aprenda a padronizar seu código
Desenvolvendo com Paixão
In

Desenvolvendo com Paixão

by

João Roberto da Paixão

PHP CS Fixer: Aprenda a padronizar seu código
O PHP CS Fixer (PHP Coding Standards Fixer) é uma ferramenta de linha de comando que corrige (Fixer) arquivos PHP adequando-os para padrões…
Apr 28, 2018
73
Outbox Pattern: Solucionando Desafios de Atomicidade em Microsserviços
Fretebras Tech
In

Fretebras Tech

by

João Roberto da Paixão

Outbox Pattern: Solucionando Desafios de Atomicidade em Microsserviços
A consistência de dados entre diferentes transações é um desafio constante devido a falta de atomicidade entre transações distintas…
Jan 16, 2024
146
See all from João Roberto da Paixão
See all from Desenvolvendo com Paixão
Recommended from Medium
Stop Memorizing Design Patterns: Use This Decision Tree Instead
Women in Technology
In

Women in Technology

by

Alina Kovtun✨

Stop Memorizing Design Patterns: Use This Decision Tree Instead
Choose design patterns based on pain points: apply the right pattern with minimal over-engineering in any OO language.

Jan 29
4K
33
Junior Devs Use try-catch Everywhere. Senior Devs Use These 4 Exception Handling Patterns
Stackademic
In

Stackademic

by

HabibWahid

Junior Devs Use try-catch Everywhere. Senior Devs Use These 4 Exception Handling Patterns
Try-catch on every method? That’s not safe code — that’s a ticking time bomb. Here’s what senior devs do instead.

Feb 1
363
12
Future of UI Design for 2026
Michal Malewicz
Michal Malewicz

The Future of UI Design past 2026
The future of UI is brighter than I thought.

Feb 5
661
25
6 brain images
Write A Catalyst
In

Write A Catalyst

by

Dr. Patricia Schmidt

As a Neuroscientist, I Quit These 5 Morning Habits That Destroy Your Brain
Most people do #1 within 10 minutes of waking (and it sabotages your entire day)

Jan 14
31K
554
I Stopped Using ChatGPT for 30 Days. What Happened to My Brain Was Terrifying.
Level Up Coding
In

Level Up Coding

by

Teja Kusireddy

I Stopped Using ChatGPT for 30 Days. What Happened to My Brain Was Terrifying.
91% of you will abandon 2026 resolutions by January 10th. Here’s how to be in the 9% who actually win.

Dec 28, 2025
6.8K
270
Apple Just Fired the Designer Who Made iOS 26 Unreadable. Here’s What Truly Happened.
Mac O’Clock
In

Mac O’Clock

by

Nov Tech

Apple Just Fired the Designer Who Made iOS 26 Unreadable. Here’s What Truly Happened.
Now he’s going to Meta, Stephen Lemay is taking over, and Apple employees are publicly celebrating. Here’s the full story.

Dec 22, 2025
8K
294
See more recommendations
Help

Status

About

Careers

Press

Blog

Privacy

Rules

Terms

Text to speech