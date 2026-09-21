<div class="container-fluid py-3">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-check2-square me-2"></i>Approval Cuti</h5>
        </div>
        <div class="card-body">
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success">
                    <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger">
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>

            <h6 class="mt-3">Menunggu Leader</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Karyawan</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_leader)): ?>
                            <?php foreach ($pending_leader as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $row['user_name'] ?></td>
                                    <td><?= $row['leave_type_name'] ?></td>
                                    <td><?= date('d M Y', strtotime($row['start_date'])) ?> - <?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                    <td><?= intval($row['total_days']) ?> hari</td>
                                    <td class="d-flex gap-2">
                                        <form action="<?= base_url() ?>leave/approve?id=<?= $row['id'] ?>" method="POST">
                                            <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                        </form>
                                        <form action="<?= base_url() ?>leave/reject?id=<?= $row['id'] ?>" method="POST">
                                            <button class="btn btn-danger btn-sm" type="submit">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada approval leader.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h6>Menunggu HR</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Karyawan</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Durasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_hr)): ?>
                            <?php foreach ($pending_hr as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= $row['user_name'] ?></td>
                                    <td><?= $row['leave_type_name'] ?></td>
                                    <td><?= date('d M Y', strtotime($row['start_date'])) ?> - <?= date('d M Y', strtotime($row['end_date'])) ?></td>
                                    <td><?= intval($row['total_days']) ?> hari</td>
                                    <td class="d-flex gap-2">
                                        <form action="<?= base_url() ?>leave/approve?id=<?= $row['id'] ?>" method="POST">
                                            <button class="btn btn-success btn-sm" type="submit">Approve</button>
                                        </form>
                                        <form action="<?= base_url() ?>leave/reject?id=<?= $row['id'] ?>" method="POST">
                                            <button class="btn btn-danger btn-sm" type="submit">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada approval HR.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
