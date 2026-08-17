#!/usr/bin/env bash
#
# Faz o container de dev reconhecer classes criadas ou removidas no host.
#
# Por que e necessario: `app/` e bind-mount, mas `vendor/` NAO -- ele vive
# dentro da imagem. O autoloader otimizado do Composer que esta no container foi
# gerado quando a imagem subiu, entao:
#
#   - classe NOVA no host  -> o container nao a encontra, e da
#     "Target class [...] does not exist"
#   - classe REMOVIDA      -> o classmap ainda a mapeia, e da
#     "Failed to open stream: No such file or directory"
#
# O `composer dump-autoload` do host nao resolve, porque escreve no vendor do
# host, que o container nao ve.
#
# E o restart nao e opcional: este container roda Swoole/Octane, e os workers
# seguram o codigo em memoria entre requisicoes.
#
# Uso:
#   scripts/refresh-container.sh
#
# Rode sempre que criar ou remover classe e o navegador comecar a reclamar.

set -euo pipefail

NOME="${SDC_CONTAINER:-newsdc_dev_app}"
CID="$(docker ps -qf "name=${NOME}")"

if [[ -z "${CID}" ]]; then
    echo "ERRO: container '${NOME}' nao esta rodando." >&2
    echo "Suba com: just dev   (ou ajuste SDC_CONTAINER)" >&2
    exit 1
fi

echo "Container: ${NOME} (${CID:0:12})"

echo "→ regenerando o autoloader..."
docker exec "${CID}" sh -c 'cd /var/www && composer dump-autoload --no-interaction' 2>&1 | tail -1

echo "→ limpando caches do framework..."
docker exec "${CID}" sh -c 'cd /var/www && php artisan route:clear && php artisan config:clear && php artisan view:clear' >/dev/null 2>&1 || true

echo "→ reiniciando (Octane segura o codigo em memoria)..."
docker restart "${CID}" >/dev/null

# 40 x 3s = 120s. O boot deste container nao e curto: ele cacheia blade e
# rotas, resolve as classes de assinatura, sobe o queue worker e so no fim o
# Octane -- passa dos 60s com folga numa maquina carregada.
echo -n "→ aguardando o app responder"
for _ in $(seq 1 40); do
    # Sem `|| echo 000`: quando o curl falha ele JA imprime 000, e o echo
    # concatenava outro, virando "000000" -- que passa por qualquer teste de
    # "!= 000" e faz o script declarar pronto um app que ainda esta subindo.
    codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://localhost:8000/login 2>/dev/null)" || codigo=000

    # So um 2xx/3xx conta como no ar. O Octane responde 000 ate os workers
    # carregarem, e ai o classmap novo ainda nao esta em memoria.
    if [[ "${codigo}" =~ ^[23][0-9][0-9]$ ]]; then
        echo " OK (HTTP ${codigo})"
        exit 0
    fi
    echo -n "."
    sleep 3
done

echo
echo "AVISO: o app nao respondeu em 120s. Ver: docker logs --tail 40 ${NOME}" >&2
exit 1
