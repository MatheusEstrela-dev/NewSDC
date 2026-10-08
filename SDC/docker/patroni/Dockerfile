FROM quay.io/coreos/etcd:v3.5.21 AS etcd
FROM postgis/postgis:18-3.6-alpine

ARG PATRONI_VERSION=4.1.5

RUN apk add --no-cache python3 py3-pip py3-psycopg2 curl haproxy \
    && python3 -m venv --system-site-packages /opt/patroni \
    && /opt/patroni/bin/pip install --no-cache-dir "patroni[etcd3]==${PATRONI_VERSION}"

ENV PATH="/opt/patroni/bin:${PATH}"

COPY patroni.yml /etc/patroni/patroni.yml
COPY haproxy.embedded.cfg /etc/haproxy/haproxy.cfg
COPY --from=etcd /usr/local/bin/etcd /usr/local/bin/etcdctl /usr/local/bin/
COPY entrypoint.sh bootstrap.py supervise.py /usr/local/bin/
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh /usr/local/bin/bootstrap.py \
    && chmod 755 /usr/local/bin/entrypoint.sh /usr/local/bin/bootstrap.py

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["patroni", "/run/patroni/patroni.yml"]
