# 🛠️ Referência de Comandos (Justfile / Makefile)

O projeto utiliza o `just` (via `Justfile`) e o `make` (via `Makefile`) para facilitar a orquestração do ambiente Docker, rodar comandos do Laravel, limpar caches, entre outras tarefas. Ambos possuem a **exata mesma lista de comandos** e portas.

> **💡 Dica:** Se estiver no Windows, prefira usar o `just`. Se estiver no Linux/Mac, pode usar tanto `just` quanto `make`.

---

## 📦 Build & Setup

Estes comandos são focados na construção das imagens Docker.

- **`build-all`**: Realiza o processo de build em paralelo de *toda* a stack (incluindo serviços principais, frontend, banco, monitoramento, etc.).
- **`build-dev`**: Realiza o build focado apenas no ambiente de desenvolvimento.
- **`build-prod`**: Realiza o build focado apenas no ambiente de produção (`docker-compose.prod.yml`).
- **`build-clean`**: O mesmo que o build-dev, mas passa a flag `--no-cache` para forçar baixar dependências e recriar camadas do zero. Útil se algum cache antigo do Docker estiver corrompido.
- **`bridge`**: Cria a rede isolada `sdc_network` na sua máquina, que será usada pela comunicação dos containers. É chamado automaticamente por muitos outros comandos que dependem de rede.

---

## 🚀 Ambiente Completo (One-Command)

Comandos práticos para inicializar ou desligar rapidamente a infraestrutura.

- **`start`**: É o "grande comando mágico". Ele executa sequencialmente `bridge`, `build-all` e `up-all`. Ou seja, prepara a rede, compila as imagens e sobe toda a stack do zero (sem o Ollama).
- **`up-all`**: Inicializa os contêineres de todos os perfis (incluindo dependências como `phpMyAdmin` ou `Redis Commander`). Usa a flag `-d` para ficar em background.
- **`down-all`**: Para toda a infraestrutura rodando atualmente.
- **`restart-all`**: Atalho conveniente que executa `down-all` seguido de `up-all`.

---

## 🔧 Desenvolvimento

Operações típicas focadas nos serviços do ambiente de dev.

- **`dev`**: Inicia os contêineres em ambiente de desenvolvimento base (`app`, `nginx`, `db`, `redis`, `mailhog`, `queue`, `node`).
- **`dev-full`**: Inicia a infra base e adiciona também *ferramentas extras* de depuração (`phpMyAdmin` e `Redis Commander`).
- **`dev-minimal`**: Sobe a versão mais enxuta possível do app (apenas `app`, `db`, `redis`), para poupar memória da máquina.
- **`dev-build`**: Desliga o ambiente, refaz o build das imagens recém-modificadas e sobe a infra de novo.
- **`dev-logs`**: Acompanha em tempo real o *output* (logs) apenas dos containers rodando no ambiente de desenvolvimento.
- **`dev-ps`**: Exibe uma tabela listando as instâncias em pé (`docker compose ps`).

---

## 🏭 Produção

Comandos para rodar, validar e debugar usando o arquivo `.prod.yml`.

- **`prod`**: Inicia o ecossistema pronto/orientado a uso real na produção (usando otimizações).
- **`prod-build`**: Realiza build sem cache das imagens oficiais da produção.
- **`prod-logs`**: Acompanha ativamente os logs da infraestrutura da etapa `.prod`.
- **`prod-down`**: Mata a stack inteira de produção.
- **`prod-scale N`**: Dimensiona (scale) a quantidade de *workers* do Laravel consumindo filas em paralelo. (Ex: `just prod-scale 5` para subir 5 workers atuando juntos).

---

## 📊 Monitoramento

Serviços de medição usando `Prometheus`, `Grafana` e `Exporters`. Auxiliam demais na resiliência do projeto identificando anomalias.

- **`monitor`**: Sobe imediatamente toda a rede de coletores e dashboards (Prometheus, Grafana, Alertmanager, cAdvisor e todos os exporters para banco, proxy e afins).
- **`monitor-down`**: Interrompe momentaneamente apenas a observabilidade (sem derrubar o frontend/API).
- **`monitor-logs`**: Segue e aponta rastros logáveis do Prometheus, Grafana e AlertManager.
- **`monitor-restart`**: Reinicia instantaneamente todo o workflow de metrics.

---

## 🐚 Acesso & Debug

Atalhos de `shell` para você iterar, inspecionar arquivos gerados ou analisar internamente de dentro dos containers ao invés de atuar por fora.

- **`shell`**: Entra no container do `app` (Laravel) interativo via Bash, utilizando o usuário padrão (`www-data`). Este é o mais aconselhado para tarefas manuais sem gerar problemas de propriedade de arquivos.
- **`shell-root`**: Entra interativo com usuário admin (`root`). Restrinja esse uso a alterações temporárias agressivas de config no ecossistema (ex: apt-get instalando debbugs).
- **`shell-db`**: Abre iterativamente a linha de comando cliente MySQL via proxy seguro conectado como super administrador do schema oficial `sdc`.
- **`shell-redis`**: Aciona localmente o console iterativo da instância em cache com `redis-cli`.
- **`shell-queue`**: Permite entrada pontual aos serviços operantes que rodam o Laravel worker sem precisar desligá-los ou misturar contexto de requisições web.
- **`shell-node`**: Entra na fundação NPM/Vite de frontend permitindo testes de compilações exclusivas diretas pelo *Alpine Linux*.

---

## 🗑️ Limpeza Constante (E Atenção a Dados!)

- **`clean`**: Para/remove contêineres ativos da pilha de dev. Ao subir de novo, você preservará todos os seus bancos de dados e volumes (tudo intocado).
- **`clean-volumes`**: ⚠️ Apaga não apenas os contêineres, mas **Deleta sumariamente as pastas (volumes)**. O MySQL `sdc_db_data` e o cache voltam irrecuperáveis a versão de fábrica do dia 1.
- **`clean-images`**: Processamento completo para também derrubar `.images` em cachês nativas do Docker (além dos dados de volumes mencionados acima). Aquele erro impossível finalmente somem, mas exige `build-all` longo logo na sequência.
- **`clean-system`**: Ataca arquivos soltos do Docker geral varrendo todos os *dangling clusters* do sistema (lixo na memória ou camadas obsoletas). Processo muito otimizador de espaço local do S.O.
- **`nuke`**: Chama todos de uma vez (arquivos órfãos, images e volumes). Limpeza Total garantida.

---

## 💾 Banco de Dados

Substitutos pontuais diretos executando `php artisan` a fundo da interface interativa do Laravel. Dispensa necessidade de abrir `shell`.

- **`db-migrate`**: Levanta de força as migrações no Database SQL mapeado ignorando perguntas Y/N.
- **`db-rollback`**: Revoga a última Batch de migration executada para refazer um alter/create em tabelas.
- **`db-fresh`**: Apaga completamente o schema do banco inteiro e reconstrói junto ao povoamento forçado das classes do mecanismo Laravel Database `Seeders`. (Ideal para zerar praça ao testar tabelas alteradas).
- **`db-seed`**: Aciona de forma direta, populando informações fakes pré-determinadas para dev com `db:seed`.
- **`db-backup`**: Extrai em hardcopy backup dumpado do MySQL gerando localmente o arquivo SQL no padrão de repositório `./storage/backups/backup-ANO-MES-DIA.sql`.
- **`db-restore FILE`**: Restaura banco completo via carregamento SQL. Ótima ideia para pegar clones de prod em dev. Exemplo de uso exigirá passagem de filepath nativa: `just db-restore storage/backups/meu-banco.sql`.

---

## 🎨 Frontend (Vite & NPM)

Para agir pontualmente no ecosistema SPA / Render / e CSS.

- **`npm-install`**: Manda container resolver as bibliotecas faltantes de package instalando com tag de legados em VueJS caso peçam.
- **`npm-update`**: Solicita rebuild para bater pacotes que tiverem brechas em sub-minor versions compatíveis do NPM.
- **`npm-dev`**: Levanta em stand-alone proxy HMR (Hot Module Replacement) encarregada da varredura veloz (ViteJS) aos arquivos frontend abertos localmente (`resources`).
- **`npm-build`**: Build e compressão fina e minificada da estrutura web pública nativamente (HTML/CSS/JS empacotados a `public/build`).
- **`npm-build-vite`**: Método alterno de empacotar apontando explicitamente ao commandline bundle executor base.
- **`npm-clean`**: Destroi os caches precompilados base persistentes (`.vite` foldout caching), que pode contornar problemas onde "CSS e compilação travaram".

---

## ⚙️ Artisan Framework Scripts

- **`cache-clear`**: Limpa sumariamente os depósitos temporários base para garantir re-carregamentos perfeitos (`cache`, `config`, `route`, `views`).
- **`cache-optimize`**: Operação inversa ao clear, força empacotamento completo antecipando compilação (necessário p/ produção, para evitar gargalos processando PHP on-the-fly).
- **`artisan CMD`**: Pass-through command. Você passa a argumentação direta e a macro delega inteligentemente a um `docker compose exec php artisan xyz`. (Por exemplo: `just artisan "make:model User"`).

---

## 🧪 Abordagem de Testes Unitários/Funcionalidades

- **`test`**: Encapsula execução oficial do framework de todos testes (Pest ou PHPUnit contidos no projeto).
- **`test-coverage`**: Similar à execução macro de cima, no entanto, anexa verificação logável rastreadora criando estatísticas de abrangências da vida real do Software.
- **`test-filter F`**: Garante pontualidade filtrado caso não precise re-rodar 3.000 suítes pra consertar uma. Ex: `just test-filter UserTest` para debugar pontualmente.

---

## 📋 Status & Informações (Diagnóstico da Instância)

Para sanidade mental do seu workspace. Rastreia o que deu pane e informa as exibições corriqueiras acessíveis.

- **`status`** (ou `ps`): Monta graficamente a teia e saúde de funcionamento dos processos mapeando suas portas.
- **`urls`**: Menuzinho sensacional apresentando listagem clara com links "clicáveis", informando de fato local onde a App, Frontend, Grafana e MySQL respondem via `http`.
- **`logs`**: Log interativo visualizando todas mensagens empilhadas do Laravel e Infra num mesmo pool interativo tail log stream (`-f`).
- **`logs-(app/nginx/db/queue)`**: Separa stream único de leitura interativa apenas por aquele serviço específico que deseja diagnosticar pontual de lentidões.
- **`health`**: Metódos curtos e simplificados para realizar interações `curl` visando descobrir de imediato se os núcleos bases do sistema atendem pings. Retorna Status (OK ou Failed).
- **`info`**: Apresenta infos macro complexas de ambiente Docker Buildkit, UIDs permissivos em ambiente logado da máquina principal, Server-wide occupation space, etc.
- **`version`**: Versões mapeadas das tecnologias envolvidas no pipeline de execução do workflow principal.

---

## 📱 Mobile

- **`mobile-dev`**: Inicializa o ambiente para testes do app com o **NativePHP Jump Server**. Identifica ips dinâmicos, ajusta dev local proxy host do Laravel para a rede conectada, escuta e expõe automaticamente **o QR code no terminal** e em caso de sucesso passa *DeepLink adb* ao aparelho Android plugado via cabo de forma transparente em debug.

---

## 🔄 Workflows Completos (Automatizados do Começo ao Fim)

Tarefas pré-estruturadas que executam múltiplas fases encadeadas essenciais em um loop seguro:

- **`setup`**: Se você nunca clonou antes é isso aqui que importa (Buildar do zero, ligar background iterativo, montar migração, ligar otimizador das pontes de requisição da web finalizando as amarras). Seu "um click deploy".
- **`reset`**: Destrói *só e unicamente volumes de schema nativo* (o banco volta limpo). Repassa os seeders novamente, retornando tudo zeradinho pra reteste se algum dev comprometeu lixo nas instâncias sem matar build cache.
- **`deploy-prod`**: Traz master repositório online localmente, aplica as recriações em contêineres e força cache/migração reprocessarem novas variáveis. Produção pronta e sem tempo de inatividade.

---

### 💡 Como Obter Interação Assistida por CLI (Menu de Opções)

Basta chamar iterativamente no terminal de forma amigável:

```bash
just menu
```

(Um prompt interativo irá listá-lo abordagens principais. Digite o número desejado da sua rotina iterativa corriqueira, e tecle `ENTER` e o **Just** executará a tarefa e as rotas por conta própria).
