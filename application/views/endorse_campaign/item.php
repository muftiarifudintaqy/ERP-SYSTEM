<?php
$k = $start;
function separator_only($angka)
{
    $number = $angka;
    return number_format(round($number), 0, ',', '.');
}
function date_format_indo($date)
{
    $month = array(
        '',
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    if ($date) {
        $date = DATE('d', strtotime($date)) . ' ' . $month[intval(DATE('m', strtotime($date)))] . ' ' . DATE('Y', strtotime($date));
    } else {
        $date = '-';
    }
    return $date;
}
foreach ($data as $v) {

    if ($v['desc'] == "") {
        $v['desc'] = '-';
    }
?>
    <div class="card mb-3" style="padding-bottom:0px">
        <div class="row">
            <div class="col-lg-8">
                <p class="mt-0 mb-0"><a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse?id_campaign=<?= $v['id'] ?>">#<?= $k + 1 ?></a></p>
                <p class="mt-0 mb-1"><a class="mb-1 text-blue fw-700 fs-16 a-none" href="<?= base_url() ?>endorse?id_campaign=<?= $v['id'] ?>"><?= $v['title'] ?></a></p>
                <p class="mb-1 text-black">SPV : <?= $v['spv'] ? $v['spv'] : '-' ?></p>
                <p class="mb-1 text-black">PIC : <?= $v['pic'] ?></p>
                <p class="mb-1 text-black">Produk : <?= $v['product_text'] ? $v['product_text'] : '-' ?></p>
                <p class="mb-1 text-black">Keterangan : <?= $v['desc'] ?></p>
            </div>
            <div class="col-lg-4 text-lg-end text-start d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="campaign-status-label fs-13" id="status-label-<?= $v['id'] ?>" style="color:<?= $v['status'] == 'Aktif' ? '#52c41a' : '#aaa' ?>;font-weight:500;">
                        <?= $v['status'] == 'Aktif' ? 'Aktif' : 'Tidak Aktif' ?>
                    </span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input campaign-toggle" type="checkbox" role="switch"
                            id="toggle-<?= $v['id'] ?>"
                            data-id="<?= $v['id'] ?>"
                            <?= $v['status'] == 'Aktif' ? 'checked' : '' ?>
                            style="cursor:pointer;width:2.5em;height:1.3em;">
                    </div>
                </div>
                <a href="#!" onclick="remove('<?= $v['id'] ?>')" class="btn btn-delete mt-0 mb-2"><i class="bi bi-trash fs-16"></i> Delete</a>
                <a href="#!" onclick="edit('<?= $v['id'] ?>')" class="btn btn-edit mt-0 mb-2"><i class="bi bi-pencil-square fs-16"></i> Edit</a>
            </div>
            <div class="col-lg-12">
                <hr>
            </div>
            <div class="col-lg-12 pb-3">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Jumlah Influencer : <?= separator_only($v['count_influencer']) ?></p>
                        <p class="mb-1 text-black">Jumlah Endorse : <?= separator_only($v['count_endorse']) ?></p>
                        <p class="mb-1 text-black">Tgl Mulai : <?= date_format_indo($v['start_at']) ?></p>
                        <?php if (!empty($v['update_terbatas']) && intval($v['update_terbatas']) === 1): ?>
                            <p class="mb-1 text-black">Update Terbatas : <?= separator_only($v['update_batas_hari']) ?> hari</p>
                        <?php else: ?>
                            <p class="mb-1 text-black">Tgl Selesai : <?= date_format_indo($v['until_at']) ?></p>
                        <?php endif; ?>
                        <p class="mb-1 text-black">Status : <span id="status-badge-<?= $v['id'] ?>" class="badge" style="<?= $v['status'] == 'Aktif' ? 'background:#f6ffed;color:#52c41a;border:1px solid #b7eb8f;' : 'background:#f5f5f5;color:#aaa;border:1px solid #ddd;' ?>"><?= $v['status'] ?></span></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1 text-black">Likes : <?= separator_only($v['likes']) ?></p>
                        <p class="mb-1 text-black">Comments : <?= separator_only($v['comment']) ?></p>
                        <p class="mb-1 text-black">Save & Share : <?= separator_only($v['share_save']) ?></p>
                        <p class="mb-1 text-black fw-600">Views : <?= separator_only($v['views']) ?></p>
                        <p class="mb-1 text-black fw-600">CPM : <?= separator_only($v['cpm']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <?php
                        $id = $v['id'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'");
                        $a = $dat[0]['count'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'
                        AND status_endorse = 'Posted Content'
                        ");
                        $b = $dat[0]['count'];
                        $dat = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count
                        FROM endorse
                        WHERE id_campaign = '$id'
                        AND status_endorse = 'Reject'
                        ");
                        $c = $dat[0]['count'];

                        ?>
                        <p class="mb-1 text-black">Total Pengajuan Post : <?= separator_only($a) ?></p>
                        <p class="mb-1 text-black">Posted : <?= separator_only($b) ?></p>
                        <p class="mb-1 text-black">Rejected : <?= separator_only($c) ?></p>
                        <p class="mb-1 text-black">Summary : <?= separator_only($b) ?>/<?= separator_only($a - $c) ?></p>
                    </div>

                </div>
            </div>
        </div>
    </div>


<?php $k += 1;
} ?>
<script>
$(document).on('change', '.campaign-toggle', function() {
    var id = $(this).data('id');
    var isActive = $(this).is(':checked');
    var newStatus = isActive ? 'Aktif' : 'Tidak Aktif';
    var $toggle = $(this);

    $toggle.prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: '<?= base_url() ?>endorse-campaign/toggle-status',
        data: { id: id, status: newStatus },
        success: function(res) {
            if (res.success) {
                if (isActive) {
                    $('#status-label-' + id).text('Aktif').css('color', '#52c41a');
                    $('#status-badge-' + id).text('Aktif').attr('style', 'background:#f6ffed;color:#52c41a;border:1px solid #b7eb8f;');
                } else {
                    $('#status-label-' + id).text('Tidak Aktif').css('color', '#aaa');
                    $('#status-badge-' + id).text('Tidak Aktif').attr('style', 'background:#f5f5f5;color:#aaa;border:1px solid #ddd;');
                }
            } else {
                $toggle.prop('checked', !isActive);
                alert('Gagal mengubah status campaign.');
            }
        },
        error: function() {
            $toggle.prop('checked', !isActive);
            alert('Terjadi kesalahan, coba lagi.');
        },
        complete: function() {
            $toggle.prop('disabled', false);
        }
    });
});
</script>
