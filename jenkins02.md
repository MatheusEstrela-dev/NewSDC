Prós na Prática (O que facilita a vida)
A. "Imutabilidade" e Recuperação de Desastres
Na prática tradicional, se o servidor Linux corrompe após um yum update, você gasta horas reconfigurando bibliotecas.

No Docker: Se a VM morrer, você sobe outra, instala o Docker, copia a pasta jenkins_home (backup), roda o docker-compose up e em 5 minutos tudo está no ar exatamente como antes.

B. Teste de Plugins sem Medo
O ecossistema do Jenkins é famoso por plugins que quebram o sistema.

Prática: Antes de atualizar um plugin crítico, você pode duplicar a pasta de dados, subir um segundo contêiner em outra porta (ex: 8081), testar a atualização e, se funcionar, aplicar no oficial. Isso é inviável numa instalação bare metal.

C. Limpeza de Workspace Automática
Em instalações nativas, a pasta /var/lib/jenkins/workspace tende a crescer infinitamente até lotar o disco.

No Docker: Se você usar agentes efêmeros (contêineres que sobem, compilam e morrem), o lixo do build desaparece automaticamente. O disco da VM agradece.

2. Contras e Desafios (Onde o bicho pega)
   Aqui estão os problemas técnicos reais que você vai enfrentar na configuração:

A. O Pesadelo do UID/GID (Permission Denied)
Este é o erro número 1.

O problema: O processo do Jenkins dentro do contêiner roda, por padrão, com o usuário jenkins (UID 1000).

O cenário: Você mapeia um volume da sua VM (ex: /opt/jenkins_home) para dentro do contêiner.

O erro: Se a pasta /opt/jenkins_home na VM tiver sido criada pelo root, o Jenkins no contêiner não consegue escrever nela. O contêiner entra em loop de reinicialização (CrashLoopBackOff).

Solução Prática: Você precisa garantir que a pasta no host tenha o dono correto: chown -R 1000:1000 /opt/jenkins_home.

B. Java Heap vs. Limite do Docker (OOM Killer)
O Java tenta alocar memória com base no total disponível na máquina. O Docker tenta limitar isso.

O problema: Se você define um limite no Docker (mem_limit: 2g), mas não configura o Java (-Xmx), a JVM pode tentar pegar mais memória do que o contêiner permite.

O resultado: O Kernel do Linux "mata" o processo do Jenkins silenciosamente (OOM Killer), e o serviço cai sem gerar log de erro no Jenkins, apenas no syslog da VM.

Solução Prática: Sempre passe os argumentos da JVM no docker-compose: JAVA_OPTS=-Xmx2048m.

C. SSH e Git (Chaves e Hosts)
Muitas vezes o Jenkins precisa clonar repositórios privados via SSH.

O desafio: As chaves SSH geradas na VM não existem dentro do contêiner automaticamente. Você deve gerar as chaves dentro do volume persistente ou injetá-las como secrets. Além disso, o arquivo known_hosts dentro do contêiner precisa ser populado manualmente na primeira vez que conectar ao GitHub/GitLab, ou o build falha.

D. Conflito de Portas e Networking
Se o seu build precisa subir um banco de dados de teste (ex: Postgres) para rodar testes de integração:

O problema: Tentar acessar localhost:5432 dentro do script do Jenkins vai falhar, porque localhost é o próprio contêiner do Jenkins, não a VM.

Solução Prática: Usar Docker Networks ou o alias host.docker.internal (dependendo da versão/OS) para que os contêineres se enxerguem.

3. Exemplo de Configuração Robusta
   Para mitigar esses problemas, aqui está um exemplo de docker-compose.yml que resolve a maioria das dores de cabeça citadas acima (incluindo o acesso ao Docker da VM para construir imagens):

YAML

services:
jenkins:
image: jenkins/jenkins:lts
container_name: jenkins_server
restart: unless-stopped
user: root # Temporariamente root para conseguir usar o docker.sock (veja nota abaixo)
ports: - "8080:8080" - "50000:50000"
environment: # Define fuso horário correto - TZ=America/Sao_Paulo # Limita a memória do Java explicitamente para não estourar o container - JAVA_OPTS=-Xmx2048m -Djava.awt.headless=true
volumes: # Persistência de dados (Jobs, Configs) - ./jenkins_home:/var/jenkins_home # Permite que o Jenkins use o Docker da VM (DooD - Docker outside of Docker) - /var/run/docker.sock:/var/run/docker.sock # Opcional: Mapear binário do docker se a imagem não tiver - /usr/bin/docker:/usr/bin/docker
Nota de Segurança Importante sobre o "user: root"
No exemplo acima, usei user: root para simplificar o acesso ao /var/run/docker.sock.

A maneira correta (mais segura): Não rodar como root. Em vez disso, criar um grupo docker dentro do contêiner com o mesmo GID do grupo docker da VM e adicionar o usuário jenkins a esse grupo. Isso é chato de configurar, mas é o ideal para produção segura.

Veredito Prático
Se você está gerenciando servidores Linux (Rocky/CentOS) e quer evitar "sujar" o SO com dependências Java e bibliotecas soltas, o Docker é superior.

A melhor estratégia hoje é:

Use o Docker apenas para o Controller (Master) do Jenkins (a interface web).

Não rode builds pesados no Controller.

Configure o Jenkins para disparar Agentes Docker para cada build.

Precisa de Python? O Jenkins sobe um container Python, roda o script e apaga.

Precisa de PHP/Laravel? O Jenkins sobe um container com Composer, roda e apaga
