<?php
$company_name = 'PT MONTERA SINERGI BERSAMA';
$employee_name = trim((string) ($slip['full_name'] ?? '-'));
$position_name = trim((string) ($slip['position_name'] ?? '-'));
$period_label = trim((string) ($slip['period_label'] ?? '-'));
$pay_date = !empty($slip['pay_date']) ? date('d M Y', strtotime($slip['pay_date'])) : '-';
$bank_name = trim((string) ($slip['bank_name'] ?? '-'));
$bank_account_number = trim((string) ($slip['bank_account_number'] ?? '-'));
$gross_amount = (float) ($slip['gross_amount'] ?? 0);
$deduction_amount = (float) ($slip['deduction_amount'] ?? 0);
$net_amount = (float) ($slip['net_amount'] ?? 0);

$period_month = '-';
$period_year = '-';
$title_period = $period_label;
if (!empty($slip['period_start'])) {
    $period_month = date('F', strtotime($slip['period_start']));
    $period_year = date('Y', strtotime($slip['period_start']));
} elseif ($period_label !== '-' && preg_match('/([A-Za-z]+)\s+(\d{4})/', $period_label, $matches)) {
    $period_month = $matches[1];
    $period_year = $matches[2];
}

if ($period_month !== '-' && $period_year !== '-') {
    $title_period = strtoupper($period_month . ' ' . $period_year);
} else {
    $title_period = strtoupper($period_label);
}

if (!function_exists('format_slip_qty')) {
    function format_slip_qty($qty)
    {
        $qty = (float) $qty;
        if ((int) $qty == $qty) {
            return number_format($qty, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Slip Gaji <?= htmlspecialchars($period_label) ?></title>
    <style>
        @page {
            margin: 10px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111111;
            line-height: 1.2;
        }

        .slip {
            border: 1px solid #222222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            border: 1px solid #222222;
            padding: 4px 5px;
            vertical-align: middle;
        }

        .no-border {
            border: 0;
        }

        .header-strip {
            height: 12px;
            background: #5d6673;
            border-bottom: 1px solid #222222;
        }

        .title {
            background: #d9d9d9;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.4px;
            color: #4e5968;
            padding: 6px 0;
        }

        .company-line {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            color: #444444;
            padding: 8px 0 10px;
            border-top: 0;
        }

        .label {
            width: 86px;
            background: #efefef;
            font-weight: 400;
        }

        .value {
            background: #f9f9f9;
        }

        .position-head {
            width: 84px;
            background: #efefef;
            text-align: center;
        }

        .position-value {
            background: #f9f9f9;
            text-align: center;
            font-size: 8.5px;
        }

        .section-head {
            background: #efefef;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .subtotal-label {
            font-weight: 700;
            text-decoration: underline;
        }

        .grand-total-row {
            background: #7a7a7a;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            text-align: center;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .small-note {
            font-size: 7.5px;
            color: #555555;
            padding: 5px 0 0;
        }
    </style>
</head>
<body>
    <div class="slip">
        <div class="header-strip"></div>

        <table>
            <tr>
                <td colspan="4" class="title">SLIP GAJI <?= htmlspecialchars($title_period) ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <td colspan="4" class="company-line"><?= htmlspecialchars($company_name) ?></td>
            </tr>
            <tr>
                <td class="label">Nama</td>
                <td class="value"><?= htmlspecialchars($employee_name) ?></td>
                <td class="position-head">Posisi</td>
                <td class="position-value"><?= htmlspecialchars($position_name) ?></td>
            </tr>
            <tr>
                <td class="label">Periode Gaji</td>
                <td class="value"><?= htmlspecialchars($period_label) ?></td>
                <td class="position-head">Tanggal Bayar</td>
                <td class="position-value"><?= htmlspecialchars($pay_date) ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <td colspan="2" class="section-head">PENDAPATAN</td>
                <td colspan="2" class="section-head">POTONGAN</td>
            </tr>
            <?php
            $max_rows = max(count($earning_items), count($deduction_items), 5);
            for ($i = 0; $i < $max_rows; $i++):
                $earning = $earning_items[$i] ?? null;
                $deduction = $deduction_items[$i] ?? null;
            ?>
                <tr>
                    <td style="width: 34%;">
                        <?php if ($earning): ?>
                            <?= htmlspecialchars($earning['component_name_snapshot']) ?>
                            <?php if (!empty($earning['qty']) || !empty($earning['unit_label'])): ?>
                                <span class="muted">(<?= htmlspecialchars(format_slip_qty($earning['qty'] ?? 0)) ?><?= !empty($earning['unit_label']) ? ' ' . htmlspecialchars($earning['unit_label']) : '' ?>)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="amount" style="width: 16%;"><?= $earning ? 'Rp' . number_format((float) $earning['amount'], 0, ',', '.') : '' ?></td>
                    <td style="width: 34%;"><?= $deduction ? htmlspecialchars($deduction['component_name_snapshot']) : '' ?></td>
                    <td class="amount" style="width: 16%;"><?= $deduction ? 'Rp' . number_format((float) $deduction['amount'], 0, ',', '.') : '' ?></td>
                </tr>
            <?php endfor; ?>
            <tr>
                <td class="subtotal-label">Total Pendapatan</td>
                <td class="amount">Rp<?= number_format($gross_amount, 0, ',', '.') ?></td>
                <td class="subtotal-label">Total Potongan</td>
                <td class="amount">Rp<?= number_format($deduction_amount, 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td colspan="4" class="grand-total-row">JUMLAH GAJI: Rp<?= number_format($net_amount, 0, ',', '.') ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="no-border small-note">
                    Bank: <?= htmlspecialchars($bank_name) ?> / <?= htmlspecialchars($bank_account_number) ?> | Digenerate otomatis pada <?= htmlspecialchars($generated_at) ?> WIB
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
