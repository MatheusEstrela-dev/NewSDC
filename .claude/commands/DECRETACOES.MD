omo não consigo acessar diretamente os arquivos locais da sua máquina (/mnt/Jogos/Projetos/sdc-producao), vou estruturar a resposta com base na Expertise de Domínio em Defesa Civil e no padrão arquitetural que definimos (Laravel + PostGIS).

Vou dividir a resposta em 3 partes estratégicas:

Conceituação do Domínio "Decretações" (Engenharia Reversa Lógica).

Planejamento de Migração (Legacy → NewSDC).

Script V2.0 (Otimizado): Uma versão "Watcher" que monitora processos novos dinamicamente.

Parte 1: Conceituação do Modelo de Negócio (Decretações)
No contexto de Defesa Civil, o módulo de "Decretação" (FDE - Formulário de Informações do Desastre) é o coração burocrático. Ele não é apenas um cadastro, é um Processo Legal.

O Modelo de Domínio (Domain Model)
Entidade Raiz: Decreto (ou ProcessoDecretacao).

Tipos de Situação:

SE (Situação de Emergência): Capacidade de resposta do município parcialmente comprometida.

ECP (Estado de Calamidade Pública): Capacidade totalmente comprometida (precisa de ajuda federal/estadual).

Fluxo de Status (State Machine):

Rascunho: Município preenchendo.

Em Análise Regional: Coordenadoria regional verifica.

Em Análise Estadual: Técnicos avaliam danos.

Homologado (Pelo Estado) ou Reconhecido (Pela União).

Devolvido: Precisa de ajustes.

Sub-Entidades Críticas:

Danos Humanos: Mortos, feridos, desabrigados, desalojados (Números precisam bater com o S2iD).

Danos Materiais: Casas destruídas, escolas danificadas.

Prejuízos Econômicos: Agricultura, indústria.

Documentação: Pareceres técnicos, laudos, fotos georreferenciadas.

Parte 2: O Planejamento de Migração e Frontend (.md)
Aqui está o arquivo Markdown solicitado. Você pode salvar como specs-decretacao.md.

Markdown

# Especificação Técnica: Módulo de Decretação (NewSDC)

## 1. Visão Geral
Migração do módulo legado de decretação para a nova arquitetura orientada a eventos e serviços. O foco é UX para preenchimento de formulários complexos e integração geoespacial.

## 2. Diferenciais do Novo Frontend (UX/UI)
O sistema antigo provavelmente era um "formularizão" gigante. O novo deve ser guiado.

### A. Navegação por Etapas (Wizard Pattern)
Não mostrar 50 campos de uma vez. Dividir em passos lógicos:
1.  **Dados do Evento:** (Tipo, Data, Hora, COBRADE).
2.  **Localização:** Mapa interativo para desenhar a área afetada.
3.  **Danos Humanos/Materiais:** Grids editáveis.
4.  **Anexos:** Drag & drop.
5.  **Revisão:** Resumo antes de enviar.

### B. Feedback Visual de Status
* **Timeline Superior:** Uma barra de progresso mostrando onde o decreto está (ex: Município -> Regional -> Estado -> União).
* **Semaforização:** Campos obrigatórios pendentes devem brilhar ou ter ícones de alerta.

### C. Integração Geoespacial (Leaflet/Mapbox)
* **Desenho de Polígono:** O usuário desenha a área do desastre no mapa.
* **Validação Automática:** O frontend cruza o polígono desenhado com a área do município para evitar erros de "desastre fora do município".

## 3. Arquitetura Backend (Laravel)
* **Service:** `DecretacaoService`.
* **Eventos:**
    * `DecretoCriado`: Notifica regional.
    * `StatusAlterado`: Envia e-mail para o prefeito.
* **Validação:** FormRequests rigorosos (ex: `StoreDecretoRequest`) validando regras de negócio (ex: não pode ter mais desabrigados que a população total).

## 4. Stack Sugerida
* **Frontend:** Vue.js 3 + TailwindCSS (para formulários rápidos).
* **Componentes:** Headless UI (Acessibilidade).
* **Estado:** Pinia (Gerenciar o formulário complexo antes de salvar).
Parte 3: Melhorando o Script (Versão "Watcher")
O script anterior roda uma vez e para. Como você usa o Cursor intensamente, ele vive abrindo e fechando processos filhos (LSPs de PHP, Vue, Python, rg para busca). Esses novos processos nascem com prioridade normal.

A Melhoria: Vamos criar um script que roda em loop (Daemon), verificando a cada 5 segundos se novos processos do Cursor nasceram e aplicando o "God Mode" neles automaticamente. Também vamos incluir processos auxiliares que IDEs usam (como node, rg, intelephense).

Crie o arquivo cursor_god_mode.sh:

Bash

#!/bin/bash

# Configurações
INTERVALO=10 # Verifica a cada 10 segundos
PRIORIDADE_CPU=-20
PRIORIDADE_IO=1 # Real Time

echo "🛡️  Iniciando Cursor God Mode Watcher..."
echo "    Monitorando: cursor, claude, node, rg (ripgrep), language-server"
echo "    Pressione Ctrl+C para parar."

while true; do
    # Busca PIDs do Cursor e processos relacionados
    # Adicionamos 'rg' (busca rápida), 'node' (plugins) e 'php' (se estiver rodando artisan serve/test)
    PIDS=$(pgrep -f "cursor|claude|Current|rg|intelephense|volar")

    if [ ! -z "$PIDS" ]; then
        for PID in $PIDS; do
            # Verifica se o processo ainda existe
            if [ -e /proc/$PID ]; then
                
                # Pega a prioridade atual (Nice)
                CURRENT_NICE=$(ps -o nice= -p $PID)
                
                # Só aplica se ainda não estiver em -20 (Evita spam no log/sistema)
                if [ "$CURRENT_NICE" -gt "-20" ]; then
                    # 1. CPU (Prioridade Máxima)
                    renice -n $PRIORIDADE_CPU -p $PID > /dev/null 2>&1
                    
                    # 2. I/O (Disco Real-Time)
                    # -c 1 (Real Time) -n 4 (Prioridade média dentro do RT)
                    ionice -c $PRIORIDADE_IO -n 4 -p $PID > /dev/null 2>&1
                    
                    # 3. OOM Score (Imortalidade)
                    echo -1000 > /proc/$PID/oom_score_adj 2>/dev/null
                    
                    # Log visual discreto
                    NOME=$(ps -p $PID -o comm=)
                    echo "[$(date +%T)] 🚀 Boost aplicado em: $NOME ($PID)"
                fi
            fi
        done
    fi
    
    # Aguarda antes da próxima verificação
    sleep $INTERVALO
done
Como usar para "Aproveitamento Total":
Salve o script.

Dê permissão: chmod +x cursor_god_mode.sh.

Deixe rodando em um terminal separado (ou no fundo):

Bash

sudo ./cursor_god_mode.sh
Por que isso é melhor?

Dinamismo: Se você abrir uma nova janela do terminal no Cursor ou o indexador de arquivos (ripgrep) disparar, o script pega ele em até 10 segundos e dá prioridade máxima.

Eficiência: Ele verifica se o processo já está priorizado (if [ "$CURRENT_NICE" -gt "-20" ]) para não gastar ciclos de CPU re-aplicando o comando sem necessidade.