(function(){
  if (window.__uiAnim) return;
  window.__uiAnim = true;

  var st = document.createElement('style');
  st.textContent = [
    '#uiToastWrap{position:fixed;top:22px;left:50%;transform:translateX(-50%);z-index:40000;display:flex;flex-direction:column;gap:9px;align-items:center;pointer-events:none}',
    '.uiToast{display:flex;align-items:center;gap:11px;background:#fff;border-radius:12px;padding:13px 20px;box-shadow:0 12px 34px rgba(0,0,0,.18);font-size:.86rem;color:#1e293b;opacity:0;transform:translateY(-14px);transition:opacity .25s,transform .28s cubic-bezier(.2,.9,.3,1.3);max-width:90vw}',
    '.uiToast.on{opacity:1;transform:none}',
    '.uiToast i{font-size:1.15rem}',
    '.uiToast.ok{border-left:4px solid #22c55e}.uiToast.ok i{color:#22c55e}',
    '.uiToast.err{border-left:4px solid #ef4444}.uiToast.err i{color:#ef4444}',
    '.uiToast.info{border-left:4px solid #3b82f6}.uiToast.info i{color:#3b82f6}',

    '.uiOv{position:fixed;inset:0;z-index:41000;display:flex;align-items:center;justify-content:center;background:rgba(15,18,28,.5);backdrop-filter:blur(3px);opacity:0;transition:opacity .2s ease}',
    '.uiOv.on{opacity:1}',
    '.uiCf{background:#fff;border-radius:18px;padding:30px 28px 22px;width:352px;max-width:90vw;text-align:center;box-shadow:0 28px 70px rgba(0,0,0,.3);transform:translateY(18px) scale(.92);transition:transform .26s cubic-bezier(.18,.9,.32,1.28)}',
    '.uiOv.on .uiCf{transform:none}',
    '.uiIc{width:62px;height:62px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.7rem;animation:uiSh .5s ease}',
    '@keyframes uiSh{0%,100%{transform:rotate(0)}25%{transform:rotate(-9deg)}75%{transform:rotate(9deg)}}',
    '.uiT{font-size:1.02rem;font-weight:600;color:#1e293b;margin-bottom:7px}',
    '.uiS{font-size:.82rem;color:#94a3b8;margin-bottom:22px;line-height:1.6}',
    '.uiB{display:flex;gap:10px}',
    '.uiB button{flex:1;border-radius:10px;padding:11px 0;font-size:.87rem;font-weight:500;border:0;cursor:pointer;transition:.18s}',
    '.uiNo{background:#f1f5f9;color:#334155}.uiNo:hover{background:#e2e8f0}',
    '.uiYa{background:#ef4444;color:#fff}.uiYa:hover{background:#dc2626}'
  ].join('');
  document.head.appendChild(st);

  window.uiToast = function(pesan, jenis){
    var wrap = document.getElementById('uiToastWrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.id = 'uiToastWrap'; document.body.appendChild(wrap); }
    var j = jenis || 'info';
    var ik = j === 'ok' ? 'bi-check-circle-fill' : (j === 'err' ? 'bi-exclamation-circle-fill' : 'bi-info-circle-fill');
    var t = document.createElement('div');
    t.className = 'uiToast ' + j;
    t.innerHTML = '<i class="bi ' + ik + '"></i><span></span>';
    t.querySelector('span').textContent = pesan;
    wrap.appendChild(t);
    requestAnimationFrame(function(){ t.classList.add('on'); });
    setTimeout(function(){
      t.classList.remove('on');
      setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 300);
    }, 3200);
  };

  window.uiConfirm = function(judul, sub, cb, ikon){
    var ov = document.createElement('div');
    ov.className = 'uiOv';
    ov.innerHTML = '<div class="uiCf"><div class="uiIc"><i class="bi ' + (ikon || 'bi-trash3') + '"></i></div>'
      + '<div class="uiT"></div><div class="uiS"></div>'
      + '<div class="uiB"><button class="uiNo">Batal</button><button class="uiYa">Ya, lanjutkan</button></div></div>';
    ov.querySelector('.uiT').textContent = judul;
    ov.querySelector('.uiS').textContent = sub;
    document.body.appendChild(ov);
    requestAnimationFrame(function(){ ov.classList.add('on'); });

    function buang(){ ov.classList.remove('on'); setTimeout(function(){ if (ov.parentNode) ov.parentNode.removeChild(ov); }, 280); }
    ov.querySelector('.uiYa').addEventListener('click', function(){ buang(); cb(); });
    ov.querySelector('.uiNo').addEventListener('click', buang);
    ov.addEventListener('click', function(e){ if (e.target === ov) buang(); });
    document.addEventListener('keydown', function esc(e){ if (e.key === 'Escape'){ buang(); document.removeEventListener('keydown', esc); } });
  };
})();
