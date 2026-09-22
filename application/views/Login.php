<link rel="stylesheet" href="<?= base_url() ?>assets/css/auth-mobile.css?v=<?= @filemtime(FCPATH . 'assets/css/auth-mobile.css') ?>">
<div class="fullscreen-container">
  <div class="login-container text-start">
    <div class="login-logo">
      <img src="<?= base_url() ?>/assets/img/logo.png" style="height:200px; width:400px; object-fit:contain; margin-top: -20px;" alt="" class="">
    </div>
    <form action="<?= base_url() ?>/auth/login-process" id="form" method="POST" class="text-white" style="margin-top: -20px;">
       <div class="form-message"></div>
      <div class="col-lg-12 pt-4">
        <label for="" class="text-start">Username</label>
        <input name="email" type="text" class="form-control" placeholder="Masukkan username disini">
      </div>
      <div class="col-lg-12">
        <label for="">Password</label>
        <div class="div-icon">
          <div class="icon-right text-secondary" onclick="func_pass_1()">
            <i class="bi bi-eye-slash" id="show_eye_1" style="display: block;"></i>
            <i class="bi bi-eye" id="hide_eye_1" style="display: none;"></i>
          </div>
          <input name="password" type="password" class="form-control" placeholder="Masukkan password disini" id="password_1">
        </div>
      </div>
      <div class="col-lg-12 mt-2 text-end">
        <a href="javascript:void(0)" class="text-white small" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" style="text-decoration: underline;">Lupa password?</a>
      </div>
      <div class="col-lg-12 mt-4">
        <div class="row align-items-center">
          <div class="col-12">
            <button class="btn btn-send text-white w-100" style="background-color: #8666BC;">Masuk Sekarang</button>
          </div>
        </div>
      </div>
      
      <!-- Sign Up Link -->
      <div class="col-lg-12 mt-3 text-center">
        <small class="text-muted">
          Don't have an account? 
          <a href="<?= base_url() ?>auth/signup" class="text-white" style="text-decoration: underline;">Sign up here</a>
        </small>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Lupa Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= base_url() ?>forgot-password-process" id="forgot-password-form" method="POST">
        <div class="modal-body">
          <div class="forgot-password-message"></div>
          <div class="mb-3">
            <label class="form-label">Username atau Email</label>
            <input type="text" name="identifier" class="form-control" placeholder="Masukkan username atau email terdaftar">
            <small class="text-muted">Sistem akan mengirim password sementara ke email akun Anda.</small>
            <small class="text-muted d-block mt-1">Setelah menerima email, login memakai username akun Anda dan password sementara yang dikirim di email tersebut.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-forgot-password text-white" style="background-color: #8666BC;">Kirim Password Baru</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .fullscreen-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: url("<?php echo base_url('assets/img/bg-login.jpg'); ?>") no-repeat center center fixed;
    background-size: cover;
  }

  .login-container {
    background: linear-gradient(160deg, rgba(255,255,255,0.55) 0%, rgba(255,255,255,0.30) 100%);
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 20px;
    box-shadow: 0 20px 55px rgba(31, 38, 90, 0.20);
    padding: 50px;
    min-height: 50vh;
    min-width: 25vw;
  }

  @media (max-width: 480px) {
    .login-container {
      min-width: 330px;
    }
  }

  .login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 5px;
  }

  .login-logo img {
    width: 400px;
    height: 200px;
    object-fit: contain;
  }

  .modal-content {
    border-radius: 14px;
  }

  .login-container .text-muted {
    color: #3d3d3d !important;
    opacity: .9;
  }

  .login-container label,
  .login-container a { color: #2f2f4f !important; }
  .login-container .text-muted { color: #5a5a6e !important; opacity: 1; }

  .login-container label {
    color: #1f2340 !important;
    font-weight: 600;
    letter-spacing: .2px;
  }
  .login-container .text-muted { color: #3f4460 !important; opacity: 1; }
  .login-container a { color: #4b3fa7 !important; font-weight: 600; }
  .login-container .form-control {
    background: rgba(255,255,255,.92);
    border: 1px solid rgba(120,120,160,.25);
    border-radius: 10px;
    padding: 12px 14px;
  }
  .login-container .form-control:focus {
    border-color: #8666BC;
    box-shadow: 0 0 0 3px rgba(134,102,188,.18);
  }
  .login-container .btn-send {
    border-radius: 10px;
    padding: 12px;
    font-weight: 600;
    box-shadow: 0 8px 20px rgba(134,102,188,.35);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .login-container .btn-send:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(134,102,188,.45);
  }

  .login-container label {
    color: #1a1d3a !important;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(255,255,255,.85);
  }
  .login-container .text-muted {
    color: #2f3352 !important;
    opacity: 1;
    text-shadow: 0 1px 3px rgba(255,255,255,.85);
  }
  .login-container a {
    color: #4b3fa7 !important;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(255,255,255,.85);
  }
</style>


<script type="text/javascript">
  $("#form").submit(function() {
    var form = $(this);

    // Loading state langsung saat tombol ditekan.
    $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
    form.find(".form-message").slideUp().html("");

    // Absensi tidak lagi direkam saat login; cukup submit kredensial.
    doLoginSubmit(form);

    return false;
  });

  function doLoginSubmit(form) {
    var mydata = new FormData(form[0]);
    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: mydata,
      cache: false,
      contentType: false,
      processData: false,
      beforeSend: function() {
        $(".btn-send").addClass("disabled").html('<div class="loading-ellipsis"><div></div><div></div><div></div><div></div></div>').attr('disabled', true);
        form.find(".form-message").slideUp().html("");
      },
      success: function(response, textStatus, xhr) {
        var str = response;
        console.log(str);
        if (str.indexOf("success") != -1) {
          $(".form-message").hide().html(response).slideDown("fast");
          setTimeout(function() {
            // Get redirect URL from server
            $.ajax({
              url: "<?= base_url() ?>auth/get_redirect_url",
              type: "GET",
              dataType: "json",
              success: function(redirectResponse) {
                console.log("Redirect to:", redirectResponse.url);
                window.location.href = redirectResponse.url;
              },
              error: function() {
                // Fallback to homepage if redirect URL fetch fails
                window.location.href = "";
              }
            });
            $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
          }, 2500);
        } else {
          $(".form-message").hide().html(response).slideDown("fast");
          $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
        }
      },
      error: function(xhr, textStatus, errorThrown) {
        $(".btn-send").removeClass("disabled").html('Masuk Sekarang').attr('disabled', false);
        $(".form-message").hide().html(xhr).slideDown("fast");
      }
    });
  }
</script>
<script type="text/javascript">
  $("#forgot-password-form").submit(function() {
    var form = $(this);
    var mydata = new FormData(this);
    $.ajax({
      type: "POST",
      url: form.attr("action"),
      data: mydata,
      cache: false,
      contentType: false,
      processData: false,
      beforeSend: function() {
        $(".btn-forgot-password").addClass("disabled").html('Mengirim...').attr('disabled', true);
        form.find(".forgot-password-message").slideUp().html("");
      },
      success: function(response) {
        var str = response;
        form.find(".forgot-password-message").hide().html(response).slideDown("fast");
        $(".btn-forgot-password").removeClass("disabled").html('Kirim Password Baru').attr('disabled', false);

        if (str.indexOf("success") != -1) {
          setTimeout(function() {
            form[0].reset();
            var forgotPasswordModalEl = document.getElementById('forgotPasswordModal');
            var forgotPasswordModal = bootstrap.Modal.getInstance(forgotPasswordModalEl);
            if (forgotPasswordModal) {
              forgotPasswordModal.hide();
            }
            window.location.href = "<?= base_url() ?>auth/login";
          }, 1800);
        }
      },
      error: function(xhr) {
        form.find(".forgot-password-message").hide().html(xhr.responseText).slideDown("fast");
        $(".btn-forgot-password").removeClass("disabled").html('Kirim Password Baru').attr('disabled', false);
      }
    });
    return false;
  });
</script>
</section>
<script>
  function func_pass_1() {
    var x = document.getElementById("password_1");
    var show_eye_1 = document.getElementById("show_eye_1");
    var hide_eye_1 = document.getElementById("hide_eye_1");
    hide_eye_1.classList.remove("d-none");
    if (x.type === "password") {
      x.type = "text";
      show_eye_1.style.display = "none";
      hide_eye_1.style.display = "block";
    } else {
      x.type = "password";
      show_eye_1.style.display = "block";
      hide_eye_1.style.display = "none";
    }
  }

  function func_pass_2() {
    var y = document.getElementById("password_2");
    var show_eye_2 = document.getElementById("show_eye_2");
    var hide_eye_2 = document.getElementById("hide_eye_2");
    hide_eye_2.classList.remove("d-none");
    if (y.type === "password") {
      y.type = "text";
      show_eye_2.style.display = "none";
      hide_eye_2.style.display = "block";
    } else {
      y.type = "password";
      show_eye_2.style.display = "block";
      hide_eye_2.style.display = "none";
    }
  }

  function func_pass_3() {
    var z = document.getElementById("password_3");
    var show_eye_3 = document.getElementById("show_eye_3");
    var hide_eye_3 = document.getElementById("hide_eye_3");
    hide_eye_3.classList.remove("d-none");
    if (z.type === "password") {
      z.type = "text";
      show_eye_3.style.display = "none";
      hide_eye_3.style.display = "block";
    } else {
      z.type = "password";
      show_eye_3.style.display = "block";
      hide_eye_3.style.display = "none";
    }
  }
</script>
</body>

</html>

<?php if (($_GET['akun'] ?? '') === 'ditolak'): ?>
<div id="msgTolak" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:rgba(25,0,0,.9);overflow:hidden">
  <div id="msgTolakKartu" style="position:relative;z-index:2;max-width:400px;width:88%;background:#fff;border-radius:20px;padding:34px 24px 26px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div id="msgHati" style="font-size:72px;line-height:1">&#128148;</div>
    <div id="msgStempel" style="display:inline-block;margin-top:16px;padding:6px 22px;border:5px solid #dc2626;border-radius:10px;color:#dc2626;font-weight:900;font-size:2.2rem;letter-spacing:.15em;opacity:0">DITOLAK</div>
    <h3 style="margin:20px 0 6px;font-weight:800;color:#0f172a">Yahh... pendaftaranmu ditolak &#128524;</h3>
    <p style="margin:0;color:#64748b;line-height:1.55">Nice try. Admin tidak menyetujui akun ini, jadi akunnya sudah dihapus.<br>Kalau kamu memang karyawan Montera, hubungi HRD ya.</p>
    <button id="msgTolakTutup" style="margin-top:22px;padding:11px 28px;border:0;border-radius:999px;background:#0f172a;color:#fff;font-weight:700;cursor:pointer">Oke deh &#128532;</button>
  </div>
</div>
<script>
(function(){
  if (history.replaceState) history.replaceState(null, '', location.pathname);
  var lapis=document.getElementById('msgTolak');
  document.getElementById('msgStempel').animate(
    [{transform:'rotate(-12deg) scale(4)',opacity:0},{transform:'rotate(-12deg) scale(.9)',opacity:1,offset:.75},{transform:'rotate(-12deg) scale(1)',opacity:1}],
    {duration:500,delay:700,easing:'cubic-bezier(.5,0,.75,0)',fill:'forwards'});
  setTimeout(function(){
    document.getElementById('msgTolakKartu').animate(
      [{transform:'translateX(0)'},{transform:'translateX(-16px)'},{transform:'translateX(13px)'},{transform:'translateX(-9px)'},{transform:'translateX(6px)'},{transform:'translateX(0)'}],
      {duration:450});
  }, 1150);
  document.getElementById('msgHati').animate(
    [{transform:'scale(1)'},{transform:'scale(1.3)'},{transform:'scale(.85) rotate(-10deg)'},{transform:'scale(1) rotate(0)'}],
    {duration:900,iterations:2});
  var emoji=['\uD83D\uDE22','\uD83D\uDC94','\uD83D\uDE2D'];
  for(var i=0;i<28;i++){
    var e=document.createElement('div');
    e.textContent=emoji[i%3];
    e.style.cssText='position:absolute;top:-40px;left:'+(Math.random()*100)+'%;font-size:'+(18+Math.random()*18)+'px;opacity:.75;z-index:1;pointer-events:none';
    lapis.appendChild(e);
    e.animate([{transform:'translateY(0)'},{transform:'translateY('+(window.innerHeight+80)+'px)'}],
              {duration:4000+Math.random()*3000,delay:Math.random()*3000,iterations:Infinity});
  }
  document.getElementById('msgTolakTutup').onclick=function(){
    lapis.animate([{opacity:1},{opacity:0}],{duration:400,fill:'forwards'}).onfinish=function(){lapis.remove();};
  };
})();
</script>
<?php endif; ?>
