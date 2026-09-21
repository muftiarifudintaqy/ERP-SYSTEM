<style>
    .st-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
    .st-card{background:#fff;border:1px solid #f0f0f0;border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    .st-ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex:0 0 auto}
    .st-lbl{font-size:.68rem;color:#8c8c8c;text-transform:uppercase;letter-spacing:.5px;line-height:1.3}
    .st-val{font-size:1.5rem;font-weight:700;color:#1e293b;line-height:1.2}
    .fo-thumb{width:46px;height:46px;border-radius:8px;object-fit:cover;background:#f1f5f9;cursor:zoom-in;transition:.15s;border:1px solid #e8e8e8}
    .fo-thumb:hover{transform:scale(1.08);box-shadow:0 4px 12px rgba(0,0,0,.18)}
    .table tbody td.col-nama{white-space:normal;min-width:170px}
    .table tbody td.col-posisi{white-space:normal;min-width:130px}
    #fotoLightbox{position:fixed;inset:0;background:rgba(8,11,20,.92);z-index:99999;display:flex;align-items:center;justify-content:center;padding:30px;opacity:0;pointer-events:none;transition:opacity .2s;cursor:zoom-out}
    #fotoLightbox.on{opacity:1;pointer-events:auto}
    #fotoLightbox img{max-width:88vw;max-height:80vh;border-radius:12px;object-fit:contain;transform:scale(.93);transition:transform .22s cubic-bezier(.2,.9,.3,1.2);box-shadow:0 20px 60px rgba(0,0,0,.55)}
    #fotoLightbox.on img{transform:scale(1)}
    #fotoLbInfo{position:absolute;bottom:24px;left:0;right:0;text-align:center;color:#e2e8f0;font-size:.86rem}
    #fotoLbClose{position:absolute;top:20px;right:26px;background:rgba(255,255,255,.16);border:0;color:#fff;width:40px;height:40px;border-radius:50%;font-size:1.3rem;cursor:pointer;line-height:1}
    .fo-dist{display:block;font-size:.62rem;margin-top:3px;padding:1px 5px;border-radius:4px;text-align:center}
    .fo-ok{background:#f6ffed;color:#52c41a;border:1px solid #b7eb8f}
    .fo-warn{background:#fff2f0;color:#ff4d4f;border:1px solid #ffccc7}
    /* Ant Design-like Table Styling */
    /* daterangepicker-dikecualikan
       Pustaka daterangepicker memakai kelas .table untuk kalendernya,
       sehingga kalender ikut terkena aturan tabel absensi di bawah dan
       kolom tanggalnya saling menimpa. Aturan tabel di sini dibatasi
       hanya untuk tabel yang bukan bagian dari kalender. */
    .daterangepicker { z-index: 3000; }
    .daterangepicker td.active,
    .daterangepicker td.active:hover,
    .daterangepicker td.start-date,
    .daterangepicker td.end-date,
    .daterangepicker .ranges li.active,
    .daterangepicker .applyBtn {
        background-color: #1F4696 !important;
        border-color: #1F4696 !important;
        color: #fff !important;
    }
    .daterangepicker td.in-range {
        background-color: #E8EDF8 !important;
        color: #1F2937 !important;
    }
    .daterangepicker .table,
    .daterangepicker table {
        width: auto !important;
        table-layout: auto !important;
        border-collapse: collapse !important;
    }
    .daterangepicker td,
    .daterangepicker th {
        white-space: nowrap !important;
        min-width: 30px !important;
        width: 30px !important;
        height: 28px !important;
        padding: 2px !important;
        font-size: 13px !important;
        border: 0 !important;
        text-align: center !important;
        vertical-align: middle !important;
    }

    .table:not(.daterangepicker .table) {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
    }

    .table thead th {
        padding: 14px 14px !important;
        white-space: nowrap;
        background-color: #fafafa;
        color: rgba(0, 0, 0, 0.85);
        font-weight: 500;
        text-align: left;
        padding: 12px 8px;
        font-size: 14px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
    }

    .table tbody td {
        padding: 16px 14px !important;
        white-space: nowrap;
        font-size: 14px;
        color: rgba(0, 0, 0, 0.65);
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.3s ease;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: #fafafa;
    }

    /* Card Styling - Ant Design-like */
    .card {
        border-radius: 2px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
        margin-bottom: 16px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #f0f0f0;
        padding: 16px;
        height: 56px;
    }

    .card-body {
        padding: 16px;
    }

    /* Button Styling - Ant Design-like */
    .btn {
        border-radius: 2px;
        padding: 4px 15px;
        font-size: 14px;
        height: 32px;
        line-height: 1.5;
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
    }

    .btn-primary {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .btn-primary:hover {
        background-color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Badge Styling - Ant Design-like */
    .badge {
        font-size: 12px;
        height: 22px;
        padding: 0 8px;
        line-height: 22px;
        border-radius: 2px;
        font-weight: normal;
    }

    /* Validation pill (GPS / Device / IP) */
    .val-pill {
        display: inline-block;
        min-width: 36px;
        text-align: center;
        font-size: 11px;
        line-height: 18px;
        height: 18px;
        padding: 0 6px;
        border-radius: 2px;
        margin-right: 4px;
        font-weight: 500;
    }

    .val-ok {
        background-color: #f6ffed;
        color: #52c41a;
        border: 1px solid #b7eb8f;
    }

    .table tbody td.col-validasi{white-space:nowrap;text-align:center}
    .table tbody td.col-validasi .val-pill{margin:2px}
    .table thead th.th-center, .table tbody td.td-center{text-align:center}

    .badge-hadir{ display:inline-block; font-size:11.5px; font-weight:600; padding:2px 9px; border-radius:20px; line-height:18px; white-space:nowrap; }

    .val-flag {
        background-color: #fff1f0;
        color: #cf1322;
        border: 1px solid #ffa39e;
        font-weight: 700;
        cursor: help;
    }

    .val-na {
        background-color: #fafafa;
        color: rgba(0,0,0,0.35);
        border: 1px dashed #d9d9d9;
    }

    .val-pc {
        background-color: #f0f5ff;
        color: #2f54eb;
        border: 1px solid #adc6ff;
        font-weight: 600;
    }

    .val-no {
        background-color: #fff2f0;
        color: #ff4d4f;
        border: 1px solid #ffccc7;
    }

    /* Pagination Styling - Ant Design-like */
    .pagination {
        margin-top: 16px;
        justify-content: flex-end;
    }

    .page-item {
        margin-right: 8px;
    }

    .page-item:last-child {
        margin-right: 0;
    }

    .page-item.active .page-link {
        background-color: #1890ff;
        border-color: #1890ff;
    }

    .page-link {
        min-width: 32px;
        height: 32px;
        line-height: 30px;
        text-align: center;
        color: rgba(0, 0, 0, 0.65);
        border-radius: 2px;
        padding: 0;
        margin: 0;
        border: 1px solid #d9d9d9;
    }

    .page-link:hover {
        color: #40a9ff;
        border-color: #40a9ff;
    }

    /* Search Form Styling - Ant Design-like */
    .search-form {
        margin-bottom: 16px;
    }

    .form-control {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-control:hover {
        border-color: #40a9ff;
    }

    .form-control:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    .form-select {
        height: 32px;
        padding: 4px 11px;
        font-size: 14px;
        border: 1px solid #d9d9d9;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .form-select:hover {
        border-color: #40a9ff;
    }

    .form-select:focus {
        border-color: #40a9ff;
        box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
    }

    /* Alert Styling - Ant Design-like */
    .alert {
        padding: 8px 15px;
        border-radius: 2px;
        font-size: 14px;
    }

    .alert-info {
        background-color: #e6f7ff;
        border-color: #91d5ff;
        color: rgba(0, 0, 0, 0.65);
    }

    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 2px;
            overflow: hidden;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: rgba(0, 0, 0, 0.85);">Attendance Management</h5>
                <input type="text" id="rangeExport" class="form-control me-2"
                       style="width:200px;display:inline-block" readonly
                       title="Periode yang akan diunduh">
                <button class="btn btn-success me-2" id="btnExportBulan"><i class="bi bi-download me-1"></i>Export Excel</button>
                <a href="<?= base_url() ?>attendance/belum" class="btn btn-outline-danger btn-sm me-2"><i class="bi bi-person-dash"></i> Belum Absen</a>
                    <a href="<?= base_url() ?>attendance/face_list" class="btn btn-outline-primary me-2">
                    <i class="bi bi-person-bounding-box me-1"></i> Wajah Terdaftar
                </a>
                <a href="<?= base_url() ?>attendance/devices" class="btn btn-outline-primary">
                    <i class="bi bi-phone me-1"></i> Browser Absensi
                    <?php if (!empty($pending_device_count)): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= (int) $pending_device_count ?> pending</span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="st-row">
                <div class="st-card"><div class="st-ic" style="background:#eef2ff;color:#6366f1"><i class="bi bi-people"></i></div><div><div class="st-lbl">Total Karyawan</div><div class="st-val"><?= (int)$stat_total ?></div></div></div>
                <div class="st-card"><div class="st-ic" style="background:#f6ffed;color:#52c41a"><i class="bi bi-check2-circle"></i></div><div><div class="st-lbl">Hadir</div><div class="st-val" style="color:#389e0d"><?= (int)$stat_hadir ?></div></div></div>
                <div class="st-card"><div class="st-ic" style="background:#fffbe6;color:#faad14"><i class="bi bi-clock-history"></i></div><div><div class="st-lbl">Terlambat</div><div class="st-val" style="color:#d48806"><?= (int)$stat_telat ?></div></div></div>
                <div class="st-card"><div class="st-ic" style="background:#fff2f0;color:#ff4d4f"><i class="bi bi-exclamation-circle"></i></div><div><div class="st-lbl">Tidak Hadir</div><div class="st-val" style="color:#cf1322"><?= (int)$stat_tidak_hadir ?></div></div></div>
                <div class="st-card"><div class="st-ic" style="background:#fff0f6;color:#eb2f96"><i class="bi bi-person-bounding-box"></i></div><div><div class="st-lbl">Wajah Perlu Ditinjau</div><div class="st-val" style="color:#c41d7f"><?= (int)$stat_wajah_review ?></div></div></div>
            </div>
            <form action="" class="search-form">
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <div class="input-group" style="box-shadow: 0 2px 0 rgba(0,0,0,0.02);">
                            <input type="text" name="keyword" class="form-control" placeholder="Cari nama karyawan..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>"
                                style="border: 1px solid #d9d9d9; box-shadow: none; height: 32px; padding: 4px 11px; margin-right: 10px; border-radius: 2px;">
                            <button class="btn btn-primary" type="submit"
                                style="border-radius: 2px; height: 32px; padding: 0 15px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="unit_filter" class="form-select" onchange="this.form.submit()" title="Filter unit/divisi">
                            <option value="">Semua Unit</option>
                            <?php foreach (($units ?? []) as $u): ?>
                                <option value="<?= htmlspecialchars($u['department']) ?>" <?= (isset($_GET['unit_filter']) && $_GET['unit_filter'] == $u['department']) ? 'selected' : '' ?>><?= htmlspecialchars($u['department']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_filter" class="form-control" value="<?= isset($_GET['date_filter']) ? htmlspecialchars($_GET['date_filter']) : '' ?>"
                            onchange="this.form.submit()" title="Filter tanggal">
                    </div>
                    <div class="col-md-2">
                        <select name="status_filter" class="form-select" onchange="this.form.submit()" title="Filter status">
                            <option value="">Semua Status</option>
                            <option value="hadir" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'hadir') ? 'selected' : '' ?>>Hadir</option>
                            <option value="telat" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'telat') ? 'selected' : '' ?>>Telat</option>
                            <option value="alfa" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'alfa') ? 'selected' : '' ?>>Alfa</option>
                            <option value="izin" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'izin') ? 'selected' : '' ?>>Izin</option>
                            <option value="valid" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'valid') ? 'selected' : '' ?>>Valid</option>
                            <option value="rejected" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'rejected') ? 'selected' : '' ?>>Rejected</option>
                            <option value="counted" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'counted') ? 'selected' : '' ?>>Dihitung Hadir</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($notif)): ?>
                    <div class="alert alert-info" style="display: flex; align-items: center;">
                        <i class="bi bi-info-circle me-2"></i>
                        <span><?= strip_tags($notif) ?></span>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table class="table table-hover" id="attendance-table">
                    <thead>
                        <tr>
                            <th class="text-start">#</th>
                            <th class="text-start">Tanggal</th>
                            <th class="text-start">Nama Karyawan</th>
                            <th class="text-start">Posisi</th>
                            <th class="text-start">Jam Masuk</th>
                            <th class="text-start">Istirahat</th>
                            <th class="text-start">Msk Kembali</th>
                            <th class="text-start">Jam Pulang</th>
                            <th class="th-center">Validasi</th>
                            <th class="th-center">Foto</th>
                            <th class="th-center">Status</th>
                            <th class="th-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-tbody">
                        <!-- Content will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <?= $pagination ?>
            </div>
        </div>
    </div>
</div>

<div id="fotoLightbox">
    <button id="fotoLbClose">&times;</button>
    <img id="fotoLbImg" src="" alt="">
    <div id="fotoLbInfo"></div>
</div>

<script>
    (function(){
        var lb = document.getElementById('fotoLightbox');
        var im = document.getElementById('fotoLbImg');
        var inf = document.getElementById('fotoLbInfo');
        function tutup(){ lb.classList.remove('on'); setTimeout(function(){ im.src=''; }, 200); }
        $(document).on('click', '.fo-thumb', function(e){
            e.stopPropagation();
            im.src = this.src;
            inf.textContent = (this.dataset.nm || '') + (this.dataset.tg ? ' - ' + this.dataset.tg : '');
            requestAnimationFrame(function(){ lb.classList.add('on'); });
        });
        lb.addEventListener('click', tutup);
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') tutup(); });
    })();

    /**
     * Loads attendance data via AJAX
     */
    // muatDenganRentang
    // Rentang periode ikut dikirim ke tabel supaya yang terlihat di layar
    // sama persis dengan isi berkas yang diunduh. Sebelumnya kolom periode
    // hanya mengatur unduhan, sementara tabel tetap menampilkan semuanya.
    var tblDari = '', tblSampai = '';

    function rentangParam() {
        if (!tblDari || !tblSampai) return '';
        var pemisah = '<?= $param ?>'.indexOf('?') >= 0 ? '&' : '?';
        return pemisah + 'dari=' + tblDari + '&sampai=' + tblSampai;
    }

    function loadAttendanceData() {
        $.ajax({
            type: 'GET',
            url: "<?= base_url() ?>attendance/item<?= $param ?>" + rentangParam(),
            beforeSend: function() {
                $('#attendance-tbody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function(data) {
                $('#attendance-tbody').html(data);
            },
            error: function(xhr, status, error) {
                console.error('Error loading data:', error);
                $('#attendance-tbody').html('<tr><td colspan="8" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
            }
        });
    }

    // Initialize the page
    $(document).ready(function() {
        loadAttendanceData();

        var expDari   = '<?= preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['dari'] ?? '')) ? $_GET['dari'] : date('Y-m-01') ?>';
        var expSampai = '<?= preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_GET['sampai'] ?? '')) ? $_GET['sampai'] : date('Y-m-d') ?>';

        function tulisRange() {
            // daterangepicker berhenti mengisi kolomnya sendiri begitu
            // diberi callback, jadi tulisannya diurus di sini.
            $('#rangeExport').val(
                moment(expDari).format('DD/MM/YYYY') + ' - ' +
                moment(expSampai).format('DD/MM/YYYY')
            );
        }

        $('#rangeExport').daterangepicker({
            // Popup ditempelkan ke body supaya lepas dari .card-header
            // yang memakai d-flex; sebagai anak di sana lebarnya ditarik
            // sebagai item flex.
            parentEl: 'body',
            opens: 'left',
            startDate: moment(expDari),
            endDate:   moment(expSampai),
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Terapkan', cancelLabel: 'Batal',
                customRangeLabel: 'Pilih Sendiri',
                daysOfWeek: ['Mg','Sn','Sl','Rb','Km','Jm','Sb'],
                monthNames: ['Januari','Februari','Maret','April','Mei','Juni',
                             'Juli','Agustus','September','Oktober','November','Desember']
            },
            ranges: {
                'Hari Ini':        [moment(), moment()],
                'Kemarin':         [moment().subtract(1,'days'), moment().subtract(1,'days')],
                '7 Hari Terakhir': [moment().subtract(6,'days'), moment()],
                'Bulan Ini':       [moment().startOf('month'), moment()],
                'Bulan Lalu':      [moment().subtract(1,'month').startOf('month'),
                                    moment().subtract(1,'month').endOf('month')]
            }
        }, function(mulai, selesai){
            expDari   = mulai.format('YYYY-MM-DD');
            expSampai = selesai.format('YYYY-MM-DD');
            tulisRange();

            // rentang-lewat-url
            // Rentang ditaruh di URL halaman, bukan hanya di JavaScript.
            // Tautan halaman dibuat template->pagination() dari parameter
            // URL, jadi tanpa ini penyaring hilang begitu pindah halaman.
            // Sekalian membuat hitungan "N data ditemukan" ikut benar,
            // karena itu dihitung di index() saat halaman dimuat.
            var u = new URL(window.location.href);
            u.searchParams.set('dari', expDari);
            u.searchParams.set('sampai', expSampai);
            u.searchParams.delete('page');
            window.location.href = u.toString();
        });

        tulisRange();

        $('#btnExportBulan').on('click', function(){
            window.location.href = "<?= base_url() ?>attendance/export_bulanan?dari="
                                 + expDari + "&sampai=" + expSampai;
        });

        $(document).on('click', '.btn-hapus-absen', function(){
            var id = $(this).data('id'), nm = $(this).data('nm'), tg = $(this).data('tg');
            Swal.fire({
                icon:'warning', title:'Yakin hapus absensi ini?',
                html:'Absensi <b>'+nm+'</b> tanggal <b>'+tg+'</b> akan dihapus permanen.',
                showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Tidak',
                confirmButtonColor:'#ff4d4f', reverseButtons:true,
                showClass:{popup:'swal2-show'}, hideClass:{popup:'swal2-hide'}
            }).then(function(r){
                if (!r.isConfirmed) return;
                $.post("<?= base_url() ?>attendance/delete_attendance", {id:id}, function(x){
                    var o = (typeof x === 'string') ? JSON.parse(x) : x;
                    if (o.success) {
                        Swal.fire({icon:'success', title:'Terhapus', text:o.message, timer:1400, showConfirmButton:false});
                        loadAttendanceData();
                    } else { Swal.fire('Gagal', o.message, 'error'); }
                });
            });
        });

        $(document).on('click', '.btn-edit-jam', function(){
            var id = $(this).data('id');
            $.getJSON("<?= base_url() ?>attendance/attendance_detail?id=" + id, function(r){
                if (!r.success) { Swal.fire('Gagal', 'Data tidak ditemukan.', 'error'); return; }
                var d = r.data;
                function jam(v){ return v ? v.substring(11,16) : ''; }
                Swal.fire({
                    title: 'Edit Jam - ' + d.full_name,
                    html:
                      '<div style="text-align:left;font-size:.86rem">'
                      + '<label>Jam Masuk</label><input type="time" id="e_masuk" class="swal2-input" style="width:100%;margin:4px 0 10px" value="'+jam(d.check_in_at)+'">'
                      + '<label>Jam Istirahat</label><input type="time" id="e_ik" class="swal2-input" style="width:100%;margin:4px 0 10px" value="'+jam(d.break_out_at)+'">'
                      + '<label>Masuk Kembali</label><input type="time" id="e_im" class="swal2-input" style="width:100%;margin:4px 0 10px" value="'+jam(d.break_in_at)+'">'
                      + '<label>Jam Pulang</label><input type="time" id="e_pulang" class="swal2-input" style="width:100%;margin:4px 0 4px" value="'+jam(d.check_out_at)+'">'
                      + '<div style="font-size:.75rem;color:#8c8c8c;margin-top:6px">Kosongkan kolom untuk menghapus jam tersebut.</div>'
                      + '</div>',
                    showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal', confirmButtonColor: '#1890ff',
                    preConfirm: function(){
                        return {
                            masuk: document.getElementById('e_masuk').value,
                            istirahat_keluar: document.getElementById('e_ik').value,
                            istirahat_masuk: document.getElementById('e_im').value,
                            pulang: document.getElementById('e_pulang').value
                        };
                    }
                }).then(function(res){
                    if (!res.isConfirmed) return;
                    var v = res.value; v.id = id;
                    $.post("<?= base_url() ?>attendance/update_attendance_time", v, function(x){
                        var o = (typeof x === 'string') ? JSON.parse(x) : x;
                        if (o.success) { Swal.fire('Berhasil', o.message, 'success'); loadAttendanceData(); }
                        else { Swal.fire('Gagal', o.message, 'error'); }
                    });
                });
            });
        });
    });
</script>
