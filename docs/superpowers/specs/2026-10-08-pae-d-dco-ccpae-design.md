# Fase D do PAE: DCO e requisito de emissão do CCPAE

- Data: 2026-10-08
- Branch: `codex/pae-dco-gmg83`
- Base: `origin/dev` em `5e6af26aa`

## Objetivo e decisões confirmadas

A CEDEC precisa registrar se a Declaração de Conformidade e Operacionalidade do PAEBM (DCO) é exigível para cada protocolo, comprovar as declarações apresentadas e impedir a emissão de CCPAE sem DCO positiva quando ela for aplicável. Durante a vigência do certificado, a equipe precisa enxergar a entrega anual e casos de não conformidade para decidir as medidas cabíveis.

O usuário priorizou este requisito antes de continuar os demais itens do Anexo B. Escolheu guardar o arquivo da DCO, o ano de competência, a data e o número SEI. Aprovou decisão fundamentada de aplicabilidade pela CEDEC, histórico anual, bloqueio de emissão e alerta sem revogação automática. A intenção é fechar a lacuna do art. 137 sem reclassificar automaticamente barragens a partir de dados incompletos no cadastro.

O roteiro preliminar da fase A chamava a calculadora de evacuação de “D” e incluía DCO em “E”. A prioridade foi alterada pelo usuário nesta conversa; esta fase D trata somente da DCO e não altera o histórico do roteiro anterior.

## Base normativa

- [Resolução GMG nº 83/2024, arts. 137 e 139](https://liferay.meioambiente.mg.gov.br/documents/117662/7003603/ResolucaoGMG_Nr83-2024/f6263034-1002-0587-f566-aeb642a1488f?t=1723499015768&version=1.0): DCO positiva é requisito da emissão do CCPAE quando aplicável; a DCO deve ser encaminhada anualmente durante sua vigência. Ausência ou não conformidade no prazo da ANM é causa a avaliar para suspensão ou revogação conforme o caso concreto.
- [Resolução ANM nº 95/2022, arts. 44 e 45](https://www.gov.br/anm/pt-br/assuntos/barragens/legislacao/resolucao-no-95-2022.pdf): define os casos de ACO/DCO para barragens de mineração e o ciclo anual de envio via SIGBM entre 1º e 30 de junho. O enquadramento exige análise da classificação e da regra aplicável na data; o SDC não o infere apenas do nome ou tipo do empreendimento.

## Abordagens consideradas

1. **Registros próprios por protocolo e competência, escolhida:** preservam revisões, decisão de aplicabilidade e a prova específica usada na emissão. O serviço atual do CCPAE consulta a situação dentro da sua transação.
2. **Campos no CCPAE e um único anexo:** diminuem o schema inicial, mas não representam entregas anuais nem corrigem uma DCO posteriormente substituída.
3. **Consulta automática ao SIGBM/SEI:** dependeria de integração e identidade de barragem ainda indisponíveis; a CEDEC precisa avaliar o documento apresentado no processo.

## Dados e responsabilidades

Uma migration principal da fase D cria `pae_dco_avaliacoes` e `pae_dco_documentos` e acrescenta referências de evidência a `pae_ccpae`. Nenhuma migration já aplicada será editada.

- Cada avaliação de aplicabilidade é imutável: protocolo, resultado `aplicavel` ou `nao_aplicavel`, fundamentação obrigatória, referência SEI, responsável e data. A avaliação mais recente é a vigente. `Nao_aplicavel` não é atalho sem justificativa; a CEDEC registra o fundamento da classificação. Sem avaliação, a emissão fica pendente.
- Cada DCO é imutável: protocolo, competência anual, resultado conferido (`positiva` ou `nao_conforme`), data do documento, data da apresentação à CEDEC, número SEI, arquivo PDF privado, observação, responsável e data do registro. Uma nova apresentação para a mesma competência gera outra versão; a mais recente prevalece, inclusive se tornar não conforme uma declaração antes positiva. Registros anteriores permanecem consultáveis.
- O CCPAE novo guarda a avaliação usada e, quando aplicável, o documento DCO usado na emissão. Essas referências não mudam quando a avaliação ou a DCO são revistas. CCPAE já emitidos conservam referências nulas e exibem ausência de avaliação histórica, sem ganhar conformidade fictícia.
- Um serviço de domínio calcula a competência exigível e a situação anual a partir das datas. Requests e componentes Vue não repetem essa regra.

O arquivo é guardado no disco privado `pae`, com rota autenticada de download e verificação de vínculo ao protocolo. Falha no armazenamento ou no banco remove arquivo parcial e mantém o registro pendente. O disco precisa ser compartilhado entre as quatro réplicas, como os demais anexos PAE; a verificação integrada inclui upload por uma réplica e download por outra.

## Emissão e acompanhamento

1. Usuário com `pae.protocolos.validar` registra ou revisa a aplicabilidade com fundamentação e SEI. A interface mostra claramente `não avaliada`, `aplicável` ou `não aplicável`, autor e data; a mudança não apaga decisões antigas.
2. Quando aplicável, usuário com `pae.protocolos.validar` registra o resultado após conferir o arquivo, a competência, a data e o SEI. Documento sem arquivo, de outro protocolo, com data futura ou resultado indefinido não satisfaz o requisito. `pae.protocolos.view` permite consultar e baixar o histórico; ausência da permissão nega acesso ao arquivo.
3. `PaeCcpaeService::emitir` bloqueia o protocolo na transação existente e verifica a avaliação vigente. Se não aplicável, exige sua fundamentação registrada. Se aplicável, considera somente DCOs com documento e apresentação não posteriores à data informada para emissão, seleciona a competência mais recente ainda válida e sua última revisão até aquela data, e exige resultado positivo. Uma DCO registrada depois não serve de prova retroativa nem impede o uso da competência anterior ainda exigível em janeiro a junho. Ausência, competência vencida, documento negativo ou avaliação não feita devolvem erro de validação; não mudam status, não criam CCPAE, comunicação nem evento outbox.
4. Para uma emissão de janeiro a junho, a competência exigível é a do ano anterior, admitindo a do próprio ano se já apresentada; a partir de 1º de julho, é a do ano corrente. A regra acompanha o vencimento anual de 30 de junho da ANM e é calculada em uma única classe testável. Uma DCO positiva posterior à data pretendida para o CCPAE não serve de prova retroativa para aquela emissão.
5. Após a emissão, a tela apresenta a competência anual, a data limite de 30 de junho e a situação: comprovada, aguardando o prazo, ausente após o prazo ou não conforme. O próximo ciclo é exibido mesmo que o certificado tenha sido emitido antes de junho. A lista de protocolos destaca casos que exigem atenção. Registro tardio não apaga o fato histórico de que houve pendência no período.
6. Pendência anual e DCO não conforme geram alerta para análise da CEDEC. O sistema não suspende nem revoga o CCPAE automaticamente: o art. 139 reserva a decisão à avaliação do caso concreto. Protocolos legados já certificados são sinalizados como não avaliados até o cadastro dos dados, sem alteração automática de status.

## Integração e segurança

- O fluxo de CCPAE continua com o mesmo único escritor de status, `PaeProtocoloWorkflow`; o guard de DCO fica no serviço transacional de emissão para não existir caminho alternativo. O contexto de transição não ganha estado mutável, preservando segurança no Octane.
- O formulário/modal de emissão mostra a situação DCO antes de enviar, com acesso à avaliação e aos documentos. A checagem final é sempre no servidor para cobrir abas antigas e requisições diretas.
- Não há novo slug de ACL. `pae.protocolos.validar` controla decisão de aplicabilidade e conferência da DCO; `pae.protocolos.view` controla leitura/download. O envio do arquivo também exige `validar`, pois seu resultado interfere na emissão.
- Dados de outra barragem ou protocolo não podem ser vinculados ao CCPAE por IDs enviados pelo cliente. As referências usadas são resolvidas no servidor dentro da transação. A ação de registrar avaliação ou DCO bloqueia o mesmo protocolo para serializar com uma emissão concorrente.
- A lista usa agregados ou carregamento em lote para evitar consultas N+1; downloads usam o disco privado, não URL pública.

## Verificação e aceite

- Emissão falha sem avaliação, com DCO exigível ausente, de competência vencida ou não conforme; funciona com avaliação fundamentada `nao_aplicavel` ou DCO positiva vigente.
- Emissão com data passada não aceita DCO apresentada depois daquela data. Duas emissões concorrentes continuam criando no máximo um certificado e um conjunto de comunicações.
- Mudanças posteriores na avaliação/DCO não alteram a prova congelada do CCPAE, mas atualizam o alerta anual. Substituição na mesma competência preserva o histórico e a última revisão é a vigente.
- Permissões, arquivo inválido, falha de storage, download cruzado entre protocolos e visualização por outra réplica são verificados. Migração, suíte PAE, build e fluxos no navegador são conferidos. Arquivos de testes transitórios ficam fora do commit conforme `AGENTS.md`.

## Fora do escopo

Classificação automática da barragem no SIGBM, envio automático da DCO à ANM ou à CEDEC, análise automática do conteúdo do PDF, suspensão/revogação automática ou novo processo decisório do art. 139, DCE, PAAP, exercícios simulados e demais itens do Anexo B. Esses temas exigem fases próprias.
