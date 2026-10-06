# PAE B: admissibilidade e comunicações oficiais — desenho

**Data:** 2026-10-05
**Base:** `dev` em `82f9c34d`; continuação de `2026-10-02-pae-a-prazos-ccpae-design.md`
**Estado:** aguardando revisão do usuário antes do plano de implementação

## Objetivo e decisões confirmadas

Permitir que a CEDEC registre a triagem física do PAE, decida sua admissibilidade ou reprovação sumária com fundamento e acompanhe a comunicação oficial à FEAM e às COMPDECs da ZAS. A decisão deve ser auditável e não pode surgir automaticamente de um item ausente.

O usuário escolheu registrar a comunicação oficial no PAE com data, número SEI e comprovante, sem envio automático de e-mail. Também escolheu cadastrar, por protocolo, os municípios abrangidos pela ZAS e ZSS. O uso direto de `.md` nesta fase foi autorizado porque o Zen MCP com Gemini não está disponível nesta sessão.

## Base normativa e interpretação operacional

- O Anexo J contém nove pré-requisitos de protocolo: pasta física vermelha; fichas originais de assinatura; PAE físico e digital; relatório de treinamento interno; protocolo nas COMPDECs de ZAS/ZSS; mapas impressos e digitais; Anexos B, C e D.
- O art. 131 aponta como fundamentos de reprovação sumária os arts. 118 a 124, 128 e 129. A lista de fundamentos não equivale, item a item, ao checklist do Anexo J. O § 1º exige justificativa fundamentada e avaliação das particularidades do caso; portanto, um `não` no checklist não muda o status por si só.
- O art. 131 § 2º prevê correção em 30 dias, sem prorrogação, para PAE submetido até a publicação da resolução. Essa regra transitória exige confirmação expressa da CEDEC, data e SEI da submissão; não se deve confundir a data da resolução com a data de publicação nem usar a data de importação no SDC.
- O art. 11 § 1º exige cópia da notificação à COMPDEC da ZAS, com comunicação pelo registro no SDC conforme § 2º. O art. 134 prevê comunicação à FEAM e à COMPDEC da ZAS na emissão do CCPAE. O art. 138 prevê a comunicação dos motivos de reprovação às mesmas partes.
- O art. 137, requisito de DCO positiva quando aplicável, não é alterado nesta fase; deve ser tratado em trabalho próprio antes de se afirmar conformidade integral da emissão do CCPAE.

Fonte: [Resolução GMG nº 83/2024](https://liferay.meioambiente.mg.gov.br/documents/117662/7003603/ResolucaoGMG_Nr83-2024/f6263034-1002-0587-f566-aeb642a1488f?t=1723499015768&version=1.0).

## Abordagem escolhida

Usar entidades próprias para municípios afetados, itens do checklist, decisões e comunicações. Campos soltos em `pae_protocolos` diminuiriam o trabalho inicial, mas não preservariam versões de decisão nem destinatários históricos. Enviar e-mail automaticamente dependeria de cadastros de destinatários ainda incompletos e não produziria, por si só, a prova do encaminhamento oficial escolhida pela CEDEC.

O `PaeProtocoloWorkflow` continua sendo o único escritor de status. A triagem solicita transições por um serviço de aplicação e um guard registrado no contrato `GuardaTransicaoPae`. A comunicação é um registro de obrigação e execução, com estado `pendente` ou `registrada`; criar uma pendência não significa que o órgão foi comunicado.

## Dados e migração

Uma única migração principal desta fase adiciona as estruturas abaixo, com chaves, índices e restrições de unicidade. Alterações necessárias durante a implementação serão consolidadas nela antes do commit.

| Estrutura | Conteúdo e restrições |
| --- | --- |
| `pae_protocolo_municipios` | `protocolo_id`, `municipio_id`, `na_zas`, `na_zss`, usuário e data da confirmação; uma linha por par protocolo/município; ao menos uma zona verdadeira. O município do empreendimento não é usado como substituto automático da lista. |
| `pae_admissibilidade_itens` | Um registro por protocolo e chave estável dos nove itens do Anexo J; resultado `sim`, `nao` ou `nao_aplicavel`, justificativa e auditoria. A decisão guarda uma cópia desses resultados para preservar avaliações anteriores. |
| `pae_admissibilidade_decisoes` | Uma linha imutável por decisão (`admitido`, `correcao_solicitada` ou `reprovado_sumariamente`), responsável, data, fundamentação, fundamentos legais quando houver, snapshot do checklist, número SEI e prazo transitório quando aplicável. A última decisão é a vigente. |
| `pae_comunicacoes` | Uma linha por evento e destinatário: tipo e ID da origem, FEAM ou COMPDEC, município da COMPDEC, estado, data de envio, número SEI, arquivo de comprovante e usuário registrador. Índice único impede repetição da mesma origem para o mesmo destinatário. |
| `pae_protocolos` | Indicador técnico `admissibilidade_legada_sem_triagem`: verdadeiro para registros já existentes na implantação, falso por padrão para novos. Não representa dispensa legal; evita bloquear retroativamente os fluxos antigos sem avaliação cadastrada. |

O catálogo de nove chaves do Anexo J e o catálogo de fundamentos 118–124, 128 e 129 ficam em código de domínio versionável, não em texto livre ou duplicado entre backend e frontend. A comunicação guarda o destinatário e a zona no instante de sua criação para que uma edição futura do mapa não altere o histórico.

## Fluxo de admissibilidade

1. Na entrada física, operador com `pae.protocolos.edit` cadastra os municípios da ZAS/ZSS e registra os nove itens. Cada `nao` ou `nao_aplicavel` exige observação. Um checklist incompleto continua pendente.
2. Usuário com `pae.protocolos.validar` examina os itens e as justificativas. Pode admitir com fundamentação, inclusive quando uma ausência fundamentada for aceita, ou reprovar sumariamente indicando ao menos um artigo do rol do art. 131 e motivação específica. Nenhuma escolha é automática.
3. Para submissão comprovadamente anterior à publicação da resolução, pode registrar `correcao_solicitada` com data de notificação e prazo de 30 dias improrrogáveis. O vencimento sinaliza a CEDEC; não reprova sozinho. Após correção, nova decisão preserva a anterior.
4. Novo protocolo só avança de `ENTRADA_PROCESSO` para `CRIACAO_SDC` após decisão `admitido`. O guard também impede atalho para `CCPAE` sem admissão. A exceção técnica para protocolo legado já existente é explícita e auditável. O serviço de decisão permite a saída antecipada de `ENTRADA_PROCESSO` para o novo estado terminal `REPROVADO_SUMARIAMENTE`; a reprovação comum da análise permanece distinta e conserva o evento `ParecerConcluidoV1` apenas para a análise.
5. A decisão cria trâmite/timeline na mesma transação. Dupla submissão e concorrência são tratadas com bloqueio do protocolo e chave de idempotência para não gerar decisões ou comunicações duplicadas.

O formulário PAE já existente não substitui o checklist físico. Seus anexos podem ser consultados como apoio, mas a triagem registra o que foi efetivamente apresentado na CEDEC.

## Comunicação oficial

- Na emissão do CCPAE, criar pendências para FEAM e para cada COMPDEC de município marcado `na_zas`. A fonte é `PaeCcpaeService::emitir`; `CcpaeEmitidoV1` é persistido no outbox na mesma transação, como previsto na spec da fase A.
- Na reprovação sumária ou na reprovação após análise, criar pendências equivalentes e preservar no registro os motivos comunicáveis. Não alterar o contrato nem a semântica de `ParecerConcluidoV1`.
- Na emissão de notificação do art. 11, criar pendência de cópia para cada COMPDEC da ZAS; não incluir FEAM nesse gatilho. A comunicação deve identificar a notificação específica.
- Registrar o envio oficial por destinatário exige data, número SEI e comprovante armazenado no disco privado `pae`. O registro inclui ator e horário. Download exige `pae.protocolos.view`; registro exige `pae.protocolos.edit`. A tela mostra claramente pendências e envios comprovados.
- Se a lista da ZAS ainda não estiver confirmada, exibir impedimento para concluir a decisão de triagem e alerta de destinatários pendentes em eventos de protocolos legados. Não declarar a comunicação completa sem a lista e os comprovantes.
- Criar pendências de comunicação na transação do evento de origem; gravar o comprovante e marcar `registrada` de modo consistente, removendo arquivo em caso de falha. Reenvio de requisição não gera segundo registro.

## Interface e contratos

A página de protocolos apresenta acesso à triagem no protocolo em `ENTRADA_PROCESSO`, com painel dos nove itens, municípios ZAS/ZSS, justificativas e decisão. Protocolos legados exibem “triagem não registrada no sistema” em vez de “admitido”. O histórico mostra cada decisão e o estado das comunicações, inclusive o destinatário e o número SEI; a listagem destaca comunicações pendentes.

Rotas autenticadas separadas recebem a atualização da lista de municípios, os itens, a decisão e o registro de cada comunicação. Requests validam enums, referências a município, data, artigo legal, arquivo e autorização. A página recebe dados por props do controller, seguindo o padrão Inertia atual; não duplica regras normativas em JavaScript.

## Verificação e aceite

- Checklist com nove itens e municípios ZAS/ZSS é salvo e reapresentado; ausência aceita exige motivação e não causa reprovação automática.
- Decisão sem `pae.protocolos.validar`, sem fundamento do rol, sem motivação ou com checklist incompleto é rejeitada. A admissão libera avanço; a reprovação sumária grava estado distinto e não emite parecer analítico.
- Prazo transitório só aparece com submissão anterior à publicação e expira sem transição automática.
- CCPAE, ambas as modalidades de reprovação e notificações criam exatamente as pendências exigidas; editar municípios depois não reescreve destinatários históricos.
- Comunicação só fica `registrada` com data, SEI e comprovante, exibidos no histórico e acessíveis apenas a quem pode ver PAE.
- Protocolos anteriores à implantação continuam acessíveis e não ganham admissão fictícia. Novos protocolos não contornam o guard por `changeStatus` ou pelo atalho de CCPAE.
- Rodar testes relevantes em banco isolado, suíte PHP completa e build Vite. Conforme AGENTS.md, testes criados para o trabalho ficam fora dos commits, salvo autorização específica do usuário.

## Fora do escopo

Envio automático de e-mail/SEI, alteração dos 300 dias da análise, revisão da DCO do art. 137, reavaliação integral de PAEs antigos, migração de anexos físicos e mudança do conteúdo técnico da análise. O cadastro de municípios nesta fase descreve ZAS/ZSS para este protocolo; não substitui o mapa oficial.
