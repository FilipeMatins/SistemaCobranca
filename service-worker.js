const CACHE_NAME = 'cobranca-v1';
const urlsToCache = [
  '/SistemaCobranca/',
  '/SistemaCobranca/index.php',
  '/SistemaCobranca/assets/css/app.css',
  '/SistemaCobranca/assets/js/app.js',
  '/SistemaCobranca/manifest.json'
];

// Instalação - cacheia arquivos essenciais
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting())
  );
});

// Ativação - limpa caches antigos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch - estratégia Network First (API) / Cache First (assets)
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Para APIs, sempre busca da rede
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          return new Response(JSON.stringify({ error: 'Sem conexão' }), {
            headers: { 'Content-Type': 'application/json' }
          });
        })
    );
    return;
  }
  
  // Para assets, tenta cache primeiro
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          // Atualiza cache em background
          fetch(event.request).then(networkResponse => {
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, networkResponse);
            });
          });
          return response;
        }
        
        return fetch(event.request).then(networkResponse => {
          // Cacheia nova resposta
          if (networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseClone);
            });
          }
          return networkResponse;
        });
      })
  );
});

// Push notifications
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nova notificação',
    icon: '/SistemaCobranca/assets/icons/icon-192.png',
    badge: '/SistemaCobranca/assets/icons/icon-72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      { action: 'open', title: 'Abrir' },
      { action: 'close', title: 'Fechar' }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('Sistema de Cobrança', options)
  );
});

// Clique na notificação
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'open' || !event.action) {
    event.waitUntil(
      clients.openWindow('/SistemaCobranca/')
    );
  }
});


