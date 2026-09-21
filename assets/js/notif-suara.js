(function(){
  if (window.__notifSuara) return;
  window.__notifSuara = true;

  var BASE = window.__BASE_URL__ || '/';

  if ('Notification' in window && Notification.permission === 'default') {
    document.addEventListener('click', function minta(){
      Notification.requestPermission();
      document.removeEventListener('click', minta);
    }, { once: true });
  }

  var audio = new Audio(BASE + 'assets/sound/notif.mp3');
  audio.volume = 0.85;
  audio.preload = 'auto';

  window.notifBunyi = function(){
    try { audio.currentTime = 0; audio.play().catch(function(){}); } catch(e){}
  };

  window.notifBrowser = function(judul, isi, url){
    window.notifBunyi();
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      var n = new Notification(judul, { body: isi || '', icon: BASE + 'assets/img/fav.png', tag: 'montera-' + Date.now() });
      n.onclick = function(){
        window.focus();
        if (url) location.href = url;
        n.close();
      };
      setTimeout(function(){ n.close(); }, 12000);
    } catch(e){}
  };
})();
