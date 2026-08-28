/*
 * Handlers de Web Push do SDC.
 *
 * Este arquivo NAO e gerado pelo build: ele e importado pelo service worker do
 * vite-plugin-pwa via workbox.importScripts em vite.config.js. A estrategia do
 * plugin e generateSW, que produz um worker so de cache e nao aceita codigo
 * proprio; importScripts e a forma de acrescentar comportamento sem migrar tudo
 * para injectManifest.
 *
 * O payload chega de GeneralNotification::toWebPush().
 */

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let dados;
    try {
        dados = event.data.json();
    } catch (e) {
        // Push sem JSON valido nao deveria acontecer, mas mostrar um aviso vazio
        // e pior do que nao mostrar nada.
        return;
    }

    const titulo = dados.titulo || 'SDC';
    const url = dados.url || '/notificacoes';

    const opcoes = {
        body: dados.mensagem || '',
        icon: '/imgs/pwa-192x192.png',
        badge: '/imgs/pwa-192x192.png',
        // Mesma tag substitui o aviso anterior em vez de empilhar: e o
        // equivalente, no sistema operacional, ao agrupamento do sino.
        tag: dados.tag || 'sdc',
        renotify: true,
        // Alerta urgente fica na tela ate o usuario interagir.
        requireInteraction: dados.tipo === 'urgent',
        data: { url },
    };

    event.waitUntil(self.registration.showNotification(titulo, opcoes));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const destino = (event.notification.data && event.notification.data.url) || '/notificacoes';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((janelas) => {
            // Reaproveita uma aba do SDC ja aberta em vez de abrir outra: o
            // usuario tipico deixa o sistema aberto o dia inteiro.
            for (const janela of janelas) {
                if (janela.url.includes(self.location.origin)) {
                    return janela.focus().then((focada) =>
                        focada && 'navigate' in focada ? focada.navigate(destino) : focada
                    );
                }
            }

            return self.clients.openWindow(destino);
        })
    );
});
