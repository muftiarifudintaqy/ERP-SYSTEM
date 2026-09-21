(function(){
  function bungkus(){
    if (window.innerWidth > 767) return;
    document.querySelectorAll('.card table, .card-body table').forEach(function(t){
      var p = t.parentElement;
      if (p && (p.classList.contains('mfx-scroll') || p.classList.contains('table-responsive'))) {
        p.classList.add('mfx-scroll'); return;
      }
      var w = document.createElement('div');
      w.className = 'mfx-scroll';
      t.parentNode.insertBefore(w, t);
      w.appendChild(t);
    });
  }
  if (document.readyState === 'complete') bungkus();
  else window.addEventListener('load', bungkus);
})();
