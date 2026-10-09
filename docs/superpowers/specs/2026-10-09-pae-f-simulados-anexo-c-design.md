# Fase F do PAE: relatório anual de exercício simulado (Anexo C)

- Data: 2026-10-09
- Branch: `feat/pae-simulados-anexo-c`
- Base: `origin/dev` em `6f840025`

## Objetivo e decisões confirmadas

A COMPDEC elabora, com apoio do empreendedor, o relatório anual do exercício simulado (Anexo C) e o envia à CEDEC junto do PAE (Arts. 90 e 104). A realização do simulado é pré-requisito para aprovar o PAE (Art. 96); a falta do relatório é causa de reprovação sumária na Licença de Operação e na renovação (Arts. 119 e 123); o simulado não validado leva à reavaliação do PAE (Art. 101). A CEDEC precisa registrar e conferir cada relatório, exigir relatório validado na emissão do CCPAE e acompanhar a periodicidade anual.

Decisões do usuário nesta conversa:

1. **Escopo:** conferência anual pela CEDEC (registro do relatório, critérios do item 8.1, tabelas de tempo da seção 7), não o formulário completo do Anexo C.
2. **Efeito:** bloqueia a emissão do CCPAE quando o simulado é exigível e não há relatório validado vigente; durante a vigência, ausência ou não validação geram alerta e sinal de reavaliação, sem revogação automática (mesmo padrão da DCO).
3. **Vigência:** vale o relatório validado de simulado realizado nos 12 meses anteriores à data de referência (emissão ou hoje).
4. **Critérios que decidem a validação:** as 8 linhas reprováveis do item 8.1. As 2 linhas de participação (Art. 100) e os objetivos VII (unidades de ensino) e VIII (recursos) do Art. 98, que não têm linha no 8.1, são registrados como informativos.
5. **Quem decide:** o analista da CEDEC marca "atende/não atende" em cada critério, com justificativa obrigatória no "não atende"; o sistema mostra indícios e conclui "validado" somente se os 8 critérios forem atendidos.
6. **Tempos:** saída maior ou igual à chegada da onda numa linha da seção 7 é indício exibido no critério correspondente; não reprova sozinho.

No roteiro original esta era parte do subprojeto E (simulados/PAAP/DCO). A DCO virou a fase D, a evacuação a fase E; os simulados passam a fase F. PAAP, sinalização do Art. 139 e sigilo seguem pendentes.

## Base normativa

- Arts. 90, 91 e 104: relatório anual elaborado pela COMPDEC com apoio do empreendedor, um por ano em que houver simulado, compilado num único documento.
- Art. 17: PAE para Licença de Instalação dispensa o Anexo C. Art. 21: métodos alternativos em situação excepcional, com consulta prévia e aprovação da CEDEC.
- Arts. 92 e 85: estimativa permitida para unidades hospitalares, prisionais e locais com aglomeração; simulado após a construção da ECJ.
- Art. 94: aviso à CEDEC com uma semana de antecedência.
- Art. 95: nível 2 (evacuação preventiva) e nível 3 (evacuação imediata com alarme eficaz).
- Arts. 96, 97, 99, 100, 101 e 103: pré-requisito, verificação in loco pela COMPDEC, critérios do item 8.1, participação não reprova, validação exige todos os critérios.
- Arts. 19, 20, 119 e 123: juntada durante o CCPAE, encaminhamento da falta aos órgãos fiscalizadores, reprovação sumária sem relatório.
- Anexo C, item 8.1: 10 critérios; os textos são guardados literalmente no sistema.

## Abordagens consideradas

1. **Padrão da DCO com conferência estruturada, escolhida:** exigibilidade e relatórios imutáveis por protocolo, guard na emissão, referência congelada no CCPAE, situação anual na listagem.
2. **Documento anual genérico unificando DCO e simulados:** elimina repetição, mas exige migrar tabelas da DCO já em uso; risco alto agora.
3. **Só o checklist do 8.1:** mais simples, mas sem o confronto de tempos que apoia o analista.

## Motor de critérios e janela

Classe pura `SimuladoAnexoC`, única dona do catálogo e da regra:

- Catálogo literal das 10 linhas do item 8.1 (índice e critério de validação), com a marcação de reprovável (1 a 8) ou informativo (9 e 10, Art. 100).
- `validado(array $criterios): bool`: verdadeiro somente se os 8 reprováveis estiverem marcados "atende".
- Indícios, calculados a partir dos dados, por critério:
  - critérios 5, 6, 7 e 8: linhas das tabelas correspondentes da seção 7 com saída maior ou igual à chegada da onda (comparação com a mesma tolerância de 1e-6 da fase E), ou com "houve problemas" ou "ponto de encontro inválido";
  - critério 2: alarme não audível em todos os pontos sem o morador indicado (nome e localização).
- Tempos em segundos, exibidos em mm:ss, reaproveitando `TempoAnexoE` (formatação e conversão).

Classe pura `PaeSimuladoJanela`: o relatório vale para uma data de referência quando a realização está entre a referência menos 12 meses e a própria referência, inclusive nos dois extremos.

## Dados e gravação

A migration principal da fase, `SDC/database/migrations/2026_10_09_120000_create_pae_simulado_registros.php`, cria duas tabelas e as referências no CCPAE. Nenhuma migration aplicada é editada; ajustes desta fase são consolidados nela.

`pae_simulado_avaliacoes` (exigibilidade, imutável, a mais recente vale): `protocolo_id`, `resultado` (`exigivel` | `dispensado`), `motivo_dispensa` (`licenca_instalacao` | `metodo_alternativo`, obrigatório quando dispensado), `fundamentacao`, `num_sei`, `chave_idempotencia`, `decidido_por`, `decidido_em`.

`pae_simulado_relatorios` (imutável; revisões de um mesmo simulado identificadas por `dt_realizacao` e `versao`, a maior versão vale):

- Envio: `dt_realizacao`, `versao`, `nivel_emergencia` (2 | 3), `dt_apresentacao`, `num_sei`, `observacao`, metadados do PDF no disco `pae`, `integrado` (bool) e `barragens_integradas` (texto, Art. 102), `aviso_cedec_em` (data, Art. 94; alerta informativo quando a antecedência for menor que 7 dias).
- `criterios` jsonb: para cada um dos 10 índices, `atende` (bool) e `justificativa` (obrigatória quando não atende; os informativos aceitam nulo).
- `tempos` jsonb, por categoria da seção 7: `sem_dificuldade` (por rota: população, chegada da onda, saída, houve problemas, ponto válido), `com_dificuldade`, `ensino`, `hospitalares_prisionais` (com nível de emergência indicado e marcação de estimativa), `aglomeracao`.
- `alarme` jsonb: audível em todos os pontos (bool), morador indicado (nome, localização) quando não audível.
- `informativos` jsonb: participação (população da ZAS, participantes, cadastrados no PAE, anos anteriores), observações sobre ensino e recursos, conclusão declarada pela COMPDEC (sim/não).
- Calculados no servidor: `validado` (bool) e `indicios` (jsonb).
- `chave_idempotencia`, `registrado_por`, `registrado_em`.

`pae_ccpae` ganha `simulado_avaliacao_id` e `simulado_relatorio_id`, nullable, FK com `restrictOnDelete`.

`PaeSimuladoService`:

- `avaliar` e `registrarRelatorio` validam pelas regras de fonte única nos Requests, travam o protocolo com `lockForUpdate`, aplicam idempotência (mesma chave e mesmos dados devolve o existente; dados diferentes, recusa), gravam timeline (`simulado_avaliacao`, `simulado_relatorio`) na mesma transação e limpam o PDF se a transação falhar.
- O relatório exige avaliação vigente `exigivel`; protocolo arquivado é somente leitura.
- `evidenciaParaEmissao(protocoloBloqueado, dataEmissao)`: sem avaliação, erro; `dispensado`, devolve só a avaliação; `exigivel`, exige a última versão de um relatório validado com realização na janela de 12 meses da data de emissão, apresentação até essa data e arquivo existente; senão, erro de validação na chave `simulado`.
- `resumo` e `anotarListagem` (lote, sem N+1) com a situação: `nao_avaliada`, `dispensado`, `em_dia`, `nao_validado` (há relatório na janela, mas nenhum simulado vigente validado; vigente é a última revisão de cada simulado), `vencido` (sem relatório vigente na janela e com CCPAE), `pendente_emissao` (sem relatório vigente na janela e sem CCPAE).

## Emissão e acompanhamento

- `PaeCcpaeService::emitir` chama `evidenciaParaEmissao` logo após a guarda da DCO, dentro do mesmo lock; falha não cria status, CCPAE, comunicação nem outbox. Os IDs usados ficam congelados no certificado; revisões posteriores não os alteram. CCPAE antigo conserva referências nulas.
- O modal de emissão mostra a situação do simulado e o erro do servidor na chave `simulado`; a checagem final é sempre no servidor.
- A listagem ganha o selo "Simulado" e a ação "Simulados" no menu do protocolo; não há suspensão nem revogação automática (Art. 139 fica para fase própria).

## HTTP, permissões e interface

Rotas sob `/pae/protocolo/{paeProtocolo}/simulados`: `GET` página (`pae.protocolos.view`), `POST .../avaliacoes` e `POST .../relatorios` (`pae.protocolos.validar`), `GET .../relatorios/{relatorio}/download` (`pae.protocolos.view`, com vínculo ao protocolo). Sem slug novo; o protocolo vem da rota.

Página `PaeSimulados.vue`: situação anual com a data do próximo vencimento (realização mais 12 meses), exigibilidade com histórico, formulário do relatório em abas (envio, critérios com indícios ao lado, tempos por categoria, alarme, informativos), histórico de relatórios e versões com download, referência congelada no CCPAE. Modo somente leitura sem `validar`, em versão histórica ou protocolo arquivado. Organismos em `Components/Organisms/Pae/Simulados/`, atomic design e modo escuro.

## Padrão visual das telas do PAE

Decisão do usuário em 2026-10-09: as telas novas do PAE (DCO, conferência de evacuação, ficha do Anexo B e a nova tela de simulados) devem seguir o padrão visual e a abordagem atômica do sistema, com o RAT (`Pages/Rat/RatCreate.vue`, `Components/Rat/Templates/RatFormLayout.vue`, `RatHeader`, `RatTabs`, `Components/Rat/Sections/*`) como referência:

- Cabeçalho com ícone em destaque, título, selo de situação e informação de contexto (protocolo), no lugar do título simples atual.
- Abas quando a tela tem áreas distintas (ex.: simulados: Envio, Critérios, Tempos, Alarme, Informativos, Histórico; DCO: Aplicabilidade, Declarações; evacuação: Setores, Rotas, Acessos, Pontos, Histórico).
- Seções em cartões recolhíveis com ícone, título e subtítulo, como as seções do RAT.
- Campos montados com os átomos e moléculas de formulário já existentes; nada de inputs soltos com classes repetidas.
- Breadcrumb legível ("Início › PAE › Protocolo … › DCO"), sem o nome técnico da página ("Pae Evacuacao", "Pae Dco").
- Campos numéricos iniciam vazios, não com 0, para não disparar a validação de mínimo do navegador antes do preenchimento.

Como os componentes do RAT são do módulo, a primeira tarefa visual extrai deles versões genéricas (cabeçalho de página, abas, cartão de seção recolhível) para `Components/Molecules` ou `Organisms`, mantendo o RAT com aparência idêntica, e as quatro telas do PAE passam a usá-las. Backend e regras não mudam nessas tarefas.

## Reaproveitamento antes da fase

Primeira tarefa, isolada e coberta pelos testes da DCO e da evacuação, extrai três mecanismos de baixo risco hoje duplicados: comparação de idempotência (`exigirMesmosDados`/`normalizar`), gravação de PDF no disco `pae` com limpeza em caso de falha, e o helper de anotação da listagem paginada. Comportamento da DCO e da evacuação não muda.

## Ambiguidades: leitura adotada

1. Art. 103 cita "critérios do Art. 98": adotado o item 8.1 (Arts. 99 e 101), decisão 4.
2. "Para o nível de emergência simulado": o mesmo conjunto de critérios em qualquer nível; o nível é registrado.
3. Critério 2 (alarme): o analista decide; o sistema aponta indício quando há "não audível" sem morador indicado.
4. Critérios de tempo sem limiar: indício quando saída ≥ chegada, decisão 6.
5. Critério 3 (comunicação): o analista decide; o sistema registra as ações informadas sem regra automática.
6. Critério 4 (pontos de encontro): o analista decide; "ponto inválido" numa linha vira indício.
7. Critério 7 cita só prisionais: hospitalares e prisionais ficam na mesma tabela e no mesmo critério.
8. Unidades de ensino e dificuldade de locomoção fora da periodicidade do Art. 91: registradas; ensino é informativo.
9. "Grande aglomeração": sem limiar; a lista vem do relatório.
10. Estudo do Art. 92 reenviado ou atualizado: o relatório de cada ano registra o que foi apresentado, com a marcação de estimativa.
11. Participação: só informativa (Art. 100).
12. Objetivo da apresentação: substituído pela exigibilidade do protocolo.
13. Um relatório por ano em que houve simulado; a ausência aparece na situação anual.
14. Assinaturas: o PDF é a prova; o sistema não confere assinaturas.
15. Numeração inconsistente do formulário: ignorada; o sistema usa as categorias.
16. Nota 5 só na seção 4: justificativa obrigatória vale para qualquer "não atende".
17. Quem valida: a CEDEC registra a própria conferência; a conclusão da COMPDEC fica como informativa.
18. Simulado integrado: cada protocolo registra seu relatório, com a marcação de integrado.

## Verificação e aceite

- Motor: 8 critérios atendidos validam; qualquer reprovável não atendido invalida; informativos não afetam; indícios por categoria; justificativa obrigatória.
- Janela: limites exatos (referência menos 12 meses, mesmo dia; um dia antes fora), ano bissexto.
- Serviço: imutabilidade, revisões, idempotência, arquivado, PDF limpo em falha, evento de histórico.
- Emissão: bloqueio sem avaliação, exigível sem relatório, relatório não validado, fora da janela, apresentado depois da emissão; liberação com dispensa ou relatório validado; referências congeladas; DCO continua valendo.
- HTTP e ACL; listagem com consultas constantes; regressão completa do PAE (DCO e evacuação incluídas); build.

## Fora do escopo

Formulário completo do Anexo C, relatório compartilhado entre barragens integradas, envio automático a órgãos, conferência de assinaturas, PAAP (Anexo D), sinalização do Art. 139 e sigilo.
