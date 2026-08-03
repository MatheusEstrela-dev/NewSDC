# Exposição da aplicação à internet — portas, reverse proxy e redes docker

> Levantamento feito em 2026-08-03 na VM on-premise (`10.160.131.50`). Ver também
> a memória de projeto "Network/VLAN segmentation" para o histórico do pedido de
> firewall à Prodemge.

## TL;DR — já tem proxy reverso?

**Sim.** O Caddy já está rodando como reverse proxy em produção, no Swarm:

- Serviço `sdc_caddy` (stack `sdc`), imagem `caddy:2-alpine`
- Publicado direto no host: `0.0.0.0:80` e `0.0.0.0:443`
- Faz proxy para `app:8000` (as 2 réplicas do `sdc_app`)
- Config em `/opt/sdc/caddy/Caddyfile`, TLS interno self-signed por IP
  (`10.160.131.50`) porque ainda não tem domínio/firewall público liberado

Não precisa criar outro. O único cuidado é que o **Reverb** (websockets, porta
8082) hoje está publicado direto no host, fora do Caddy — ver seção 3.

## 1. Reverse proxy — já existe, não precisa de ngrok

O **Caddy** já roda em produção no Swarm (`sdc_caddy`), publicado direto no host em
`0.0.0.0:80` e `0.0.0.0:443`, e faz proxy para `app:8000` (2 réplicas).

Config em uso: `/opt/sdc/caddy/Caddyfile` (bind mount do serviço `sdc_caddy`).

```caddyfile
{
	default_sni 10.160.131.50   # acesso por IP não manda SNI
}

10.160.131.50 {
	tls internal                # self-signed — sem domínio/firewall liberado ainda
	encode zstd gzip
	reverse_proxy app:8000 {
		health_uri /health
		health_interval 15s
		header_up X-Forwarded-Proto https
	}
	log {
		output file /var/log/caddy/access.log
		format json
	}
}
```

Quando o domínio público e a liberação de firewall saírem, troca-se o bloco por
`{$APP_DOMAIN} { ... }` (sem `tls internal`) para TLS automático via Let's Encrypt —
o comentário no próprio arquivo já indica essa troca.

O repositório também tem um `docker/caddy/Caddyfile` (template/dev) e um
`docker/compose.ngrok.yml` (túnel ngrok só para expor a stack de **dev** local) —
nenhum dos dois é o que está de fato servindo produção hoje.

## 2. Portas por serviço — o que deveria ou não ir pra internet

| Porta | Serviço | Exposição atual no host | Deveria ir pra internet? |
|---|---|---|---|
| 80 / 443 | Caddy → `app:8000` | `0.0.0.0` | ✅ Sim — é a porta de entrada |
| 8082 | Reverb (websockets) | `0.0.0.0:8082->8080`, direto, **sem passar pelo Caddy** | ⚠️ Deveria ir atrás do Caddy (rota dedicada), não publicado direto |
| 8080 / 50000 | Jenkins | `0.0.0.0` | ❌ Não — só LAN/VPN |
| 22 | SSH | `0.0.0.0` | ❌ Não — só LAN/VPN (hoje usado pro túnel do DB) |
| 5432 (db) / 6379 (redis) | Postgres/Redis (`sdc-data`) | sem porta publicada (só overlay) | ❌ Correto assim, não expor |
| 2375 | docker_proxy (socket) | sem porta publicada | ❌ Correto assim, nunca expor |
| 5000 | Registry | `127.0.0.1` apenas | ❌ Correto, loopback |
| 8000/8081/6380/5433/1025/8025 | Stack de **dev** (`newsdc_dev_*`) | `0.0.0.0` | ❌ Nunca — é ambiente local |

## 3. O que falta pra "sair pra internet" de fato

Não é o reverse proxy — ele já está pronto. O que falta é o **inbound**:

1. **NAT/firewall de entrada da Prodemge** liberando 80/443 (e a rota do Reverb, se
   ficar separada) do IP público até `10.160.131.50`. Pedido rascunhado em 2026-07-13,
   ainda sem confirmação de aprovação — mesmo bloqueio que trava Jenkins e SSH
   (ver memória `project_newsdc_network_ports`).
2. Confirmado nesta sessão: o egress da VM só existe via proxy HTTP da Prodemge
   (`proxy.prodemge.gov.br:8080`, configurado em `HTTP_PROXY`/`HTTPS_PROXY` do host
   e do daemon docker) — conexão TCP direta pra internet é bloqueada. Isso é só
   saída, não afeta entrada, mas confirma que a rede é 100% controlada por firewall
   central.
3. Quando o firewall liberar, trocar o `Caddyfile` de produção para usar
   `{$APP_DOMAIN}` (Let's Encrypt automático) em vez de `tls internal`.

### Recomendação para o Reverb

Hoje publicado direto no host (`0.0.0.0:8082->8080`), bypassando o Caddy. Ideal é
rotear via Caddy (`reverse_proxy /app/* reverb:8080` com upgrade de websocket) e
remover a porta publicada do serviço no stack file — assim só 80/443 ficam
expostos no host.

## 4. Redes docker (bridge/overlay) — isolamento entre apps em paralelo

O `docker0` padrão (`172.17.0.0/16`) **não é usado** por nenhum serviço — nem
compose nem swarm. Cada stack cria sua própria rede isolada, e é isso (não
configuração do bridge padrão) que garante que as aplicações paralelas não se
enxerguem:

| Rede | Tipo | Subnet | Quem está nela |
|---|---|---|---|
| `sdc_internal` | overlay (swarm) | `10.0.1.0/24` | app, queue, reverb, db, redis (camada privada) |
| `sdc_sdc_edge` | overlay (swarm) | `10.0.2.0/24` | caddy, app, reverb (camada de borda) |
| `newsdc-dev_default` | bridge (compose) | `172.18.0.0/16` | stack de dev inteira |
| `jenkins_internal` | bridge | `172.25.0.0/24` | Jenkins isolado |
| `jenkins_egress` | bridge | `172.20.0.0/16` | Jenkins isolado |

`sdc_app` (as 2 réplicas) está propositalmente em **duas** redes ao mesmo tempo —
`sdc_sdc_edge` (pra ser alcançado pelo Caddy) e `sdc_internal` (pra falar com
db/redis/queue) — padrão correto de multi-tier, já implementado assim.

### Pontos verificados e OK

- **Sobreposição de subnet**: nenhuma entre as redes ativas (`172.17-172.25.x`
  nos bridges, `10.0.0-2.0/24` nos overlays).
- **Porta publicada no host**: dev usa `8000/8081/6380/5433/1025/8025`, prod usa
  `80/443/8082/8080/50000/22` — sem colisão entre os stacks paralelos.

### Gap não urgente

Não há `default-address-pools` customizado em `/etc/docker/daemon.json` (só a
config de proxy da Prodemge). Não é problema hoje — ainda há espaço no pool
padrão do Docker —, mas se surgirem mais stacks/projetos no mesmo host, o Docker
vai continuar incrementando `172.x` automaticamente. Se a VLAN da Prodemge algum
dia rotear algo dentro de `172.16.0.0/12`, uma rede docker auto-atribuída nessa
faixa pode sombrear uma rota real da rede corporativa e quebrar egress
silenciosamente naquele container.

Mitigação (sem risco, só afeta redes criadas depois da mudança): fixar um pool
explícito fora de `172.16-172.31.0.0` e fora de `10.0.0.0/8` (já usado pelos
overlays do swarm) — por exemplo `192.168.192.0/18` dividido em blocos `/24`.

## 5. Referências

- `/opt/sdc/caddy/Caddyfile` — Caddyfile de produção (bind mount do `sdc_caddy`)
- `docker/caddy/Caddyfile` — template/dev, não é o que está servindo produção
- `docker/compose.ngrok.yml` — túnel ngrok só para expor a stack de dev local
- `docker/scripts/host/` — provisionamento do host (sync de horário via HTTP,
  fallback ao NTP que também está bloqueado pelo firewall — UDP 123 outbound
  está na mesma fila de liberação)
- Memória de projeto: `project_newsdc_network_ports` (pedidos de firewall à
  Prodemge, ainda não confirmados aprovados em 2026-08-03)
