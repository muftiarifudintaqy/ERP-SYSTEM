(function(){
  if (window.innerWidth > 767) return;
  window.addEventListener('load', function(){
    setTimeout(function(){
      var W = document.documentElement.clientWidth, bad = [];
      document.querySelectorAll('.content-body *').forEach(function(el){
        var r = el.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) return;
        if (r.right > W + 2 || r.left < -2) {
          var t = (el.innerText || '').trim().slice(0, 28);
          bad.push({
            tag: el.tagName.toLowerCase(),
            cls: (el.className || '').toString().slice(0, 60),
            kiri: Math.round(r.left),
            kanan: Math.round(r.right),
            lebar: Math.round(r.width),
            teks: t
          });
        }
      });
      if (!bad.length) { console.log('%c✅ tidak ada elemen keluar layar', 'color:green;font-size:14px'); return; }
      console.log('%c⚠ ' + bad.length + ' elemen keluar layar (lebar layar ' + W + 'px)', 'color:red;font-size:14px');
      console.table(bad.slice(0, 25));
    }, 1400);
  });
})();
