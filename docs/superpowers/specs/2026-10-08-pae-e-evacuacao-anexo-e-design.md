# Fase E do PAE: conferência de evacuação do Anexo E

- Data: 2026-10-08
- Branch: `feat/pae-evacuacao-anexo-e`
- Base: `origin/dev` em `fec3f1c5`

## Objetivo e decisões confirmadas

O empreendedor apresenta no PAE a memória de cálculo do tempo de evacuação (Resolução GMG nº 83/2024, art. 48, §4º) e preenche os critérios de validação do item 8 do Anexo B. A CEDEC precisa conferir esse cálculo de forma reprodutível, registrar o que foi conferido e enxergar não conformidades na análise.

Decisões do usuário nesta conversa:

1. **Quem usa:** o analista da CEDEC lança no protocolo os dados da memória de cálculo do empreendedor; o sistema recalcula, compara com o tempo declarado e preenche os critérios do Anexo B.
2. **Tempo total:** TTE é o **maior** valor entre o tempo máximo de deslocamento (TMD) e o tempo de estrangulamento (TE), conforme o texto do item 5.1 do Anexo E e a nota do item 8.2 do Anexo B. A fórmula e o exemplo numérico do item 5.1 somam os dois (15 + 10 = 25 min) e contradizem o próprio texto; a CEDEC decidiu pelo maior valor.
3. **Escopo:** Anexo E completo, Critério 1 (pontos de encontro) e Critério 2 (rotas de fuga) do item 8 do Anexo B.
4. **Reprovação sinaliza e não bloqueia:** nenhuma transição de status, emissão de CCPAE ou aprovação depende desta conferência.

No roteiro original (memória de 2026-10-02) a calculadora de evacuação era o subprojeto D; a DCO foi antecipada para a fase D e esta passa a ser a fase E.

## Base normativa

- Art. 48: rotas de fuga usam calçadas; rua só onde não há calçada (§3º, itens 3.1.1 e 3.1.2 do Anexo E); memória de cálculo obrigatória no PAE (§4º); estrangulamento menor que 1,2 m impede o uso da rota (§6º); resultado preenchido no Anexo B, Critério 2 (§7º).
- Anexo E, itens 3 a 5: setores, densidade, velocidade, tempo por setor e por rota, TMD, estrangulamento e TTE.
- Anexo B, item 8: Critério 1 (pessoas por m² do ponto de encontro menor que 3) e Critério 2 (tempo de saída menor que a chegada da onda, com nível de emergência).

## Abordagens consideradas

1. **Versão imutável com listas em `jsonb` e motor de cálculo puro, escolhida.** Mesmo padrão da ficha do Anexo B (fase C): histórico completo, auditável, resultado gravado junto da entrada.
2. **Tabelas normalizadas por versão** (setores, rotas, pivô, acessos, pontos): consulta relacional fina, mas cinco tabelas e versionamento trabalhoso para um formulário de conferência.
3. **Calculadora sem persistir a entrada:** simples, mas perde a prova do que foi conferido.

## Motor de cálculo

Classe pura `CalculoEvacuacaoAnexoE`, sem banco, única dona das regras. Requests, controller e Vue não repetem a regra; a tela obtém resultados pelo endpoint de simulação.

**Setor de evacuação** (identificador único, ex.: A, B):

- População: moradores; em área comercial, mais 30%, arredondado para cima.
- Área de passeio:
  - com calçada: largura × lados (1 ou 2) × distância (o exemplo do Anexo E usa 1,5 m × 2 × 170 m);
  - sem calçada (rua): (largura da rua − 2,90 m em mão única ou − 5,80 m em mão dupla) × distância; sobra menor ou igual a zero torna o setor inválido.
- Densidade D = P / A.
- Velocidade (m/s) pela tabela 01, conforme terreno plano ou inclinado (declividade predominante acima de 5%):

| Densidade (pessoas/m²) | Plano | Inclinado ou escadas |
| --- | --- | --- |
| D ≤ 0,54 | 1,20 | 1,05 |
| 0,54 < D ≤ 1,0 | 1,03 | 0,90 |
| 1,0 < D ≤ 1,5 | 0,84 | 0,74 |
| 1,5 < D ≤ 2,0 | 0,66 | 0,58 |
| D > 2 | 1,4 − 0,372 × D | 1,23 − 0,327 × D |

- Velocidade menor ou igual a zero pela fórmula (D por volta de 3,76 ou mais) marca o setor como **densidade inviável**; o setor e as rotas que passam por ele ficam sem tempo calculado.
- Tempo do setor TES = d / V, em segundos.

**Rota de fuga:** sequência ordenada de setores; TERF = soma dos TES; TMD = maior TERF.

**Acesso à área segura (estrangulamento):** largura L do ponto de maior afunilamento, terreno e rotas que chegam por ele. N = soma das populações dos setores dessas rotas, contando cada setor uma vez. TE (minutos) = 1,2 × N / (100 × L) no plano, ou 1,2 × N / (79 × L) em rampa ou escada. L menor que 1,2 m torna inválidas as rotas desse acesso (art. 48, §6º).

**Tempo de saída por rota** = maior entre a TERF da rota e o TE do seu acesso; rota sem acesso informado usa só a TERF. **TTE da área** = maior entre TMD e o maior TE, isto é, o pior tempo de saída entre as rotas; sem nenhum acesso, TE fica nulo e TTE = TMD. O cálculo por rota atende a tabela do Critério 2, que é por rota.

**Critério 2:** por rota, conforme quando o tempo de saída é estritamente menor que o tempo de chegada da onda informado (mm:ss); registra o nível de emergência em que a evacuação é indicada (1, 2 ou 3). Rota inválida ou sem tempo calculado é não conforme.

**Critério 1:** por ponto de encontro (nome e endereço, população estimada, área em m²), conforme quando população / área é estritamente menor que 3 pessoas/m².

**Tempo declarado:** o TTE declarado pelo empreendedor, quando informado, é comparado ao calculado; a diferença é exibida e há sinalização quando o calculado excede o declarado.

**Exibição:** tempos em mm:ss, convertendo a fração de minuto × 60 e arredondando ao segundo, como no exemplo do item 4.2 do Anexo E. Comparações de critério usam o valor exato, sem arredondamento.

## Dados e gravação

A migration principal da fase, `SDC/database/migrations/2026_10_08_130000_create_pae_evacuacao_conferencias.php`, cria `pae_evacuacao_conferencias`. Nenhuma migration aplicada é editada; ajustes de schema desta fase são consolidados nela.

Cada linha é uma versão imutável; a de maior `versao` é a vigente.

- `protocolo_id`, `versao` (únicos juntos).
- Entrada em `jsonb`: `setores`, `rotas`, `acessos`, `pontos_encontro`.
- `tte_declarado_segundos` (nulo quando não informado), `observacao`, `num_sei`.
- Resultados calculados no servidor: `tmd_segundos`, `te_segundos`, `tte_segundos` (nulos quando não calculáveis), `criterio1_conforme`, `criterio2_conforme`, `possui_rota_invalida`, `possui_setor_inviavel`, `excede_declarado` e `resultado` em `jsonb` com o detalhe por setor, rota, acesso e ponto.
- `chave_idempotencia` (UUID, única por protocolo), `criado_por`, `created_at`.

`PaeEvacuacaoService` valida a entrada, executa o motor e grava entrada, resultado e evento de histórico na mesma transação, com `lockForUpdate` no protocolo. Regras:

- O cliente nunca envia resultado; o servidor sempre recalcula.
- Mesma chave de idempotência com os mesmos dados devolve a versão existente; com dados diferentes, é recusada.
- Rotas referenciam setores pelo identificador; acessos referenciam rotas. Identificador inexistente ou repetido, rota sem setor, acesso sem rota e rota ligada a mais de um acesso são erros de validação com mensagem no campo. Setor que não pertence a nenhuma rota também é erro.
- Limites: até 50 setores, 20 rotas, 20 acessos e 50 pontos; números positivos com casas decimais limitadas.
- Protocolo arquivado é somente leitura.
- Versões antigas mantêm o resultado gravado; uma mudança futura de regra não reescreve o histórico.

## HTTP, permissões e interface

Rotas sob `/pae/protocolo/{paeProtocolo}/evacuacao`:

- `GET` página, com `pae.protocolos.view`.
- `POST .../simular`: valida e calcula sem gravar, devolve JSON; `pae.protocolos.edit`.
- `POST .../conferencias`: grava nova versão; `pae.protocolos.edit`.
- `GET .../conferencias/{versao}`: consulta uma versão; `pae.protocolos.view`.

Sem slug novo de ACL; `edit` segue o padrão da ficha do Anexo B, pois é registro da análise e não condiciona emissão. O protocolo é resolvido pela rota; `protocolo_id` no corpo é ignorado.

Página `PaeEvacuacao.vue`, acessada pela ação "Evacuação" do protocolo:

1. Painel de resultado: TTE, TMD e TE em mm:ss, selos dos Critérios 1 e 2, alertas de rota inválida, setor inviável e excesso sobre o declarado, e aviso de que a conferência não bloqueia a tramitação.
2. Editores em lista para setores, rotas (setores em ordem), acessos (rotas) e pontos de encontro; cada linha mostra seu resultado após a simulação.
3. TTE declarado, SEI e observação; botões "Simular" e "Registrar conferência". O registro exige simulação sem erros com os dados atuais.
4. Histórico de versões com autor, data, SEI, TTE e conformidade, e visualização somente leitura de cada versão.

Organismos em `Components/Organisms/Pae/Evacuacao/` (quatro editores e o painel) e composable `usePaeEvacuacaoForm` para estado, simulação e envio, seguindo atomic design e modo escuro.

## Sinalização e integração

- Listagem de protocolos: selo "Evacuação" com *não conferida*, *conforme* ou *não conforme* (falha em critério, rota inválida, setor inviável ou excesso sobre o declarado), por consulta em lote da versão vigente dos protocolos da página, sem N+1.
- Modal de emissão do CCPAE: exibe a situação da evacuação como informação.
- Histórico do protocolo: evento `evacuacao_conferencia` com versão, TTE e conformidade, gravado por `TimelinePae`.
- Workflow, guards, outbox e emissão não mudam.

## Verificação e aceite

- Motor reproduz os exemplos do Anexo E: D = 680 / (3 × 170) ≈ 1,33 e 0,84 m/s no plano; TERF01 = 8 + 4 + 3 = 15 min; TMD = 15 min entre 15, 13 e 14; 750 pessoas em 2,2 m no plano = 4,09 min (4 min 05 s); TTE = max(15, 10) = 15 min.
- Bordas: limites exatos da tabela (0,54; 1,0; 1,5; 2,0), D acima de 2 e densidade inviável, rua de mão única e dupla com sobra não positiva, acréscimo comercial arredondado, estrangulamento de 1,19 m contra 1,20 m, ponto com exatamente 3 pessoas/m² não conforme, setor compartilhado por duas rotas contado uma vez em N.
- Serviço: versões imutáveis, idempotência com dados iguais e diferentes, referências inválidas, protocolo arquivado, rollback sem linha nem evento.
- HTTP: 403 sem `edit` para simular ou gravar, leitura com `view`, 422 de validação, simulação sem gravação.
- Listagem com situação correta e número de consultas constante.
- Suíte PAE, build e navegador. Testes criados durante a implementação ficam fora do commit.

## Fora do escopo

Importação da memória de cálculo por arquivo ou shapefile, desenho de rotas em mapa, obtenção automática do tempo de chegada da onda, qualquer bloqueio de tramitação, conferência dos demais itens do Anexo B, simulados, PAAP, sinalização do art. 139 e sigilo.
