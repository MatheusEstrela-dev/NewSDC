#!/bin/sh
# ============================================================================
# SDC - Entrypoint do container de queue (supervisord)
# Mesma guarda do entrypoint do app (docker/swoole/scripts/entrypoint.sh):
# os jobs gravam anexos/exports no bind mount, entao o worker NAO pode subir
# com o disco de anexos ausente.
# ============================================================================

set -e

# Guarda contra mount ausente: o fstab usa nofail, entao se o disco de anexos
# nao montar no boot o host sobe com /mnt/newsdc_storage VAZIO e o Docker
# bind-monta o diretorio vazio — os jobs gravariam no disco errado sem erro.
# O marcador .sdc_storage_mounted existe so dentro do filesystem do disco.
if [ -n "${ANEXOS_ROOT:-}" ] && [ ! -e "${ANEXOS_ROOT}/.sdc_storage_mounted" ]; then
    echo "ERRO: ANEXOS_ROOT=${ANEXOS_ROOT} sem o marcador .sdc_storage_mounted."
    echo "      O disco de anexos provavelmente nao esta montado no host (fstab nofail)."
    echo "      Verifique: mount | grep newsdc_storage"
    exit 1
fi

# Mesmo umask do app: arquivos/diretorios novos graveis pelo grupo (a raiz de
# cada modulo no bind mount tem setgid www-data no host).
umask 002

# A imagem de queue e uma so; SUPERVISOR_PROFILE escolhe o conjunto de workers
# que o supervisor sobe (o servico queue-videos usa o perfil videos).
# videos = so o worker de transcodificacao; default = todos menos ele.
if [ "${SUPERVISOR_PROFILE:-default}" = "videos" ]; then
    find /etc/supervisor/conf.d -type f ! -name 'videos-worker.conf' -delete
else
    rm -f /etc/supervisor/conf.d/videos-worker.conf
fi

exec "$@"
