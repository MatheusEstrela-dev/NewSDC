// Load-test comparativo FrankenPHP vs Swoole (Fase 5).
// Roda via k6 na rede dev:
//   docker run --rm --network newsdc-dev_default -e URL=http://app:8000/api/health -i grafana/k6 run - < k6-health.js
//   docker run --rm --network newsdc-dev_default -e URL=http://swoole:8000/api/health -i grafana/k6 run - < k6-health.js
// Alvos: /health (hot path, overhead puro) e /api/health (DB+Redis, I/O-bound).
import http from 'k6/http';
import { check } from 'k6';

const URL = __ENV.URL || 'http://app:8000/health';

export const options = {
  scenarios: {
    ramp: {
      executor: 'ramping-vus',
      startVUs: 5,
      stages: [
        { duration: '10s', target: 40 },
        { duration: '20s', target: 40 },
        { duration: '5s', target: 0 },
      ],
      gracefulStop: '5s',
    },
  },
};

export default function () {
  const res = http.get(URL);
  // /api/health pode responder 503 sob degradacao (circuit breaker); conta como
  // resposta valida do servidor (o que importa aqui e throughput/latencia).
  check(res, { 'status 200/503': (r) => r.status === 200 || r.status === 503 });
}
