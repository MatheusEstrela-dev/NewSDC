# Checklist de adequação da camada DTO por módulo

Auditoria da branch `dev` no commit `794714bf`, em 07/10/2026. Escopo: `SDC/app/Modules`, com 27 módulos, 81 arquivos em `DTO/` ou `DTOs/` e 87 classes nesses arquivos. O padrão de **entrada e escrita** definido pelo projeto é **Request → DTO → Controller → Service → Model**. Marcação `[ ]` indica trabalho pendente, não implementação realizada. As verificações de DTOs de saída, integração e objetos de domínio respeitam a finalidade de cada classe.

Esta versão considera os DTOs adicionados na `dev` em `Acessos`, `Inventario`, `Pae` e `Tdap`. `Core/Actions` não pertence a `SDC/app/Modules`.

## P0 — corrigir defeitos funcionais

### Rat — 7 arquivos de DTO

- [ ] `DTOs/RatEnvolvidoDTO.php`: mudar `id` para `?string`; alinhar `Http/Requests/RatEnvolvidoRequest.php` à validação UUID e verificar o uso do ID da rota em `RatUnifiedController::updateEnvolvidos()`.
- [ ] `DTOs/RatVistoriaDTO.php`: mudar `id` para `?string`; corrigir `boolOrNull()` para que `"false"` não vire `true`.
- [ ] `DTOs/RatBoDTO.php`: mudar `ocorrenciaOrigemId` para `?string` e remover o cast inteiro de `ocorrencia_origem_id`. Conferir a validação e o caminho que fornece essa chave.
- [ ] `Http/Requests/RatVistoriaRequest.php` e `Controllers/RatUnifiedController.php`: completar as regras para os campos persistíveis; hoje 54 chaves `v_*` têm regras e 109 são mapeadas pelo DTO. Normalizar os formatos plano e aninhado e **só então** substituir `all()`/`input()` por dados validados.
- [ ] `DTOs/RatVistoriaDTO.php`: preservar a distinção entre campo ausente, `null`, `''`, `false` e `0` no `toArray()`; permitir limpar colunas quando o update enviar vazio.
- [ ] `DTOs/RatDadosGeraisDTO.php`, `RatEnvolvidoDTO.php`, `RatRecursoDTO.php`, `RatHistoricoDTO.php`, `RatAgenteDTO.php` e `RatBoDTO.php`: revisar cada `array_filter()` conforme o uso em create/update; corrigir somente as perdas de valores realmente possíveis. Revisar casts booleanos de `RatDadosGeraisDTO`, `RatEnvolvidoDTO`, `RatRecursoDTO` e `RatAgenteDTO`.
- [ ] `Services/RatRelatoService.php` e `RatWriteService.php`: retirar a construção de DTOs de **entrada** a partir de arrays recebidos da requisição; fazer Controller/Request fornecer objetos tipados sem perder os fluxos de rascunho e relato composto.
- [ ] Conferir `@property int $id` em `Models/Relatos/RatRelatoVistoria.php` e `RatRelatoEnvolvidos.php`; o Model base usa UUID.
- [ ] Regressão: UUID em edição de envolvido e origem do BO; vistoria plana e aninhada; campo ausente, limpo, `false` e `0`.

### Decretacoes — 2 arquivos de DTO, 8 classes

- [ ] `DTO/DecretacoesDTO.php` (`ProcessoRequestDTO`): receber dados validados em vez de ler `Request::input()`; no update, impedir que `status` seja transportado sem regra em `Requests/UpdateProcessoRequest.php`.
- [ ] `Controllers/DecretacoesController.php::storeDesastres()`: passar dados validados a `DesastreSubmissionDTO`; conferir o contrato aninhado antes de remover `all()`.
- [ ] `DTO/DecretacoesDTO.php` (`DesastreData`): revisar a propriedade mutável `entradaCategoriaDesastreId` e a atribuição posterior em `Services/DesastreDataService.php`; separar dado de entrada de estado calculado se isso simplificar o contrato.
- [ ] `DTO/RedecDTO.php`: manter como DTO de saída; `fromModel()` e criação pelo serviço de consulta são adequados à sua finalidade. Não mover sua criação para Controller apenas para imitar um DTO de entrada.
- [ ] Regressão: payload de update com `status` não previsto; submissão aninhada de desastres.

### Suporte — 1 arquivo de DTO

- [ ] `Controllers/SupportController.php`, `DTOs/CreateTicketDTO.php` e `Services/SupportTicketService.php`: definir armazenamento do `attachment` validado, transportar o caminho no DTO e persistir ou associar o arquivo conforme o Model.
- [ ] `DTOs/CreateTicketDTO.php`: acrescentar `declare(strict_types=1);`.
- [ ] Regressão: criar ticket com anexo e recuperar o anexo associado.

### Cisterna — passagem do DTO ao Model

- [ ] `Requests/StoreOrdemServicoRequest.php`, `DTOs/OrdemServicoDTO.php`, `Services/OrdemServicoService.php` e `Models/CisternaOrdemServico.php`: incluir `documento_url` no contrato de atribuição do Model ou persistir a coluna explicitamente; conferir create e update com leitura posterior.
- [ ] `DTOs/ComunidadeDTO.php` e `Services/ComunidadeService.php`: no update, `ativa` ausente deve preservar o valor anterior; `false` explícito deve ser gravado.
- [ ] `DTOs/ComunidadeDTO.php`, `OrdemServicoDTO.php` e demais DTOs de escrita Cisterna: não enviar `legacy_id => null` em updates HTTP que não aceitam `legacy_id`; preservar o ID de registros migrados.

### Compdec e Tdap — valores omitidos no update

- [ ] `Compdec/DTOs/EquipeDTO.php`, `Requests/UpdateEquipeRequest.php` e `Services/EquipeService.php`: editar equipe inativa sem `ativo` não deve reativá-la; preservar `false` explícito.
- [ ] `Compdec/DTOs/OrgaoDTO.php`, `Requests/UpdateOrgaoRequest.php` e `Services/OrgaoService.php`: omitir `tem_*` não deve convertê-los em `false` no Model; preservar `false` explícito.
- [ ] `Compdec/DTOs/EquipeDTO.php`, `OrgaoDTO.php` e demais DTOs de escrita do módulo: preservar `legacy_id` em updates HTTP. Os factories produzem `null` quando as Requests não fornecem a chave e o Model aceita a atribuição.
- [ ] `Tdap/DTOs/PrestadorDTO.php`, `Requests/PrestadoresRequest.php` e `Services/PrestadorService.php`: separar default `ativo = true` do cadastro da semântica de edição; omissão no update não deve reativar prestador inativo.
- [ ] `Tdap/DTOs/AtaDTO.php` e `LoteDTO.php`, respectivas Requests de update e Services: a mesma omissão de `ativo` vira `true`; preservar atas e lotes inativos durante edições comuns.
- [ ] Regressão: registro migrado conserva `legacy_id`; `ativa`/`ativo` e `tem_*` ausentes conservam o valor anterior; `false` enviado altera o valor conforme a Request.

## P1 — aderir ao fluxo de escrita

### Acessos — 4 arquivos de DTO de diretório

- [ ] `Controllers/CadastroAcessoController.php`: substituir o envio direto de `validated()` a `CadastroAcesso::create()/update()` por DTOs de entrada e um Service com parâmetro tipado, preservando regras de auditoria e autorização.
- [ ] Manter `ChecagemDiretorio`, `ContaDiretorio`, `ReferenciaConta` e `ResultadoOperacao` como contratos da integração com AD. Eles não substituem o DTO do cadastro HTTP; preservar a omissão de senha em logs/serialização.

### Inventario — 2 arquivos de DTO de remanejamento

- [ ] `Controllers/EquipamentoController.php` e `EstacaoController.php`: introduzir DTOs de entrada para os fluxos que hoje passam `validated()` diretamente a Models.
- [ ] `Controllers/MovimentacaoController.php`: avaliar DTO tipado entre a validação inline e `service->registrar()`, mantendo as regras de destino e quantidade.
- [ ] Preservar `RemanejamentoData` e `PessoaRemanejadaData`: `SalvarRemanejamentoRequest::dados()` cria o DTO com dados validados e autor explícito; `RemanejamentoService` recebe o tipo em `registrar()`/`editar()`.

### Pmda — 1 arquivo de DTO

- [ ] `Controllers/PmdaController.php` e `Services/PmdaService.php`: criar `PmdaPlanoDTO::deFormulario($request->validated())` no Controller e tipar `criar()`/`atualizar()` com o DTO. Não fazer o Service reconstruí-lo a partir de array.
- [ ] Preservar em `DTOs/PmdaPlanoDTO.php` o comportamento existente de chaves presentes; testar ausência, `null`, `false` e `0`. O `toArray()` atual não usa `array_filter()`.

### Cedec — 1 arquivo de DTO

- [ ] `DTOs/PrefeituraFiltroDTO.php` e `Controllers/PrefeituraController.php`: validar/normalizar query string na fronteira HTTP e fornecer dados explícitos ao DTO de filtro, sem importar `Illuminate\Http\Request` nele.
- [ ] Tornar o estado do filtro imutável se não houver atribuições posteriores.

### Demandas — 4 arquivos de DTO

- [ ] `DTOs/CriarDemandaData.php`, `AtualizarDemandaData.php` e `ResolucaoDemandaData.php`: factories recebem os dados validados e, quando necessário, ID do usuário em argumento explícito; remover dependência de FormRequest dentro dos DTOs.
- [ ] `DTOs/FiltroDemanda.php`: deixar a validação de query na Request/Controller e receber dados já validados. Seu `array_filter()` é de **filtro de consulta**, não um bug de limpeza de coluna.
- [ ] Preservar em `AtualizarDemandaData` a lista de chaves presentes no update parcial.

### Compdec — 5 arquivos de DTO

- [ ] Revisar imutabilidade de `DTOs/AnexoDTO.php`, `EquipeDTO.php`, `OrgaoDTO.php`, `PlanoContingenciaDTO.php` e `PrefeituraDTO.php`. Os Controllers já criam DTOs com `validated()` antes dos Services.
- [ ] Verificar casts booleanos e numéricos desses factories em conjunto com as regras das Requests; não classificar todo cast direto como bug quando a entrada já está normalizada.
- [ ] Depois das correções P0, conferir também campos opcionais não booleanos e `metadata` nos updates que aceitam payload incompleto.

### Pae — 4 arquivos de DTO

- [ ] `DTOs/PaeFormAnexoDTO.php`: revisar mutabilidade e o transporte de `UploadedFile`; os Controllers já passam dados validados ao Service tipado.
- [ ] Manter os contratos de `PaeFormInfoGeraisDTO` e `PaeFormObjetivoDTO`; tornar `final` é ajuste opcional, sem defeito funcional identificado.
- [ ] Preservar `EmitirCcpaeDTO` no fluxo `EmitirCcpaeRequest::validated()` → `PaeProtocoloController` → `PaeCcpaeService`.

## P2 — qualidade de integração e consistência

### Cemaden — 2 arquivos de DTO

- [ ] `DTOs/LeituraCemadenDTO.php` e `EstacaoCemadenDTO.php`: verificar `is_numeric`/formato antes de converter números do feed; texto inválido não deve virar medição zero.

### Inmet — 2 arquivos de DTO

- [ ] `DTOs/LeituraMeteorologicaDTO.php` e `EstacaoDTO.php`: tratar texto numérico inválido como erro ou ausência em `parseFloat()`/conversores; preservar zero legítimo.

### AjudaHumanitaria — 5 arquivos de DTO

- [ ] Conferir factories de `PedidoAhDTO`, `ItemPedidoDTO`, `ParecerDTO`, `EntregaBeneficiarioDTO` e `TransicaoPedidoDTO` ao reutilizá-los fora das Requests: defaults `0`/`''` não devem mascarar obrigatório ausente. Fluxo HTTP atual segue o padrão.

### Cisterna — 7 arquivos de DTO

- [ ] Manter o fluxo `validated() → deValidados() → Service` em `BeneficiarioDTO`, `ComunidadeDTO`, `ItemConferidoDTO`, `LoteDTO`, `NotificacaoDTO`, `OrdemServicoDTO` e `VistoriaDTO` após resolver as perdas P0 entre `toArray()` e Model.
- [ ] Se separar responsabilidades no futuro, considerar resolver alias polimórfico/erro de validação fora de `NotificacaoDTO`; o mapeamento explícito de tipos permitidos deve permanecer.

### Geoespacial — 4 arquivos de DTO

- [ ] Manter `MetadadoCamadaDTO` criado a partir de `validated()`. `CamadaGeoDTO`, `FeicaoKmlDTO` e `ProcedenciaDTO` são contratos de normalização/resultado, sem exigência de factory de Request.

### Tdap — 9 arquivos de DTO

- [ ] Manter o fluxo atual dos Controllers com `validated()` para `AtaDTO`, `CaminhaoDTO`, `CronoCaminhaoDTO`, `CronogramaDTO`, `CronoViagemDTO`, `LoteDTO`, `PrestadorDTO` e `VistoriaDTO`.
- [ ] Preservar o tratamento de `ativo` ausente em `CaminhaoDTO`, a normalização de prestador na Request e a preservação de itens ausentes em `VistoriaDTO`. Corrigir em `PrestadorDTO`, `AtaDTO` e `LoteDTO` a omissão de `ativo` no update, sem afetar o default do create.
- [ ] Preservar em `CronogramaDTO` a lista `ponto_captacao_ids` fora de `toArray()` e sua persistência pelo pivot; `VinculoPmdaDTO` é contrato de leitura entre PMDA e TDAP, sem mapeamento direto para uma tabela própria.

### Dashboard — 1 arquivo de DTO

- [ ] Manter `DashboardStatsDTO` como DTO de saída; suas propriedades já são `public readonly`. `readonly class` e `final` são opcionais aqui.

### PlanCon — 2 arquivos de DTO

- [ ] Manter `MunicipioDTO` e `PlanConStatsDTO` como saída de consulta; suas propriedades já são `public readonly`. Criação no Service de leitura não viola o fluxo de **entrada**.

### Plantao — 6 arquivos de DTO

- [ ] Manter `MovimentacaoDTO`, `PlantaoDetailDTO`, `PlantaoListDTO`, `ReservaListDTO`, `SnapshotDTO` e `ViaturaListDTO` como DTOs de saída. `fromModel()` e importação de Model são apropriados; propriedades já são `public readonly`.

### Medalhao — 1 arquivo de DTO

- [ ] Manter `PayloadBruto` como contrato da coleta externa; não aplicar o fluxo de formulário.

### Notificacoes — 1 arquivo de DTO

- [ ] Manter `NotificacaoSpec` como contrato de domínio com invariantes; reclassificação de pasta é opcional e não deve alterar seu comportamento.

### Ranking — 7 arquivos de DTO

- [ ] Manter `ContextoInstitucional`, `FatoNormalizado`, `FiltroPlacar`, `RegraVigente`, `ScoreDecisionData`, `ScoreRuleData` e `ScoreTransactionData` segundo seus contratos de domínio/consulta. Não impor `fromValidated()` ou `toArray()` sem uso real.

### Resgate — 1 arquivo de DTO

- [ ] Manter `Carteira` como objeto de cálculo de saldo; não tratá-lo como entrada HTTP.

### Sismos — 1 arquivo de DTO

- [ ] Manter `SismoDTO` como resultado dos normalizadores externos; preservar validação de coordenadas no caminho de ingestão.

### Treinamento — 1 arquivo de DTO

- [ ] Manter `ResultadoAutenticacaoCidadao` como resultado de autenticação; carregar `Cidadao` nos estados previstos não é acoplamento indevido de um DTO de entrada.

### Estoque e Shared — sem DTO

- [ ] `Estoque`: não criar DTO antes de existir um fluxo de escrita implementado.
- [ ] `Shared`: não criar DTO apenas para satisfazer contagem; contém infraestrutura compartilhada.

## Critério para encerrar cada correção

Confirmar no caminho real: Request valida todos os campos persistíveis; DTO distingue tipos e presença; Controller entrega objeto tipado; Service não recebe array bruto da entrada quando há DTO; Model grava o valor esperado. Conferir regressão de UUID, `false`, `0`, `null`, campo ausente e payload aninhado conforme o módulo. Logs temporários de diagnóstico devem ser retirados após o teste. Testes criados durante o trabalho não entram em commit, conforme a regra do projeto.
