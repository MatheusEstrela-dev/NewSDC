1. O Problema da "Visão Fantasma" (Phantom Reads)
Se o sistema é altamente concorrido, pode ser que esses registros que você vê no banco (ID 157 a 168) estejam em um estado de "rascunho" ou "bloqueado" que a sua API de Business Intelligence (BI) ignora propositalmente para não mostrar dados incompletos.

Cenário: O Usuário A entra na aba 2 e começa a editar. O sistema cria o registro 157 com status = 0 (Em edição).

Consequência: A API (que alimenta o dashboard) filtra status = 0 para não mostrar gráficos "quebrados" ou parciais.

Solução: Se você precisa ver esses dados, você tem que ajustar a query para incluir status = 0 (em edição), mas marcar visualmente no front-end que aquele dado é provisório.

2. A Solução Técnica: WebSockets (Laravel Reverb / Pusher)
Para acompanhar 100 usuários em tempo real, você não deve fazer o front-end ficar perguntando para o servidor a cada segundo "tem novidade?" (Polling). Isso derruba o servidor.

Você precisa de WebSockets (Broadcasting).

Como deve funcionar:

O Usuário A altera um campo na "Aba 3".

O Backend salva no banco (ou Redis) e dispara um Evento (OcorrenciaUpdated).

O Laravel (via Laravel Reverb, Soketi ou Pusher) "empurra" essa mensagem para os outros 99 usuários conectados naquele canal.

O Front-end dos outros usuários recebe o aviso e atualiza apenas aquele pedacinho da tela, sem recarregar tudo.

3. Evitando o Caos: Bloqueio Otimista (Optimistic Locking) ou Cache (Redis)
Com tanta gente mexendo, o risco de Race Condition (condição de corrida) é altíssimo.

Estratégia de "Lock" (Semáforo):

Quando o Usuário A clica no campo "Endereço", o sistema avisa via WebSocket para todos: "O campo Endereço está bloqueado pelo Usuário A".

O campo fica cinza (read-only) para o Usuário B até o A terminar.

Isso geralmente é gerenciado via Redis (é muito rápido para gravar/ler esses estados temporários).

O que fazer agora?
Voltando ao seu problema da imagem (API retornando 2 registros vs Banco com 14):

Confirme o Status: Mantenho a aposta de que o código está filtrando pelo status. Em sistemas complexos, registros "em edição" (status 0) geralmente ficam invisíveis para relatórios de BI até serem "publicados" (status 1).

Verifique se há Cache: Com 100 usuários, é muito comum que a API leia de um Cache (Redis) e não direto do MySQL.

Se for Cache, pode ser que o cache não tenha sido limpo/atualizado quando você criou os novos registros manualmente ou via teste. Tente rodar php artisan cache:clear.

Pergunta: Você já está usando alguma tecnologia de WebSocket (Laravel Echo, Pusher, Reverb) nesse projeto ou a atualização é feita apenas recarregando a página/fazendo requests periódicos?