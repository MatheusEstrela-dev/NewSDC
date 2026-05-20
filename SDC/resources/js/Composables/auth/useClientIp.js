/**
 * Detecta o(s) IP(s) reais do client e os envia em todas as requests via
 * header X-Client-IP. Funciona com:
 *
 *  - Inertia router (POST /login, navegacao, formularios) -> registrado em
 *    router.on('before', ...). Inertia 2.x usa fetch interno, NAO o axios.
 *  - Axios global (XHR diretos, fetch programatico) -> interceptor de request.
 *
 * Camadas de coleta (best-effort, falhas silenciosas):
 *  1. WebRTC RTCPeerConnection -> revela IPs LAN do client (192.168.x, 10.x).
 *     Em browsers modernos, candidatos ".local" sao filtrados.
 *  2. api.ipify.org -> IP publico de saida do gateway NAT.
 *
 * Persistencia: o ultimo resultado fica em sessionStorage para uso imediato
 * em pageloads subsequentes (sem precisar coletar de novo).
 *
 * SEGURANCA: o header vem do navegador e PODE ser forjado. Backend trata
 * como "best-effort para auditoria" (last_login_ip), nunca como autoridade
 * de seguranca.
 */

const CLIENT_IP_HEADER = 'X-Client-IP';
const STORAGE_KEY = 'sdc:client-ips';

const isUsefulIp = (ip) => {
    if (!ip || typeof ip !== 'string') return false;
    if (ip.endsWith('.local')) return false;
    if (ip.startsWith('fe80:')) return false;
    if (ip === '127.0.0.1' || ip === '::1') return false;
    return /^\d{1,3}(\.\d{1,3}){3}$/.test(ip) || /:/.test(ip);
};

let cachedIps = [];
let collectingPromise = null;

function loadFromStorage() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.filter(isUsefulIp) : [];
    } catch {
        return [];
    }
}

function saveToStorage(ips) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(ips));
    } catch {
        // ignore quota/private mode
    }
}

async function collectViaWebRTC(timeoutMs = 1500) {
    if (typeof window === 'undefined' || !('RTCPeerConnection' in window)) {
        return [];
    }
    return new Promise((resolve) => {
        const ips = new Set();
        let pc;
        try {
            // Servidores STUN publicos sao necessarios para que o ICE descubra
            // candidatos srflx (server-reflexive) -> IP publico de saida do NAT.
            // Sem STUN, browsers modernos so retornam mDNS hashes ".local"
            // (que filtramos), resultando em set vazio.
            pc = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun.cloudflare.com:3478' },
                ],
            });
        } catch {
            resolve([]);
            return;
        }

        const finalize = () => {
            try { pc.close(); } catch {}
            resolve([...ips]);
        };
        const timer = setTimeout(finalize, timeoutMs);

        pc.onicecandidate = (event) => {
            if (!event || !event.candidate || !event.candidate.candidate) {
                clearTimeout(timer);
                finalize();
                return;
            }
            const parts = event.candidate.candidate.split(' ');
            const ip = parts[4];
            if (isUsefulIp(ip)) ips.add(ip);
        };

        pc.createDataChannel('probe');
        pc.createOffer()
            .then((offer) => pc.setLocalDescription(offer))
            .catch(() => {
                clearTimeout(timer);
                finalize();
            });
    });
}

async function collectViaIpify(timeoutMs = 1200) {
    try {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), timeoutMs);
        const res = await fetch('https://api.ipify.org?format=json', {
            signal: controller.signal,
            credentials: 'omit',
            referrerPolicy: 'no-referrer',
        });
        clearTimeout(timer);
        if (!res.ok) return [];
        const data = await res.json();
        return isUsefulIp(data.ip) ? [data.ip] : [];
    } catch {
        return [];
    }
}

/**
 * Garante uma coleta (uma unica vez por carregamento). Retorna o array de IPs.
 * Pode ser chamada repetidas vezes — apenas a primeira dispara o I/O.
 */
export function ensureClientIpsCollected() {
    if (cachedIps.length > 0) {
        return Promise.resolve(cachedIps);
    }
    if (collectingPromise) {
        return collectingPromise;
    }
    collectingPromise = (async () => {
        const [webrtc, ipify] = await Promise.all([
            collectViaWebRTC(),
            collectViaIpify(),
        ]);
        cachedIps = [...new Set([...webrtc, ...ipify])].filter(isUsefulIp);
        saveToStorage(cachedIps);
        return cachedIps;
    })();
    return collectingPromise;
}

/**
 * Setup completo:
 *   - hidrata cache do sessionStorage (uso imediato no primeiro request);
 *   - dispara coleta nova em background;
 *   - registra hook no Inertia router (cobre POST /login e todos os visits);
 *   - registra interceptor no axios global (cobre XHR/Fetch diretos).
 *
 * @param {object} options
 * @param {import('@inertiajs/vue3').Router} [options.router] - router do Inertia
 */
export function installClientIpInterceptor({ router } = {}) {
    if (typeof window === 'undefined') return;
    if (window.__clientIpInterceptorInstalled) return;
    window.__clientIpInterceptorInstalled = true;

    // 1. Cache hidratado do storage para 1o request ja sair com header.
    const stored = loadFromStorage();
    if (stored.length > 0) {
        cachedIps = stored;
    }

    // 2. Dispara coleta em background (atualiza cache se mudou de rede etc).
    ensureClientIpsCollected();

    const headerValue = () => (cachedIps.length > 0 ? cachedIps.join(', ') : null);

    // 3. Inertia router hook — pega POST /login, navegacao, forms, etc.
    if (router && typeof router.on === 'function') {
        router.on('before', (event) => {
            const value = headerValue();
            if (!value) return;
            const visit = event.detail?.visit;
            if (!visit) return;
            visit.headers = visit.headers || {};
            visit.headers[CLIENT_IP_HEADER] = value;
        });
    }

    // 4. Axios interceptor — XHR/fetch nao-Inertia.
    if (window.axios) {
        window.axios.interceptors.request.use((config) => {
            const value = headerValue();
            if (value) {
                config.headers = config.headers || {};
                config.headers[CLIENT_IP_HEADER] = value;
            }
            return config;
        });

        // Tambem seta como default header para cobrir clientes que copiam defaults.
        const initial = headerValue();
        if (initial) {
            window.axios.defaults.headers.common[CLIENT_IP_HEADER] = initial;
        }
    }
}

/**
 * Forca uma nova deteccao (limpa cache em memoria + storage).
 */
export async function refreshClientIp() {
    cachedIps = [];
    collectingPromise = null;
    try { sessionStorage.removeItem(STORAGE_KEY); } catch {}
    return ensureClientIpsCollected();
}
