# Treinamento EAD: versoes de curso publicado

Data: 2026-11-18. Modulo: Treinamento (banco `sdc_treinamento`, schema `treinamento`).

## Intencao

Um curso EAD publicado evolui. Exemplo real: o "Primeiros S.O.S" ja tem gente formada e vai ganhar mais 3 modulos. Hoje o professor edita o curso no lugar e a mudanca vale para todo mundo na hora: quem esta no meio passa a ter os modulos novos na trilha, e o conteudo que um formado de fato fez deixa de existir como registro.

O que o usuario quer preservar e o **historico da pessoa formada**: quem concluiu a 1.0 continua formado na 1.0, com o conteudo que fez, mesmo depois que a 2.0 existir. Ao mesmo tempo, o formado na 1.0 deve ter motivo para ir para a 2.0: ele faz so o que e novo.

## Objetivos

1. Criar uma nova versao (2.0, 3.0...) de um curso EAD publicado, a partir da anterior, sem mexer na anterior.
2. Manter a versao anterior intacta e consultavel por quem esta nela ou se formou nela.
3. Formado na versao anterior que se inscreve na nova recebe como concluido o que nao mudou e so faz o que e novo ou alterado.
4. Certificado, credencial QR e historico mostram a versao.

## Nao objetivos (fora desta entrega)

- Comparar versoes lado a lado.
- Desfazer (despublicar) uma versao publicada.
- Filtro por versao no painel de indicadores (continua somando por curso do catalogo).
- Versionar curso presencial (nao tem arvore de conteudo).
- Migrar inscricoes em andamento da versao antiga para a nova.

## Anti-objetivos (falha mesmo que "funcione")

- Qualquer alteracao em inscricao, progresso, tentativa de prova ou certificado de uma versao anterior por causa de uma versao nova.
- Excluir um arquivo fisico (PDF, video, HLS) que ainda e usado por outra versao.
- Formado na 1.0 perder uma das 3 tentativas da regra de reinscricao ao se atualizar para a 2.0.

## Restricoes

- Treinamento vive no banco proprio (`ConexaoTreinamento`); nunca JOIN entre bancos.
- Migrations consolidadas na migration principal `2026_11_05_210000_create_treinamento_schemas_tables` (regra do projeto), idempotente; homolog reaplica apagando o registro dela em `sdc_treinamento.migrations`.
- Padrao de codigo do projeto (DRY/SOLID, services finos por responsabilidade, sem emoji em codigo).
- Conclusao EAD ja e congelada (`concluido_em` preenchido encerra a avaliacao, ver `ReavaliacaoDeConclusaoService`). Isso nao muda.

## Como o sistema e hoje (o que a feature toca)

- Catalogo: `treinamento.cursos` agrupa treinamentos (`treinamentos.curso_id`). Cada `treinamento` e uma oferta/turma. A regra das 3 inscricoes (`RegraDeReinscricaoService`) conta por `curso_id`.
- Arvore de conteudo EAD pendurada no `treinamento`: `modulos` (pastas) -> `modulo_itens` (video, PDF, prova, projeto) -> `provas` -> `prova_questoes` -> `prova_alternativas`. Itens de video apontam para `videos` por `video_id`; itens de PDF guardam `arquivo_path` no disco `treinamentos`.
- Progresso por inscricao: `progresso_itens` (por `modulo_item_id`), `progresso_pastas`, `prova_tentativas`. Certificado: `certificados` (por `inscricao_id`/`treinamento_id`).
- Ciclo: `SituacaoCurso` (ATIVO/PAUSADO/DESATIVADO, com `recebeInscricao()`, `apareceNoCatalogo()`, `permiteAcessoDoAluno()`) e publicacao em `ConteudoCursoService::publicar()`.
- Precedente no proprio sistema: Notas Tecnicas tem `notas_tecnicas` (identidade) + `nota_tecnica_versoes` (uma linha por versao, `numero`, `changelog`, `publicada_em`) e a leitura e registrada por versao. Aqui o par equivalente e `cursos` (identidade) + `treinamentos` (versao), e a UI de lista de versoes ("v3.0 VIGENTE / v2.0 / v1.0") deve seguir a das Notas Tecnicas.

## Abordagens consideradas

| | Abordagem | Troca |
|---|---|---|
| **A (escolhida)** | Cada versao e um `treinamento` proprio, no mesmo curso do catalogo; "Nova versao" copia a arvore de conteudo para um rascunho. | Reaproveita trilha, progresso, provas, conclusao e certificado sem mudar o sentido de nada; o custo e copiar as linhas de texto da arvore a cada versao. |
| B | Um treinamento so, com conteudo versionado por dentro e cada inscricao presa a uma versao. | Uma pagina unica, mas mexe em trilha, progresso, provas e conclusao ao mesmo tempo: o miolo mais delicado do EAD. |
| C | So um numero de versao no curso, conteudo editado no lugar. | Barato, mas perde o conteudo que o formado fez. Nao atende ao objetivo. |

Variante avaliada e descartada para A: compartilhar linhas de item entre versoes (tabela de ligacao versao x item, copia so no que muda, estilo git). Economiza linhas, mas toda consulta de trilha, progresso e prova passaria a resolver o item pela versao, o que traz de volta a complexidade da B.

**Custo da copia (resposta a "da para otimizar?")**: o que se copia sao so linhas de texto (titulos, ordem, enunciados e alternativas). Arquivos pesados nao se copiam: o item copiado aponta para o mesmo `video_id` e o mesmo `arquivo_path`. Um curso grande (10 pastas, 80 itens, 5 provas de 20 questoes com 4 alternativas) da cerca de 600 linhas, na ordem de 100 a 200 KB por versao. O "Primeiros S.O.S" atual da menos de 10 linhas. A otimizacao que importa e a dos arquivos, e ela ja esta no desenho.

## Desenho (decidido)

### Regras

1. **Quem cria versao**: quem pode editar o curso (`treinamento.cursos.edit`). So curso EAD com publicacao.
2. **Numeracao**: automatica, so o numero principal (1.0, 2.0, 3.0). Ajuste pequeno continua sendo edicao no lugar, sem versao nova. Todo treinamento EAD existente vira 1.0.
3. **Nova versao**: copia da versao vigente para um rascunho no mesmo curso do catalogo: pastas, itens, provas, questoes e alternativas; professores designados; dados do curso (titulo, descricao, carga horaria, capa, criterios). Nao copia inscricoes, progresso, tentativas, frequencias, certificados, historico. So pode existir um rascunho de versao por vez por curso.
4. **Origem de cada item**: cada item e prova copiados guardam de qual item/prova vieram. Qualquer alteracao de conteudo no rascunho (arquivo, video, paginas, tipo, obrigatoriedade, questoes/alternativas/nota minima da prova) faz o item deixar de ser "igual" ao de origem. Editar so titulo/descricao/ordem nao quebra a igualdade.
5. **Publicar a nova versao**: passa pelas mesmas verificacoes de publicacao de hoje. Ao publicar:
   - a nova vira a **vigente**: aparece no catalogo e recebe inscricao;
   - a anterior para de aceitar inscricao e sai do catalogo, mas quem esta inscrito nela continua acessando e pode concluir;
   - formados na anterior recebem notificacao "Versao 2.0 disponivel".
6. **Aproveitamento**: formado (inscricao concluida) em qualquer versao anterior do mesmo curso que se inscreve na vigente:
   - itens "iguais" ao de origem entram como concluidos no progresso da nova inscricao;
   - prova "igual" conta como feita se a pessoa foi aprovada nela na versao de origem (a tentativa nao e copiada; o item da prova entra como concluido);
   - o que e novo ou alterado precisa ser feito;
   - ao completar, a conclusao e o certificado sao da nova versao; o certificado da anterior continua valido e no historico.
   - Se tudo for "igual" (versao so com remocoes), a inscricao conclui na hora pelo fluxo normal de reavaliacao.
7. **Regra das 3 inscricoes**: a inscricao de atualizacao (pessoa ja formada em versao anterior do mesmo curso) nao conta como tentativa.
8. **Arquivos compartilhados**: excluir item/pasta/curso ou trocar arquivo em uma versao nunca apaga o arquivo fisico ou o video se outra versao ainda o referencia.

### O que o aluno ve

Card em Meus cursos de quem se formou na 1.0 depois que a 2.0 foi publicada:

```
+ CONCLUIDO - v1.0 ----------------------+
| Primeiros S.O.S                        |
| 100% concluido                         |
| > Versao 2.0 disponivel: +3 modulos    |
|              [Revisar] [Atualizar]     |
+----------------------------------------+
```

- "Atualizar" inscreve na vigente com aproveitamento; "Revisar" continua abrindo a 1.0.
- A versao aparece no card, na sala de aula, no certificado (PDF e validacao publica) e no resultado do validador por QR/CPF.
- Catalogo para inscricao nova mostra so a vigente.

### O que o gestor/professor ve

- Na tela do curso: botao "Nova versao" (na vigente, quando nao ha rascunho) e lista de versoes no padrao das Notas Tecnicas (numero, VIGENTE, data de publicacao, quantos inscritos/formados), com acesso de leitura as anteriores.
- O rascunho e editado com as telas de conteudo que ja existem.
- Campo "O que mudou" (texto curto) ao publicar a versao, mostrado na notificacao e na lista de versoes.

### Dados (direcao, detalhes ao construtor)

- `treinamentos`: numero da versao e referencia a versao de origem.
- `modulo_itens` e `provas`: referencia ao item/prova de origem e a marca de "alterado desde a copia".
- `inscricoes`: marca de inscricao de atualizacao (e de qual inscricao concluida ela aproveitou).
- Tudo na migration principal consolidada, idempotente; os existentes ganham versao 1.

### Decisoes complementares (tomadas na revisao do documento, marcadas como proposta do Claude)

1. **Regra das 3 com versoes**: continua somando por curso do catalogo, entre todas as versoes. A inscricao de atualizacao e identificada pela marca gravada nela (nao recalculada) e **nunca** conta, nem se for cancelada ou reprovada depois. Pessoa formada pode se atualizar mesmo com as 3 tentativas gastas: a atualizacao nao passa pela regra.
2. **Rascunho**: e o treinamento com `publicado_em` nulo (como hoje). Nunca aparece em catalogo, Meus cursos de aluno, indicadores ou consulta para edital.
3. **Vigente e anterior**: vigente = versao publicada de maior numero do curso. A anterior continua `SituacaoCurso::ATIVO` (acessa e conclui normalmente); o que a tira do catalogo e da inscricao e existir versao publicada mais nova, nao a situacao. Pausar/desativar continua valendo por versao, como hoje.
4. **Indicadores e consulta para edital**: somam todas as versoes publicadas do curso (cada versao e um treinamento com capacitados proprios). Nada muda nesses servicos alem de ignorar rascunho.
5. **Identidade nas telas**: cada versao e um treinamento completo, com titulo, capa e dados proprios (copiados na criacao e editaveis no rascunho). Telas que hoje abrem um treinamento continuam abrindo um treinamento; a lista de versoes e o elo entre eles.
6. **Origem do aproveitamento**: calculado uma vez, no momento da inscricao de atualizacao, a partir da inscricao concluida mais recente da pessoa no curso. Um item da vigente e "igual" ao da versao de origem se a cadeia de origem (3.0 -> 2.0 -> 1.0) chega ao item que a pessoa concluiu sem nenhuma marca de "alterado" no caminho. Se uma versao nova sair enquanto a pessoa faz a atualizacao, ela termina a versao em que esta.
7. **O que conta como igual**:
   - reprocessar o video (novo HLS no mesmo `video_id`) nao e alteracao;
   - mover item de pasta ou reordenar nao e alteracao;
   - **projeto** igual conta como feito se a entrega da pessoa foi aprovada na origem (a entrega nao e copiada), como a prova;
   - pastas nao se aproveitam diretamente: a conclusao de pasta sai do fluxo normal a partir dos itens;
   - prova: aprovada = alguma tentativa aprovada na versao de origem, com a nota de la, sem recalculo; mudar nota minima, questoes ou alternativas torna a prova alterada.
8. **Versao no certificado e no validador**: o numero da versao aparece em card, sala de aula, certificado e validador **so quando o curso tem mais de uma versao publicada** (curso com versao unica continua como hoje). PDFs ja emitidos nao sao regerados; o validador publico e o QR mostram a versao a partir dos dados.
9. **Exclusao**: rascunho pode ser excluido livremente (com a regra 8 de arquivos). Versao publicada segue as guardas de exclusao que ja existem para treinamento; excluir uma versao nao afeta as outras. Excluir o curso do catalogo continua como hoje (nao cascateia para treinamentos).

## Deixado ao construtor

- Nomes de colunas, indices e onde fica a copia (service dedicado, ex. `NovaVersaoDoCursoService`), seguindo o padrao do modulo.
- Como detectar "alterado" (marca gravada nas operacoes de edicao do `ConteudoCursoService`/`ProvaConteudoService` ou comparacao de campos), desde que siga a regra 4.
- Como garantir a regra 8 (checagem de referencia antes de apagar o arquivo/video).
- Como a anterior deixa de receber inscricao (situacao propria, flag de vigencia ou consulta "existe versao mais nova publicada"), desde que nao use PAUSADO/DESATIVADO, que tem outro significado para o aluno.
- Canal da notificacao: o modulo de Notificacoes ja existente.
- Testes de feature cobrindo: copia completa sem tocar a origem; publicacao trocando a vigente; aproveitamento (igual, alterado, prova aprovada/reprovada); regra das 3 nao contando a atualizacao; arquivo compartilhado nao apagado.
