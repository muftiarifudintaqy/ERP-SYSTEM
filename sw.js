const CACHE = 'montera-v8';
const STATE = 'montera-state';
const MUTE_KEY = '/__montera_mute__';

self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(clients.claim()));
self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request).catch(function () {
      return caches.match(e.request).then(function (c) {
        return c || new Response('Offline', { status: 503, statusText: 'Offline' });
      });
    })
  );
});

async function sedangBisu() {
  try {
    const c = await caches.open(STATE);
    const r = await c.match(MUTE_KEY);
    if (!r) return false;
    return Date.now() < parseInt(await r.text(), 10);
  } catch (err) { return false; }
}

async function pasangBisu(ms) {
  const c = await caches.open(STATE);
  await c.put(MUTE_KEY, new Response(String(Date.now() + ms)));
}

self.addEventListener('push', function (e) {
  e.waitUntil((async function () {
    let data = {};
    try { data = e.data ? e.data.json() : {}; }
    catch (err) { data = { title: 'Montera', body: e.data ? e.data.text() : '' }; }

    const bisu = await sedangBisu();

    // === kelompokkan notifikasi: 1 bubble per pengirim per kategori ===
    const judul = String(data.title || '');
    let jenis = 'lain';

    if (/pesan baru/i.test(judul))            jenis = 'chat';
    else if (/absen|kehadiran/i.test(judul))  jenis = 'absensi';
    else if (/tugas/i.test(judul))            jenis = 'tugas';
    else if (/komentar|dokumen/i.test(judul)) jenis = 'dokumen';
    else if (/jadwal/i.test(judul))           jenis = 'jadwal';

    let pengirim = '';
    let m = judul.match(/dari\s+(.+)$/i);
    if (m) pengirim = m[1].trim();
    else {
      m = judul.match(/^(.+?)\s+(berkomentar|menambahkan|membuat|menyelesaikan)/i);
      if (m) pengirim = m[1].trim();
    }

    const tag = jenis + '::' + (pengirim || 'umum');

    await self.registration.showNotification(data.title || 'Montera', {
      body: data.body || '',
      icon: '/assets/img/icon-192.png',
      badge: '/assets/img/icon-192.png',
      data: { url: data.url || '/', jenis: jenis, pengirim: pengirim },
      vibrate: bisu ? [] : [200, 100, 200],
      silent: bisu ? true : false,
      renotify: true,
      requireInteraction: false,
      tag: tag,
      actions: (function(){
        if (jenis === 'chat') {
          return [
            { action: 'balas', title: 'Balas', type: 'text', placeholder: 'Ketik balasan...' },
            { action: 'baca',  title: 'Tandai dibaca' }
          ];
        }
        var label = 'Lihat';
        if (jenis === 'absensi')      label = 'Lihat Absen';
        else if (jenis === 'tugas')   label = 'Lihat Tugas';
        else if (jenis === 'dokumen') label = 'Lihat Komentar';
        else if (jenis === 'jadwal')  label = 'Lihat Jadwal';
        else if (jenis === 'postingan') label = 'Lihat Postingan';
        return [
          { action: 'buka', title: label },
          { action: 'baca', title: 'Tandai dibaca' }
        ];
      })()
    });
  })());
});

self.addEventListener('notificationclick', function (e) {
  const aksi = e.action;
  let url = (e.notification.data && e.notification.data.url) ? e.notification.data.url : '/';
  e.notification.close();

  // balas langsung dari notifikasi (tanpa membuka aplikasi)
  if (aksi === 'balas') {
    var teks = (e.reply || '').trim();
    if (!teks) { return; }
    var cocok = /[?&]team=(\d+)/.exec(url);
    var tid = cocok ? cocok[1] : '';
    if (!tid) { return; }

    var fd = new FormData();
    fd.append('team_id', tid);
    fd.append('message', teks);

    e.waitUntil(
      fetch('/kinerja/send_chat_message', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      }).then(function(r){ return r.json(); })
        .then(function(j){
          if (!j || !j.status) {
            return self.registration.showNotification('Balasan gagal terkirim', {
              body: 'Buka aplikasi untuk mengirim ulang',
              icon: '/assets/img/fav.png',
              tag: 'balas-gagal'
            });
          }
        })
        .catch(function(){
          return self.registration.showNotification('Balasan gagal terkirim', {
            body: 'Periksa koneksi lalu coba lagi',
            icon: '/assets/img/fav.png',
            tag: 'balas-gagal'
          });
        })
    );
    return;
  }

  if (aksi === 'bisukan') {
    e.waitUntil(pasangBisu(2 * 60 * 60 * 1000));
    return;
  }

  if (aksi === 'baca') {
    e.waitUntil(
      fetch('/notifications/mark_all_read', {
        method: 'POST',
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(function () {})
    );
    return;
  }

  // tombol "Lihat ..." -> selalu buka halaman tujuan langsung
  if (aksi === 'buka') {
    e.waitUntil(
      clients.openWindow(url).catch(function () {
        return clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (l) {
          if (l.length && l[0].navigate) { l[0].focus(); return l[0].navigate(url); }
        });
      })
    );
    return;
  }

  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
      // sudah ada jendela di halaman yang sama -> cukup fokuskan
      for (let i = 0; i < list.length; i++) {
        if (list[i].url === url && 'focus' in list[i]) return list[i].focus();
      }
      // ada jendela lain -> pindahkan ke halaman tujuan
      for (let j = 0; j < list.length; j++) {
        if ('focus' in list[j] && list[j].navigate) {
          return list[j].focus().then(function (c) {
            return (c || list[j]).navigate(url);
          }).catch(function () {
            return clients.openWindow ? clients.openWindow(url) : null;
          });
        }
      }
      // tidak ada jendela -> buka baru
      return clients.openWindow ? clients.openWindow(url) : null;
    })
  );
});
