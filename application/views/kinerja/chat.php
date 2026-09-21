<?php $halaman='chat'; $this->load->view('kinerja/_tabs', array('teams'=>$teams,'team_id'=>$team_id,'halaman'=>$halaman)); ?>
<style>
.md-panel{position:absolute;top:0;right:0;bottom:0;width:330px;background:#fff;border-left:1px solid #e5e8f0;z-index:60;transform:translateX(100%);transition:transform .28s cubic-bezier(.2,.9,.3,1);display:flex;flex-direction:column;box-shadow:-8px 0 26px rgba(0,0,0,.09)}
.md-panel.on{transform:none}
.md-hd{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #e5e8f0}
.md-hd .t{font-size:.92rem;font-weight:600;color:#1e293b}
.md-hd .x{margin-left:auto;border:0;background:none;color:#94a3b8;font-size:1.05rem;cursor:pointer}
.md-tab{display:flex;border-bottom:1px solid #e5e8f0}
.md-tab button{flex:1;border:0;background:none;padding:11px;font-size:.79rem;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;letter-spacing:.4px}
.md-tab button.on{color:#6E4FA8;border-bottom-color:#6E4FA8}
.md-isi{flex:1;overflow-y:auto;padding:12px}
.md-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.md-it{position:relative;border-radius:8px;overflow:hidden;background:#f1f5f9;aspect-ratio:1;cursor:zoom-in}
.md-it img,.md-it video{width:100%;height:100%;object-fit:cover;display:block}
.md-it .pl{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:1.4rem;text-shadow:0 2px 8px rgba(0,0,0,.6);pointer-events:none}
.md-dok{display:flex;align-items:center;gap:11px;padding:10px;border-radius:10px;text-decoration:none;color:#334155;transition:.15s;border:1px solid #eef1f6;margin-bottom:7px}
.md-dok:hover{background:#f8fafc;border-color:#d9def0;color:#334155}
.md-dok .ic{width:38px;height:38px;border-radius:9px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex:0 0 auto}
.md-dok .nm{font-size:.8rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.md-dok .mt{font-size:.68rem;color:#94a3b8;margin-top:2px}
.md-lnk{display:flex;align-items:flex-start;gap:11px;padding:11px;border-radius:10px;text-decoration:none;color:#334155;border:1px solid #eef1f6;margin-bottom:7px;transition:.15s}
.md-lnk:hover{background:#f8fafc;border-color:#c7d2fe;color:#334155}
.md-lnk .fav{width:36px;height:36px;border-radius:9px;background:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-size:1rem;flex:0 0 auto}
.md-lnk .hs{font-size:.79rem;font-weight:600;color:#2563eb;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.md-lnk .ur{font-size:.7rem;color:#94a3b8;word-break:break-all;line-height:1.4;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.md-lnk .mt{font-size:.67rem;color:#cbd5e1;margin-top:4px}
.md-kosong{text-align:center;padding:50px 16px;color:#94a3b8;font-size:.82rem}
.md-buka{border:1px solid #e2e6ee;background:#fff;border-radius:9px;padding:5px 12px;font-size:.76rem;color:#475569;cursor:pointer;transition:.15s}
.md-buka:hover{border-color:#6E4FA8;color:#6E4FA8}
@media(max-width:767.98px){.md-panel{width:100%}}
.ch-wrap{position:relative;display:flex;height:calc(100vh - 230px);min-height:420px;border:1px solid #e5e8f0;border-radius:12px;overflow:hidden;background:#fff}
@media(max-width:767.98px){
  body.chLockScroll{overflow:hidden !important;position:fixed;width:100%}
  .ch-wrap{position:fixed;left:0;right:0;top:var(--chTop,0px);bottom:0;height:auto;border-radius:0;border:0;z-index:5}
}
.ch-main{flex:1;display:flex;flex-direction:column;min-width:0}
.ch-topbar{padding:8px 14px;border-bottom:1px solid #e5e8f0;display:flex;justify-content:space-between;align-items:center;background:#fff;position:relative}
.ch-kebab{display:none;border:0;background:none;color:#475569;font-size:1.15rem;padding:4px 8px;border-radius:8px;cursor:pointer;line-height:1}
.ch-back{display:none;border:0;background:none;color:#475569;font-size:1.2rem;padding:4px 6px;margin-right:2px;border-radius:8px;cursor:pointer;line-height:1;flex:0 0 auto}
.ch-back:hover{background:#f1f5f9}
@media(max-width:767.98px){.ch-back{display:inline-flex;align-items:center}}
.ch-kebab:hover{background:#f1f5f9}
.ch-actmenu{display:flex;gap:8px;align-items:center}
@media(max-width:767.98px){
  .ch-topbar{flex-wrap:nowrap!important;gap:8px}
  .ch-topbar .t{width:auto!important;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .ch-kebab{display:inline-flex;align-items:center;flex:0 0 auto}
  .ch-actmenu{position:absolute;top:46px;right:10px;width:230px!important;flex-direction:column;align-items:stretch;gap:2px;background:#fff;border:1px solid #e5e8f0;border-radius:13px;box-shadow:0 16px 40px rgba(0,0,0,.16);padding:6px;z-index:70;display:none}
  .ch-actmenu.on{display:flex}
  .ch-actmenu .md-buka,.ch-actmenu .btn{width:100%!important;flex:none!important;text-align:left!important;justify-content:flex-start!important;border:0!important;background:none!important;color:#334155!important;font-size:.84rem!important;padding:10px 12px!important;border-radius:9px!important}
  .ch-actmenu .md-buka:hover,.ch-actmenu .btn:hover{background:#f1f5f9!important}
  .ch-actmenu #chClearAll{color:#ef4444!important}
  .ch-actmenu #chClearAll:hover{background:#fef2f2!important}
}
.ch-topbar .t{font-size:.86rem;font-weight:600;color:#1e293b}
#chPinBar{display:none;align-items:center;gap:10px;padding:8px 14px;background:#fff9e6;border-bottom:1px solid #f5e6a8;cursor:pointer}
#chPinBar.on{display:flex}
#chPinBar .ic{color:#eab308;font-size:1rem;flex:0 0 auto}
#chPinBar .body{flex:1;min-width:0}
#chPinBar .lbl{font-size:.66rem;color:#a16207;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
#chPinBar .tx{font-size:.8rem;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#chPinBar .tg{font-size:.68rem;color:#94a3b8;flex:0 0 auto}
#chPinBar .nav{display:flex;gap:2px;flex:0 0 auto}
#chPinBar .nav button{border:0;background:none;color:#a16207;font-size:.85rem;cursor:pointer;padding:2px 4px}
.ch-pin-ic{color:#eab308;font-size:.72rem;margin-right:4px}
.ch-star-ic{color:#eab308;font-size:.72rem;margin-left:4px}
.ch-msgs{flex:1;overflow-y:auto;padding:16px;background:#eef0f4}
.ch-side{width:262px;border-left:1px solid #e5e8f0;padding:0;overflow-y:auto;flex:0 0 auto;position:relative;background:#fbfcfe}
.ch-side-inner{padding:14px}
.ch-mem-card{display:flex;align-items:center;gap:10px;padding:9px 11px;border-radius:11px;margin-bottom:6px;background:#fff;border:1px solid #eef1f6;transition:.16s;position:relative}
.ch-mem-card:hover{border-color:#d9def0;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.ch-mem-card.saya{background:#f6f4fd;border-color:#e0d9f5}
.ch-mem-card .info{flex:1;min-width:0}
.ch-mem-card .nm{font-size:.83rem;font-weight:600;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.3}
.ch-mem-card .st{font-size:.68rem;line-height:1.4}
.ch-mem-card .st.on{color:#16a34a}
.ch-mem-card .st.off{color:#94a3b8}
.ch-av-wrap{position:relative;flex:0 0 auto}
.ch-av-dot{position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;border-radius:50%;border:2px solid #fff}
.ch-av-dot.on{background:#22c55e;box-shadow:0 0 6px rgba(34,197,94,.85)}
.ch-av-dot.off{background:#cbd5e1}
.ch-badge-saya{font-size:.6rem;background:#ede9fe;color:#6d28d9;padding:1px 6px;border-radius:99px;font-weight:600}
.ch-grp{font-size:.64rem;letter-spacing:.7px;text-transform:uppercase;color:#94a3b8;font-weight:600;margin:14px 0 7px;padding:0 2px}
.ch-grp:first-of-type{margin-top:0}
.ch-mem-card .ch-kick{border:0;background:none;color:#cbd5e1;font-size:.85rem;cursor:pointer;padding:2px 4px;border-radius:6px;opacity:0;transition:.15s;flex:0 0 auto}
.ch-mem-card:hover .ch-kick{opacity:1}
.ch-mem-card .ch-kick:hover{color:#ef4444;background:#fef2f2}
.ch-badge-pj{display:inline-block;font-size:.62rem;font-weight:600;color:#a16207;background:#fef9c3;border-radius:6px;padding:1px 6px;margin-left:4px;vertical-align:middle}
.ch-mem-card .ch-pj{border:0;background:none;color:#cbd5e1;font-size:.85rem;cursor:pointer;padding:2px 4px;border-radius:6px;opacity:0;transition:.15s;flex:0 0 auto}
.ch-mem-card:hover .ch-pj{opacity:1}
.ch-mem-card .ch-pj:hover{color:#eab308;background:#fefce8}
.ch-mem-card .ch-pj.aktif{color:#eab308;opacity:1}
.ch-keluar{width:100%;border:1px solid #fecaca;background:#fff;color:#ef4444;border-radius:11px;padding:9px;font-size:.79rem;cursor:pointer;transition:.18s;margin-top:8px}
.ch-keluar:hover{background:#fef2f2;border-color:#ef4444}
@media(max-width:767.98px){.ch-mem-card .ch-kick{opacity:1} .ch-mem-card .ch-pj{opacity:1}}
.ch-tambah{width:100%;border:1.5px dashed #cbd5e1;background:transparent;color:#64748b;border-radius:11px;padding:10px;font-size:.8rem;cursor:pointer;transition:.18s;margin-top:6px}
.ch-tambah:hover{border-color:#6E4FA8;color:#6E4FA8;background:#faf9fd}
#chAddPanel{position:absolute;top:52px;left:12px;right:12px;background:#fff;border:1px solid #e2e6ee;border-radius:13px;box-shadow:0 18px 42px rgba(0,0,0,.16);padding:12px;z-index:40;display:none;max-height:320px;overflow-y:auto}
#chAddPanel.on{display:block}
#chAddPanel .hd{font-size:.74rem;font-weight:600;color:#334155;margin-bottom:9px;display:flex;justify-content:between;align-items:center}
.ch-addrow{display:flex;align-items:center;gap:9px;padding:7px 8px;border-radius:9px;font-size:.81rem;color:#334155;transition:.15s}
.ch-addrow:hover{background:#f6f7fb}
.ch-addrow .av{width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;flex:0 0 auto}
.ch-addrow .nm2{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ch-addrow button{border:0;background:#6E4FA8;color:#fff;border-radius:8px;padding:4px 11px;font-size:.71rem;cursor:pointer;transition:.15s}
.ch-addrow button:hover{background:#5b3f95}
.ch-addcari{width:100%;border:1px solid #e2e6ee;border-radius:9px;padding:7px 11px;font-size:.79rem;margin-bottom:9px}
.ch-addcari:focus{outline:0;border-color:#6E4FA8}
.ch-side-back{display:none;align-items:center;gap:7px;border:0;background:none;color:#6E4FA8;font-size:.85rem;font-weight:600;padding:8px 2px;margin-bottom:6px;cursor:pointer;width:100%;text-align:left;border-radius:8px}
.ch-side-back:hover{background:#f5f3fb}
@media(max-width:767.98px){
  .ch-side{padding-top:0!important}
  .ch-side-inner{padding-top:0!important}
  .ch-side-back{
    display:inline-flex!important;
    position:fixed!important;
    top:var(--chTop,70px)!important;
    left:0!important;right:0!important;
    width:auto!important;
    z-index:60!important;
    background:#fbfcfe!important;
    border-bottom:1px solid #e5e8f0!important;
    padding:13px 16px!important;margin:0!important;
    border-radius:0!important;
    box-shadow:0 2px 6px rgba(0,0,0,.05)!important;
  }
  .ch-side-inner{padding-top:76px!important}
  .ch-side-close{display:none!important}
}
.ch-side-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.ch-side h6{font-size:.72rem;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;margin:0}
.ch-addbtn{border:1px solid #e2e6ee;background:#fff;border-radius:50%;width:24px;height:24px;font-size:.8rem;cursor:pointer;color:#6366f1;line-height:1}
.ch-mem{display:flex;align-items:center;gap:8px;padding:6px 0}
.ch-av{width:30px;height:30px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex:0 0 auto}
.ch-mem-nm{font-size:.82rem;color:#334155}
.ch-sys{display:flex;justify-content:center;margin:12px 0}
.ch-sys span{background:rgba(255,255,255,.9);border:1px solid #e2e8f0;color:#64748b;font-size:.72rem;padding:5px 14px;border-radius:99px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.ch-sys i{font-size:.78rem}
.ch-sys.masuk span{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.ch-sys.keluar span{background:#fff7ed;border-color:#fed7aa;color:#c2410c}
.ch-row{display:flex;margin-bottom:10px;position:relative}
.ch-row.me{justify-content:flex-end}
.ch-bub{max-width:64%;padding:8px 12px;border-radius:12px;font-size:.86rem;position:relative;box-shadow:0 1px 1px rgba(0,0,0,.07)}
.ch-row.me .ch-bub{background:#fef3c7;border-top-right-radius:2px}
.ch-row:not(.me) .ch-bub{background:#fff;border-top-left-radius:2px}
.ch-nm{font-size:.72rem;font-weight:700;color:#6366f1;margin-bottom:2px}
.ch-tx{white-space:pre-wrap;word-break:break-word}
.ch-tx.del{color:#94a3b8;font-style:italic;display:flex;align-items:center;gap:8px}
.ch-meta{font-size:.64rem;color:#94a3b8;margin-top:4px;display:flex;align-items:center;gap:4px;justify-content:flex-end}
.ch-tick{font-size:.78rem;color:#94a3b8}
.ch-tick.read{color:#34b7f1}
.ch-att img{max-width:220px;border-radius:8px;display:block;margin-bottom:4px;cursor:zoom-in}
.ch-att video{max-width:220px;border-radius:8px;display:block;margin-bottom:4px}
.ch-att a.file{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e6ee;border-radius:10px;padding:8px 10px;margin-bottom:4px;text-decoration:none;color:#334155;font-size:.78rem;min-width:180px}
.ch-att a.file .fi{width:32px;height:32px;border-radius:8px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1rem;flex:0 0 auto}
.ch-inputbar{padding:10px;border-top:1px solid #e5e8f0;background:#fff}
.ch-attach-row{display:flex;gap:8px;align-items:center}
.ch-attbtn{border:1px solid #e2e6ee;background:#fff;border-radius:50%;width:36px;height:36px;flex:0 0 auto;cursor:pointer;color:#64748b;font-size:1rem}
.ch-attbtn:hover{background:#f1f5f9}
.ch-preview{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e6ee;border-radius:10px;padding:8px 10px;margin-bottom:8px}
.ch-preview img{width:44px;height:44px;object-fit:cover;border-radius:8px}
.ch-preview .fi{width:44px;height:44px;border-radius:8px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.ch-preview .nm{font-size:.8rem;font-weight:600;color:#1e293b;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ch-preview .sz{font-size:.7rem;color:#94a3b8}
.ch-preview .x{margin-left:auto;border:0;background:none;color:#ef4444;cursor:pointer;font-size:1rem}
.ch-reply{background:rgba(0,0,0,.05);border-left:3px solid #6366f1;border-radius:6px;padding:5px 8px;margin-bottom:5px;font-size:.76rem;cursor:pointer}
.ch-reply .rn{font-weight:700;color:#6366f1;font-size:.7rem;display:block}
.ch-reply .rt{color:#64748b;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px}
.ch-replybar{display:flex;align-items:center;gap:10px;background:#f8fafc;border-left:3px solid #6366f1;border-radius:8px;padding:8px 10px;margin-bottom:8px}
.ch-replybar .isi{flex:1;min-width:0}
.ch-replybar .nm{font-size:.74rem;font-weight:700;color:#6366f1}
.ch-replybar .tx{font-size:.76rem;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ch-replybar .x{border:0;background:none;color:#94a3b8;cursor:pointer;font-size:1rem}
.ch-dot{width:9px;height:9px;border-radius:50%;flex:0 0 auto;margin-left:auto;box-shadow:0 0 0 2px #fff}
.ch-dot.on{background:#22c55e;box-shadow:0 0 0 2px #fff,0 0 7px rgba(34,197,94,.8)}
.ch-dot.off{background:#ef4444}
.ch-mem{position:relative}
.ch-bub{position:relative}
.ch-caret{position:absolute;top:3px;right:4px;width:22px;height:22px;border:0;background:transparent;color:#94a3b8;font-size:.78rem;cursor:pointer;border-radius:50%;opacity:0;transition:.15s;line-height:1;padding:0;z-index:3}
.ch-bub:hover .ch-caret{opacity:1}
.ch-caret:hover{background:rgba(0,0,0,.07);color:#475569}
.ch-menu{position:fixed;background:#fff;border-radius:12px;box-shadow:0 14px 40px rgba(0,0,0,.2);padding:6px;z-index:31000;min-width:180px;opacity:0;transform:scale(.92);transition:opacity .14s,transform .16s cubic-bezier(.2,.9,.3,1.3);pointer-events:none}
.ch-menu.on{opacity:1;transform:scale(1);pointer-events:auto}
.ch-menu button{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:9px 12px;border-radius:8px;font-size:.84rem;color:#334155;cursor:pointer;text-align:left;transition:.13s}
.ch-menu button:hover{background:#f4f6fa}
.ch-menu button.bahaya{color:#ef4444}
.ch-menu button.bahaya:hover{background:#fef2f2}
.ch-menu i{font-size:.94rem;width:18px}
@media(max-width:767.98px){.ch-caret{opacity:1}}
.ch-permdel{border:0;background:none;color:#ef4444;font-size:.72rem;cursor:pointer;padding:0}
#chLbBox{position:fixed;inset:0;background:rgba(8,11,20,.94);z-index:20000;display:flex;align-items:center;justify-content:center;padding:40px 20px;cursor:zoom-out;opacity:0;pointer-events:none;transition:opacity .2s}
#chLbBox.on{opacity:1;pointer-events:auto}
#chLbBox img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:10px;transform:scale(.92);transition:transform .2s cubic-bezier(.2,.9,.3,1.2)}
#chLbBox.on img{transform:scale(1)}
#chClearModal{position:fixed;inset:0;background:rgba(15,18,28,.55);z-index:30001;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .18s}
#chClearModal.on{opacity:1;pointer-events:auto}
.chcl-card{background:#fff;border-radius:16px;padding:24px 24px 18px;width:360px;max-width:90vw;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.28);transform:translateY(14px) scale(.94);transition:transform .2s cubic-bezier(.2,.9,.3,1.2)}
#chClearModal.on .chcl-card{transform:translateY(0) scale(1)}
.chcl-opts{text-align:left;background:#f8fafc;border-radius:10px;padding:12px 14px;margin:14px 0}
.chcl-opts label{display:flex;align-items:center;gap:8px;font-size:.82rem;color:#334155;padding:5px 0;cursor:pointer}
.chcl-opts input{width:16px;height:16px}
#chConfirmBox{position:fixed;inset:0;background:rgba(15,18,28,.55);z-index:30000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .18s}
#chConfirmBox.on{opacity:1;pointer-events:auto}
.ch-cf-card{background:#fff;border-radius:16px;padding:26px 26px 20px;width:320px;max-width:90vw;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.28);transform:translateY(14px) scale(.94);transition:transform .2s cubic-bezier(.2,.9,.3,1.2)}
#chConfirmBox.on .ch-cf-card{transform:translateY(0) scale(1)}
.ch-cf-ic{width:52px;height:52px;border-radius:50%;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:1.4rem}
.ch-cf-btns{display:flex;gap:10px}
.ch-cf-btns button{flex:1;border-radius:9px;padding:8px 0;font-size:.85rem;border:0;cursor:pointer}
.ch-cf-cancel{background:#f1f5f9;color:#334155}
.ch-cf-ok{background:#ef4444;color:#fff}
#chAddPanel{position:absolute;top:36px;right:14px;width:250px;background:#fff;border:1px solid #e2e6ee;border-radius:12px;box-shadow:0 14px 34px rgba(0,0,0,.14);padding:10px;z-index:40;display:none;max-height:280px;overflow-y:auto}
#chAddPanel.on{display:block}
.ch-addrow{display:flex;align-items:center;justify-content:space-between;padding:6px 2px;font-size:.8rem;color:#334155}
.ch-addrow button{border:0;background:#eef2ff;color:#6366f1;border-radius:7px;padding:3px 9px;font-size:.72rem;cursor:pointer}
</style>

<?php if (!$team_id): ?>
  <div class="card"><div class="card-body text-center py-5 text-muted">Belum ada tim.</div></div>
<?php else: ?>
  <div class="ch-wrap">
    <div class="ch-main">
      <div class="ch-topbar">
        <button class="ch-back" id="chBack" type="button" title="Kembali"><i class="bi bi-arrow-left"></i></button>
        <div class="t"><i class="bi bi-chat-dots me-1"></i>Chat Grup</div>
        <button class="ch-kebab" id="chKebab" type="button" title="Menu"><i class="bi bi-three-dots-vertical"></i></button>
        <div class="ch-actmenu" id="chActMenu">
          <button class="md-buka" id="btnMedia"><i class="bi bi-images me-1"></i>Media &amp; Dokumen</button>
          <button class="md-buka d-md-none" id="btnToggleMem"><i class="bi bi-people me-1"></i>Anggota</button>
          <?php if ($boleh): ?><a href="<?= base_url() ?>kinerja/alias_settings?team=<?= $team_id ?>" class="btn btn-sm btn-outline-secondary" id="chAliasBtn"><i class="bi bi-incognito me-1"></i>Nama Samaran</a><?php endif; ?>
          <?php if ($boleh): ?><button class="btn btn-sm btn-outline-danger" id="chClearAll"><i class="bi bi-trash3 me-1"></i>Hapus Semua Riwayat</button><?php endif; ?>
        </div>
      </div>
      <div id="chPinBar"><i class="bi bi-pin-angle-fill ic"></i>
        <div class="body"><div class="lbl">Pesan disematkan</div><div class="tx" id="chPinTx"></div></div>
        <div class="tg" id="chPinTg"></div>
        <div class="nav" id="chPinNav" style="display:none"><button data-d="-1"><i class="bi bi-chevron-up"></i></button><button data-d="1"><i class="bi bi-chevron-down"></i></button></div>
      </div>
      <div id="chMsgs" class="ch-msgs">
        <div class="text-center text-muted small mt-4">Memuat percakapan...</div>
      </div>
      <div class="ch-inputbar">
        <div id="chPreview" class="ch-preview" style="display:none">
          <div id="chPrevThumb"></div>
          <div>
            <div class="nm" id="chPrevNm"></div>
            <div class="sz" id="chPrevSz"></div>
          </div>
          <button class="x" id="chPrevX"><i class="bi bi-x-circle-fill"></i></button>
        </div>
        <div id="chReplyBar" class="ch-replybar" style="display:none">
          <i class="bi bi-reply text-primary"></i>
          <div class="isi"><div class="nm"></div><div class="tx"></div></div>
          <button class="x" id="chReplyX"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="ch-attach-row">
          <button class="ch-attbtn" id="chAttBtn" title="lampirkan berkas"><i class="bi bi-paperclip"></i></button>
          <input type="file" id="chFile" style="display:none">
          <input type="text" id="chInput" class="form-control form-control-sm" placeholder="Ketik pesan disini...">
          <button class="btn btn-primary btn-sm" id="chSend"><i class="bi bi-send"></i></button>
        </div>
      </div>
    </div>
    <div class="md-panel" id="mdPanel">
      <div class="md-hd">
        <button class="x" id="mdTutup"><i class="bi bi-x-lg"></i></button>
        <span class="t">Media &amp; Dokumen</span>
      </div>
      <div class="md-tab">
        <button class="on" data-t="media">MEDIA</button>
        <button data-t="dokumen">DOKUMEN</button>
        <button data-t="tautan">TAUTAN</button>
      </div>
      <div class="md-isi" id="mdIsi"><div class="md-kosong">memuat...</div></div>
    </div>

    <div class="ch-side"><button class="ch-side-close" id="btnTutupMem"><i class="bi bi-x-lg"></i></button>
      <div class="ch-side-inner">
        <button class="ch-side-back" id="chSideBack" type="button"><i class="bi bi-arrow-left"></i> Kembali ke Chat</button>
        <div class="ch-side-head">
          <h6>Anggota (<span id="chMemCount"><?= count($anggota) ?></span>)</h6>
        </div>

        <div class="ch-grp" id="grpOn">Online — <span id="cntOn">0</span></div>
        <div id="listOn"></div>

        <div class="ch-grp" id="grpOff">Offline — <span id="cntOff">0</span></div>
        <div id="listOff"></div>

        <?php if ($boleh || $is_pj): ?>
          <button class="ch-tambah" id="chAddBtn"><i class="bi bi-person-plus me-1"></i>Tambah Anggota</button>
        <?php endif; ?>
        <button class="ch-keluar" id="chKeluar"><i class="bi bi-box-arrow-right me-1"></i>Keluar dari Tim</button>
      </div>

      <div id="chAddPanel">
        <div class="hd"><span>Tambah ke grup</span></div>
        <input type="text" class="ch-addcari" id="chAddCari" placeholder="cari nama...">
        <div id="chAddList"><div class="text-muted small">memuat...</div></div>
      </div>
    </div>

    <script id="dataAnggota" type="application/json"><?= json_encode($anggota) ?></script>
  </div>
<?php endif; ?>

<div id="chLbBox"><img id="chLbImg" src="" alt=""></div>
<div id="chConfirmBox">
  <div class="ch-cf-card">
    <div class="ch-cf-ic"><i class="bi bi-trash3"></i></div>
    <div id="chCfTitle" style="font-size:.95rem;font-weight:600;color:#1e293b;margin-bottom:6px">Hapus pesan ini?</div>
    <div style="font-size:.78rem;color:#94a3b8;margin-bottom:16px">Tindakan ini tidak bisa dibatalkan.</div>
    <div class="ch-cf-btns">
      <button class="ch-cf-cancel" id="chCfCancel">Batal</button>
      <button class="ch-cf-ok" id="chCfOk">Hapus</button>
    </div>
  </div>
</div>

<div id="chClearModal">
  <div class="chcl-card">
    <div class="ch-cf-ic"><i class="bi bi-trash3"></i></div>
    <div style="font-size:.95rem;font-weight:600;color:#1e293b;margin-top:8px">Hapus SEMUA riwayat chat grup ini?</div>
    <div style="font-size:.78rem;color:#94a3b8;margin-top:4px">Tindakan ini tidak bisa dibatalkan.</div>
    <div class="chcl-opts">
      <label><input type="checkbox" id="chClDelStar"> Hapus juga pesan yang dibintangi</label>
      <label><input type="checkbox" id="chClDelPin"> Hapus juga pesan yang disematkan</label>
    </div>
    <div class="ch-cf-btns">
      <button class="ch-cf-cancel" id="chClCancel">Batal</button>
      <button class="ch-cf-ok" id="chClOk">Hapus</button>
    </div>
  </div>
</div>

<script>
(function(){
  var BASE = '<?= base_url() ?>';

  (function lockMobileScroll(){
    function tinggiHeader(){
      // cari semua elemen fixed/sticky yang nempel di atas layar,
      // ambil tepi bawah paling rendah = itu batas aman chat dimulai
      var maxBottom = 0;
      var semua = document.body.querySelectorAll('*');
      for (var i = 0; i < semua.length; i++){
        var el = semua[i];
        if (el.classList.contains('ch-wrap') || el.closest('.ch-wrap')) continue;
        var cs = window.getComputedStyle(el);
        if (cs.position !== 'fixed' && cs.position !== 'sticky') continue;
        if (cs.display === 'none' || cs.visibility === 'hidden') continue;
        var r = el.getBoundingClientRect();
        if (r.height === 0 || r.width < window.innerWidth * 0.5) continue;
        if (r.top > 8) continue;              // harus nempel di atas
        if (r.bottom > window.innerHeight * 0.5) continue; // bukan overlay layar penuh
        if (r.bottom > maxBottom) maxBottom = r.bottom;
      }
      return Math.round(maxBottom);
    }

    function terapkan(){
      var html = document.documentElement;
      if (window.innerWidth > 767) {
        document.body.classList.remove('chLockScroll');
        html.style.removeProperty('--chTop');
        return;
      }
      var wrap = document.querySelector('.ch-wrap');
      if (!wrap) return;

      // lepas dulu supaya pengukuran tidak terpengaruh state sebelumnya
      var tadinyaTerkunci = document.body.classList.contains('chLockScroll');
      if (tadinyaTerkunci){
        document.body.classList.remove('chLockScroll');
        html.style.removeProperty('--chTop');
      }

      var top = tinggiHeader();
      if (!top || top < 0) {
        // fallback: posisi wrap terhadap viewport (bukan dokumen)
        top = Math.max(0, Math.round(wrap.getBoundingClientRect().top));
      }

      html.style.setProperty('--chTop', top + 'px');
      document.body.classList.add('chLockScroll');
    }

    function jadwalkan(){
      terapkan();
      setTimeout(terapkan, 120);
      setTimeout(terapkan, 400);
    }

    if (document.readyState === 'complete') jadwalkan();
    else window.addEventListener('load', jadwalkan);
    jadwalkan();

    window.addEventListener('resize', terapkan);
    window.addEventListener('orientationchange', function(){ setTimeout(terapkan, 250); });
    if (window.visualViewport) window.visualViewport.addEventListener('resize', terapkan);
  })();
  var TEAM = <?= intval($team_id) ?>;
  var UID  = <?= intval($uid) ?>;
  var BOLEH = <?= $boleh ? 'true' : 'false' ?>;
  var CANADD = <?= ($boleh || $is_pj) ? 'true' : 'false' ?>;
  if (!TEAM) return;

  var msgsEl = document.getElementById('chMsgs');
  var pal = ['#6366f1','#f59e0b','#10b981','#ef4444','#0ea5e9','#a855f7','#ec4899'];
  function warna(nm){ var n=(nm||'?').charCodeAt(0)||0; return pal[n % pal.length]; }
  function esc(t){ var d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
  function fmtSize(b){ return b > 1048576 ? (b/1048576).toFixed(1)+' MB' : Math.round(b/1024)+' KB'; }

  var lb = document.getElementById('chLbBox');
  var lbImg = document.getElementById('chLbImg');
  function bukaLb(src){ lbImg.src = src; requestAnimationFrame(function(){ lb.classList.add('on'); }); }
  function tutupLb(){ lb.classList.remove('on'); setTimeout(function(){ lbImg.src=''; }, 200); }
  lb.addEventListener('click', tutupLb);
  document.addEventListener('keydown', function(e){ if (e.key==='Escape') tutupLb(); });

  var cfBox = document.getElementById('chConfirmBox');
  var cfOk = document.getElementById('chCfOk');
  var cfCancel = document.getElementById('chCfCancel');
  var cfTitle = document.getElementById('chCfTitle');
  function chConfirm(judul, cb){
    cfTitle.textContent = judul;
    cfBox.classList.add('on');
    function bersih(){ cfBox.classList.remove('on'); cfOk.removeEventListener('click', onOk); cfCancel.removeEventListener('click', onCancel); }
    function onOk(){ bersih(); cb(true); }
    function onCancel(){ bersih(); cb(false); }
    cfOk.addEventListener('click', onOk);
    cfCancel.addEventListener('click', onCancel);
  }

  function renderAtt(m){
    if (!m.file_path) return '';
    var url = BASE + m.file_path;
    var e = (m.file_type||'').toLowerCase();
    if (['jpg','jpeg','png','gif','webp'].indexOf(e) >= 0) {
      return '<div class="ch-att"><img src="'+url+'" data-src="'+url+'" class="ch-imglb" loading="lazy"></div>';
    } else if (['mp4','webm','mov'].indexOf(e) >= 0) {
      return '<div class="ch-att"><video src="'+url+'" controls preload="metadata" playsinline></video></div>';
    }
    var ic = 'bi-file-earmark';
    if (e=='pdf') ic='bi-file-earmark-pdf';
    else if (['doc','docx'].indexOf(e)>=0) ic='bi-file-earmark-word';
    else if (['xls','xlsx'].indexOf(e)>=0) ic='bi-file-earmark-excel';
    else if (e=='zip') ic='bi-file-earmark-zip';
    return '<div class="ch-att"><a class="file" href="'+url+'" target="_blank"><div class="fi"><i class="bi '+ic+'"></i></div><span>'+esc(m.file_name||'berkas')+'</span></a></div>';
  }

  var totalMembersLain = <?= count($anggota) - 1 ?>;

  function render(data){
    var reads = data.reads || {};
    var boleh = data.boleh;
    var h = '';
    data.messages.forEach(function(m){
      var me = parseInt(m.user_id) === UID;

      if (parseInt(m.is_system) === 1) {
        var txt = m.message || '';
        var jenis = 'masuk', ikon = 'bi-person-plus-fill';
        if (txt.indexOf('keluar') >= 0 || txt.indexOf('dikeluarkan') >= 0) { jenis = 'keluar'; ikon = 'bi-person-dash-fill'; }
        var jam = (m.created_at || '').substring(11, 16);
        h += '<div class="ch-sys '+jenis+'"><span><i class="bi '+ikon+'"></i>'
           + esc(txt) + (jam ? ' &middot; ' + jam : '') + '</span></div>';
        return;
      }

      if (parseInt(m.deleted) === 1) {
        var bisaPermanen = me || boleh;
        h += '<div class="ch-row'+(me?' me':'')+'" data-id="'+m.id+'"><div class="ch-bub"><div class="ch-tx del"><span>pesan telah dihapus</span>'
           + (bisaPermanen ? '<button class="ch-permdel" data-id="'+m.id+'" title="hapus permanen"><i class="bi bi-trash3"></i></button>' : '')
           + '</div></div></div>';
        return;
      }
      var waktu = m.edited_at || m.created_at;
      var tagEdit = m.edited == 1 ? ' &middot; <i>diedit</i>' : '';
      var att = renderAtt(m);
      var balas = '';
      if (m.reply_to && (m.balas_nama || m.balas_teks)) {
        var isiBalas = parseInt(m.balas_dihapus) === 1
          ? '<i>pesan telah dihapus</i>'
          : esc(m.balas_teks || m.balas_file || 'lampiran');
        balas = '<div class="ch-reply" data-goto="'+m.reply_to+'">'
              + '<span class="rn">'+esc(m.balas_nama||'Pengguna')+'</span>'
              + '<span class="rt">'+isiBalas+'</span></div>';
      }
      var isiPesan = esc(m.message||'')
            .replace(/\\n/g,'<br>')
            .replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#2563eb;text-decoration:underline;word-break:break-all">$1</a>');
      var teks = m.message ? '<div class="ch-tx" data-teks="'+esc(m.message).replace(/"/g,'&quot;')+'">'+isiPesan+'</div>' : '';

      var centang = '';
      if (me) {
        var dibacaSemua = totalMembersLain > 0;
        for (var uidLain in reads) {
          if (parseInt(uidLain) === UID) continue;
          if ((reads[uidLain]||0) < parseInt(m.id)) { dibacaSemua = false; break; }
        }
        centang = '<i class="bi '+(dibacaSemua ? 'bi-check2-all ch-tick read' : 'bi-check2-all ch-tick')+'"></i>';
      }

      var dsn = esc(m.nama||'').replace(/"/g,'&quot;');
      var dst = esc(m.message||m.file_name||'lampiran').replace(/"/g,'&quot;');
      var aksi = '<button class="ch-caret" data-id="'+m.id+'" data-nm="'+dsn+'" data-tx="'+dst+'"'
               + ' data-me="'+(me?'1':'0')+'" data-teks="'+(m.message?'1':'0')+'"'
               + ' data-pin="'+(parseInt(m.pinned)===1?'1':'0')+'" data-star="'+(STARRED.indexOf(parseInt(m.id))>=0?'1':'0')+'"><i class="bi bi-chevron-down"></i></button>';
      var lencana = '';
      if (parseInt(m.pinned) === 1) lencana += '<i class="bi bi-pin-angle-fill ch-pin-ic"></i>';
      if (STARRED.indexOf(parseInt(m.id)) >= 0) lencana += '<i class="bi bi-star-fill ch-star-ic"></i>';

      h += '<div class="ch-row'+(me?' me':'')+'" data-id="'+m.id+'">'
         + '<div class="ch-bub">' + aksi
         + (me ? '' : '<div class="ch-nm" style="color:'+warna(m.nama)+'">'+esc(m.nama||'Pengguna')+'</div>')
         + balas + att + teks
         + '<div class="ch-meta">'+lencana+waktu+tagEdit+' '+centang+'</div>'
         + '</div></div>';
    });
    var nearBottom = (msgsEl.scrollTop + msgsEl.clientHeight) >= (msgsEl.scrollHeight - 60);
    msgsEl.innerHTML = h || '<div class="text-center text-muted small mt-4">Belum ada pesan. Mulai percakapan!</div>';
    if (nearBottom || firstLoad) { msgsEl.scrollTop = msgsEl.scrollHeight; }
    firstLoad = false;

    STARRED = (data.starred_ids || []).map(function(x){ return parseInt(x); });
    if (data.anggota) { ANGGOTA = data.anggota; }
    gambarAnggota(data.online || []);

    msgsEl.querySelectorAll('.ch-caret').forEach(function(b){
      b.addEventListener('click', function(e){
        e.stopPropagation();
        bukaMenu(this);
      });
    });
    msgsEl.querySelectorAll('.ch-reply[data-goto]').forEach(function(r){
      r.addEventListener('click', function(){
        var t = msgsEl.querySelector('.ch-row[data-id="'+this.dataset.goto+'"]');
        if (t) {
          t.scrollIntoView({behavior:'smooth', block:'center'});
          t.style.transition = 'background .3s';
          t.style.background = 'rgba(99,102,241,.14)';
          setTimeout(function(){ t.style.background = ''; }, 900);
        }
      });
    });

    msgsEl.querySelectorAll('.ch-imglb').forEach(function(img){
      img.addEventListener('click', function(){ bukaLb(this.dataset.src); });
    });

    msgsEl.querySelectorAll('.ch-permdel').forEach(function(b){
      b.addEventListener('click', function(){
        var id = this.dataset.id;
        chConfirm('Hapus permanen dari riwayat?', function(ok){
          if (!ok) return;
          var fd = new FormData(); fd.append('id', id);
          fetch(BASE+'kinerja/delete_chat_message', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){return r.json();}).then(function(){ poll(); });
        });
      });
    });
    window.mulaiEdit = function(id){
      (function(){
        var row = msgsEl.querySelector('.ch-row[data-id="'+id+'"]');
        if (!row || row.querySelector('.ch-eedit')) return;
        var txtEl = row.querySelector('.ch-tx');
        var asli = txtEl.dataset.teks || '';

        sedangEdit = true;
        row.querySelectorAll('.ch-actions').forEach(function(x){ x.style.display='none'; });

        var wrap = document.createElement('div');
        wrap.className = 'mt-1';
        wrap.innerHTML = '<textarea class="form-control form-control-sm ch-eedit mb-1" rows="2"></textarea>'
          + '<div class="d-flex gap-2"><button class="btn btn-primary btn-sm ch-esave">Simpan</button>'
          + '<button class="btn btn-outline-secondary btn-sm ch-ecancel">Batal</button></div>';
        txtEl.style.display = 'none';
        txtEl.insertAdjacentElement('afterend', wrap);

        var inp = wrap.querySelector('.ch-eedit');
        inp.value = asli;
        inp.focus();
        inp.setSelectionRange(inp.value.length, inp.value.length);

        function selesai(){ sedangEdit = false; poll(true); }

        wrap.querySelector('.ch-ecancel').addEventListener('click', selesai);
        wrap.querySelector('.ch-esave').addEventListener('click', function(){
          var baru = inp.value.trim();
          if (!baru) { alert('Pesan tidak boleh kosong.'); return; }
          var fd = new FormData(); fd.append('id', id); fd.append('message', baru);
          fetch(BASE+'kinerja/edit_chat_message', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r){return r.json();}).then(selesai);
        });
        inp.addEventListener('keydown', function(e){
          if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); wrap.querySelector('.ch-esave').click(); }
          if (e.key === 'Escape') selesai();
        });
      })();
    };
  }

  var STARRED = [];
  var PINNED_LIST = [];
  var pinIdx = 0;
  var ANGGOTA = [];
  try { ANGGOTA = JSON.parse(document.getElementById('dataAnggota').textContent) || []; } catch(e){}

  function pasangKick(b){
    b.addEventListener('click', function(){
      var uid2 = this.dataset.id, nama = this.dataset.nm;
      chConfirm('Keluarkan ' + nama + ' dari tim?', function(ok){
        if (!ok) return;
        var fd = new FormData(); fd.append('team_id', TEAM); fd.append('user_id', uid2);
        fetch(BASE+'kinerja/remove_chat_member', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d){
            if (!d.status) { alert(d.msg); return; }
            ANGGOTA = ANGGOTA.filter(function(x){ return String(x.id) !== String(uid2); });
            poll(true);
          });
      });
    });
  }

  function kartuAnggota(a, aktif){
    var w = warna(a.full_name);
    var saya = parseInt(a.id) === UID;
    var pj = a.role_in_team === 'admin';
    return '<div class="ch-mem-card'+(saya?' saya':'')+'">'
      + '<div class="ch-av-wrap"><div class="ch-av" style="background:'+w+'">'
      +   esc((a.full_name||'?').charAt(0).toUpperCase())
      + '</div><span class="ch-av-dot '+(aktif?'on':'off')+'"></span></div>'
      + '<div class="info"><div class="nm">'+esc(a.full_name||'Pengguna')
      +   (saya ? ' <span class="ch-badge-saya">kamu</span>' : '')
      +   (pj ? ' <span class="ch-badge-pj">Penanggung Jawab</span>' : '') + '</div>'
      + '<div class="st '+(aktif?'on':'off')+'">'+(aktif?'online':'offline')+'</div></div>'
      + ((BOLEH && !saya) ? '<button class="ch-pj'+(pj?' aktif':'')+'" data-id="'+a.id+'" data-nm="'+esc(a.full_name||'').replace(/"/g,'&quot;')+'" data-pj="'+(pj?'1':'0')+'" title="'+(pj?'Copot Penanggung Jawab':'Jadikan Penanggung Jawab')+'"><i class="bi '+(pj?'bi-star-fill':'bi-star')+'"></i></button>' : '')
      + ((CANADD && !saya) ? '<button class="ch-kick" data-id="'+a.id+'" data-nm="'+esc(a.full_name||'').replace(/"/g,'&quot;')+'" title="keluarkan"><i class="bi bi-x-lg"></i></button>' : '')
      + '</div>';
  }

  function pasangPJ(b){
    b.addEventListener('click', function(){
      if (this.disabled) return;
      this.disabled = true;
      var uid2 = this.dataset.id, nama = this.dataset.nm, sudahPJ = this.dataset.pj === '1';
      var fd = new FormData(); fd.append('team_id', TEAM); fd.append('user_id', uid2);
      fetch(BASE+'kinerja/set_team_admin', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          if (!d.status) { alert(d.msg); return; }
          var it = ANGGOTA.find(function(x){ return String(x.id) === String(uid2); });
          if (it) it.role_in_team = d.role_in_team;
          poll(true);
        });
    });
  }

  function gambarAnggota(ol){
    var on = [], off = [];
    ANGGOTA.forEach(function(a){
      if (ol.indexOf(parseInt(a.id)) >= 0) on.push(a); else off.push(a);
    });
    var eOn = document.getElementById('listOn'), eOff = document.getElementById('listOff');
    if (!eOn) return;
    eOn.innerHTML  = on.length  ? on.map(function(a){ return kartuAnggota(a, true); }).join('')
                                : '<div class="text-muted" style="font-size:.75rem;padding:2px 4px 6px">Tidak ada yang online.</div>';
    eOff.innerHTML = off.length ? off.map(function(a){ return kartuAnggota(a, false); }).join('') : '';
    eOn.querySelectorAll('.ch-kick').forEach(pasangKick);
    eOff.querySelectorAll('.ch-kick').forEach(pasangKick);
    eOn.querySelectorAll('.ch-pj').forEach(pasangPJ);
    eOff.querySelectorAll('.ch-pj').forEach(pasangPJ);
    document.getElementById('cntOn').textContent  = on.length;
    document.getElementById('cntOff').textContent = off.length;
    document.getElementById('grpOff').style.display = off.length ? '' : 'none';
    var mc = document.getElementById('chMemCount');
    if (mc) mc.textContent = ANGGOTA.length;
  }
  gambarAnggota([]);

  var menuEl = null;
  function tutupMenu(){
    if (!menuEl) return;
    menuEl.classList.remove('on');
    var m = menuEl; menuEl = null;
    setTimeout(function(){ if (m.parentNode) m.parentNode.removeChild(m); }, 180);
  }
  document.addEventListener('click', tutupMenu);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutupMenu(); });
  window.addEventListener('scroll', tutupMenu, true);

  function bukaMenu(btn){
    tutupMenu();
    var id  = btn.dataset.id;
    var nm  = btn.dataset.nm;
    var tx  = btn.dataset.tx;
    var me  = btn.dataset.me === '1';
    var adaTeks = btn.dataset.teks === '1';

    var m = document.createElement('div');
    m.className = 'ch-menu';
    var lagiPin = btn.dataset.pin === '1';
    var lagiStar = btn.dataset.star === '1';
    var h = '<button data-a="balas"><i class="bi bi-reply"></i> Balas</button>';
    h += '<button data-a="pin"><i class="bi bi-pin-angle'+(lagiPin?'-fill':'')+'"></i> '+(lagiPin?'Lepas sematan':'Sematkan pesan')+'</button>';
    h += '<button data-a="star"><i class="bi bi-star'+(lagiStar?'-fill':'')+'"></i> '+(lagiStar?'Lepas bintang':'Bintangi pesan')+'</button>';
    if (adaTeks) h += '<button data-a="salin"><i class="bi bi-clipboard"></i> Salin teks</button>';
    if (me && adaTeks) h += '<button data-a="edit"><i class="bi bi-pencil"></i> Edit pesan</button>';
    if (me) h += '<button data-a="hapus" class="bahaya"><i class="bi bi-trash3"></i> Hapus pesan</button>';
    m.innerHTML = h;
    document.body.appendChild(m);

    var r = btn.getBoundingClientRect();
    var lebar = m.offsetWidth || 180, tinggi = m.offsetHeight || 120;
    var kiri = r.right - lebar;
    if (kiri < 8) kiri = 8;
    if (kiri + lebar > window.innerWidth - 8) kiri = window.innerWidth - lebar - 8;
    var atas = r.bottom + 6;
    if (atas + tinggi > window.innerHeight - 8) atas = r.top - tinggi - 6;
    if (atas < 8) atas = 8;
    m.style.left = kiri + 'px';
    m.style.top  = atas + 'px';
    requestAnimationFrame(function(){ m.classList.add('on'); });
    menuEl = m;

    m.addEventListener('click', function(e){ e.stopPropagation(); });
    m.querySelectorAll('button').forEach(function(b){
      b.addEventListener('click', function(){
        var a = this.dataset.a;
        tutupMenu();
        if (a === 'balas') setBalas(id, nm, tx);
        else if (a === 'salin') {
          var ta = document.createElement('textarea');
          ta.value = tx; document.body.appendChild(ta); ta.select();
          try { document.execCommand('copy'); } catch(err){}
          document.body.removeChild(ta);
        }
        else if (a === 'edit') { if (window.mulaiEdit) window.mulaiEdit(id); }
        else if (a === 'pin') {
          var fd = new FormData(); fd.append('id', id);
          fetch(BASE+'kinerja/toggle_pin_chat', {method:'POST', body:fd, credentials:'same-origin'})
            .then(function(r2){return r2.json();}).then(function(){ poll(true); muatPinBar(); });
        }
        else if (a === 'star') {
          var fd2 = new FormData(); fd2.append('id', id);
          fetch(BASE+'kinerja/toggle_star_chat', {method:'POST', body:fd2, credentials:'same-origin'})
            .then(function(r2){return r2.json();}).then(function(){ poll(true); });
        }
        else if (a === 'hapus') {
          function eksekusiHapus(confirmPinned){
            var fd = new FormData(); fd.append('id', id);
            if (confirmPinned) fd.append('confirm_pinned', 1);
            fetch(BASE+'kinerja/delete_chat_message', {method:'POST', body:fd, credentials:'same-origin'})
              .then(function(r2){return r2.json();})
              .then(function(d2){
                if (d2.butuh_konfirmasi_pin) {
                  chConfirm('Pesan ini disematkan. Hapus juga sematannya?', function(ok2){
                    if (ok2) eksekusiHapus(true);
                  });
                  return;
                }
                poll(true); muatPinBar();
              });
          }
          chConfirm('Hapus pesan ini?', function(ok){
            if (!ok) return;
            eksekusiHapus(false);
          });
        }
      });
    });
  }

  /* ===== panel media & dokumen ===== */
  var mdPanel = document.getElementById('mdPanel');
  var mdIsi   = document.getElementById('mdIsi');
  var mdTabAktif = 'media';
  var mdData = null;

  function ikonDok(e){
    e = (e||'').toLowerCase();
    if (e === 'pdf') return 'bi-file-earmark-pdf';
    if (['doc','docx'].indexOf(e) >= 0) return 'bi-file-earmark-word';
    if (['xls','xlsx','csv'].indexOf(e) >= 0) return 'bi-file-earmark-excel';
    if (['ppt','pptx'].indexOf(e) >= 0) return 'bi-file-earmark-ppt';
    if (e === 'zip') return 'bi-file-earmark-zip';
    if (['mp3','wav'].indexOf(e) >= 0) return 'bi-file-earmark-music';
    return 'bi-file-earmark';
  }

  function gambarMedia(){
    if (!mdData) { mdIsi.innerHTML = '<div class="md-kosong">memuat...</div>'; return; }

    if (mdTabAktif === 'media') {
      var m = mdData.media || [];
      if (!m.length) { mdIsi.innerHTML = '<div class="md-kosong"><i class="bi bi-images" style="font-size:1.9rem;opacity:.35;display:block;margin-bottom:9px"></i>Belum ada foto atau video.</div>'; return; }
      mdIsi.innerHTML = '<div class="md-grid">' + m.map(function(x){
        var url = BASE + x.file_path;
        var vid = ['mp4','webm','mov'].indexOf((x.file_type||'').toLowerCase()) >= 0;
        return '<div class="md-it md-lb" data-src="'+url+'">'
             + (vid ? '<video src="'+url+'" preload="metadata"></video><i class="bi bi-play-circle-fill pl"></i>'
                    : '<img src="'+url+'" loading="lazy">') + '</div>';
      }).join('') + '</div>';

      mdIsi.querySelectorAll('.md-lb').forEach(function(x){
        x.addEventListener('click', function(){ bukaLb(this.dataset.src); });
      });

    } else if (mdTabAktif === 'tautan') {
      var l = mdData.tautan || [];
      if (!l.length) { mdIsi.innerHTML = '<div class="md-kosong"><i class="bi bi-link-45deg" style="font-size:1.9rem;opacity:.35;display:block;margin-bottom:9px"></i>Belum ada tautan dibagikan.</div>'; return; }
      mdIsi.innerHTML = l.map(function(x){
        var tgl = (x.created_at||'').substring(0,10);
        return '<a class="md-lnk" href="'+x.url+'" target="_blank" rel="noopener">'
             + '<div class="fav"><i class="bi bi-link-45deg"></i></div>'
             + '<div style="min-width:0"><div class="hs">'+esc(x.host||'')+'</div>'
             + '<div class="ur">'+esc(x.url)+'</div>'
             + '<div class="mt">'+esc(x.nama||'-')+' &middot; '+tgl+'</div></div></a>';
      }).join('');

    } else {
      var d = mdData.dokumen || [];
      if (!d.length) { mdIsi.innerHTML = '<div class="md-kosong"><i class="bi bi-folder2-open" style="font-size:1.9rem;opacity:.35;display:block;margin-bottom:9px"></i>Belum ada dokumen.</div>'; return; }
      mdIsi.innerHTML = d.map(function(x){
        var tgl = (x.created_at||'').substring(0,10);
        return '<a class="md-dok" href="'+BASE+x.file_path+'" target="_blank">'
             + '<div class="ic"><i class="bi '+ikonDok(x.file_type)+'"></i></div>'
             + '<div style="min-width:0"><div class="nm">'+esc(x.file_name||'berkas')+'</div>'
             + '<div class="mt">'+esc(x.nama||'-')+' &middot; '+tgl+'</div></div></a>';
      }).join('');
    }
  }

  function muatMedia(){
    mdIsi.innerHTML = '<div class="md-kosong">memuat...</div>';
    fetch(BASE+'kinerja/chat_media?team_id='+TEAM, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ mdData = d.status ? d : {media:[],dokumen:[],tautan:[]}; gambarMedia(); });
  }

  var bMedia = document.getElementById('btnMedia');
  if (bMedia) bMedia.addEventListener('click', function(){
    mdPanel.classList.add('on');
    muatMedia();
  });
  document.getElementById('mdTutup').addEventListener('click', function(){ mdPanel.classList.remove('on'); });
  document.querySelectorAll('.md-tab button').forEach(function(b){
    b.addEventListener('click', function(){
      document.querySelectorAll('.md-tab button').forEach(function(x){ x.classList.remove('on'); });
      this.classList.add('on');
      mdTabAktif = this.dataset.t;
      gambarMedia();
    });
  });

  var sideEl = document.querySelector('.ch-side');
  var btnTutupMem = document.getElementById('btnTutupMem');
  if (btnTutupMem && sideEl) btnTutupMem.addEventListener('click', function(){ sideEl.classList.remove('mobile-on'); });
  var btnTM = document.getElementById('btnToggleMem');
  if (btnTM && sideEl) {
    btnTM.addEventListener('click', function(){ sideEl.classList.toggle('mobile-on'); });
    document.addEventListener('click', function(e){
      if (window.innerWidth > 767) return;
      if (sideEl.classList.contains('mobile-on') && !sideEl.contains(e.target) && e.target !== btnTM && !btnTM.contains(e.target)) {
        sideEl.classList.remove('mobile-on');
      }
    });
  }

  var firstLoad = true;
  var sedangEdit = false;
  function poll(paksa){
    if (sedangEdit && !paksa) return;
    fetch(BASE+'kinerja/poll_chat?team_id='+TEAM, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){ if (d.status && (!sedangEdit || paksa)) render(d); });
  }
  function muatPinBar(){
    fetch(BASE+'kinerja/list_pinned_chat?team_id='+TEAM, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        PINNED_LIST = (d.status && d.data) ? d.data : [];
        var bar = document.getElementById('chPinBar');
        var nav = document.getElementById('chPinNav');
        if (!PINNED_LIST.length) { bar.classList.remove('on'); return; }
        if (pinIdx >= PINNED_LIST.length) pinIdx = 0;
        tampilkanPin();
        nav.style.display = PINNED_LIST.length > 1 ? 'flex' : 'none';
        bar.classList.add('on');
      });
  }
  function tampilkanPin(){
    var p = PINNED_LIST[pinIdx];
    if (!p) return;
    document.getElementById('chPinTx').textContent = p.message || (p.file_name ? '📎 '+p.file_name : 'lampiran');
    document.getElementById('chPinTg').textContent = (p.pinned_at || p.created_at || '').substring(0,16).replace('T',' ');
  }
  document.getElementById('chPinNav').addEventListener('click', function(e){
    var b = e.target.closest('button'); if (!b) return;
    e.stopPropagation();
    pinIdx = (pinIdx + parseInt(b.dataset.d) + PINNED_LIST.length) % PINNED_LIST.length;
    tampilkanPin();
  });
  document.getElementById('chPinBar').addEventListener('click', function(e){
    if (e.target.closest('.nav')) return;
    var p = PINNED_LIST[pinIdx];
    if (!p) return;
    var t = msgsEl.querySelector('.ch-row[data-id="'+p.id+'"]');
    if (t) {
      t.scrollIntoView({behavior:'smooth', block:'center'});
      t.style.transition = 'background .3s';
      t.style.background = 'rgba(234,179,8,.18)';
      setTimeout(function(){ t.style.background = ''; }, 900);
    }
  });
  muatPinBar();

  poll();
  setInterval(poll, 3000);

  // ===== attach file (preview profesional) =====
  var attBtn = document.getElementById('chAttBtn');
  var fileInp = document.getElementById('chFile');
  var prevEl = document.getElementById('chPreview');
  var prevThumb = document.getElementById('chPrevThumb');
  var prevNm = document.getElementById('chPrevNm');
  var prevSz = document.getElementById('chPrevSz');

  attBtn.addEventListener('click', function(){ fileInp.click(); });
  fileInp.addEventListener('change', function(){
    var f = this.files[0];
    if (!f) { prevEl.style.display='none'; return; }
    prevNm.textContent = f.name;
    prevSz.textContent = fmtSize(f.size);
    if (f.type.indexOf('image/') === 0) {
      var rd = new FileReader();
      rd.onload = function(e){ prevThumb.innerHTML = '<img src="'+e.target.result+'">'; };
      rd.readAsDataURL(f);
    } else {
      prevThumb.innerHTML = '<div class="fi"><i class="bi bi-file-earmark-arrow-up"></i></div>';
    }
    prevEl.style.display = 'flex';
  });
  document.getElementById('chPrevX').addEventListener('click', function(){
    fileInp.value = ''; prevEl.style.display = 'none';
  });

  var balasId = 0;
  function setBalas(id, nm, tx){
    balasId = id;
    var bar = document.getElementById('chReplyBar');
    bar.querySelector('.nm').textContent = nm || 'Pengguna';
    bar.querySelector('.tx').textContent = tx || '';
    bar.style.display = 'flex';
    document.getElementById('chInput').focus();
  }
  function batalBalas(){
    balasId = 0;
    document.getElementById('chReplyBar').style.display = 'none';
  }
  document.getElementById('chReplyX').addEventListener('click', batalBalas);

  function kirim(){
    var btnKirim = document.getElementById('chSend');
    if (btnKirim.disabled) return;
    var txt = document.getElementById('chInput').value.trim();
    var f = fileInp.files[0];
    if (!txt && !f) return;
    var fd = new FormData();
    fd.append('team_id', TEAM);
    fd.append('message', txt);
    if (balasId) fd.append('reply_to', balasId);
    if (f) fd.append('berkas', f);
    btnKirim.disabled = true;
    fetch(BASE+'kinerja/send_chat_message', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        btnKirim.disabled = false;
        if (d.status) {
          document.getElementById('chInput').value = '';
          fileInp.value = ''; prevEl.style.display='none';
          batalBalas();
          poll();
        } else { alert(d.msg); }
      })
      .catch(function(){ btnKirim.disabled = false; alert('Gagal menghubungi server.'); });
  }
  document.getElementById('chSend').addEventListener('click', kirim);
  document.getElementById('chInput').addEventListener('keydown', function(e){ if (e.key==='Enter') kirim(); });

  // ===== hapus semua riwayat =====
  var clearBtn = document.getElementById('chClearAll');
  if (clearBtn) {
    clearBtn.addEventListener('click', function(){
      document.getElementById('chClDelStar').checked = false;
      document.getElementById('chClDelPin').checked = false;
      document.getElementById('chClearModal').classList.add('on');
    });
  }
  document.getElementById('chClCancel').addEventListener('click', function(){
    document.getElementById('chClearModal').classList.remove('on');
  });
  document.getElementById('chClOk').addEventListener('click', function(){
    var fd = new FormData();
    fd.append('team_id', TEAM);
    fd.append('hapus_starred', document.getElementById('chClDelStar').checked ? 1 : 0);
    fd.append('hapus_pinned', document.getElementById('chClDelPin').checked ? 1 : 0);
    fetch(BASE+'kinerja/clear_chat_history', {method:'POST', body:fd, credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        document.getElementById('chClearModal').classList.remove('on');
        if (d.status) { poll(); muatPinBar(); } else alert(d.msg);
      });
  });

  // ===== tambah anggota =====
  var addBtn = document.getElementById('chAddBtn');
  var addPanel = document.getElementById('chAddPanel');
  var addList = document.getElementById('chAddList');
  if (addBtn) {
    addBtn.addEventListener('click', function(){
      addPanel.classList.toggle('on');
      if (addPanel.classList.contains('on')) muatAddable();
    });
    document.addEventListener('click', function(e){
      if (!addPanel.contains(e.target) && e.target !== addBtn) addPanel.classList.remove('on');
    });
  }
  var dataAddable = [];
  function gambarAddable(cari){
    var q = (cari||'').toLowerCase();
    var isi = dataAddable.filter(function(u){ return !q || (u.full_name||'').toLowerCase().indexOf(q) >= 0; });
    if (!isi.length) {
      addList.innerHTML = '<div class="text-muted" style="font-size:.78rem;padding:8px 4px">'
        + (dataAddable.length ? 'Tidak ada yang cocok.' : 'Semua sudah jadi anggota.') + '</div>';
      return;
    }
    addList.innerHTML = isi.map(function(u){
      return '<div class="ch-addrow">'
        + '<div class="av" style="background:'+warna(u.full_name)+'">'+esc((u.full_name||'?').charAt(0).toUpperCase())+'</div>'
        + '<div class="nm2">'+esc(u.full_name)+'</div>'
        + '<button data-id="'+u.id+'">Tambah</button></div>';
    }).join('');

    addList.querySelectorAll('button').forEach(function(b){
      b.addEventListener('click', function(){
        var tb = this; var uid2 = tb.dataset.id;
        tb.disabled = true; tb.textContent = '...';
        var fd = new FormData(); fd.append('team_id', TEAM); fd.append('user_id', uid2);
        fetch(BASE+'kinerja/add_chat_member', {method:'POST', body:fd, credentials:'same-origin'})
          .then(function(r){return r.json();})
          .then(function(d2){
            if (!d2.status) { alert(d2.msg); tb.disabled=false; tb.textContent='Tambah'; return; }
            var u = dataAddable.filter(function(x){ return String(x.id) === String(uid2); })[0];
            if (u) { ANGGOTA.push(u); dataAddable = dataAddable.filter(function(x){ return String(x.id) !== String(uid2); }); }
            tb.textContent = 'Ditambahkan';
            tb.style.background = '#22c55e';
            setTimeout(function(){ gambarAddable(document.getElementById('chAddCari').value); poll(true); }, 500);
          });
      });
    });
  }

  function muatAddable(){
    addList.innerHTML = '<div class="text-muted" style="font-size:.78rem;padding:8px 4px">memuat...</div>';
    fetch(BASE+'kinerja/list_addable_members?team_id='+TEAM, {credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        dataAddable = (d.status && d.data) ? d.data : [];
        gambarAddable('');
      });
  }

  var btnKeluar = document.getElementById('chKeluar');
  if (btnKeluar) btnKeluar.addEventListener('click', function(){
    chConfirm('Keluar dari tim ini?', function(ok){
      if (!ok) return;
      var fd = new FormData(); fd.append('team_id', TEAM); fd.append('user_id', UID);
      fetch(BASE+'kinerja/remove_chat_member', {method:'POST', body:fd, credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          if (!d.status) { alert(d.msg); return; }
          location.href = BASE + 'kinerja';
        });
    });
  });

  var cariEl = document.getElementById('chAddCari');
  if (cariEl) cariEl.addEventListener('input', function(){ gambarAddable(this.value); });
})();
</script>

<script>
(function(){
  var kb = document.getElementById('chKebab');
  var mn = document.getElementById('chActMenu');
  if (!kb || !mn) return;
  kb.addEventListener('click', function(e){
    e.stopPropagation();
    mn.classList.toggle('on');
  });
  mn.addEventListener('click', function(){
    if (window.innerWidth <= 767.98) mn.classList.remove('on');
  });
  document.addEventListener('click', function(e){
    if (!mn.contains(e.target) && e.target !== kb) mn.classList.remove('on');
  });
})();
</script>

<style>
/* ===== SERAGAMKAN TOMBOL TOPBAR CHAT (DESKTOP) ===== */
@media(min-width:768px){
  .ch-topbar{padding:10px 14px!important;gap:10px;flex-wrap:nowrap}
  .ch-topbar .t{flex:1;min-width:0}
  .ch-actmenu{display:flex!important;align-items:stretch!important;gap:8px;flex:0 0 auto}
  .ch-actmenu .md-buka,
  .ch-actmenu .btn,
  .ch-actmenu button{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    height:36px!important;
    padding:0 14px!important;
    margin:0!important;
    font-size:.82rem!important;
    line-height:1!important;
    border-radius:9px!important;
    border:1px solid #e5e8f0!important;
    background:#fff!important;
    color:#334155!important;
    white-space:nowrap!important;
    box-shadow:none!important;
    vertical-align:middle!important;
    transition:background .15s;
  }
  .ch-actmenu .md-buka:hover,
  .ch-actmenu .btn:hover,
  .ch-actmenu button:hover{background:#f1f5f9!important}
  .ch-actmenu #chClearAll{
    border-color:#fecaca!important;
    color:#ef4444!important;
  }
  .ch-actmenu #chClearAll:hover{background:#fef2f2!important}
  .ch-actmenu i{margin-right:6px!important;font-size:.9em}
}
</style>

<script>
(function(){
  function fokusBalas(){
    if (location.hash !== '#balas') return;
    var inp = document.querySelector('#chInput, #chatInput, .ch-input textarea, .ch-input input[type=text]');
    if (!inp) { setTimeout(fokusBalas, 300); return; }
    inp.focus();
    try { inp.scrollIntoView({block:'end'}); } catch(e){}
    history.replaceState(null, '', location.pathname + location.search);
  }
  if (document.readyState === 'complete') setTimeout(fokusBalas, 500);
  else window.addEventListener('load', function(){ setTimeout(fokusBalas, 500); });
})();
</script>

<script>
(function(){
  var b = document.getElementById('chBack');
  if (!b) return;
  b.addEventListener('click', function(){
    document.body.classList.remove('chLockScroll');
    document.documentElement.style.removeProperty('--chTop');
    location.href = '<?= base_url() ?>kinerja/ringkasan?team=<?= intval($team_id) ?>';
  });
})();
</script>

<script>
(function(){
  var b = document.getElementById('chSideBack');
  if (!b) return;
  b.addEventListener('click', function(){
    var tutup = document.getElementById('btnTutupMem');
    if (tutup) { tutup.click(); return; }
    var side = document.querySelector('.ch-side');
    if (side) side.classList.remove('on', 'show', 'buka');
  });
})();
</script>
