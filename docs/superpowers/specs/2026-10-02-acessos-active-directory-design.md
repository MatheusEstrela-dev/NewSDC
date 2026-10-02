# Acessos — Integracao direta com o Active Directory (F1–F3)

> Spec de design — 02/10/2026. Branch `feat/acessos-active-directory` (a partir de `dev`).
> Entrega E4 do plano `docs/superpowers/plans/2026-09-23-migracao-cedec-demanda-estrategia.md` (linhas 94-104) e fatia 8 da auditoria `.superpowers/sdd/catalogo-demandas/auditoria-inventario-acessos.md` (linha 101). Escopo desta spec: F1 fundacao, F2 leitura (consulta, bloqueio, sincronizacao agendada), F3 acoes de conta (desbloquear, redefinir senha, habilitar/desabilitar, exigir troca de senha). Criar usuario no AD (F4) e importar o legado (F5) ficam fora (secao 13).

## 1. Objetivo e criterios de sucesso

Substituir o caminho do `cedec-demanda` ate o AD (PowerShell via `shell_exec`, gateway HTTP sem autenticacao numa estacao de trabalho, scripts Python, IP fixo) por uma integracao LDAPS direta, auditavel e testavel, que implementa a porta existente `App\Modules\Acessos\Contracts\DiretorioCorporativo` e mantem funcionando o consumidor atual (automacao de Demandas).

Sucesso significa:

1. Nenhuma chamada ao AD acontece numa requisicao web: toda operacao vira uma linha em `acessos_operacoes_ad` e um job na fila `diretorio`, consumido so por um worker on-premise dentro da rede corporativa.
2. O operador ve o ciclo de cada acao (`solicitado` -> `enviado` -> `confirmado` | `falhou`) e o motivo da falha num codigo saneado, nunca a mensagem crua do AD.
3. A sincronizacao agendada atualiza o espelho do AD no cadastro casando por `objectGUID`, restrita a SearchBase, e reporta divergencias; nunca desativa ninguem.
4. A senha de um reset e aleatoria e forte, sai com `pwdLastSet=0` e aparece uma unica vez para quem executou a acao; nao e persistida em claro, logada, auditada, enfileirada nem enviada em props do Inertia.
5. A conta de servico tem privilegio minimo, delegado so na OU; o worker recusa alvo fora da SearchBase ou protegido, mesmo que o AD permitisse.
6. Sem `shell_exec`, PowerShell, Python, gateway em estacao, IP fixo ou senha fixa no codigo; hosts, OU, credencial e CA vem de configuracao.
7. A automacao de Demandas (desbloquear, ativar, resetar) continua: mesmo botao, mesmo historico (`automation_requested|confirmed|failed`), mesma chave de idempotencia.

## 2. Decisoes tomadas

| # | Decisao | Motivo |
|---|---|---|
| D1 | Transporte LDAPS (636) com `directorytree/ldaprecord` (core, sem `ldaprecord-laravel`); StartTLS na 389 so se o DC exigir (`DIRETORIO_SEGURANCA=starttls`). Certificado validado contra a CA corporativa montada no container (`LDAP_OPT_X_TLS_REQUIRE_CERT=HARD`). `ext-ldap` entra na imagem. | Decisao do usuario. AD so aceita `unicodePwd` em conexao cifrada; validar o certificado impede MITM. O core basta: nao ha login via AD no NewSDC. |
| D2 | A porta e ampliada com DTOs tipados (secao 4) e o adaptador e escolhido por `DIRETORIO_DRIVER` = `desligado` (padrao) \| `ldap` \| `fake`. `HttpDiretorioCorporativo` e `services.corporate_directory` saem. | O adaptador HTTP apontava para um gateway que nunca existiu e que o usuario vetou. Com `desligado` como padrao, o app web nunca tem credencial do AD. |
| D3 | Chamada ao AD so dentro de job, na conexao/fila `diretorio`. O app web (Azure App Service) so enfileira. O worker e a mesma imagem do NewSDC, numa VM on-premise, rodando so `php artisan queue:work diretorio --queue=diretorio`. | Decisao do usuario. O Azure nao alcanca os DCs; a rede corporativa alcanca. |
| D4 | **Recomendacao para P1 (secao 10.1):** fila `database` do Laravel no Postgres compartilhado, numa tabela do proprio modulo (`acessos_fila_diretorio`), com papel Postgres de privilegio minimo para o worker. | A operacao e o job entram na mesma transacao (atomico, sem job perdido). O worker so precisa de Postgres e LDAPS: sem Redis, Reverb ou credencial de cache. |
| D5 | Uma tabela-razao unica, `acessos_operacoes_ad`, para toda acao no AD, venha da tela de Acessos ou da automacao de Demandas. Chave de idempotencia unica; indice unico parcial impede duas operacoes iguais em voo para a mesma conta. | Separa "solicitado", "enviado" e "confirmado pelo AD" (plano E4, linha 101); substitui os flags booleanos do legado (auditoria, linha 83). |
| D6 | Toda operacao e convergente: o efeito e definido pelo estado-alvo (`lockoutTime=0`, bit 2 do UAC, `pwdLastSet=0`, senha nova), entao repetir e seguro. Falha transitoria repete (3 tentativas, backoff 10/30 s); falha definitiva encerra na hora. O erro persistido e um `codigo_erro` de lista fechada; a mensagem e o diagnostico do LDAP nunca sao gravados. | Retry sem efeito duplicado; o precedente de `ExecutarAutomacaoDemandaJob` (resposta do diretorio nunca persistida) vira regra. |
| D7 | O alvo e identificado por `objectGUID`. O DN e resolvido na execucao (a conta pode ter mudado de OU). Antes de qualquer escrita o worker confere: DN dentro da SearchBase, objeto `user` que nao e `computer`, `adminCount` diferente de 1, `sAMAccountName` fora de `DIRETORIO_CONTAS_PROTEGIDAS`. | Defesa em profundidade alem da delegacao: um erro de ACL no AD nao vira acao em conta privilegiada. |
| D8 | A sincronizacao casa por `objectGUID`; cadastro sem GUID so e vinculado por `login_ad` exato (sem diferenca de caixa), com uma unica conta na SearchBase. Nunca por nome. Atualiza so as colunas-espelho; `status` (aprovacao local) nunca muda. Diferencas viram linhas em `acessos_divergencias_ad`. Se faltarem contas demais, a rodada aborta sem aplicar nada. | Corrige o legado, que casava por nome aproximado e desativava em silencio quem nao casava. |
| D9 | Reset: senha gerada no job por CSPRNG, conforme a politica do AD, gravada com `pwdLastSet=0` no mesmo modify. Entrega por leitura unica: o job grava a senha cifrada (chave propria, `DIRETORIO_CHAVE_ENTREGA`) em `acessos_entregas_senha`, presa ao par operacao + operador, com TTL de 10 min. A tela consulta o estado e busca a senha uma vez por `POST`, que apaga a linha (`DELETE ... RETURNING`). Comparacao com a alternativa sincrona na secao 6.4. | Decisao do usuario. A senha so existe em claro na memoria do worker e no navegador do operador. |
| D10 | Permissoes: `acessos.cadastros.view` ve o espelho do AD; novo `acessos.diretorio.view` consulta ao vivo e ve operacoes e divergencias; o existente `acessos.diretorio.manage` desbloqueia, habilita, desabilita, exige troca e dispara a sincronizacao; novo `acessos.diretorio.reset` redefine senha. Ninguem age sobre a propria conta. | Reset entrega uma credencial valida de outra pessoa: merece slug proprio. O `acessos.diretorio.manage` ja existe sem uso. |
| D11 | A automacao de Demandas passa a ser cliente do caso de uso de Acessos (`SolicitarOperacaoDiretorio`). `ExecutarAutomacaoDemandaJob` sai; o historico da demanda e gravado por um listener do evento `OperacaoDiretorioConcluida`. O reset pela demanda entrega a senha ao operador pelo mesmo componente de leitura unica. | DRY: uma razao, um job, uma regra de autorizacao. Sem isso o reset pela demanda deixaria uma senha que ninguem conhece. |
| D12 | Trilha: toda operacao fica em `acessos_operacoes_ad`; se houver cadastro, tambem em `acessos_auditoria` (`diretorio_<acao>_solicitado\|confirmado\|falhou`, `senha_exibida`). `dados` leva so `operacao_id`, `codigo_erro` e `motivo`. Nenhum segredo. | O legado nao auditava acao no AD. |
| D13 | O worker nao usa Redis, Reverb nem notificacoes: `CACHE_STORE=array`, `BROADCAST_CONNECTION=null`, `QUEUE_CONNECTION=sync`. A tela acompanha por polling do endpoint da operacao. Concorrencia por `lockForUpdate` e advisory lock do Postgres. `CircuitBreakerService` e reaproveitado com o servico `diretorio` (estado por processo, suficiente com um worker). | O worker so alcanca Postgres e DCs. `NotificacaoDispatcher` enfileiraria no Redis do Azure, que o worker nao alcanca. Notificar fica para depois. |
| D14 | Schema consolidado na migration principal `2026_09_23_000002_create_acessos_tables.php`; bancos que ja a rodaram recebem `2026_10_02_100000_ajusta_acessos_active_directory.php` idempotente, que tambem chama o `SincronizadorDePermissoes` (precedente `2026_09_28_100000`). | Regra do repositorio. |
| D15 | A credencial do AD existe so no worker on-premise (`DIRETORIO_SENHA_FILE` lido de segredo do Swarm). O web roda com `DIRETORIO_DRIVER=desligado`. | Um vazamento das configuracoes do App Service nao leva a credencial do AD. |

## 3. Arquitetura

```
 Azure App Service (web, Octane)                 VM on-premise 10.160.131.50 (Swarm)
 DIRETORIO_DRIVER=desligado                      stack sdc-diretorio, servico worker
 ------------------------------                  DIRETORIO_DRIVER=ldap
 Controller -> FormRequest (can:)                -----------------------------------
   -> SolicitarOperacaoDiretorio                 queue:work diretorio --queue=diretorio
      DB::transaction {                            -> ExecutarOperacaoDiretorioJob(operacaoId)
        insert acessos_operacoes_ad (solicitado)       lock da operacao, estado=enviado
        insert acessos_fila_diretorio (job)  ====>     guardas de escopo (D7)
        insert acessos_auditoria                       AcaoDiretorio -> handler -> porta
      }                                                LdapDiretorioCorporativo --LDAPS 636--> DCs
 Tela: polling GET /acessos/operacoes/{id}         confirmado | falhou (+ codigo_erro)
       POST /acessos/operacoes/{id}/senha          evento OperacaoDiretorioConcluida
       (leitura unica, apaga a entrega)            -> listener Demandas (historico)
 Scheduler: acessos:sincronizar-diretorio
   -> insert acessos_sincronizacoes_ad + job ===>  SincronizarDiretorioJob (paginado)
            Postgres compartilhado (Azure Flexible Server, TLS verify-full)
```

Camadas no modulo (`SDC/app/Modules/Acessos`), no padrao DDD dos outros modulos:

- `Contracts/DiretorioCorporativo.php`: a porta (secao 4).
- `DTOs/`: `ContaDiretorio`, `ReferenciaConta`, `ResultadoOperacao`.
- `Exceptions/`: `DiretorioIndisponivel` (transitoria), `DiretorioRecusou` (definitiva, carrega `CodigoErroDiretorio`), `OperacaoDiretorioProibida` (validacao local, filha de `ValidationException` como `RemanejamentoProibido`).
- `Enums/`: `AcaoDiretorio`, `EstadoOperacaoAd`, `StatusAd`, `CodigoErroDiretorio`, `TipoDivergenciaAd`, `OrigemOperacaoAd`.
- `Infrastructure/`: `LdapDiretorioCorporativo`, `FabricaConexaoLdap`, `TradutorErroLdap`, `FakeDiretorioCorporativo`, `DiretorioDesligado`.
- `Services/`: `SolicitarOperacaoDiretorio`, `GuardaDeAlvo`, `GeradorSenhaForte`, `CofreEntregaSenha`, `SincronizadorDiretorio`, `AplicaEspelhoAd`.
- `Services/Acoes/`: um handler por acao (`ConsultarConta`, `DesbloquearConta`, `HabilitarConta`, `DesabilitarConta`, `ExigirTrocaSenha`, `RedefinirSenhaConta`), todos com a interface `HandlerAcaoDiretorio`.
- `Jobs/`: `ExecutarOperacaoDiretorioJob`, `SincronizarDiretorioJob`.
- `Events/OperacaoDiretorioConcluida.php`.
- `Console/`: `SincronizarDiretorioCommand` (`acessos:sincronizar-diretorio`), `DiagnosticoDiretorioCommand` (`acessos:diretorio-diagnostico`), `LimparEntregasSenhaCommand` (`acessos:limpar-entregas-senha`).
- `Controllers/`: `OperacaoDiretorioController`, `EntregaSenhaController`, `DivergenciaDiretorioController`.

## 4. Porta e DTOs

### 4.1 Interface

```php
namespace App\Modules\Acessos\Contracts;

interface DiretorioCorporativo
{
    /** Conta pelo sAMAccountName dentro da SearchBase; null se nao existe. */
    public function consultar(string $login): ?ContaDiretorio;

    /** Conta pelo objectGUID (forma canonica, 36 chars) em qualquer ponto do dominio; null se nao existe. */
    public function buscarPorGuid(string $objectGuid): ?ContaDiretorio;

    /**
     * Todas as contas de usuario da SearchBase, paginadas no servidor.
     * @return iterable<ContaDiretorio>
     */
    public function listarContas(): iterable;

    public function desbloquear(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function habilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function desabilitar(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    public function exigirTrocaSenha(ReferenciaConta $conta, string $operationId): ResultadoOperacao;

    /** Grava unicodePwd e, se $exigirTroca, pwdLastSet=0 num unico modify. */
    public function redefinirSenha(
        ReferenciaConta $conta,
        #[\SensitiveParameter] string $senha,
        bool $exigirTroca,
        string $operationId,
    ): ResultadoOperacao;
}
```

Contrato de erro, igual para todos os adaptadores:
- `DiretorioIndisponivel`: sem conexao, timeout, servidor ocupado, bind falhou por rede, circuito aberto. O job repete.
- `DiretorioRecusou(CodigoErroDiretorio $codigo)`: o AD respondeu e recusou. O job encerra sem repetir.
- O adaptador nunca encadeia a excecao original (`previous`) nem a loga, e o `getMessage()` das excecoes de dominio e um texto fixo por codigo. A mensagem e o `getDiagnosticMessage()` do LdapRecord nao saem do adaptador.

Os metodos antigos (`solicitarDesbloqueio`, `solicitarReset`, `solicitarAtivacao` e o `consultar` que devolvia `array`) deixam de existir: o unico consumidor (o job de Demandas) passa a usar o caso de uso de Acessos (D11).

### 4.2 DTOs (`final readonly`)

| DTO | Campos |
|---|---|
| `ContaDiretorio` | `objectGuid` (string canonica), `login` (sAMAccountName), `upn` ?string, `dn`, `nomeExibicao` ?string, `email` ?string, `ativa` bool (bit 2 do UAC desligado), `bloqueada` bool (bit 0x10 de `msDS-User-Account-Control-Computed`), `trocaSenhaPendente` bool (`pwdLastSet == 0`), `protegida` bool (`adminCount == 1`), `alteradaEm` ?DateTimeImmutable (`whenChanged`). Metodo `referencia(): ReferenciaConta`. Nenhum atributo fora desta lista. |
| `ReferenciaConta` | `objectGuid`, `dn`, `login`, `nomeExibicao` ?string. Montada a partir de uma `ContaDiretorio` recem-lida (o DN nunca vem do banco). |
| `ResultadoOperacao` | `contaDepois` (`ContaDiretorio` relida apos o modify), `efetivada` bool (false quando ja estava no estado-alvo, por exemplo desbloquear conta nao bloqueada), `senhaParaEntrega` ?string (so o handler de reset preenche; leva a senha da memoria do handler ate o cofre dentro da transacao do job; `__debugInfo`/`__serialize` a omitem). |

### 4.3 Adaptador LDAP: regras

- Conexao montada por `FabricaConexaoLdap` a partir de `config('acessos.diretorio')`:
  - hosts por FQDN, nunca IP: lista de `DIRETORIO_HOSTS` ou descoberta pelo SRV `_ldap._tcp.dc._msdcs.<DIRETORIO_DOMINIO>` (`dns_get_record`, ordenado por prioridade/peso);
  - `use_ssl` (636) ou `use_tls` (389);
  - `options`: `LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_HARD`, `LDAP_OPT_X_TLS_CACERTFILE => DIRETORIO_CA_CERT`, `LDAP_OPT_NETWORK_TIMEOUT`, `LDAP_OPT_TIMELIMIT`, `LDAP_OPT_REFERRALS => 0`;
  - bind simples com o UPN da conta de servico;
  - `DIRETORIO_HOST_PREFERENCIAL` (o PDC emulator) vai primeiro na lista: o bloqueio replica com urgencia para ele.
- Atributos lidos (lista fechada): `objectGUID`, `sAMAccountName`, `userPrincipalName`, `distinguishedName`, `displayName`, `mail`, `userAccountControl`, `msDS-User-Account-Control-Computed`, `pwdLastSet`, `adminCount`, `objectClass`, `whenChanged`.
- Filtros sempre com valor escapado (`$query->escape($valor)->filter()`, ou `where()` do LdapRecord, que escapa). O GUID entra no filtro em hex escapado (`(objectGUID=\xx\xx...)`, via `LdapRecord\Models\Attributes\Guid::getEncodedHex()`), respeitando a ordem de bytes mista do AD. DN nunca e montado por concatenacao de entrada.
- Filtro base de conta: `(&(objectCategory=person)(objectClass=user)(!(objectClass=computer)))`.
- Escritas:
  - `desbloquear` = replace `lockoutTime=0`;
  - `habilitar`/`desabilitar` = le o UAC atual e grava com so o bit 2 alterado (os outros bits sao preservados);
  - `exigirTrocaSenha` = replace `pwdLastSet=0`;
  - `redefinirSenha` = um unico modify com replace `unicodePwd` (UTF-16LE de `"senha"` entre aspas) e replace `pwdLastSet=0`.
  - Toda escrita rele a conta por GUID e devolve `ResultadoOperacao`.
- `TradutorErroLdap` mapeia o codigo de resultado LDAP (e o subcodigo `data XXX` do AD, lido em memoria e descartado) para `CodigoErroDiretorio`, conforme a tabela da secao 9.

### 4.4 Adaptadores de apoio

- `DiretorioDesligado`: todo metodo lanca `DiretorioIndisponivel` com codigo `config_ausente`. E o padrao do web e de qualquer ambiente sem configuracao.
- `FakeDiretorioCorporativo`: estado em memoria, opcionalmente persistido num JSON (`DIRETORIO_FAKE_ARQUIVO`, com `flock`) para dev/homolog, onde web e worker sao processos diferentes. Implementa a mesma semantica do adaptador LDAP (escopo, `efetivada`, bits). API de teste:
  - `semear(ContaDiretorio ...$contas)`;
  - `falharCom(string $metodo, CodigoErroDiretorio|string $codigo)` (`'indisponivel'` lanca a transitoria);
  - `chamadas(): list<array{metodo:string,guid:?string,operationId:?string}>`;
  - `ultimaSenha(string $guid): ?string` (so o fake guarda, so para teste).

## 5. Dados

Tudo consolidado em `2026_09_23_000002_create_acessos_tables.php`. Bancos que ja rodaram essa migration recebem `2026_10_02_100000_ajusta_acessos_active_directory.php`, idempotente (`hasTable`/`hasColumn`/existencia do indice), no-op em instalacao limpa, que ao fim chama o `SincronizadorDePermissoes`.

### 5.1 `acessos_cadastros` (alterado: espelho do AD)

| Coluna | Tipo | Regra |
|---|---|---|
| `object_guid` | uuid nullable, unique | Chave da sincronizacao. Gravado na primeira resolucao bem-sucedida (consulta, acao ou sync). |
| `dn_ad` | string(500) nullable | Ultimo DN visto. So exibicao e diagnostico; nunca usado para escrever. |
| `conta_ativa_ad` | boolean nullable | null = nunca lido. |
| `bloqueada_ad` | boolean nullable | |
| `troca_senha_pendente_ad` | boolean nullable | `pwdLastSet == 0`. |
| `ad_sincronizado_em` | timestamp nullable | Hora da ultima leitura do AD (consulta, acao ou sync). |
| `status_ad` | string(30) default `desconhecido` (existente) | Valores de `StatusAd`: `desconhecido` (nunca lido), `ativa`, `desabilitada`, `nao_encontrada` (sem conta na SearchBase pelo GUID/login), `fora_do_escopo` (GUID existe, DN fora da SearchBase), `protegida` (`adminCount=1` ou lista de protegidas). Index `(status_ad)`. |

`status` (aprovacao local: pendente/aprovado/ativo/inativo/rejeitado) nao e tocado por nada desta spec.

### 5.2 `acessos_operacoes_ad` (novo)

| Coluna | Tipo | Regra |
|---|---|---|
| `id` | uuid PK | `operacao_id` exposto a tela. |
| `chave_idempotencia` | string(120) unique | `acessos:{cadastro_id}:{acao}:{seq}` ou `demanda:{id}:{acao}:{seq}` (formato atual de Demandas). |
| `cadastro_id` | FK `acessos_cadastros` nullable, nullOnDelete | Null quando a demanda cita um login sem cadastro. |
| `login_ad` | string(100) | Login informado (validado com `^[A-Za-z0-9._-]{1,20}$`). |
| `object_guid` | uuid nullable | Alvo resolvido na execucao. |
| `acao` | string(30) | `AcaoDiretorio`: `consultar`, `desbloquear`, `habilitar`, `desabilitar`, `exigir_troca_senha`, `redefinir_senha`. |
| `origem` | string(20) | `OrigemOperacaoAd`: `acessos`, `demanda`. |
| `origem_id` | unsignedBigInteger nullable | Id da demanda. |
| `solicitado_por_id` | FK users nullable, nullOnDelete | O ator. Unico que pode ler a senha. |
| `motivo` | string(500) nullable | Obrigatorio em `desabilitar`. |
| `estado` | string(20) default `solicitado` | `EstadoOperacaoAd`: `solicitado`, `enviado`, `confirmado`, `falhou`. Index `(estado, created_at)`. |
| `codigo_erro` | string(40) nullable | `CodigoErroDiretorio` (secao 9). Nunca texto livre. |
| `tentativas` | unsignedSmallInteger default 0 | Incrementado a cada `enviado`. |
| `resultado` | jsonb nullable | Lista fechada: `{efetivada, ativa, bloqueada, troca_senha_pendente, senha_entregue_em?, senha_expirada?}`. |
| `enviado_em`, `concluido_em` | timestamp nullable | |
| `created_at`, `updated_at` | timestamps | |

Indices: `(cadastro_id, created_at)`, `(origem, origem_id)`, e unico parcial `acessos_operacoes_ad_em_voo_unique` em `(lower(login_ad), acao) WHERE estado IN ('solicitado','enviado')` (via `DB::statement`): duas solicitacoes iguais em voo viram uma (a segunda recebe a operacao existente).

Transicoes validas: `solicitado -> enviado -> confirmado|falhou`; `enviado -> enviado` (retry); `solicitado -> falhou` (validacao no worker, circuito aberto esgotado). Estados finais nao mudam.

### 5.3 `acessos_entregas_senha` (novo)

`operacao_id` uuid PK, FK `acessos_operacoes_ad` cascadeOnDelete; `destinatario_id` FK users cascadeOnDelete; `senha_cifrada` text (AES-256-GCM com `DIRETORIO_CHAVE_ENTREGA`, payload amarrado a `operacao_id|destinatario_id` como dado associado, entao copiar a linha para outra operacao nao decifra); `expira_em` timestamp, index; `created_at`. No maximo uma linha por operacao. Lida e apagada num unico `DELETE ... WHERE operacao_id=? AND destinatario_id=? AND expira_em > now() RETURNING senha_cifrada`. `acessos:limpar-entregas-senha` (a cada 5 min) apaga as vencidas e marca `resultado.senha_expirada=true` na operacao.

### 5.4 `acessos_sincronizacoes_ad` e `acessos_divergencias_ad` (novos)

`acessos_sincronizacoes_ad`: `id` uuid PK; `disparada_por_id` FK users nullable (null = agenda); `simulacao` boolean default false; `estado` string(20) (`solicitada`, `executando`, `concluida`, `abortada`, `falhou`); `codigo_erro` string(40) nullable; `totais` jsonb nullable (`{lidas, casadas_por_guid, vinculadas_por_login, atualizadas, divergencias: {tipo: n}}`); `iniciada_em`, `concluida_em`; timestamps. Index `(estado, created_at)`.

`acessos_divergencias_ad`: `id`; `sincronizacao_id` FK cascadeOnDelete; `cadastro_id` FK nullable cascadeOnDelete; `object_guid` uuid nullable; `login_ad` string(100) nullable; `tipo` string(40) (`TipoDivergenciaAd`); `detalhe` jsonb (lista fechada: `{local: {status, login_ad, conta_ativa_ad}, ad: {login, ativa, dn}}`); `created_at`. Index `(sincronizacao_id, tipo)`. Retencao: `DIRETORIO_RETENCAO_SINCRONIZACOES_DIAS` (padrao 30), purgada pelo proprio comando de sincronizacao.

`TipoDivergenciaAd`:

| Tipo | Quando |
|---|---|
| `cadastro_sem_conta` | Cadastro com GUID ou login que nao existe na SearchBase. |
| `conta_fora_do_escopo` | GUID existe no dominio, DN fora da SearchBase. |
| `login_divergente` | GUID casa, `sAMAccountName` diferente de `login_ad` (renomeada). O `login_ad` local nao e alterado. |
| `ativo_local_desabilitada_ad` | `status=ativo` e conta desabilitada. |
| `inativo_local_habilitada_ad` | `status=inativo` e conta habilitada. |
| `login_ambiguo` | Cadastro sem GUID cujo login casa com mais de uma conta (nao deveria ocorrer; registrado e nao vinculado). |
| `conta_sem_cadastro` | Conta da SearchBase sem cadastro. So contagem em `totais` mais as primeiras `DIRETORIO_SYNC_MAX_SEM_CADASTRO` (padrao 200) linhas, para a primeira rodada nao gerar milhares de linhas. |

### 5.5 `acessos_fila_diretorio` (novo, se P1 = fila no Postgres)

Esquema da tabela `jobs` do Laravel (`id`, `queue` index, `payload` longText, `attempts`, `reserved_at`, `available_at`, `created_at`), com nome do modulo. O payload so carrega o id da operacao/sincronizacao. Falhas vao para `failed_jobs` (ja existe).

### 5.6 Modelos

`OperacaoAd` (HasUuids; casts dos enums, `resultado` array; `cadastro()`, `solicitante()`, `entrega()`; `estaEmVoo(): bool`, `estaFinalizada(): bool`), `EntregaSenha` (sem `$fillable` de `senha_cifrada` para atribuicao em massa; `$hidden = ['senha_cifrada']`), `SincronizacaoAd`, `DivergenciaAd`. `CadastroAcesso` ganha os novos campos em `$fillable`, casts boolean/datetime e `operacoesAd(): HasMany`.

## 6. Fluxos

### 6.1 Solicitar (web, comum a toda acao)

`SolicitarOperacaoDiretorio::paraCadastro(CadastroAcesso $cadastro, AcaoDiretorio $acao, User $ator, ?string $motivo = null): OperacaoAd` e `::paraLogin(string $login, AcaoDiretorio $acao, User $ator, OrigemOperacaoAd $origem, ?int $origemId, string $chave): OperacaoAd`.

1. Valida localmente, lancando `OperacaoDiretorioProibida` (422): login presente e no formato; ator nao e o dono da conta (secao 7.2); login fora de `DIRETORIO_CONTAS_PROTEGIDAS`; `motivo` presente em `desabilitar`. O web nao testa a conexao com o AD (nao a tem, D15).
2. `DB::transaction`:
   - procura operacao em voo para `(lower(login), acao)`; se existir, devolve ela (idempotente para duplo clique);
   - cria `OperacaoAd` (`solicitado`, chave de idempotencia, `object_guid` do cadastro se houver);
   - grava `acessos_auditoria` `diretorio_<acao>_solicitado` (se houver cadastro);
   - `ExecutarOperacaoDiretorioJob::dispatch($operacao->id)` na conexao `config('acessos.diretorio.fila.conexao')`, fila `diretorio`, **sem** `afterCommit` (fila no mesmo Postgres: a linha do job entra na mesma transacao). Uma corrida com o indice parcial cai em `UniqueConstraintViolationException`, tratada relendo a operacao em voo.
3. Responde redirect com flash `operacao_diretorio = {id, acao}`. A tela abre o acompanhamento.

### 6.2 Executar (worker, `ExecutarOperacaoDiretorioJob`)

`$tries = config tentativas (3)`, `backoff = [10, 30]`, `$timeout = 30`, payload = `operacaoId`.

1. `DB::transaction` curta: `lockForUpdate` da operacao. Se final, sai (retry tardio ou duplicata). Senao `estado=enviado`, `enviado_em`, `tentativas++`. Commit (o lock nao fica aberto durante a chamada ao AD).
2. Circuito: `CircuitBreakerService::isOpen('diretorio')` -> `release(60)` sem gastar tentativa de dominio (o estado continua `enviado`).
3. Resolve a conta: por `object_guid` (operacao ou cadastro) com `buscarPorGuid`; senao `consultar(login)`. Nao achou -> `DiretorioRecusou(conta_inexistente)`. GUID achado e cadastro sem GUID -> grava o GUID no cadastro.
4. `GuardaDeAlvo::assegurar(ContaDiretorio)`: DN termina na SearchBase (comparacao normalizada, sem diferenca de caixa e espacos apos virgula), nao protegida, nao e a conta de servico. Falha -> `DiretorioRecusou(conta_fora_do_escopo|conta_protegida)`. `consultar` tambem passa pela guarda, mas so para marcar `status_ad`; nao falha.
5. `AcaoDiretorio::handler()` resolve o handler; ele chama a porta e devolve `ResultadoOperacao`.
6. `DB::transaction`: `AplicaEspelhoAd` grava as colunas-espelho do cadastro a partir de `contaDepois`; operacao `confirmado`, `concluido_em`, `resultado`; auditoria `diretorio_<acao>_confirmado`. Commit, depois `event(new OperacaoDiretorioConcluida($operacao))` e `recordSuccess('diretorio')`.
7. Excecoes:
   - `DiretorioIndisponivel`: `recordFailure('diretorio')` e relanca (o Laravel repete). Na ultima tentativa, `failed()`.
   - `DiretorioRecusou`: `fail($e)` direto (nao repete), como o 4xx do job atual.
   - `failed(Throwable)`: operacao `falhou`, `codigo_erro` = o codigo da excecao de dominio ou `indisponivel`/`erro_interno`; auditoria `diretorio_<acao>_falhou` com o codigo; evento `OperacaoDiretorioConcluida`. Nada de `$e->getMessage()`.

### 6.3 Por acao

| Acao | Handler | Efeito no AD | Pos-condicao no cadastro |
|---|---|---|---|
| `consultar` | `ConsultarConta` | nenhum (le) | espelho atualizado; `status_ad` pela guarda |
| `desbloquear` | `DesbloquearConta` | `lockoutTime=0` | `bloqueada_ad=false` |
| `habilitar` | `HabilitarConta` | UAC sem o bit 2 | `conta_ativa_ad=true`, `status_ad=ativa` |
| `desabilitar` | `DesabilitarConta` | UAC com o bit 2 | `conta_ativa_ad=false`, `status_ad=desabilitada` |
| `exigir_troca_senha` | `ExigirTrocaSenha` | `pwdLastSet=0` | `troca_senha_pendente_ad=true` |
| `redefinir_senha` | `RedefinirSenhaConta` | `unicodePwd` + `pwdLastSet=0` num modify | `troca_senha_pendente_ad=true`, `bloqueada_ad` relido; entrega gravada (6.4) |

`RedefinirSenhaConta` (dentro do passo 5):
1. `GeradorSenhaForte::gerar(ContaDiretorio $conta)`: `random_int`, tamanho `DIRETORIO_SENHA_TAMANHO` (padrao 16, minimo 14), ao menos uma maiuscula, minuscula, digito e simbolo de um conjunto seguro para copiar (`!@#$%*-_=+?`), sem caracteres ambiguos (`0O1lI`), sem conter o `sAMAccountName` nem pedaco de 3+ caracteres do `displayName` (regra de complexidade do AD); gera de novo se violar.
2. Porta `redefinirSenha($ref, $senha, exigirTroca: true, $operationId)`.
3. O handler devolve `ResultadoOperacao` com `senhaParaEntrega`; o job chama `CofreEntregaSenha::guardar($operacao, $senha)` dentro da transacao do passo 6 (substitui a entrega anterior da mesma operacao) e descarta a referencia em seguida (`sodium_memzero` na copia local quando disponivel).
4. Se o modify passou e a transacao falhou, o job repete e redefine de novo com outra senha: a senha que valeria no AD e sempre a ultima entregue (convergente, D6).

### 6.4 Entrega da senha: assincrona com leitura unica (recomendada) x sincrona

Fluxo recomendado:
1. A tela (Acessos ou Demandas) recebe o `operacao_id` no flash e chama `GET /acessos/operacoes/{operacao}` a cada 2 s por 60 s, depois a cada 10 s ate 5 min (`useOperacaoDiretorio`). A resposta e JSON com `estado`, `codigo_erro`, rotulo e `senha_disponivel` (true so para o ator, com entrega nao vencida). Nunca a senha.
2. Com `confirmado` e `senha_disponivel`, a tela mostra "Exibir senha". O clique faz `POST /acessos/operacoes/{operacao}/senha` (CSRF, `can:acessos.diretorio.reset` ou `can:demandas.chamados.automatizar` conforme a origem, e ator = `solicitado_por_id`). O `CofreEntregaSenha::retirar()` executa o `DELETE ... RETURNING`, decifra e responde `{senha}` com `Cache-Control: no-store`, `Pragma: no-cache`. Segunda chamada: 410 "A senha ja foi exibida ou expirou; gere um novo reset."
3. O `SenhaTemporariaModal` mostra a senha com botao copiar e aviso "anote agora; ela nao sera exibida de novo; o usuario tera de troca-la no primeiro acesso". Fechar zera a variavel local. A senha nunca entra em store, prop do Inertia, `localStorage`, URL ou log de console.
4. Auditoria `senha_exibida` (sem valor) e `resultado.senha_entregue_em`.

| Criterio | Assincrona + leitura unica | Sincrona (requisicao espera o AD) |
|---|---|---|
| Viabilidade | Funciona com o web no Azure e o AD so on-prem. | Exige o web alcancar os DCs (VPN Azure-Prodemge) ou a requisicao segurar um worker do Octane esperando o job (polling dentro da requisicao). |
| Exposicao | Senha cifrada em repouso ate 10 min, amarrada a operador + operacao, apagada na leitura. | Nunca em repouso; mas trafega na resposta da mesma requisicao. |
| Falha no meio | Operacao fica `confirmado` com entrega; operador pode reabrir a tela ate o TTL. | Timeout da requisicao com o reset ja aplicado: senha perdida, conta inutilizavel ate novo reset. |
| Retry | Convergente; a ultima senha e a entregue. | Repetir = outro reset sem o operador saber qual valeu. |
| Custo | Tabela, endpoint, polling, purga. | Menos codigo, mas acoplamento de rede e de tempo. |

Recomendacao: assincrona. A sincrona so seria aceitavel se P1 virasse "web alcanca o AD", o que contradiz D3.

### 6.5 Sincronizacao (F2)

1. Agenda (`routes/console.php`, sob `config('acessos.diretorio.sincronizar.agendar')`): `acessos:sincronizar-diretorio` `hourlyAt(20)->between('06:00','20:00')->timezone('America/Sao_Paulo')->onOneServer()->withoutOverlapping(30)`; `acessos:limpar-entregas-senha` `everyFiveMinutes()`. Botao "Sincronizar agora" (diretorio.manage) chama o mesmo caso de uso.
2. O comando cria `SincronizacaoAd` (`solicitada`) e despacha `SincronizarDiretorioJob($id)` na fila `diretorio`, na mesma transacao. Se ja houver uma `solicitada|executando` com menos de 1 h, nao cria outra. `--simular` grava `simulacao=true`: calcula e reporta, nao aplica espelho.
3. O job toma `pg_try_advisory_lock(hashtext('acessos:sincronizacao'))`; se nao conseguir, encerra a rodada como `abortada` (`codigo_erro=concorrente`).
4. `listarContas()` paginado (`DIRETORIO_SYNC_TAMANHO_PAGINA`, padrao 500), indexado em memoria por GUID e por `lower(login)` (so os campos do DTO; ordem de grandeza: milhares de contas).
5. Para cada cadastro (cursor por id):
   - com GUID: casa pelo GUID; sem par na SearchBase -> `buscarPorGuid` para separar `conta_fora_do_escopo` de `cadastro_sem_conta`;
   - sem GUID e com `login_ad`: casa por login exato e unico -> grava GUID (`vinculadas_por_login`); mais de um -> `login_ambiguo`; nenhum -> `cadastro_sem_conta`;
   - casado: compara e acumula divergencias de login e de status; prepara a atualizacao do espelho.
6. Freio de seguranca antes de aplicar: se `cadastro_sem_conta` passar de `DIRETORIO_SYNC_LIMIAR_AUSENCIA` (padrao 0.2) dos cadastros com GUID ou login, ou se `listarContas()` voltou vazio, a rodada vira `abortada` (`codigo_erro=resultado_suspeito`), grava as divergencias e **nao** aplica nada. Protege contra SearchBase errada, permissao de leitura retirada ou DC parcial.
7. Aplica o espelho em lotes de 200 (`conta_ativa_ad`, `bloqueada_ad`, `troca_senha_pendente_ad`, `dn_ad`, `status_ad`, `ad_sincronizado_em`). `cadastro_sem_conta` recebe `status_ad=nao_encontrada`; nada mais. `status` local nunca muda.
8. `totais`, `concluida`, libera o advisory lock, purga rodadas alem da retencao.

### 6.6 Automacao de Demandas (consumidor existente)

1. `DemandaController::automatizar` e `ExecutarAutomacaoDemanda::solicitar` mantem assinatura, validacao de login, sequencia e formato do `operation_id` (`demanda:{id}:{acao}:{seq}`) e o historico `AUTOMACAO_SOLICITADA`.
2. Em vez de despachar `ExecutarAutomacaoDemandaJob`, chama `SolicitarOperacaoDiretorio::paraLogin($login, $acaoAd, $ator, OrigemOperacaoAd::DEMANDA, $demanda->id, $operationId)`; mapa `desbloquear -> desbloquear`, `ativar -> habilitar`, `resetar -> redefinir_senha`. `OperacaoDiretorioProibida` vira `DomainException` com a mesma mensagem (o controller ja trata).
3. `Demandas\Listeners\RegistraResultadoAutomacao` (sincrono, roda no worker) escuta `OperacaoDiretorioConcluida` com `origem=demanda` e grava `AUTOMACAO_CONFIRMADA` ou `AUTOMACAO_FALHOU` ("falhou: <rotulo do codigo_erro>"), com `operation_id` no contexto.
4. `DemandasShow` recebe a ultima operacao de automacao do ator (`automacao: {operacao_id, acao, estado}`); o `DemandaAutomacaoButton` reusa `useOperacaoDiretorio` e `SenhaTemporariaModal`.
5. `ExecutarAutomacaoDemandaJob` e removido.

## 7. HTTP e autorizacao

### 7.1 Rotas (`routes/modules/acessos.php`, prefixo `acessos.`)

Rotas literais registradas antes de `/{cadastro}`; `{cadastro}` passa a `whereNumber`; `{operacao}` com `whereUuid` (`operacao` nao colide com `Route::model` global; conferir de novo na implementacao).

| Metodo | URI | Nome | Gate |
|---|---|---|---|
| POST | `/acessos/{cadastro}/diretorio/consultar` | `diretorio.consultar` | `acessos.diretorio.view` |
| POST | `/acessos/{cadastro}/diretorio/desbloquear` | `diretorio.desbloquear` | `acessos.diretorio.manage` |
| POST | `/acessos/{cadastro}/diretorio/habilitar` | `diretorio.habilitar` | `acessos.diretorio.manage` |
| POST | `/acessos/{cadastro}/diretorio/desabilitar` | `diretorio.desabilitar` | `acessos.diretorio.manage` (+ `motivo` obrigatorio) |
| POST | `/acessos/{cadastro}/diretorio/exigir-troca-senha` | `diretorio.exigir-troca` | `acessos.diretorio.manage` |
| POST | `/acessos/{cadastro}/diretorio/redefinir-senha` | `diretorio.redefinir-senha` | `acessos.diretorio.reset` |
| GET | `/acessos/operacoes/{operacao}` | `operacoes.show` (JSON) | policy `OperacaoAdPolicy::ver`: ator, ou `acessos.diretorio.view` |
| POST | `/acessos/operacoes/{operacao}/senha` | `operacoes.senha` (JSON) | policy `OperacaoAdPolicy::retirarSenha`: ator e (`acessos.diretorio.reset` se origem acessos; `demandas.chamados.automatizar` se origem demanda) |
| GET | `/acessos/diretorio/divergencias` | `diretorio.divergencias` | `acessos.diretorio.view` |
| POST | `/acessos/diretorio/sincronizar` | `diretorio.sincronizar` | `acessos.diretorio.manage` |

- Escritas com `throttle:acessos-diretorio` (10/min por usuario; reset e a leitura de senha 5/min), limitador registrado no `AcessosServiceProvider`.
- FormRequests: `SolicitarOperacaoDiretorioRequest` (autoriza pelo slug da acao vinda da rota; `motivo` `required_if` desabilitar, max 500) e `SincronizarDiretorioRequest`. Controllers finos chamam o caso de uso.
- `Handler::$dontFlash` ganha `senha`; a rota `operacoes.senha` fica fora de qualquer middleware que registre corpo de resposta.
- `CadastroAcessoController::index/show` passam a enviar as colunas-espelho, `pode.diretorio = {ver, gerenciar, redefinir}` e, no show, as 10 ultimas operacoes (sem `resultado` alem dos booleanos).

### 7.2 Regras de autorizacao

1. Slugs: `acessos.diretorio.view` e `acessos.diretorio.reset` novos; `acessos.diretorio.manage` existente. Em `config/permissions.php`, sai `Cadastros.directory` e entra o grupo `ACESSOS > Diretorio` (`view`, `manage`, `reset`).
2. Papeis (proposta, P4): `admin` ja tem `acessos.*`; `manager` recebe view, manage, reset; `analyst` recebe view e manage; `operator` recebe view. Concedidos pelo config e pelo `SincronizadorDePermissoes` (o companion chama o sincronizador como o precedente).
3. Ninguem age sobre a propria conta: proibido se `cadastro.user_id == ator.id`, ou `cadastro.cpf == ator.cpf`, ou o login alvo e o `login_ad` de algum cadastro do ator (por `user_id` ou `cpf`). Vale para Acessos e para Demandas (a regra mora no caso de uso). `consultar` tambem e bloqueado na propria conta (evita usar a tela para ver o proprio estado e depois pedir a outro).
4. Contas protegidas (lista + `adminCount=1` + a propria conta de servico) recusam qualquer acao, inclusive consultar ao vivo.
5. Senha: so o ator le, mesmo um admin nao le a senha de reset de outro operador.

## 8. Configuracao

`config/acessos.php` (novo), chave `diretorio`; envs documentadas no `.env.example` (sem valores reais).

| Env | Padrao | Uso |
|---|---|---|
| `DIRETORIO_DRIVER` | `desligado` | `desligado` \| `ldap` \| `fake`. Web: sempre `desligado`. |
| `DIRETORIO_HOSTS` | vazio | FQDNs separados por virgula (nunca IP). |
| `DIRETORIO_DOMINIO` | vazio | Dominio para a descoberta SRV quando `DIRETORIO_HOSTS` esta vazio. |
| `DIRETORIO_HOST_PREFERENCIAL` | vazio | PDC emulator, posto primeiro na lista. |
| `DIRETORIO_SEGURANCA` | `ldaps` | `ldaps` (636) \| `starttls` (389). Sem opcao de texto claro. |
| `DIRETORIO_PORTA` | por seguranca | Sobrescreve 636/389. |
| `DIRETORIO_BASE_DN` | vazio | Raiz do dominio (busca por GUID). |
| `DIRETORIO_SEARCH_BASE` | vazio | OU delegada; escopo de leitura, sync e escrita. |
| `DIRETORIO_USUARIO` | vazio | UPN da conta de servico. |
| `DIRETORIO_SENHA_FILE` / `DIRETORIO_SENHA` | vazio | Arquivo do segredo do Swarm (preferido) ou valor. |
| `DIRETORIO_CA_CERT` | `/etc/ssl/diretorio/ca.pem` | Cadeia da CA que emitiu o certificado dos DCs. |
| `DIRETORIO_TIMEOUT_CONEXAO` / `DIRETORIO_TIMEOUT_OPERACAO` | 5 / 10 | Segundos. |
| `DIRETORIO_CONTAS_PROTEGIDAS` | vazio | sAMAccountNames separados por virgula. A conta de servico entra sempre. |
| `DIRETORIO_FILA_CONEXAO` | `diretorio` | Conexao de fila; `sync` so em teste de unidade. |
| `DIRETORIO_FILA_DB_CONNECTION` | `pgsql` | Conexao de banco da fila `database`. |
| `DIRETORIO_TENTATIVAS` / `DIRETORIO_BACKOFF` | 3 / `10,30` | Retry de falha transitoria. |
| `DIRETORIO_SENHA_TAMANHO` | 16 | Minimo 14. |
| `DIRETORIO_ENTREGA_TTL_MINUTOS` | 10 | Vida da entrega cifrada. |
| `DIRETORIO_CHAVE_ENTREGA_FILE` / `DIRETORIO_CHAVE_ENTREGA` | vazio | `base64:` de 32 bytes; igual no web e no worker; sem ela o reset recusa (`config_ausente`). |
| `DIRETORIO_SINCRONIZAR_AGENDA` | `false` | Liga a agenda. |
| `DIRETORIO_SYNC_TAMANHO_PAGINA` / `DIRETORIO_SYNC_LIMIAR_AUSENCIA` / `DIRETORIO_SYNC_MAX_SEM_CADASTRO` | 500 / 0.2 / 200 | |
| `DIRETORIO_RETENCAO_SINCRONIZACOES_DIAS` | 30 | |
| `DIRETORIO_FAKE_ARQUIVO` | vazio | Estado do fake em dev/homolog. |

`config/queue.php` ganha a conexao `diretorio` (`driver=database`, `connection=env DIRETORIO_FILA_DB_CONNECTION`, `table=acessos_fila_diretorio`, `queue=diretorio`, `retry_after=120`, `after_commit=false`). `config/services.php` perde `corporate_directory`.

## 9. Erros e casos de borda

### 9.1 `CodigoErroDiretorio`

| Codigo | Origem | Repete? | Mensagem na tela |
|---|---|---|---|
| `indisponivel` | sem conexao, `LDAP_SERVER_DOWN` (-1/81), busy (51), unavailable (52), circuito aberto | sim | "O diretório não respondeu. Tentaremos de novo." / apos esgotar: "O diretório está indisponível." |
| `timeout` | `LDAP_TIMEOUT` (85), timelimit (3) | sim | "O diretório demorou a responder." |
| `credencial_servico` | bind recusado (49) | nao | "A conta de serviço do SDC foi recusada pelo AD. Avise a infraestrutura." |
| `certificado` | falha de TLS/CA na conexao | nao | "Certificado do diretório inválido. Avise a infraestrutura." |
| `sem_permissao` | insufficientAccessRights (50) | nao | "O SDC não tem permissão para esta ação nesta conta." |
| `conta_inexistente` | noSuchObject (32) ou busca vazia | nao | "Conta não encontrada no AD." |
| `conta_fora_do_escopo` | guarda (D7) | nao | "A conta está fora da unidade organizacional gerenciada pelo SDC." |
| `conta_protegida` | guarda (D7) | nao | "Conta protegida: não pode ser alterada pelo SDC." |
| `politica_senha` | constraintViolation (19) / `data 52D` | nao (o job tenta gerar de novo uma vez antes) | "A senha gerada não atendeu à política do domínio." |
| `recusado` | unwillingToPerform (53), outros codigos 4xx-like | nao | "O AD recusou a operação." |
| `config_ausente` | driver desligado, chave de entrega ausente | nao | "Integração com o AD não configurada neste ambiente." |
| `erro_interno` | qualquer outra excecao | nao | "Erro inesperado ao executar a operação." |

### 9.2 Casos de borda

| Situacao | Comportamento |
|---|---|
| Duplo clique / duas abas | Indice parcial: devolve a operacao em voo; nenhuma segunda chamada ao AD. |
| Worker parado | Operacao fica `solicitado`; a tela mostra "aguardando o processador do AD" apos 60 s. `acessos:diretorio-diagnostico --fila` (rodado no web) mostra a idade do job mais antigo. Nada expira sozinho. |
| Worker cai no meio (apos `enviado`) | `retry_after` do banco devolve o job; todas as acoes sao convergentes. |
| Modify aplicado mas sem resposta (timeout) | Repete; o estado-alvo e o mesmo; reset gera outra senha e a entrega e a ultima. |
| Conta renomeada (login mudou) | Operacao resolve por GUID; divergencia `login_divergente` na sync. |
| Conta movida para fora da OU | `conta_fora_do_escopo`; nenhuma escrita. |
| Desbloquear conta nao bloqueada | `confirmado` com `resultado.efetivada=false` ("A conta já estava desbloqueada"). |
| Bloqueio ainda nao replicado | Leitura no host preferencial (PDC); a tela mostra `ad_sincronizado_em`. |
| Senha nao retirada no TTL | Purga apaga; `senha_expirada=true`; tela pede novo reset. A senha continua valendo no AD com troca obrigatoria; o usuario tambem pode pedir outro reset. |
| Operador perde a tela antes de ver a senha | Reabre o cadastro/demanda dentro do TTL e clica "Exibir senha" (a tela pergunta a operacao pelo historico). |
| Primeira sincronizacao | Muitos `conta_sem_cadastro`: so contagem + 200 linhas. Rodar antes com `--simular`. |
| SearchBase errada / permissao de leitura retirada | Freio do 6.5 passo 6: `abortada`, nada aplicado. |
| Versao do web e do worker diferentes | Payload so tem o id; tabelas novas entram antes no web (migrations so no pipeline do Azure). O worker nunca roda migration (comando sobrescrito, sem `/start.sh`). Implantar o worker logo depois do web (secao 10.3). |
| Login de demanda sem cadastro | Operacao com `cadastro_id=null`; guardas e regra de propria conta valem (por CPF do ator -> cadastros do ator). |
| AD fora durante a sync agendada | Rodada `falhou` com `indisponivel`; nenhum espelho alterado; proxima hora tenta de novo. |

## 10. Infraestrutura e implantacao

### 10.1 P1: como o web no Azure e o worker on-prem compartilham a fila (decisao previa)

Fato que limita: a VM on-prem so sai para a internet pelo proxy HTTP `proxy.prodemge.gov.br:8080` (a porta 22 ja provou que nao atravessa). Postgres (5432) e Redis TLS (6380) do Azure nao passam por um proxy HTTP: qualquer opcao que nao seja HTTPS exige regra de firewall de saida na Prodemge **e** regra de entrada no recurso do Azure para o IP de saida da Prodemge (ou VPN/Private Endpoint).

| Opcao | Como | Rede exigida | Raio de exposicao do worker | Atomicidade operacao+job | Codigo |
|---|---|---|---|---|---|
| A. Redis comum | Fila `redis` na Azure Cache for Redis que o web ja usa | Saida 6380 para o Azure Redis + firewall do Redis | Le e escreve todo o Redis: filas, cache, payloads de outros modulos | Nao (job despachado apos o commit; Redis fora = operacao sem job) | Minimo |
| **B. Fila no Postgres (recomendada)** | Fila `database` em `acessos_fila_diretorio`, no Postgres que o web ja usa | Saida 5432 para `newsdc.postgres.database.azure.com` + regra de firewall do Flexible Server; TLS `verify-full` | Papel `sdc_diretorio_worker` com GRANT so nas tabelas `acessos_*`, `failed_jobs`, leitura de `users` e `tasks`, INSERT em `task_audit_logs` (historico de Demandas) | Sim: mesma transacao | Pequeno (conexao de fila + tabela) |
| C. Agente HTTPS (puxa) | Worker chama `GET/POST https://<app>/api/interno/diretorio/...` com token de escopo unico, executa e devolve o resultado; a senha volta cifrada no corpo | So saida 443 pelo proxy, que ja funciona | So o endpoint interno | Sim (o web grava a operacao; o agente so le pendentes) | Maior: API interna, assinatura, agente; deixa de ser `queue:work` puro (o agente roda os mesmos handlers via `dispatchSync`) |
| D. Outbox existente | Worker le `outbox_events` | Igual a B | Igual a B, mais o outbox de todo o sistema | Sim | Medio, e mistura o despacho do ranking com o do AD |

Recomendacao: **B**. Mantem `queue:work` (D3), da atomicidade de graca e o worker so precisa de Postgres e LDAPS. A regra de firewall e do mesmo tipo que A exigiria, com menos exposicao. Se a Prodemge negar a saida 5432, o plano B e **C** (sem mudar dominio, handlers nem tela: so o transporte troca). O codigo isola o transporte na conexao `diretorio` do `config/queue.php`, entao A tambem e uma troca de configuracao se o NewSDC de producao for para o on-prem (la o Redis ja e comum na rede `sdc_internal`).

### 10.2 F0: pre-requisitos para a infraestrutura (TI/Prodemge)

Nada de F3 vai a producao sem estes itens; F1/F2 podem ser homologados com o driver `fake`.

1. **Conta de servico** dedicada (ex.: `svc-sdc-diretorio`), sem caixa de correio, sem membro em grupo privilegiado, "conta sensivel, nao pode ser delegada", logon interativo e RDP negados por GPO, senha longa com rotacao combinada (o segredo do Swarm e trocado sem rebuild).
2. **Delegacao na OU** gerenciada (`DIRETORIO_SEARCH_BASE`), aplicada a "objetos Usuario descendentes", e so isto:
   - Ler: `objectGUID`, `sAMAccountName`, `userPrincipalName`, `distinguishedName`, `displayName`, `mail`, `userAccountControl`, `msDS-User-Account-Control-Computed`, `pwdLastSet`, `lockoutTime`, `adminCount`, `objectClass`, `whenChanged` (normalmente ja coberto por Authenticated Users; confirmar);
   - Direito estendido **Reset Password** (`User-Force-Change-Password`, `00299570-246d-11d0-a768-00aa006e0529`);
   - Ler/Gravar `lockoutTime` (desbloquear);
   - Ler/Gravar `pwdLastSet` (exigir troca);
   - Ler/Gravar `userAccountControl` (habilitar/desabilitar);
   - **Nao** conceder: criar/excluir filhos, gravar `member`, controle total, gravar todas as propriedades, nada fora da OU. Leitura do restante do dominio so para `buscarPorGuid` (default de Authenticated Users).
   - Contas com `adminCount=1` ficam fora pela AdminSDHolder; o SDC recusa de qualquer forma.
3. **LDAPS**: certificado de servidor valido em cada DC usado (SAN com o FQDN), e a cadeia da CA emissora em PEM entregue para montar no worker. Informar a validade e quem renova.
4. **DCs**: FQDNs dos DCs que o worker deve usar (ou confirmar que o SRV `_ldap._tcp.dc._msdcs.<dominio>` resolve pelos DNS corporativos), qual e o PDC emulator, e se o DC exige StartTLS em vez de 636.
5. **Firewall**: saida TCP 636 (ou 389 + StartTLS) da VM `10.160.131.50` para esses DCs; DNS 53 para `10.100.19.163`, `10.160.131.10`, `10.160.131.11`.
6. **P1 = B**: saida TCP 5432 da VM para `newsdc.postgres.database.azure.com` e o IP de saida da Prodemge liberado no firewall do Flexible Server; papel `sdc_diretorio_worker` criado pelo DBA (script no plano).
7. **Politica de senha** do dominio (tamanho minimo, complexidade, historico, FGPP na OU) para calibrar `DIRETORIO_SENHA_TAMANHO`.
8. **Conta de teste** na OU (ex.: `tst-sdc-ad01`) para a verificacao de ponta a ponta, e uma OU de homologacao se houver.

### 10.3 Worker on-prem

- Stack Swarm propria `sdc-diretorio` (`SDC/docker/jenkins/stack.diretorio.onpremise.yml`), separada de `sdc` e `sdc-data` (nao depende da `sdc_internal`: so sai para Azure e DCs). Uma replica, `stop-first`, `stop_grace_period: 60s`.
- Imagem: a mesma `sdc-app:<VERSION>` do pipeline, com `ext-ldap`. `command` sobrescrito: `php artisan queue:work diretorio --queue=diretorio --sleep=3 --tries=3 --timeout=60 --max-time=3600` (nao passa pelo `/start.sh`, entao nao roda migration nem sobe Octane). `healthcheck` herdado da imagem desligado (como o `reverb`) e trocado por `pgrep -f "queue:work diretorio"`.
- Env: `APP_ENV=production`, `APP_KEY`, `APP_NAME=sdc-diretorio` (aparece no `pg_stat_activity`), `DB_*` do papel `sdc_diretorio_worker`, `DB_SSLMODE=verify-full`, `DB_SSL_CA=/etc/ssl/azure-pg/ca.pem`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `BROADCAST_CONNECTION=null`, `SESSION_DRIVER=array`, `LOG_CHANNEL=stderr`, `LOG_LEVEL=warning`, `DIRETORIO_*` da secao 8 com `DIRETORIO_DRIVER=ldap` e `DIRETORIO_SENHA_FILE=/run/secrets/diretorio_senha`.
- Segredos/configs do Swarm: `diretorio_senha` (secret), `diretorio_chave_entrega` (secret, lido por `DIRETORIO_CHAVE_ENTREGA_FILE`), `diretorio_ca` (config -> `/etc/ssl/diretorio/ca.pem`), `azure_pg_ca` (config -> `/etc/ssl/azure-pg/ca.pem`). O resto via `/opt/sdc/.env.diretorio` (600, root) exportado antes do `docker stack deploy`, no padrao dos stacks existentes.
- DNS: o container herda os resolvedores do daemon (corporativos). Nao declarar `dns:` com servidor publico; nunca `8.8.8.8`. A verificacao roda `getent hosts <dc>` e a consulta SRV de dentro do container.
- Proxy: LDAP e Postgres nao usam proxy HTTP. Nao injetar `HTTP_PROXY` no worker (o worker nao faz HTTP); se um dia fizer, `NO_PROXY` com o dominio do AD.
- Atualizacao: o Jenkins on-prem ganha o estagio "Worker diretorio" que roda `docker service update --image $REGISTRY/sdc-app:$VERSION sdc-diretorio_worker` para o mesmo commit implantado no Azure, **depois** do deploy do web (que aplica as migrations). Como a imagem do Azure (ACR) e a on-prem (registry local) sao buildadas separadamente, a tag e o SHA do commit (P3).
- Dev e homolog: servico `queue_diretorio` em `compose.dev.yml` e `compose.homolog.yml`, com `DIRETORIO_DRIVER=fake` e `DIRETORIO_FAKE_ARQUIVO=/var/www/storage/app/diretorio-fake.json` compartilhado com o app (que tambem roda `fake`, para semear pela tinker).
- Diagnostico: `php artisan acessos:diretorio-diagnostico [--fila] [--json]` checa conexao com o banco, presenca das tabelas/colunas, resolucao DNS dos hosts, handshake TLS e validade da CA (dias restantes), bind, leitura da SearchBase (1 entrada) e idade do job mais antigo da fila. Nunca imprime segredo.

## 11. Frontend (Atomic Design)

Mesmo padrao das telas de Acessos ja refatoradas (Page fina -> Template -> Organisms -> Atoms), componentes canonicos, dark mode por classe, zero overflow em 375/840 px. Textos com acento.

- `Support/diretorio.js` (novo): rotulos e variantes de `AcaoDiretorio`, `EstadoOperacaoAd`, `StatusAd`, `CodigoErroDiretorio`, `TipoDivergenciaAd`; `rotuloAcaoAuditoria` de `Support/acessos.js` passa a cobrir `diretorio_*` e `senha_exibida`.
- `Composables/acessos/useOperacaoDiretorio.js` (novo): `solicitar(url, dados)` (Inertia `router.post`, le o flash `operacao_diretorio`), `acompanhar(operacaoId)` com o polling da 6.4 e cancelamento no `onUnmounted`, `retirarSenha(operacaoId)` (axios `POST`, devolve a string e nao guarda), estado `{estado, codigoErro, senhaDisponivel, processando}`. Ao chegar em estado final, `router.reload({ only: ['cadastro', 'operacoes'] })`.
- `Atoms/Acessos/StatusAdBadge.vue` (novo) e `Atoms/Acessos/EstadoOperacaoBadge.vue` (novo).
- `Organisms/Acessos/Show/AcessoDiretorioCard.vue` (novo, substitui a linha "Status no AD" do `AcessoSituacaoCard`): badges ativa/desabilitada, bloqueada, troca pendente, `ad_sincronizado_em`, DN (quebra de linha); botoes conforme `pode.diretorio` e estado ("Consultar no AD", "Desbloquear" so se bloqueada, "Habilitar"/"Desabilitar", "Exigir troca de senha", "Redefinir senha"); cada um com `ConfirmDialog` (desabilitar com campo motivo; redefinir com aviso de que a senha aparece uma vez); faixa de acompanhamento da operacao em andamento.
- `Organisms/Acessos/Show/AcessoOperacoesCard.vue` (novo): ultimas 10 operacoes (acao, ator, estado, codigo de erro rotulado, data); a do proprio usuario com entrega disponivel mostra "Exibir senha".
- `Organisms/Acessos/SenhaTemporariaModal.vue` (novo, compartilhado com Demandas): senha em fonte mono, copiar (Clipboard API), aviso, botao "Já anotei" que fecha e zera.
- `Organisms/Acessos/AcessosLista.vue` e `AcessosFiltersSection.vue`: coluna/badge do AD e filtro `status_ad` e `bloqueada`; `CadastroAcessoController::index` valida os filtros novos.
- `Pages/Acessos/Divergencias.vue` + `Templates/Acessos/AcessosDivergenciasTemplate.vue` + `Organisms/Acessos/Divergencias/{SincronizacaoResumoCard,DivergenciasLista}.vue` (novos): resumo da ultima rodada (estado, hora, totais, simulacao), lista paginada filtrada por tipo com link para o cadastro, botao "Sincronizar agora" e "Simular". Entrada no menu Acessos para quem tem `acessos.diretorio.view`.
- Demandas: `Organisms/Demandas/Show/DemandaAutomacaoButton.vue` passa a usar `useOperacaoDiretorio` e `SenhaTemporariaModal`; `DemandaController::show` envia `automacao` (secao 6.6).

## 12. Testes

`SDC/tests/Feature/Acessos/` e `SDC/tests/Unit/Acessos/` (gitignored, nunca commitados), contra `sdc_test` com `DatabaseTransactions`. Tudo com `FakeDiretorioCorporativo`, salvo o adaptador.

- Unidade do adaptador LDAP com `LdapRecord\Testing\DirectoryFake`/`LdapFake` (precisa de `ext-ldap`, roda na imagem nova): filtros escapados (login com `*`, `(`, `)`, `\`, NUL), GUID em hex, bit 2 preservando os outros bits do UAC, modify unico com `unicodePwd`+`pwdLastSet`, traducao de cada codigo LDAP da 9.1, nenhuma excecao com `previous` nem mensagem do servidor, hosts por SRV e ordem com o preferencial.
- `GeradorSenhaForte`: 1.000 senhas cumprem tamanho e classes, nao contem o login nem pedacos do nome, sem ambiguos.
- `CofreEntregaSenha`: leitura unica (segunda leitura nula), outro usuario nao le, vencida nao le, linha copiada para outra operacao nao decifra (dado associado), purga marca `senha_expirada`.
- Caso de uso e job (rodando a fila de verdade: `Artisan::call('queue:work', ['connection' => 'diretorio', '--queue' => 'diretorio', '--stop-when-empty' => true])`, backoff 0 em teste):
  - operacao e job entram na mesma transacao (rollback leva os dois);
  - idempotencia: duplo pedido = uma operacao, uma chamada no fake;
  - transicoes `solicitado -> enviado -> confirmado`; transitoria repete e confirma; definitiva falha sem repetir; `codigo_erro` correto; `failed_jobs` sem senha;
  - guardas: fora da SearchBase, `adminCount`, lista protegida, conta de servico;
  - propria conta (por `user_id`, `cpf` e login de cadastro do ator) recusada;
  - espelho aplicado; `status` local intocado.
- Reset: senha no fake = senha entregue; `pwdLastSet=0`; nada da senha em `acessos_operacoes_ad`, `acessos_auditoria`, `failed_jobs`, `acessos_fila_diretorio`, `storage/logs`, nem nas props Inertia de `acessos.show` (busca pela string exata depois do fluxo inteiro); retry depois de falha na transacao entrega a ultima.
- Sincronizacao: casa por GUID; vincula por login exato unico; nunca por nome (cadastro com nome igual e login diferente nao casa); cada `TipoDivergenciaAd`; freio de ausencia e retorno vazio abortam sem aplicar; `--simular` nao aplica; advisory lock impede rodada concorrente; `status` nunca muda.
- HTTP: cada rota com e sem o slug da tabela 7.1; policy da operacao e da senha; 410 na segunda leitura; `Cache-Control: no-store`; throttle; `{operacao}` nao-uuid = 404.
- Demandas: os testes atuais de `DemandaAutomacaoTest` reescritos para o fake (mesmos cenarios: login com caracteres de shell recusado, falha registra `automation_failed` sem dado do AD, `operation_id` incrementa, definitiva nao repete) mais reset pela demanda entregando a senha so ao operador.
- Frontend: `npx vite build`; overflow 375/840 px claro/escuro nas telas de Acessos (show, index, divergencias) e no show de Demandas.

## 13. Fora do escopo (proximos passos)

- **F4 — Criar usuario no AD** a partir do cadastro aprovado (exige direito de criar objeto na OU, regra de nome/login, grupos iniciais): nova acao `criar_conta` sobre a mesma porta/razao.
- **F5 — Importar o legado** (`cadastro_acessos`: `dn_ad`, `conta_ativa_ad`, flags de intencao) sem disparar acao no AD, com conciliacao por CPF/login (plano E4, linha 104).
- Notificacao ao operador quando a operacao conclui (exige o worker alcancar a fila de notificacoes ou um listener no lado web).
- Entrega de senha cifrada ponta a ponta com chave efemera do navegador (Web Crypto): nem o banco nem o web veem a senha.
- Grupos do AD, "remover exigencia de troca" (`pwdLastSet=-1`), auto-cadastro publico (fatia 14 da auditoria).

## 14. Riscos

1. **Rede Azure <-> on-prem (P1)**: sem a regra de saida 5432 (ou 6380), nada funciona em producao. Mitigacao: decidir P1 antes de F3; transporte isolado em configuracao; plano B (C) desenhado.
2. **Privilegio da conta de servico**: gravar `userAccountControl` permite ligar outros bits (ex.: `PASSWD_NOTREQD`, `DONT_REQ_PREAUTH`). Mitigacao: o codigo so troca o bit 2 e preserva o resto; auditoria por operacao; revisao periodica da delegacao pela TI; se a TI preferir, desabilitar fica fora da delegacao e a acao responde `sem_permissao`.
3. **Vazamento da senha**: logs, stack traces, payloads. Mitigacao: senha gerada no worker, `#[\SensitiveParameter]`, `zend.exception_ignore_args=On` conferido na imagem, sem `previous`, `dontFlash`, teste que procura a string em todo destino persistente.
4. **Sincronizacao destrutiva** (o erro do legado). Mitigacao: nunca altera `status`; freio de ausencia; `--simular` antes da primeira rodada real; relatorio.
5. **Divergencia de versao web x worker** (dois pipelines, dois registries). Mitigacao: payload minimo, worker atualizado logo apos o web, diagnostico confere colunas.
6. **Certificado do DC vence** e o worker para de conectar. Mitigacao: diagnostico mostra dias restantes; `certificado` como codigo proprio.
7. **Replicacao de bloqueio entre DCs**: leitura num DC atrasado mostra estado velho. Mitigacao: host preferencial = PDC.
8. **Worker unico** e ponto unico de falha. Aceito: as operacoes esperam em `solicitado`; a tela avisa; uma segunda replica funcionaria (fila com `SKIP LOCKED`) mas o circuito ficaria por processo.
9. **Postgres do Azure como fila**: carga desprezivel (dezenas de operacoes por dia + 1 rodada por hora); o polling do worker (`--sleep=3`) faz uma consulta leve a cada 3 s.

## 15. Referencias

- NewSDC: `SDC/app/Modules/Acessos/**`, `SDC/app/Modules/Demandas/Services/ExecutarAutomacaoDemanda.php`, `SDC/app/Modules/Demandas/Jobs/ExecutarAutomacaoDemandaJob.php`, `SDC/database/migrations/2026_09_23_000002_create_acessos_tables.php`, `SDC/database/migrations/2026_09_28_100000_ajusta_inventario_remanejamentos.php` (precedente do companion), `SDC/config/permissions.php` (bloco `ACESSOS`, papeis), `SDC/app/Services/Webhook/CircuitBreakerService.php`, `SDC/routes/console.php`, `SDC/docker/jenkins/stack.{app,data}.onpremise.yml`, `SDC/docker/swoole/Dockerfile`.
- Plano estrategico: `docs/superpowers/plans/2026-09-23-migracao-cedec-demanda-estrategia.md` linhas 35, 44, 100, 101, 104, 119.
- Auditoria: `.superpowers/sdd/catalogo-demandas/auditoria-inventario-acessos.md` linhas 83-87 (tabela de Acessos e notas de seguranca), 101 (fatia 8).
- Legado: `cedec-demanda/app/Http/Controllers/Acessos/CadastroAcessoController.php` (`shell_exec` 104-120, gateway 172/206/233, `sincronizacaoFantasma` 282-297).
- LdapRecord core v3: configuracao, `DirectoryFake`/`LdapFake`, escape, paginacao (docs consultadas via Context7).

## 16. Perguntas abertas (decisao do usuario)

| # | Pergunta | Proposta desta spec |
|---|---|---|
| P1 | Transporte da fila entre o Azure e o worker on-prem (10.1). | B: fila `database` no Postgres do Azure, com saida 5432 liberada; C (agente HTTPS) se a Prodemge negar. |
| P2 | Uma OU gerenciada ou varias? | Uma (`DIRETORIO_SEARCH_BASE`). Varias viram lista, com a guarda aceitando qualquer uma. |
| P3 | Quem implanta o web no Azure hoje e como o worker on-prem recebe a mesma versao? | Tag = SHA do commit nos dois registries; estagio no Jenkins on-prem depois do deploy do Azure. |
| P4 | Mapeamento de papeis (quem e a equipe de TI que reseta?). | manager: view+manage+reset; analyst: view+manage; operator: view. |
| P5 | A TI aceita delegar gravacao de `userAccountControl` (habilitar/desabilitar)? | Sim, com o risco 14.2 documentado; senao a acao fica fora e responde `sem_permissao`. |
| P6 | Mudar o `status` local (aprovado -> ativo, ativo -> inativo) deve disparar habilitar/desabilitar no AD? | Nao nesta entrega: acoes explicitas; a sync aponta a divergencia. |
| P7 | Existe OU/dominio de homologacao com conta de teste para validar F3 antes da producao? | Homolog com `fake`; F3 validada numa conta de teste da OU real antes de ligar para todos. |
