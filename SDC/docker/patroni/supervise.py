"""Mantem etcd, Patroni e HAProxy no mesmo ciclo de vida do container."""

import signal
import subprocess
import sys
import threading


def stop(process, timeout):
    if process is None or process.poll() is not None:
        return
    process.terminate()
    try:
        process.wait(timeout=timeout)
    except subprocess.TimeoutExpired:
        process.kill()
        process.wait()


def main():
    stopping = threading.Event()
    for sig in (signal.SIGTERM, signal.SIGINT):
        signal.signal(sig, lambda *_: stopping.set())

    etcd = None
    patroni = None
    proxy = None
    result = 0
    try:
        etcd = subprocess.Popen(['/usr/local/bin/etcd'])
        patroni = subprocess.Popen(sys.argv[1:])
        proxy = subprocess.Popen(['haproxy', '-db', '-f', '/etc/haproxy/haproxy.cfg'])
        while not stopping.wait(0.25):
            if any(process.poll() is not None for process in (etcd, patroni, proxy)):
                result = 1
                break
    finally:
        stop(proxy, 5)
        # O DCS permanece disponivel enquanto o Patroni desliga o PostgreSQL.
        stop(patroni, 60)
        stop(etcd, 10)

    return result


if __name__ == '__main__':
    sys.exit(main())
