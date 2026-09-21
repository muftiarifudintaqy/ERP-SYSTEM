<?php
$disc_nama = ['D'=>'Dominance','I'=>'Influence','S'=>'Steadiness','C'=>'Compliance'];
$bf_nama   = ['O'=>'Keterbukaan','C'=>'Kehati-hatian','E'=>'Ekstraversi','A'=>'Keramahan','N'=>'Kepekaan Emosi'];

$puncak = function ($n) {
    $m = max($n); $s = [];
    foreach ($n as $k => $v) if ($v === $m) $s[] = $k;
    return $s;
};

$kartu = [];

if ($disc) {
    $n = ['D'=>(int)$disc['net_d'],'I'=>(int)$disc['net_i'],'S'=>(int)$disc['net_s'],'C'=>(int)$disc['net_c']];
    $p = $puncak($n);
    $kartu[] = ['label'=>'DISC','nilai'=>implode('/',$p),
        'ket'=> count($p)>1 ? 'Seimbang' : ($disc_nama[$p[0]] ?? ''),
        'warna'=>'#E5484D','bar'=>max($n)];
}

if ($karakteristik) {
    $kartu[] = ['label'=>'Karakteristik','nilai'=>(int)$karakteristik['persen'].'%',
        'ket'=>$karakteristik['kategori'],'warna'=>'#30A46C','bar'=>(int)$karakteristik['persen']];
}
if ($aptitude) {
    $b = (int)$aptitude['benar']; $t = (int)$aptitude['total_soal'];
    $kartu[] = ['label'=>'Test IQ','nilai'=>$b.'/'.$t,
        'ket'=>'Estimasi '.(int)$aptitude['iq'],'warna'=>'#3E63DD',
        'bar'=> $t>0 ? (int)round($b/$t*100) : 0];
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/asesmen.css') ?>?v=9">

<div class="as">
  <div class="as-kepala">
    <p class="as-label">Asesmen selesai</p>
    <h1><?= html_escape($a['nama']) ?></h1>
    <p class="as-sub"><?= html_escape($a['divisi'] ?: 'Divisi belum diisi') ?><?php
      if ($a['selesai_at']) echo ' &middot; ' . date('d F Y', strtotime($a['selesai_at'])); ?></p>
    <div class="as-jalur"><span style="width:100%"></span></div>
  </div>

  <div class="as-kartu" id="as-batang">
    <h2 class="as-h2">Ringkasan empat test</h2>
    <?php foreach ($kartu as $k): ?>
      <div class="as-dim">
        <div class="as-dim-atas">
          <span class="as-kode" style="background:<?= $k['warna'] ?>"><?= html_escape(mb_substr($k['label'],0,1)) ?></span>
          <div class="as-dim-teks">
            <p class="as-dim-nama"><?= html_escape($k['label']) ?></p>
            <p class="as-dim-ket"><?= html_escape($k['ket']) ?></p>
          </div>
          <span class="as-dim-angka"><?= html_escape($k['nilai']) ?></span>
        </div>
        <div class="as-jalur2">
          <span class="as-isi" style="--w:<?= max(2,min(100,$k['bar'])) ?>%;background:<?= $k['warna'] ?>"></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div id="as-ai-wadah">
    <div class="as-kartu"><p class="as-sub as-memuat">Menyusun gambaran menyeluruh&hellip;</p></div>
  </div>

  <div class="as-kartu as-tengah">
    <h2 class="as-h2">Terima kasih</h2>
    <p class="as-sub">Hasil Anda sudah tersimpan dan akan ditinjau bersama HRD.</p>
    <p class="as-nota" style="margin-top:18px">Hasil asesmen menggambarkan kecenderungan gaya kerja pada saat pengisian, bukan ukuran kecerdasan atau kelayakan seseorang. Angka pada Test IQ adalah estimasi internal, bukan hasil tes psikometri resmi.</p>
  </div>
</div>

<script>
(function () {
  var w = document.getElementById('as-batang');
  if (w) requestAnimationFrame(function(){ requestAnimationFrame(function(){ w.classList.add('as-siap'); }); });

  var wadah = document.getElementById('as-ai-wadah');
  if (!wadah) return;
  var url = <?= json_encode(base_url('asesmen/narasi/' . $a['kode'] . '/gabungan')) ?>;

  function aman(t){ var e=document.createElement('div'); e.textContent=String(t==null?'':t); return e.innerHTML; }
  function daftar(j,i,c){ if(!i||!i.length) return '';
    var h='<h3 class="as-h3 '+(c||'')+'">'+j+'</h3><ul class="as-daftar '+(c||'')+'">';
    i.forEach(function(t){ h+='<li>'+aman(t)+'</li>'; }); return h+'</ul>'; }
  function alinea(j,t){ return t ? '<h3 class="as-h3">'+j+'</h3><p>'+aman(t)+'</p>' : ''; }
  function tips(i){ if(!i||!i.length) return '';
    var h='<h3 class="as-h3">Langkah yang bisa dicoba</h3>';
    i.forEach(function(t,n){ h+='<div class="as-tip"><span class="as-tip-no">'+(n+1)+'</span><span>'+aman(t)+'</span></div>'; });
    return h; }

  fetch(url,{credentials:'same-origin'})
    .then(function(r){ return r.json(); })
    .then(function(j){
      if(!j.ok||!j.ai||!j.ai.summary) throw new Error('kosong');
      var a=j.ai,h='<div class="as-kartu"><h2 class="as-h2">Gambaran menyeluruh</h2>';
      if(a.headline) h+='<p class="as-headline">'+aman(a.headline)+'</p>';
      h+='<p>'+aman(a.summary)+'</p>';
      h+=daftar('Ciri khas',a.traits);
      h+=daftar('Kekuatan yang menonjol',a.strengths,'as-hijau');
      h+=daftar('Yang perlu dikembangkan',a.development_areas,'as-jingga');
      h+='</div>';
      if(a.work_style||a.cocok_di_peran||a.kehidupan_sehari){
        h+='<div class="as-kartu">'+alinea('Gaya kerja',a.work_style)
          +alinea('Cocok di peran',a.cocok_di_peran)+alinea('Sehari-hari',a.kehidupan_sehari)+'</div>';
      }
      if(a.tips&&a.tips.length) h+='<div class="as-kartu">'+tips(a.tips)+'</div>';
      wadah.innerHTML=h;
    })
    .catch(function(){
      wadah.innerHTML='<div class="as-kartu"><p class="as-sub">Gambaran menyeluruh belum bisa disusun sekarang. Semua skor sudah tersimpan, jadi bagian ini bisa dilihat lagi nanti.</p></div>';
    });
})();
</script>
