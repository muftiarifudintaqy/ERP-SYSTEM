(function(){
  if (window.__reaksiAnim) return;
  window.__reaksiAnim = true;

  var st = document.createElement('style');
  st.textContent = [
    '.rx-lapis{position:fixed;inset:0;pointer-events:none;z-index:45000;overflow:hidden;contain:strict}',
    '.rx-fly{position:absolute;will-change:transform,opacity;font-size:1.35rem;line-height:1}',
    '.rx-ring{position:absolute;border-radius:50%;border:2.5px solid currentColor;will-change:transform,opacity}'
  ].join('');
  document.head.appendChild(st);

  var lapis = document.createElement('div');
  lapis.className = 'rx-lapis';
  document.addEventListener('DOMContentLoaded', function(){ document.body.appendChild(lapis); });
  if (document.body) document.body.appendChild(lapis);

  var warna = {
    '👍':'#3b82f6','❤️':'#ef4444','🔥':'#f97316','😂':'#f0b429',
    '🎉':'#a855f7','👏':'#22c55e','😮':'#0ea5e9','🙏':'#8b5cf6',
    '💯':'#e11d48','✅':'#16a34a','🚀':'#6366f1','💪':'#0891b2'
  };

  var sibuk = false;

  window.rxLedak = function(el, emoji){
    if (!el || sibuk) return;
    sibuk = true;
    setTimeout(function(){ sibuk = false; }, 260);

    var r = el.getBoundingClientRect();
    var cx = r.left + r.width / 2;
    var cy = r.top + r.height / 2;
    var wr = warna[emoji] || '#6E4FA8';

    var ring = document.createElement('div');
    ring.className = 'rx-ring';
    ring.style.cssText = 'left:' + cx + 'px;top:' + cy + 'px;width:10px;height:10px;color:' + wr + ';transform:translate(-50%,-50%) scale(1);opacity:.8';
    lapis.appendChild(ring);
    ring.animate(
      [ { transform:'translate(-50%,-50%) scale(1)', opacity:.8 },
        { transform:'translate(-50%,-50%) scale(9)', opacity:0 } ],
      { duration:560, easing:'cubic-bezier(.2,.8,.3,1)', fill:'forwards' }
    ).onfinish = function(){ ring.remove(); };

    if (!emoji) return;

    for (var i = 0; i < 3; i++) {
      var fl = document.createElement('div');
      fl.className = 'rx-fly';
      fl.textContent = emoji;
      fl.style.left = (cx - 11) + 'px';
      fl.style.top  = (cy - 11) + 'px';
      lapis.appendChild(fl);

      var dx = (Math.random() * 76 - 38);
      var dy = -(78 + Math.random() * 46);
      var rot = (Math.random() * 44 - 22);

      (function(node){
        node.animate(
          [ { transform:'translate(0,0) scale(.55) rotate(0deg)',  opacity:0 },
            { transform:'translate(' + (dx*0.28) + 'px,-16px) scale(1.15) rotate(' + (rot*0.5) + 'deg)', opacity:1, offset:.22 },
            { transform:'translate(' + dx + 'px,' + dy + 'px) scale(.6) rotate(' + rot + 'deg)', opacity:0 } ],
          { duration: 880 + i * 90, easing:'cubic-bezier(.15,.85,.3,1)', delay: i * 55, fill:'forwards' }
        ).onfinish = function(){ node.remove(); };
      })(fl);
    }
  };

  /* satu listener untuk seluruh halaman — tidak ada pemantau berkala */
  document.addEventListener('click', function(e){
    var b = e.target.closest('[data-e],[data-emoji],.rx-emoji,.dk-emo,.pg-emo-btn');
    if (!b) return;
    var em = b.dataset.e || b.dataset.emoji || (b.textContent || '').trim().charAt(0);
    window.rxLedak(b, em);
  }, true);
})();
