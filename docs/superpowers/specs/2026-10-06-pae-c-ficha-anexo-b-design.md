# Fase C do PAE: ficha cadastral do item 2 do Anexo B

- Data: 2026-10-06
- Branch: `feat/pae-gmg83-fase-c`
- Base: `origin/dev` em `794714bf`

## Contexto e objetivo

A Resolução GMG nº 83/2024 apresenta, no item 2 do Anexo B, os dados básicos sobre barragem, ZAS e ZSS. O PAE já possui cadastro compartilhado de empreendimento, análise técnica em `pae_forms` e, desde a fase B, municípios ZAS/ZSS por protocolo. Ainda não há uma ficha que preserve os dados apresentados para cada protocolo e suas revisões.

Esta fase entrega uma **Ficha cadastral — Anexo B** vinculada a um protocolo PAE. O operador pode registrar e revisar apenas os dados do item 2. Cada salvamento efetivo produz uma versão imutável, identificada por autor e data. A ficha pode permanecer incompleta enquanto é preparada, sem alterar o status do protocolo.

Fonte normativa: [Resolução GMG nº 83/2024, Anexo B, item 2](https://liferay.meioambiente.mg.gov.br/documents/117662/7003603/ResolucaoGMG_Nr83-2024/f6263034-1002-0587-f566-aeb642a1488f?t=1723499015768&version=1.0). A numeração 2.2 aparece duas vezes no anexo; a interface usa os nomes dos campos para evitar ambiguidade.

## Escopo funcional

A ficha contém:

| Grupo | Dados do item 2 |
| --- | --- |
| Identificação | Nome da barragem; nome da mina; método construtivo; volume do reservatório |
| Localização | Município sede e coordenadas geográficas da estrutura em graus decimais |
| Rejeito ou resíduo | Tipo e toxicidade conforme classificação informada com referência à ABNT NBR 10004 |
| ZAS | Extensão em quilômetros; população total, incluindo moradores, trabalhadores e público flutuante; população com dificuldade de locomoção ou necessidades especiais |
| ZSS | População total |
| Território | Municípios na ZAS e na ZSS; rios e cursos d’água diretamente afetados |
| Edificações sensíveis na ZAS | Quantidades de unidades hospitalares, escolares, prisionais e outras |
| Estruturas associadas | Relação de estruturas, como ECJ, pilhas e diques |

O cadastro `pae_empntos` serve somente para pré-preencher nome, mina, método, volume, população ZAS e município sede quando ainda não existe versão salva. O usuário confirma ou corrige esses valores na ficha; o salvamento não modifica o cadastro compartilhado nem o relatório técnico `pae_forms`.

Os municípios são mantidos na lista canônica `pae_protocolo_municipios` criada na fase B. A ficha exibe ZAS e ZSS e oferece acesso à triagem para corrigi-los. Ao salvar, grava uma cópia dos IDs, nomes e indicadores de zona na versão da ficha. Se a lista canônica mudar depois, a versão antiga continua fiel ao que foi salvo; a próxima gravação incorpora a lista atual e avisa que houve mudança. A ficha não mantém um segundo editor de municípios.

## Experiência de uso

- Uma ação no protocolo abre a página própria da ficha, com identificação do protocolo, seções do item 2, campos faltantes e histórico de versões.
- Sem versão salva, os valores do empreendimento aparecem como rascunho de pré-preenchimento, distinguíveis de dados persistidos. A lista ZAS/ZSS vem da triagem; ausências são sinalizadas.
- O botão Salvar aceita rascunho incompleto. Campos numéricos em branco permanecem ausentes, enquanto zero é um valor informado. Listas de rios e estruturas permitem declarar explicitamente “nenhum” ou acrescentar itens; vazio não declarado continua pendente.
- O histórico permite consultar versões anteriores em leitura, inclusive município e nome registrados à época. A versão atual é a maior numeração do protocolo. Protocolos arquivados permanecem consultáveis e não aceitam salvamento.
- A ficha não emite parecer, não conclui o PAE, não muda status e não interfere na emissão do CCPAE nesta fase.

## Modelo de dados e gravação

Uma migration principal da fase C cria `pae_fichas_anexo_b`, sem alterações em migrations antigas. Cada linha é um snapshot completo e imutável. Há chave estrangeira para `pae_protocolos`, número de versão único por protocolo, autor, data de criação, colunas tipadas para escalares do item 2 e JSONB para listas de rios, estruturas e municípios. Não há linha mutável separada para “versão atual”; ela é obtida pela maior versão. A exclusão lógica ou arquivamento do protocolo não apaga o histórico.

O serviço de gravação atua em transação, bloqueia o protocolo e compara a versão-base enviada pela tela à versão vigente. Em caso de edição concorrente, retorna conflito com orientação para recarregar, sem sobrescrever dados. Normaliza valores e ordena as listas antes de comparar; envio idêntico à versão vigente não cria outra linha nem evento. Uma mudança efetiva cria a próxima versão e um evento na `pae_timeline` com número da versão e autor. O snapshot de municípios é obtido no servidor da relação canônica no instante do salvamento, nunca aceito do cliente como fonte de verdade.

## Validação e completude

O backend valida tipos, comprimentos e limites: latitude entre -90 e 90; longitude entre -180 e 180; volume, extensão, populações e contagens de edificações não negativos; população com dificuldade de locomoção ou necessidades especiais não superior à população total da ZAS. Município sede deve existir no catálogo. Rios e estruturas são listas de textos limitados, sem duplicatas após normalização.

O rascunho pode omitir qualquer campo do item 2. A interface e a resposta do backend calculam a mesma lista de pendências a partir da versão atual. `0` conta como informado para valores numéricos. Para listas de rios e estruturas, uma lista vazia declarada representa “nenhum”; `null` representa “não informado”. A completude exige dados escalares, declaração de rios e estruturas e pelo menos um município em cada zona. Um município pode estar simultaneamente na ZAS e na ZSS, conforme a lista canônica. A completude é apenas informativa nesta fase.

## Integração e acesso

Rotas de consulta, gravação e leitura de versão ficam sob `/pae/protocolo/{paeProtocolo}/ficha-anexo-b`. A consulta e o histórico usam `pae.protocolos.view`; a gravação usa `pae.protocolos.edit`. O backend resolve o protocolo pela rota, verifica arquivamento na gravação e nunca aceita outro `protocolo_id` no corpo. A página aparece nas ações do protocolo para usuários autorizados.

O campo `numero_zas` do relatório técnico hoje recebe indevidamente `pop_zas` como valor inicial. A fase C remove apenas esse fallback incorreto, mantendo o campo independente da população cadastrada.

## Compatibilidade, riscos e verificação

- Protocolos existentes não recebem versões artificiais. Ao abrir a ficha pela primeira vez, veem pré-preenchimento não salvo e pendências explícitas.
- A versão registra os nomes dos municípios além de seus IDs para que mudanças futuras no catálogo não alterem a leitura histórica.
- Consultas e gravações seguem as permissões PAE já existentes; não há novo slug de ACL.
- Verificação local cobre pré-preenchimento legado, validação, primeira gravação, idempotência, concorrência, histórico, snapshot municipal, arquivamento e autorização. Os arquivos de teste usados durante o trabalho não entram no commit, conforme `AGENTS.md`.
- A interface é conferida no navegador em largura desktop e móvel; o fluxo de status e CCPAE é rechecado para ausência de efeito colateral.

## Fora de escopo

Os demais itens do Anexo B, cálculos de evacuação, PAAP/DCO, exercícios simulados, sinalização do art. 139, anexos da ficha, exportação e qualquer bloqueio de tramitação dependente desta completude ficam para fases posteriores.
