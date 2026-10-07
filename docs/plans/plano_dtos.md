# Plano de adequação da camada DTO do NewSDC

Auditoria em 07/10/2026 da branch `dev` no commit `794714bf`, limitada a `SDC/app/Modules`. O padrão definido pelo projeto para **entrada e escrita** é **Request → DTO → Controller → Service → Model**: a Request valida e normaliza a entrada, o DTO representa somente dados aceitos, o Controller o entrega ao Service, e o Service persiste pelo Model. DTOs de saída, integração e resultados de domínio têm origem diferente e são avaliados conforme sua função. A revisão dos métodos de escrita também compara chaves validadas, `toArray()`, `$fillable` e chamadas `create()`/`update()`.

Na `dev`, a inspeção encontrou **27 módulos**, **81 arquivos** em `DTO/` ou `DTOs/`, **87 classes** nesses arquivos e **25 módulos com essas pastas**. `Decretacoes/DTO/DecretacoesDTO.php` contém sete classes. Apenas `Estoque` e `Shared` não têm DTO nessas pastas. `Acessos` agora possui quatro DTOs de integração com o diretório, `Inventario` possui dois DTOs de remanejamento, `Pae` possui `EmitirCcpaeDTO` e `Tdap` possui `VinculoPmdaDTO`; esses acréscimos não corrigem automaticamente os outros fluxos de escrita. `Core/Actions` está fora do escopo solicitado. A auditoria foi estática, com reproduções diretas em PHP para DTOs RAT e Compdec; não houve confirmação em banco ou execução da aplicação completa. O PHP CLI local é 8.1 e não carrega classes `readonly` do projeto.

## 1. Diagnóstico por prioridade

### P0 — defeitos funcionais

1. **RAT: UUID incompatível com DTO e Request.** A migration `2026_05_18_000001_switch_rat_tables_to_uuid.php` converte as chaves primárias RAT para UUID. `RatEnvolvidoDTO::$id` e `RatVistoriaDTO::$id` continuam `?int`; `RatEnvolvidoRequest` valida `id` como `integer`. Um UUID passado a `RatVistoriaDTO::fromArray()` produziu `TypeError`. `RatBoDTO` também converte `ocorrencia_origem_id` (UUID) para inteiro; no teste, um UUID iniciado por `0199` virou `199`. Conferir também os `@property int $id` dos Models RAT.
2. **RAT: vistoria persiste campos sem contrato de validação completo.** `RatUnifiedController::storeVistoria()` usa `$request->all()`. `RatVistoriaRequest` declara 54 regras `v_*`; `RatVistoriaDTO::toArray()` mapeia 109 chaves `v_*`. As 55 chaves sem regra seriam descartadas por uma troca isolada para `validated()`. O mesmo Controller usa `all()` ou `input()` em outros caminhos de escrita RAT. É preciso completar as regras e normalizar os formatos plano e aninhado antes da troca.
3. **RAT: conversão e atualização parcial.** `RatVistoriaDTO::boolOrNull()` transforma a string `"false"` em `true`; isso foi reproduzido em PHP. Seu `toArray()` remove `null` e `''`, impedindo limpar campos enviados vazios. Revisar os demais `array_filter()` RAT segundo a semântica de cada campo; `false` e `0` não devem ser eliminados.
4. **Decretacoes: entrada não validada no update.** `ProcessoRequestDTO::fromRequest()` lê `Request::input('status')`, mas `UpdateProcessoRequest` não define regra para `status`. `EntradaProcessoService` usa `$dto->allData` em `fill()`. `storeDesastres()` também passa `$request->all()` ao DTO após a Request validar a entrada. Restringir ambos os caminhos aos dados efetivamente validados.
5. **Suporte: anexo aceito e perdido.** `SupportController::store()` valida `attachment`, mas não o fornece a `CreateTicketDTO`. `attachmentPath` não aparece em `toArray()`; `SupportTicketService::create()` persiste apenas esse array. Definir o destino do arquivo e transportar o caminho até a persistência.
6. **Cisterna: URL validada, mas fora do Model.** `StoreOrdemServicoRequest` aceita `documento_url`, `OrdemServicoDTO::toArray()` a envia e `OrdemServicoService` chama `create()`/`update()`. `CisternaOrdemServico::$fillable` não contém `documento_url`, embora a coluna exista e `OrdemServicoResource` a leia. A atribuição em massa não grava esse valor (ou lança exceção se o descarte silencioso estiver desabilitado).
7. **Cisterna e Compdec: edição apaga `legacy_id`.** As Requests HTTP não aceitam essa chave, mas os DTOs `ComunidadeDTO`, `OrdemServicoDTO`, `EquipeDTO` e `OrgaoDTO`, entre outros, incluem `legacy_id => null` no `toArray()` quando ela falta. Os respectivos Services usam o array integral em `update()` e os Models permitem atribuição de `legacy_id`; editar um registro migrado pode zerar seu vínculo com o legado. Auditar os demais DTOs desses dois módulos que repetem o padrão.
8. **Updates: campo booleano ausente muda o valor persistido.** `ComunidadeDTO` converte `ativa` ausente em `true`; `EquipeDTO`, `PrestadorDTO`, `AtaDTO` e `LoteDTO` convertem `ativo` ausente em `true`; `OrgaoDTO` converte `tem_viatura` e os outros `tem_*` ausentes em `false`. As Requests de update aceitam essas omissões e os Services enviam o `toArray()` integral ao Model. Uma edição pode reativar registros inativos ou desligar indicadores sem solicitação explícita. Em `EquipeDTO` e `OrgaoDTO`, os defaults foram reproduzidos diretamente em PHP.

### P1 — desvios do fluxo de escrita e qualidade dos dados

- **Pmda:** `PmdaController` passa array validado e `PmdaService` constrói `PmdaPlanoDTO`. Mover a construção do DTO para o Controller e tipar os métodos de escrita do Service. Preservar o mecanismo já correto de chaves presentes em `PmdaPlanoDTO`.
- **Acessos e Inventario:** `CadastroAcessoController`, `EquipamentoController` e `EstacaoController` ainda escrevem `validated()` diretamente em Models. Os quatro DTOs atuais de Acessos representam contas/ações do diretório, não o cadastro HTTP. Em Inventario, `RemanejamentoData` e `PessoaRemanejadaData` já tipam o fluxo de remanejamento; manter esse contrato e introduzir DTOs apenas nos fluxos de escrita restantes. `Estoque` ainda não implementa escrita; `Shared` é infraestrutura, sem fluxo de entrada próprio.
- **Cedec e Demandas:** `PrefeituraFiltroDTO`, `FiltroDemanda` e os três `*DemandaData` recebem objetos Request/FormRequest. Fazer a Request validar e passar dados explícitos aos factories; fornecer o ID do usuário como argumento separado quando necessário. São mudanças de fronteira, não bugs de persistência confirmados.
- **Integrações Cemaden e Inmet:** casts `(float)` sobre texto externo não numérico produzem `0.0`. Rejeitar a leitura ou preservar ausência, sem confundir dado inválido com medição zero.
- **Compdec:** cinco DTOs de entrada têm propriedades públicas mutáveis. Revisar imutabilidade e casts booleanos mantendo os Controllers que já usam `validated()`. `PaeFormAnexoDTO` também é mutável. Ao corrigir updates, distinguir omissão de envio explícito de `false` e preservar identificadores do legado.

### P2 — consistência sem refatoração obrigatória imediata

- 80 dos 81 arquivos auditados declaram `strict_types=1`; a exceção é `Suporte/DTOs/CreateTicketDTO.php`.
- 63 das 87 classes são `readonly class`; outras usam `public readonly` por propriedade. Isso **não** torna `Dashboard`, `PlanCon` ou `Plantao` mutáveis. `final` e sufixos uniformes são preferências de padronização, não correções funcionais por si.
- Há 23 arquivos com cast direto `(bool)` e 12 com `array_filter()`. Esses números identificam pontos de revisão, **não** 35 bugs: por exemplo, `Tdap/CaminhaoDTO` filtra apenas a chave `ativo` ausente, e `Demandas/FiltroDemanda` remove vazios de uma consulta.
- DTOs de saída como `PlantaoDetailDTO`, `MunicipioDTO` e `RedecDTO` podem nascer de Models ou resultados de consulta. Objetos como `NotificacaoSpec`, `Carteira` e `ScoreDecisionData` têm comportamento de domínio; não aplicar mecanicamente o fluxo de formulário a eles.

## 2. Contrato de implementação

1. **Entrada de escrita:** Request valida/normaliza todos os campos persistíveis → DTO recebe somente dados validados → Controller passa DTO tipado → Service recebe DTO, executa regras de negócio e usa Model. O Service não reconstrói um DTO de entrada a partir de array da requisição.
2. **Create e update são contratos distintos.** No create, campos obrigatórios não devem ganhar valores silenciosos como `0` ou `''` no factory. No update parcial, usar `array_key_exists` ou uma lista de chaves presentes: ausente mantém a coluna; presente com `null` limpa quando permitido; presente com `false` ou `0` persiste esses valores.
3. **Tipos refletem origem e persistência.** UUID é `string` no PHP. Conversão numérica ou booleana de dados externos deve distinguir inválido, ausente e zero/falso. Usar validação da Request ou um conversor compartilhado somente quando houver reutilização real.
4. **Formato único por factory.** Se o frontend envia objetos aninhados e campos planos, normalizar na Request ou criar factories com contratos separados. `validated()` só pode substituir `all()` depois de todas as chaves aceitas terem regras.
5. **Saída e integração:** DTOs de saída podem usar `fromModel()`; normalizadores de feeds podem criar DTOs de integração. Results e Value Objects não precisam de `toArray()`, `fromValidated()` nem de mudanças de pasta para satisfazer o fluxo de escrita.
6. **Organização:** preferir `strict_types=1`, objetos de entrada imutáveis e nomes consistentes. Compor DTOs extensos quando isso reduzir acoplamento e repetição; não migrar schema nem criar enums apenas para renomear uma classe.

## 3. Execução proposta

| Etapa | Escopo | Critério de conclusão |
|---|---|---|
| 0 | RAT UUID, vistoria e update parcial; Decretacoes `status`; anexo Suporte; `documento_url`, `legacy_id` e booleanos de updates | Casos de regressão reproduzidos antes e corrigidos depois; nenhuma chave validada perdida nem campo alterado por omissão |
| 1 | Pmda, Acessos, Inventario | Escrita segue Request → DTO → Controller → Service → Model; Services recebem contratos tipados |
| 2 | Cedec, Demandas, Compdec, Pae | Entrada desacoplada de Request dentro do DTO e imutabilidade revista onde agrega proteção |
| 3 | Cemaden, Inmet e consistência restante | Medição inválida não vira zero; convenções documentadas por casos reais |

Ao editar migrations para qualquer correção de schema, consolidar a alteração na migration principal responsável, conforme as regras do projeto. Cada mudança de código deve formar uma unidade coerente; arquivos de teste criados durante o trabalho não entram em commit.

## 4. Verificações necessárias

- RAT: criar e atualizar envolvido com UUID; enviar UUID de origem; salvar vistoria nos formatos plano e aninhado; testar `false`, `0`, campo ausente e campo presente vazio; comparar todas as chaves persistíveis com as regras da Request.
- Decretacoes: confirmar que `status` fora das regras não altera processo e que dados de desastre persistem somente após validação.
- Suporte: enviar anexo e confirmar o caminho persistido e recuperável.
- Cisterna: criar e editar ordem de serviço com `documento_url` e confirmar leitura após persistência; editar registro migrado e confirmar preservação de `legacy_id`.
- Cisterna, Compdec e Tdap: editar comunidade, equipe, prestador, ata e lote inativos sem enviar `ativa`/`ativo` e confirmar preservação; enviar `false` explicitamente e confirmar gravação. Em Compdec, omitir `tem_*` e confirmar que os valores anteriores permanecem.
- Compdec: editar registros migrados de equipe e órgão e confirmar que `legacy_id` não é apagado.
- Pmda e demais updates parciais: confirmar ausência versus `null` versus `false`/`0`.
- Integrações: texto numérico inválido deve ser recusado ou tratado como ausência, nunca como medição zero.

O checklist operacional está em `docs/plans/checklist_correcoes.md`. Nenhuma correção de código foi aplicada por este plano.

## 5. Avaliação por módulo

Esta tabela resume o alinhamento de cada módulo ao fluxo de **entrada e escrita**. DTOs de leitura, integração e domínio não precisam seguir o fluxo HTTP de gravação. As falhas de persistência citadas são as identificadas na inspeção estática; os cenários de banco ainda precisam de regressão.

| Módulo | Avaliação frente ao fluxo Request → DTO → Controller → Service → Model |
|---|---|
| **Acessos** | **Fora do padrão no cadastro HTTP:** [CadastroAcessoController](../../SDC/app/Modules/Acessos/Controllers/CadastroAcessoController.php) envia `validated()` diretamente ao Model. Os quatro DTOs existentes representam integração com o diretório, não esse fluxo de escrita. |
| **AjudaHumanitaria** | **Conforme no fluxo HTTP:** Controllers criam DTOs a partir de dados validados e os passam aos Services. Revisar defaults de campos obrigatórios se os factories forem usados fora das Requests. |
| **Cedec** | **Parcial:** [PrefeituraFiltroDTO](../../SDC/app/Modules/Cedec/DTOs/PrefeituraFiltroDTO.php) lê `Request` diretamente. É DTO de consulta; não foi identificado defeito de escrita nesse ponto. |
| **Cemaden** | **Adequado à integração:** normalizadores criam os DTOs. [LeituraCemadenDTO](../../SDC/app/Modules/Cemaden/DTOs/LeituraCemadenDTO.php) pode converter texto numérico inválido em `0.0`; validar a entrada do feed. |
| **Cisterna** | **Fluxo de entrada presente, persistência incorreta em pontos concretos:** `documento_url` sai do DTO mas não está no `$fillable` do Model de ordem de serviço; updates podem apagar `legacy_id` e reativar comunidade quando `ativa` é omitida. |
| **Compdec** | **Fluxo de entrada presente, updates com perda de estado:** Controllers usam `validated()` e DTOs tipados, mas omissão de `ativo`/`tem_*` altera valores e `legacy_id => null` pode apagar o vínculo migrado. |
| **Dashboard** | **DTO de saída:** `DashboardStatsDTO` representa resultado de consulta. Não se aplica o fluxo de gravação. |
| **Decretacoes** | **Parcial, com risco na escrita:** `ProcessoRequestDTO` lê `status` de `Request::input()` sem regra correspondente no update; `storeDesastres()` entrega `all()` ao DTO. |
| **Demandas** | **Parcial:** existem DTOs de entrada e Service de escrita, mas factories recebem FormRequest. `AtualizarDemandaData` já distingue chaves presentes em update parcial. |
| **Estoque** | **Sem escrita implementada no escopo inspecionado:** não há DTO de entrada a exigir agora. |
| **Geoespacial** | **Conforme no fluxo de entrada identificado:** `MetadadoCamadaDTO` nasce de dados validados. Outros DTOs normalizam ou transportam resultados. |
| **Inmet** | **Adequado à integração, com conversão a revisar:** texto numérico inválido pode virar `0.0` nos conversores dos DTOs meteorológicos. |
| **Inventario** | **Parcial:** remanejamento já usa `RemanejamentoData` e `PessoaRemanejadaData` entre Request e Service. Equipamentos e estações ainda gravam dados validados diretamente em Models; empréstimo avulso passa array ao Service. |
| **Medalhao** | **DTO de integração:** `PayloadBruto` representa dados coletados externamente; não é DTO de formulário. |
| **Notificacoes** | **Contrato de domínio:** `NotificacaoSpec` expressa invariantes de notificação; o fluxo HTTP de gravação não se aplica mecanicamente. |
| **Pae** | **Conforme nos fluxos inspecionados:** Controllers recebem entrada validada e passam DTOs aos Services. `EmitirCcpaeDTO` também tipa a emissão do CCPAE. Revisar a mutabilidade de `PaeFormAnexoDTO` e a semântica de updates incompletos antes de alterar contratos. |
| **PlanCon** | **DTOs de saída:** `MunicipioDTO` e `PlanConStatsDTO` podem ser montados no Service de consulta. |
| **Plantao** | **DTOs de saída:** `fromModel()` é apropriado para detalhes, listas e snapshots; não indica desvio da escrita. |
| **Pmda** | **Fora da ordem definida:** `PmdaService` constrói `PmdaPlanoDTO` a partir do array validado recebido do Controller. O controle de chaves presentes do DTO deve ser preservado. |
| **Ranking** | **Contratos de domínio e consulta:** os sete DTOs representam contexto, fatos, regras, filtros e decisões; não exigem factory de Request. |
| **Rat** | **Incorreto em caminhos de escrita:** UUID é tratado como inteiro, vistoria usa `all()` com regras incompletas e `toArray()` impede limpar campos; `"false"` também pode virar `true`. |
| **Resgate** | **Objeto de domínio:** `Carteira` calcula saldo; não corresponde a entrada HTTP para Model. |
| **Shared** | **Infraestrutura compartilhada:** não há fluxo de escrita próprio que justifique DTO. |
| **Sismos** | **DTO de integração/resultado:** `SismoDTO` nasce da normalização externa, com validação de coordenadas no caminho de ingestão. |
| **Suporte** | **Fluxo incompleto:** a Request valida `attachment`, mas o Controller não leva o arquivo/caminho ao `CreateTicketDTO` persistido pelo Service. |
| **Tdap** | **Fluxo de entrada presente, com defeito em três updates:** omitir `ativo` em `PrestadorDTO`, `AtaDTO` ou `LoteDTO` produz `true` e pode reativar o registro. `CaminhaoDTO` já trata presença de `ativo` corretamente. `CronogramaDTO` separa IDs do pivot de pontos de captação das colunas do Model; `VinculoPmdaDTO` é contrato de integração entre módulos. |
| **Treinamento** | **DTO de resultado:** `ResultadoAutenticacaoCidadao` representa o resultado de autenticação, sem relação com escrita de formulário. |

## 6. DTOs com aplicação adequada na revisão estática

- **Entrada e escrita:** `Geoespacial/MetadadoCamadaDTO` recebe `validated()` no Controller e entrega campos explícitos ao Service; `Tdap/CronoCaminhaoDTO` separa a instrução `num_viagens_calculado` das colunas persistidas, e o Service calcula `num_viagens`; `AjudaHumanitaria/ParecerDTO` e `TransicaoPedidoDTO` transportam enums e dados validados para Services tipados. Em `Inventario`, `RemanejamentoData` e `PessoaRemanejadaData` transportam o lote validado; em `Pae`, `EmitirCcpaeDTO` tipa a emissão. São exemplos de contrato orientado à operação, sem obrigação de espelhar a tabela.
- **Semântica correta em partes do update:** `Pmda/PmdaPlanoDTO` preserva a diferença entre chave ausente e `null`; `Demandas/AtualizarDemandaData` registra chaves presentes; `Tdap/CaminhaoDTO` não reativa caminhão quando `ativo` falta; `Tdap/VistoriaDTO` não apaga itens do checklist inteiramente ausentes. Essas qualidades não eliminam os desvios de fluxo ou os demais campos ainda pendentes de verificação nesses módulos.
- **Saída e resultado:** `Dashboard/DashboardStatsDTO`, `PlanCon/MunicipioDTO`, `PlanCon/PlanConStatsDTO`, os seis DTOs de `Plantao` e `Decretacoes/RedecDTO` estão adequados à função de leitura. Criação a partir de Model ou consulta é esperada nesses casos.
- **Domínio e integração:** `Notificacoes/NotificacaoSpec`, `Resgate/Carteira`, os sete DTOs de `Ranking`, `Medalhao/PayloadBruto`, `Sismos/SismoDTO`, `Treinamento/ResultadoAutenticacaoCidadao`, os quatro DTOs de diretório de `Acessos` e `Tdap/VinculoPmdaDTO` cumprem papéis diferentes de um DTO de formulário; não se deve classificá-los como incorretos por não seguirem o fluxo HTTP de escrita.

"Adequado" aqui significa compatível com a finalidade observada no código. A confirmação de persistência de ponta a ponta depende dos cenários de regressão da seção 4; não há aprovação global de todos os campos de cada DTO sem essa execução.
