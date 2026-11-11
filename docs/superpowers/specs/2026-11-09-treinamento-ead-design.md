# Design: Treinamento EAD (cursos com trilha, provas e certificado verificável)

**Data:** 2026-11-09
**Autor:** Matheus Estrela (com assistência Claude)
**Status:** Proposto, aguardando revisão
**Módulo:** `App\Modules\Treinamento`
**Relacionado:** [2026-11-10-treinamento-video-onprem-design.md](2026-11-10-treinamento-video-onprem-design.md) (base técnica da Fase 2, vídeo on-premise; substitui o spec de 2026-05-28, que era para Azure)
**Mockups do brainstorming:** `.claude/.superpowers/brainstorm/1324-1794234776/content/` (`sala-de-aula.html`, `prova-e-certificado.html`, `area-professor.html`)

Legenda: **[decidido]** = decidido pelo Matheus no brainstorming. **[escolha do design]** = decisão tomada no design, pode ser revista. Sem marca = contexto ou fato do código.

---

## 1. Intenção

A CEDEC-MG precisa formar servidores e não servidores (cidadãos, membros de COMPDEC) em cursos de Defesa Civil a distância, no ritmo de cada um, com comprovação de que a pessoa estudou e aprendeu. A referência de experiência é o Hotmart: área de membros com pastas, progresso visível, "continuar de onde parei"; e, do lado de quem ensina, uma área para montar o curso.

O que torna isto diferente de um repositório de PDFs: **progressão travada por prova** e **certificado que terceiros conseguem validar**. O certificado é usado como comprovante (RH, prefeitura, banca), então ele precisa ser verificável sem login.

O conteúdo começa com **PDFs e projetos** [decidido]. Vídeo vem depois, com upload e conversão pelo próprio site [decidido], e precisa conviver com o Octane/Swoole.

## 2. Estado atual (o que já existe e será reaproveitado)

- `Treinamento` já tem `categoria` (`EVENTO`/`CURSO`), `tipo` (`PRESENCIAL`/`ONLINE`), `status` (`PLANEJADO -> EM_ANDAMENTO -> CONCLUIDO`, ou `CANCELADO`), publicação (`publicado_em`, `link_publico_slug`), `instrutor` (texto livre).
- `Inscricao` é polimórfica: `inscrito_type`/`inscrito_id` aceita `User` (servidor) e `Cidadao`. Status `PENDENTE -> APROVADA/REPROVADA/CANCELADA`, aprovação pelo admin.
- Presença: portão `presenca_liberada` (liberar/bloquear), QR, manual, offline, autoconfirmação para `ONLINE`.
- `Certificado` (1 por inscrição): `PENDENTE`/`GERADO`, `emitido_em`. Emitido por `CertificadoService::emitirSeElegivel()` pela regra tripla *inscrição aprovada + frequência mínima + treinamento CONCLUIDO*, via `EmitirCertificadosTreinamentoJob`. Impressão em `resources/views/treinamento/certificado-print.blade.php`. **Não há código de verificação nem validação pública.**
- `Modulo` (tabela `modulos`): título, descrição, ordem, carga horária, data prevista. **Sem conteúdo.**
- Dois escopos de rota já existem:
  - `routes/modules/treinamento.php`: área interna (guard `web`, servidores), permissões `treinamento.cursos.*`, `treinamento.inscricoes.*`, `treinamento.presencas.registrar`, `treinamento.certificados.*`.
  - `routes/modules/treinamento-portal.php`: portal do cidadão (guard `cidadao`), cadastro com código por e-mail, catálogo, minhas inscrições, certificados. Acesso pelo link "Cadastre-se para participar de cursos e eventos" no `/login`.
- Infra: `$azureOrLocal` em `config/filesystems.php` (sem `AZURE_STORAGE_CONNECTION_STRING` cai em `ANEXOS_ROOT/{MODULO}` ou `storage/app`; produção é **on-premise**, R760 com Swarm de um nó, decidido em 2026-11-10); `spatie/laravel-medialibrary` instalado; `GeradorQrCodeService` existe; Swoole com `upload_max_filesize=64M` (`docker/swoole/Dockerfile`) e `package_max_length` do Octane no padrão de 10 MB (não definido em `config/octane.php`).

## 3. Objetivos

1. Curso EAD organizado em **pastas** (os `modulos`) com **itens** dentro: PDF, projeto, prova e, na Fase 3, vídeo [decidido].
2. **Progresso por aluno** visível e retomável ("continuar de onde parei", página do PDF guardada) [decidido].
3. **Prova ao fim de cada pasta**; a próxima pasta só abre com a prova aprovada; **prova final** no fim do curso [decidido].
4. **Certificado ao concluir o curso, comprobatório**: código único + QR + página pública de validação [decidido].
5. **Dois acessos diferentes, com rotas e endpoints próprios: Aluno e Professor** [decidido].
6. **Dois escopos**: o módulo Treinamento dentro do SDC (servidores) e o portal antes do login (não servidores) [decidido].
7. **Cada curso declara o público**: só servidores ou só cidadãos [decidido].
8. Vídeo com upload e conversão pelo site, compatível com Swoole (Fase 3) [decidido].

## 4. Não objetivos

- Venda, pagamento, afiliados, cupons, página de vendas (o Hotmart é referência só de experiência).
- Fórum, comentários, chat entre alunos.
- Questões dissertativas com correção manual em prova (projeto já cobre trabalho avaliado à mão).
- Turmas com calendário (liberação de pastas por data). O ritmo é do aluno.
- Curso com público "ambos" (servidores e cidadãos juntos). Só as duas opções foram pedidas.
- App mobile nativo, modo offline do curso EAD.
- Mudar o fluxo de eventos presenciais/online ao vivo, exceto: certificado verificável (8.5), público do treinamento (8.1) e aprovação automática opcional (8.2).

## 5. Anti-objetivos (falha mesmo que "funcione")

- Aluno conseguir abrir a pasta seguinte ou obter certificado sem passar nas provas (inclusive chamando o endpoint direto).
- Certificado que não pode ser conferido por terceiros, ou página de validação que expõe dados pessoais além do necessário (LGPD).
- Duas implementações da sala de aula/prova/progresso, uma para servidor e outra para cidadão (viola DRY).
- Vídeo trafegando pelo worker Swoole ou conversão FFmpeg rodando dentro do Octane.
- Professor vendo ou editando curso para o qual não foi designado.
- Quebrar o fluxo atual de eventos (inscrição, presença, certificado por frequência).

## 6. Atores e acessos

| Ator | Escopo | Login | Faz |
|---|---|---|---|
| Admin CEDEC | SDC | servidor (`web`) | cria o Treinamento, define público, designa professor(es), aprova inscrições (fluxo atual) |
| Professor | SDC | servidor (`web`) | só nos cursos em que foi designado: monta pastas/itens/provas, publica, acompanha alunos, corrige projetos, libera tentativa, reemite certificado |
| Aluno servidor | SDC | servidor (`web`) | catálogo interno, inscrição, sala de aula, provas, certificados |
| Aluno cidadão | Portal | cidadão (`cidadao`) | catálogo do portal, inscrição, sala de aula, provas, certificados |
| Qualquer pessoa | Público | nenhum | valida certificado pelo código/QR |

- **Professor é sempre servidor** com login no SDC [decidido]. Ganha uma permissão de acesso à área do professor e é vinculado a cada curso pelo admin [escolha do design].
- O campo texto `instrutor` continua existindo para exibição em eventos; em cursos EAD o vínculo real é a tabela de professores [escolha do design].

## 7. Experiência (telas)

Referência visual: mockups citados no cabeçalho.

### 7.1 Aluno (igual nos dois escopos, muda só o layout em volta)
- **Página do curso**: título, progresso geral (%), botão "Continuar de onde parei", lista de pastas com estado (concluída / em andamento / travada com cadeado) e itens com estado.
- **Sala de aula (Tela B, escolhida pelo Matheus)** [decidido]: lateral com anel de progresso e pastas/itens; área principal mostra o item atual.
  - **PDF**: visualizador dentro da plataforma, com paginação e zoom; guarda a página atual para retomar; conta como concluído ao chegar na última página [escolha do design]. Botão de baixar o PDF [escolha do design].
  - **Projeto**: enunciado + envio de arquivo + comentário; estado "enviado / aprovado / devolvido com parecer". Conta como concluído quando o professor aprova.
  - **Prova**: regras visíveis (nº de questões, nota mínima, tentativa X de Y), uma questão por vez, respostas salvas automaticamente; resultado ao fim (aprovado libera a próxima pasta; reprovado mostra tentativas restantes e "Revisar conteúdo").
  - **Vídeo** (Fase 2): ocupa o mesmo lugar do visualizador de PDF; retoma pelo segundo salvo.
- **Meus certificados**: lista com imprimir/baixar e o código de verificação.

### 7.2 Professor (só no SDC)
Menu por curso, no modelo da "Área de Membros" do Hotmart [escolha do design]: Informações básicas, Conteúdo, Provas, Alunos, Correções, Certificados, Publicação.
- **Conteúdo**: criar/renomear/reordenar pastas e itens (arrastar), adicionar PDF/projeto/prova (vídeo na Fase 3), pré-visualizar como aluno.
- **Provas**: questões de múltipla escolha com uma alternativa correta, nota mínima e máximo de tentativas por prova.
- **Alunos**: tabela com origem (servidor/COMPDEC/cidadão), % de progresso, onde está, notas; filtro "travados".
- **Correções**: fila de projetos enviados para aprovar ou devolver com parecer.
- **Publicação**: curso começa não publicado; professor publica quando o conteúdo está pronto.

## 8. Regras de negócio

### 8.1 Identificação do curso EAD e público
- Curso EAD = `categoria = CURSO` com um novo `TipoTreinamento::EAD` [escolha do design]. `ONLINE` continua significando evento ao vivo com autoconfirmação de presença. (Obs.: a migration `2026_08_03_150000_simplify_tipo_treinamento_to_presencial_online` removeu um EAD antigo sem conteúdo; este é um EAD novo, com trilha.)
- Novo campo `publico_alvo` em `treinamentos`: `SERVIDORES` | `CIDADAOS` [decidido], obrigatório. **Vale para todo treinamento, eventos inclusive** [decidido]. Os treinamentos que já existem ficam como `CIDADAOS`, para não sumirem do portal [decidido]. Catálogo interno lista só `SERVIDORES`; portal lista só `CIDADAOS`. Endpoints de inscrição recusam inscrito do tipo errado (não basta esconder no catálogo).

### 8.2 Liberação
- Porta de entrada = **inscrição APROVADA**. Aprovado, a sala de aula abre com a pasta 1.
- **Aprovação configurável por curso** [decidido]: novo campo (ex.: `inscricao_aprovacao_automatica`). Automática: a inscrição já nasce `APROVADA` e a sala de aula abre na hora. Manual: segue o fluxo atual (`PENDENTE` até o admin aprovar). Default para treinamentos existentes: manual (comportamento de hoje).
- Pasta N+1 abre quando **todos os itens obrigatórios da pasta N estão concluídos**, sendo a prova da pasta o último deles [decidido: prova libera a próxima].
- Dentro de uma pasta liberada, itens podem ser feitos em qualquer ordem, mas a prova só abre depois dos demais itens da pasta [escolha do design].
- Toda checagem de liberação é feita no backend, em um único serviço (ex.: `TrilhaService`/`LiberacaoPolicy`), usado por todos os endpoints dos dois escopos.

### 8.3 Provas
- Defaults: nota mínima 70%, 3 tentativas [escolha do design; configurável por prova].
- Nota da prova = maior nota entre as tentativas [escolha do design].
- Esgotou as tentativas sem aprovar: aluno fica **travado**; aparece em "Alunos > travados"; o professor **libera tentativa(s) extra(s)**, registrado com autor e data [escolha do design].
- Questões e alternativas embaralhadas por tentativa [escolha do design]. Gabarito nunca vai para o frontend antes do envio.
- Prova final = item PROVA na última pasta ("Avaliação final") [escolha do design]; não precisa de tipo especial.

### 8.4 Conclusão e certificado
- Para cursos EAD, a conclusão é **por aluno**: inscrição aprovada + todas as pastas concluídas (todas as provas aprovadas e projetos aprovados). **Não** depende de o Treinamento estar `CONCLUIDO`, nem de frequência [escolha do design, consequência do "ritmo do aluno"].
- Implementar como critério plugável: `CriterioConclusao` com `ConclusaoPorFrequencia` (comportamento atual, eventos) e `ConclusaoPorTrilha` (EAD); `CertificadoService::emitirSeElegivel()` delega ao critério do tipo do treinamento (Strategy, aberto/fechado).
- Emissão EAD dispara ao concluir o último item (via job na fila, mesmo padrão do atual).
- **Aproveitamento** = média das notas finais das provas do curso; impresso no certificado e mostrado na validação [escolha do design].

### 8.5 Certificado verificável [decidido: "algo comprobatório"]
- Novo `codigo_verificacao` único, aleatório e não sequencial, legível (ex.: `CEDEC-7K4P-29XQ`, alfabeto sem caracteres ambíguos) [escolha do design no formato].
- Certificado impresso traz código + QR apontando para a página pública de validação.
- **Página pública de validação** (sem login, com rate limit): por código digitado ou pelo QR. Mostra: situação (válido / reemitido / cancelado), nome, curso, carga horária, modalidade, data de conclusão, aproveitamento (se houver). **Não mostra CPF completo, e-mail nem contato**; CPF mascarado no máximo [escolha do design, LGPD].
- Vale para **todos** os certificados do módulo, inclusive eventos presenciais/online existentes [escolha do design]; os já emitidos recebem código retroativo.
- Reemissão mantém o código; cancelamento passa a existir como status, para invalidar na página pública [escolha do design].

### 8.6 Regras de borda [escolha do design, salvo indicação]
- **Itens opcionais:** todo item nasce obrigatório; o professor pode marcar como opcional. Opcional não trava pasta nem conclusão. **Toda pasta precisa terminar em uma prova:** a publicação é bloqueada se alguma pasta não tiver item PROVA.
- **Anti-burla do PDF:** o endpoint de progresso só aceita posição crescente e só marca concluído quando a posição chega ao total de páginas conhecido no servidor. A trava real de progressão é a prova (correção 100% no backend), então o "lido" do PDF é sinal de acompanhamento, não de segurança. PDF de 1 página conclui ao abrir.
- **Projeto:** uma linha por envio em `projeto_entregas` (histórico). Devolvido permite reenvio sem limite. Enquanto o projeto não é aprovado, a prova da pasta fica fechada (o aluno espera a correção). Envio notifica o(s) professor(es) do curso; correção notifica o aluno, pelo módulo `Notificacoes` já existente.
- **Tentativa de prova:** sem tempo limite. A tentativa só conta quando o aluno envia; uma tentativa aberta e abandonada é retomada de onde parou.
- **Duplicidade servidor x cidadão:** a mesma pessoa pode ter conta nos dois escopos; inscrições, progresso e certificados ficam separados por inscrição, sem unificação.
- **Professor:** permissão nova (ex.: `treinamento.professor.acessar`) criada no seeder de permissões. O professor não pode se inscrever como aluno no curso em que leciona. Na tela "Alunos" ele vê nome, origem (servidor/COMPDEC/cidadão e município), progresso, notas e entregas, mas **não** CPF, e-mail nem telefone.
- **PDF e download:** visualizador e download usam URL assinada de curta duração. Baixar o PDF é permitido; não há objetivo de impedir cópia do material (sem DRM).
- **Código retroativo:** os certificados já emitidos recebem código por um comando artisan idempotente, não pela migration. Na validação de certificado de evento: carga horária vem do `Treinamento`, a data de conclusão é `emitido_em`, e o aproveitamento não aparece.
- **Dados na validação pública:** nome completo + CPF mascarado (`***.456.789-**`), como no mockup aprovado. Nada além disso de dado pessoal.

### 8.7 Edição de curso publicado [decidido]
- O professor pode corrigir e acrescentar conteúdo (pastas, itens, questões) mesmo com alunos inscritos.
- **Quem já concluiu fica congelado:** conclusão e certificado não são recalculados por edições posteriores. Grave a conclusão na inscrição (ex.: `concluido_em` + aproveitamento), em vez de derivar sempre da trilha atual.
- **Quem está no meio:** item novo em pasta que o aluno ainda não terminou passa a ser exigido. Item novo em pasta que ele já concluiu **não** reabre a pasta [escolha do design, consequência de "concluído congelado" aplicada à pasta].
- Remover item ou questão com progresso/respostas: exclusão lógica, para manter o histórico das tentativas e entregas [escolha do design].
- Mudar a nota mínima ou o gabarito não reavalia tentativas já enviadas [escolha do design].

## 9. Arquitetura

### 9.1 Escopos, rotas e endpoints próprios [decidido]
Cada acesso tem rotas e endpoints próprios; a lógica é única.

| Acesso | Arquivo de rotas | Prefixo (sugestão) | Guard |
|---|---|---|---|
| Aluno servidor | `routes/modules/treinamento.php` | `/treinamentos/aluno/...` | `web` + permissão de catálogo |
| Professor | `routes/modules/treinamento.php` (ou arquivo próprio) | `/treinamentos/professor/...` | `web` + permissão de professor + vínculo ao curso |
| Aluno cidadão | `routes/modules/treinamento-portal.php` | `/portal-treinamento/cursos/...` | `cidadao` |
| Validação pública | rota pública própria | `/validar-certificado/{codigo?}` | nenhum + throttle |

- Controllers finos por escopo; **services compartilhados** (trilha/liberação, progresso, prova, entrega de projeto, certificado). Um resolvedor do "aluno atual" abstrai `User` x `Cidadao` a partir do guard, e tudo se prende à `Inscricao` (que já é polimórfica).
- Frontend: organismos/templates compartilhados (sala de aula, visualizador de PDF, prova, lista de pastas), com duas páginas Inertia que só trocam o layout (SDC x portal). Segue a estrutura Atoms/Molecules/Organisms/Templates já usada no projeto.

### 9.2 Modelo de dados (proposta; nomes finais ficam com o builder)
- `treinamentos`: + `publico_alvo`, + flag de aprovação automática da inscrição; `tipo` aceita `EAD`.
- `inscricoes`: + `concluido_em`, + `aproveitamento` (conclusão congelada, 8.7).
- `treinamento_professores`: `treinamento_id`, `user_id` (vínculo professor x curso).
- `modulos` (pastas): sem mudança estrutural obrigatória.
- `modulo_itens`: `modulo_id`, `tipo` (`PDF` | `PROJETO` | `PROVA` | `VIDEO`), `titulo`, `descricao`, `ordem`, `obrigatorio`, metadados do tipo (ex.: nº de páginas do PDF). Campos de vídeo (status, versão HLS, duração) moram aqui, não em `modulos`; detalhe no spec de vídeo on-prem.
- `progresso_itens`: `inscricao_id`, `modulo_item_id`, `status`, `posicao` (página do PDF; segundo do vídeo na Fase 2), `concluido_em`; único por (inscrição, item).
- `provas` (1:1 com item PROVA): `nota_minima`, `max_tentativas`; `prova_questoes`; `prova_alternativas` (com `correta`); `prova_tentativas` (`inscricao_id`, `numero`, `iniciada_em`, `finalizada_em`, `nota`); `prova_respostas`; `prova_liberacoes` (tentativas extras: quantidade, autor, data).
- `projeto_entregas`: `inscricao_id`, `modulo_item_id`, arquivo, comentário, `status` (`ENVIADA` | `APROVADA` | `DEVOLVIDA`), parecer, `corrigido_por`, `corrigido_em`.
- `certificados`: + `codigo_verificacao` (único), + `aproveitamento` (nullable), status ganha `CANCELADO`.

**Migrations:** seguir a regra de ouro do projeto: alterações em tabelas existentes são consolidadas na migration principal da tabela que está sendo editada; tabelas novas ganham sua migration de criação.

### 9.3 Arquivos
- Novo disco `treinamentos` via `$azureOrLocal` (on-prem: `ANEXOS_ROOT/TREINAMENTOS` ou `storage/app/treinamentos`, o mesmo volume para `app`, `queue` e, para o HLS, `caddy`; local em dev).
- PDFs e entregas de projeto servidos por stream do Laravel em endpoint autorizado, só para quem tem inscrição aprovada no curso (ou professor/admin do curso). Nunca URL pública permanente.
- PDFs e entregas cabem no limite atual de 64 MB do php.ini, mas o `package_max_length` do Octane (10 MB por padrão) precisa ser definido explicitamente para isso valer; limite exato por tipo é do builder.

### 9.4 Vídeo e Swoole (Fase 2) [decidido em 2026-11-10: on-premise como alvo único]
- O Swoole **não** recebe o vídeo inteiro nem serve bytes de vídeo. Upload em **partes de 8 MB** gravadas no disco `treinamentos` compartilhado; o job da fila monta o original.
- Conversão para HLS (480p/720p; 1080p por config) com FFmpeg no **worker de fila do container `queue`** (supervisor, fila `videos`, timeout próprio), nunca em worker HTTP nem task worker do Swoole.
- Entrega pelo **Caddy** (`file_server` do volume compartilhado) com `forward_auth` para um endpoint leve do Octane que valida sessão + inscrição aprovada + item liberado, com cache de 5 min; progresso = maior segundo alcançado em `progresso_itens.posicao`.
- Sem Azure, SAS, MinIO ou Nginx. Detalhe completo em [2026-11-10-treinamento-video-onprem-design.md](2026-11-10-treinamento-video-onprem-design.md).

## 10. Fases de entrega [escolha do design, aprovada no brainstorming; ordem revista em 2026-11-10, decidido]

1. **Base**: `publico_alvo` + filtro dos catálogos; tipo EAD; pastas e itens PDF/projeto; progresso e retomada; sala de aula nos dois escopos; área do professor (vínculo, conteúdo, publicação, alunos, correções).
2. **Vídeo**: upload em partes, worker FFmpeg/HLS na fila, entrega pelo Caddy com `forward_auth`, player e progresso por tempo.
3. **Comprovação**: provas por pasta e final, tentativas e liberação pelo professor, trava de progressão por prova; conclusão por trilha; certificado com código, QR, aproveitamento e página pública de validação (inclusive para certificados de eventos).

## 11. Restrições

- Laravel + Inertia/Vue, Octane/Swoole; Postgres. Código no padrão do módulo (`App\Modules\Treinamento`, controllers invocáveis, Services, Requests, Resources, Enums).
- DRY e SOLID (regras de ouro); papiros do projeto como referência de padrão de código.
- Sem emojis no código. Logs de depuração removidos após o teste.
- Permissões via Spatie, no mesmo padrão `treinamento.*` já existente.
- Ações relevantes (publicar, corrigir projeto, liberar tentativa, reemitir/cancelar certificado) registradas na trilha (`RegistroDeAcao`), como o módulo já faz.

## 12. Deixado para o builder

- Nomes finais de tabelas, colunas, rotas, permissões e componentes.
- Biblioteca do visualizador de PDF (ex.: PDF.js) e como detectar "chegou na última página".
- Como embaralhar e persistir a ordem das questões por tentativa.
- Formato exato do código de verificação e do QR; layout do certificado.
- Limites de tamanho e tipos aceitos de arquivo por item.
- Se professor e admin compartilham telas de gestão já existentes ou se a área do professor é toda nova.
- Testes: feature tests dos endpoints de liberação/prova/certificado nos dois escopos (incluindo tentativa de burlar a trava), unit tests dos critérios de conclusão.
