<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'vendor/autoload.php';
require_once APPPATH . 'core/BaseController.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class Booking_fbs extends BaseController
{
    private $per_page_options = [50, 100, 200, 500];
    private $allowed_sort_columns = [
        'create_time',
        'ship_by_date',
        'pickup_done_time',
        'booking_status',
        'match_status',
        'booking_sn',
        'order_sn',
        'tracking_number',
        'shipping_carrier',
        'recipient_city',
        'shop_id',
        'shop_name',
        'print_at',
        'rts_at'
    ];
    private $allowed_filter_fields = [
        'booking_sn',
        'order_sn',
        'tracking_number',
        'booking_status',
        'match_status',
        'shipping_carrier',
        'recipient_name',
        'recipient_phone',
        'recipient_city',
        'recipient_state',
        'recipient_region',
        'region',
        'shop_id',
        'shop_name',
        'print_at',
        'rts_at'
    ];
    private $date_field_map = [
        'booking' => 'create_time',
        'pickup' => 'pickup_done_time',
        'deadline' => 'ship_by_date'
    ];

    private function hasShippingPrintHistoryTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }
        $result = $this->db->query("SHOW TABLES LIKE 'booking_fbs_print_history'");
        $hasTable = $result && $result->num_rows() > 0;
        return $hasTable;
    }

    private function saveShippingPrintHistory(string $payload, string $printUrl, string $marketplace, int $itemCount): void
    {
        if (!$this->hasShippingPrintHistoryTable()) {
            return;
        }

        $user = $this->session->userdata('user');
        $userId = $user['id'] ?? 0;
        $payloadHash = sha1($payload);

        $exists = $this->db
            ->select('id')
            ->from('booking_fbs_print_history')
            ->where('print_url', $printUrl)
            ->where('payload_hash', $payloadHash)
            ->limit(1)
            ->get()
            ->row_array();

        if (!empty($exists)) {
            return;
        }

        $data = [
            'print_url' => $printUrl,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'marketplace' => $marketplace,
            'item_count' => $itemCount,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId,
        ];

        $this->db->insert('booking_fbs_print_history', $data);
    }

    private function getShippingPrintHistory(string $printUrl, int $limit = 30): array
    {
        if (!$this->hasShippingPrintHistoryTable()) {
            return [];
        }

        $todayEnd = date('Y-m-d 23:59:59');
        $rows = $this->db
            ->select('id, marketplace, item_count, created_at, payload_hash')
            ->from('booking_fbs_print_history')
            ->where('print_url', $printUrl)
            ->where('created_at <=', $todayEnd)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        $seen = [];
        $uniqueRows = [];
        foreach ($rows as $row) {
            $hash = $row['payload_hash'] ?? '';
            if ($hash === '') {
                $hash = sha1($printUrl . '|' . ($row['created_at'] ?? ''));
            }
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $uniqueRows[] = $row;
        }

        foreach ($uniqueRows as &$row) {
            $labelParts = [];
            if (!empty($row['created_at'])) {
                $labelParts[] = date('d M Y H:i', strtotime($row['created_at']));
            }
            if (!empty($row['marketplace'])) {
                $labelParts[] = $row['marketplace'];
            }
            if (!empty($row['item_count'])) {
                $labelParts[] = $row['item_count'] . ' booking';
            }
            $row['label'] = $labelParts ? implode(' | ', $labelParts) : 'History Cetak';
        }
        unset($row);

        return $uniqueRows;
    }

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
    }

    public function index()
    {
        $data['template'] = $this->template;
        $data['title'] = 'Booking FBS Orders - ' . $this->template->title();

        $start_date = $this->normalize_date($this->input->get('start_date'), date('Y-m-01'));
        $until_date = $this->normalize_date($this->input->get('until_date'), date('Y-m-d'));
        [$date_type, $date_field] = $this->resolve_date_filter();
        $booking_status = $this->input->get('booking_status') ?? '';
        $print_status = strtolower(trim((string)($this->input->get('print_status') ?? '')));
        if (!in_array($print_status, ['printed', 'not_printed'], true)) {
            $print_status = '';
        }
        $keyword = trim($this->input->get('keyword') ?? '');
        $keyword_field = $this->input->get('keyword_field') ?? 'booking_sn';
        $limit = (int) ($this->input->get('limit') ?? 50);
        if (!in_array($limit, $this->per_page_options, true)) {
            $limit = 50;
        }
        $page = max(1, (int) ($this->input->get('page') ?? 1));
        $offset = ($page - 1) * $limit;

        $base_conditions = $this->build_base_filters($start_date, $until_date, $keyword, $keyword_field, $date_field);
        $print_condition = $this->build_print_status_condition($print_status);
        if ($print_condition) {
            $base_conditions[] = $print_condition;
        }
        $conditions = $base_conditions;

        if ($booking_status !== '') {
            $conditions[] = "booking_status = " . $this->db->escape($booking_status);
        }

        $serverFilters = $this->build_server_filters();
        if (!empty($serverFilters)) {
            $conditions = array_merge($conditions, $serverFilters);
        }

        $where_sql = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        $status_where = !empty($base_conditions) ? implode(' AND ', $base_conditions) : '1=1';

        $sort_column = $this->allowed_sort_columns[0];
        $requested_sort = $this->input->get('sort_column');
        if ($requested_sort && in_array($requested_sort, $this->allowed_sort_columns, true)) {
            $sort_column = $requested_sort;
        }
        $sort_order = strtoupper($this->input->get('sort_order')) === 'ASC' ? 'ASC' : 'DESC';

        $count_query = $this->db->query("SELECT COUNT(*) AS total FROM booking_fbs_orders WHERE {$where_sql}");
        $total_rows = (int) ($count_query->row_array()['total'] ?? 0);
        $page_count = $total_rows > 0 ? (int) ceil($total_rows / $limit) : 1;

        $query = $this->db->query("
            SELECT *
            FROM booking_fbs_orders
            WHERE {$where_sql}
            ORDER BY {$sort_column} {$sort_order}, booking_sn DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $records = $query->result_array();
        $formatted_rows = array_map([$this, 'format_row'], $records);

        $status_rows = $this->db->query("
            SELECT DISTINCT booking_status 
            FROM booking_fbs_orders
            WHERE {$status_where}
            ORDER BY booking_status ASC
        ")->result_array();
        $available_statuses = array_values(array_filter(array_column($status_rows, 'booking_status')));

        $meta = [
            'total' => $total_rows,
            'page' => $page,
            'page_count' => $page_count,
            'per_page' => $limit,
            'sort_column' => $sort_column,
            'sort_order' => $sort_order
        ];

        $paginationBaseUrl = base_url('booking-fbs') . $this->template->get_param_without('page');
        $paginationBaseUrl = preg_replace('/\?&/', '?', $paginationBaseUrl);
        $paginationHtml = $this->template->pagination($page_count, $page, $paginationBaseUrl);

        if ($this->wants_json()) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => true,
                    'rows' => $formatted_rows,
                    'meta' => $meta,
                    'pagination' => $paginationHtml
                ]));
            return;
        }

        $data['pagination'] = $paginationHtml;
        $data['booking_status'] = $booking_status;
        $data['available_statuses'] = $available_statuses;
        $data['print_status'] = $print_status;
        $data['keyword'] = $keyword;
        $data['keyword_field'] = $keyword_field;
        $data['start_date'] = $start_date;
        $data['until_date'] = $until_date;
        $data['limit'] = $limit;
        $data['date_type'] = $date_type;
        $data['per_page_options'] = $this->per_page_options;
        $data['current_page'] = $page;
        $data['page_count'] = $page_count;
        $data['total_rows'] = $total_rows;
        $data['bookings'] = $formatted_rows;
        $data['print_history'] = $this->getShippingPrintHistory('booking-fbs/print-shipping-docs-shopee', 30);

        $data['content'] = $this->load->view('booking_fbs/all', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function download_excel()
    {
        $start_date = $this->normalize_date($this->input->get('start_date'), date('Y-m-01'));
        $until_date = $this->normalize_date($this->input->get('until_date'), date('Y-m-d'));
        [$date_type, $date_field] = $this->resolve_date_filter();
        $booking_status = $this->input->get('booking_status') ?? '';
        $print_status = strtolower(trim((string)($this->input->get('print_status') ?? '')));
        if (!in_array($print_status, ['printed', 'not_printed'], true)) {
            $print_status = '';
        }
        $keyword = trim($this->input->get('keyword') ?? '');
        $keyword_field = $this->input->get('keyword_field') ?? 'booking_sn';

        $conditions = $this->build_base_filters($start_date, $until_date, $keyword, $keyword_field, $date_field);
        $print_condition = $this->build_print_status_condition($print_status);
        if ($print_condition) {
            $conditions[] = $print_condition;
        }
        if ($booking_status !== '') {
            $conditions[] = "booking_status = " . $this->db->escape($booking_status);
        }

        $serverFilters = $this->build_server_filters();
        if (!empty($serverFilters)) {
            $conditions = array_merge($conditions, $serverFilters);
        }

        $where_sql = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        $sort_column = $this->allowed_sort_columns[0];
        $requested_sort = $this->input->get('sort_column');
        if ($requested_sort && in_array($requested_sort, $this->allowed_sort_columns, true)) {
            $sort_column = $requested_sort;
        }
        $sort_order = strtoupper($this->input->get('sort_order')) === 'ASC' ? 'ASC' : 'DESC';

        $query = $this->db->query("
            SELECT booking_sn, booking_status, shipping_carrier, tracking_number, create_time,
                   ship_by_date, recipient_name, recipient_phone, recipient_city,
                   recipient_state, shop_name, items_json
            FROM booking_fbs_orders
            WHERE {$where_sql}
            ORDER BY {$sort_column} {$sort_order}, booking_sn DESC
        ");
        $rows = array_map([$this, 'format_row'], $query->result_array());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Booking FBS');

        $productRows = $this->db
            ->select('id, sku, sub_name')
            ->from('product')
            ->order_by('brand ASC, sub_name ASC')
            ->get()
            ->result_array();

        $productSkuMap = [];
        $productIdMap = [];
        $productHeaders = [];
        $productList = [];
        foreach ($productRows as $prod) {
            $pid = (int) ($prod['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $productList[] = $prod;
            $productIdMap[$pid] = $prod;
            $skuRaw = (string) ($prod['sku'] ?? '');
            if ($skuRaw !== '') {
                $key1 = $this->normalize_sku_key($skuRaw);
                if ($key1 !== '') {
                    $productSkuMap[$key1] = $pid;
                }
                $key2 = $this->normalize_sku_key(str_replace('-', '', $skuRaw));
                if ($key2 !== '' && !isset($productSkuMap[$key2])) {
                    $productSkuMap[$key2] = $pid;
                }
            }
            $productHeaders[] = strtoupper(trim((string) ($prod['sub_name'] ?? $skuRaw ?: 'PRODUCT ' . $pid)));
        }

        $headers = [
            'Booking SN',
            'Status Booking',
            'Kurir',
            'No Resi',
            'Toko',
            'Tanggal Booking',
            'Tenggat Pengiriman',
            'Nama Penerima',
            'Telepon',
            'Kota',
            'Provinsi',
            'Produk',
        ];
        $allHeaders = array_merge($headers, $productHeaders);
        $baseColumnCount = count($headers);
        $productStartCol = $baseColumnCount + 1;
        $sheet->fromArray($allHeaders, null, 'A1');
        $baseHeaderRange = 'A1:' . Coordinate::stringFromColumnIndex($baseColumnCount) . '1';
        $sheet->getStyle($baseHeaderRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('dcdcdb');
        if (!empty($productHeaders)) {
            $prodHeaderRange = Coordinate::stringFromColumnIndex($productStartCol) . '1:' . Coordinate::stringFromColumnIndex($productStartCol + count($productHeaders) - 1) . '1';
            $sheet->getStyle($prodHeaderRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('ffff00');
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            $items = $row['items'] ?? [];
            $productQtys = $this->extract_product_quantities($items, $productSkuMap, $productIdMap);
            $itemsCount = array_sum($productQtys);
            $sheet->setCellValueExplicit("A{$rowNum}", (string)($row['booking_sn'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$rowNum}", $row['booking_status'] ?? '');
            $sheet->setCellValue("C{$rowNum}", $row['shipping_carrier'] ?? '');
            $sheet->setCellValue("D{$rowNum}", $row['tracking_number'] ?? '');
            $sheet->setCellValue("E{$rowNum}", $row['shop_name'] ?? '');
            $sheet->setCellValue("F{$rowNum}", $row['create_time'] ?? '');
            $sheet->setCellValue("G{$rowNum}", $row['ship_by_date'] ?? '');
            $sheet->setCellValue("H{$rowNum}", $row['recipient_name'] ?? '');
            $sheet->setCellValueExplicit("I{$rowNum}", (string)($row['recipient_phone'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("J{$rowNum}", $row['recipient_city'] ?? '');
            $sheet->setCellValue("K{$rowNum}", $row['recipient_state'] ?? '');
            $sheet->setCellValue("L{$rowNum}", $row['items_summary'] ?? '');

            foreach ($productHeaders as $idx => $header) {
                $pid = $productList[$idx]['id'] ?? null;
                $val = '';
                if ($pid !== null && isset($productQtys[$pid])) {
                    $val = $productQtys[$pid];
                }
                $colLetter = Coordinate::stringFromColumnIndex($productStartCol + $idx);
                $sheet->setCellValue($colLetter . $rowNum, $val === '' ? null : $val);
            }

            $rowNum++;
        }

        $lastColIndex = $baseColumnCount + count($productHeaders);
        for ($c = 1; $c <= $lastColIndex; $c++) {
            $colName = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colName)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $filename = sprintf(
            'booking_fbs_%s_sampai_%s.xlsx',
            $start_date ?: date('Y-m-d'),
            $until_date ?: date('Y-m-d')
        );

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function refresh_tracking()
    {
        $booking_check_url = rtrim($this->template->endpoint_url(), '/') . "/api/booking/shopee/refresh-tracking";
        $ch_check = curl_init();
        curl_setopt_array($ch_check, [
            CURLOPT_URL => $booking_check_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch_check);
        $curlError = curl_error($ch_check);
        $statusCode = (int) curl_getinfo($ch_check, CURLINFO_HTTP_CODE);
        curl_close($ch_check);

        $ok = !$curlError && $statusCode >= 200 && $statusCode < 300;
        $payload = [
            'ok' => $ok,
            'status' => $statusCode ?: null,
            'message' => $ok
                ? 'Permintaan refresh no resi telah dikirim.'
                : ($curlError ?: 'Gagal mengirim permintaan refresh no resi.'),
        ];

        if (!$ok && $response) {
            $payload['response'] = $response;
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($ok ? 200 : 500)
            ->set_output(json_encode($payload));
    }

    private function normalize_date($value, $default)
    {
        if (!$value) {
            return $default;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt ? $dt->format('Y-m-d') : $default;
    }

    private function build_base_filters($start_date, $until_date, $keyword, $keyword_field, $date_field = 'create_time')
    {
        $filters = [];
        $filters[] = "DATE({$date_field}) >= " . $this->db->escape($start_date);
        $filters[] = "DATE({$date_field}) <= " . $this->db->escape($until_date);

        $allowedKeywordFields = ['booking_sn', 'order_sn', 'recipient_name', 'recipient_phone', 'items'];
        if ($keyword !== '' && $keyword_field === 'items') {
            $escaped = $this->db->escape_like_str($keyword);
            $filters[] = "items_json LIKE '%{$escaped}%' ESCAPE '!'";
        } elseif ($keyword !== '' && in_array($keyword_field, $allowedKeywordFields, true)) {
            $escaped = $this->db->escape_like_str($keyword);
            $filters[] = "{$keyword_field} LIKE '%{$escaped}%' ESCAPE '!'";
        } elseif ($keyword !== '') {
            $escaped = $this->db->escape_like_str($keyword);
            $filters[] = "("
                . "booking_sn LIKE '%{$escaped}%' ESCAPE '!'"
                . " OR order_sn LIKE '%{$escaped}%' ESCAPE '!'"
                . " OR recipient_name LIKE '%{$escaped}%' ESCAPE '!'"
                . " OR recipient_phone LIKE '%{$escaped}%' ESCAPE '!'"
                . " OR items_json LIKE '%{$escaped}%' ESCAPE '!'"
                . " OR recipient_full_address LIKE '%{$escaped}%' ESCAPE '!'"
                . ")";
        }

        return $filters;
    }

    private function build_print_status_condition(string $print_status = '')
    {
        if ($print_status === 'printed') {
            return "(print_at IS NOT NULL AND print_at <> '' AND print_at <> '0000-00-00 00:00:00')";
        }

        if ($print_status === 'not_printed') {
            return "(print_at IS NULL OR print_at = '' OR print_at = '0000-00-00 00:00:00')";
        }

        return null;
    }

    public function filter_values()
    {
        $field = trim((string) $this->input->get('field'));
        if (!$field || !in_array($field, $this->allowed_filter_fields, true)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode(['ok' => false, 'error' => 'Field tidak valid']));
        }

        $start_date = $this->normalize_date($this->input->get('start_date'), date('Y-m-01'));
        $until_date = $this->normalize_date($this->input->get('until_date'), date('Y-m-d'));
        [$date_type, $date_field] = $this->resolve_date_filter();
        $booking_status = $this->input->get('booking_status') ?? '';
        $keyword = trim($this->input->get('keyword') ?? '');
        $keyword_field = $this->input->get('keyword_field') ?? 'booking_sn';
        $print_status = strtolower(trim((string)($this->input->get('print_status') ?? '')));
        if (!in_array($print_status, ['printed', 'not_printed'], true)) {
            $print_status = '';
        }

        $conditions = $this->build_base_filters($start_date, $until_date, $keyword, $keyword_field, $date_field);
        $print_condition = $this->build_print_status_condition($print_status);
        if ($print_condition) {
            $conditions[] = $print_condition;
        }
        if ($booking_status !== '') {
            $conditions[] = "booking_status = " . $this->db->escape($booking_status);
        }

        $serverFilters = $this->build_server_filters($field);
        if (!empty($serverFilters)) {
            $conditions = array_merge($conditions, $serverFilters);
        }

        $where_sql = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        $query = $this->db->query("
            SELECT DISTINCT {$field} AS val
            FROM booking_fbs_orders
            WHERE {$where_sql}
            ORDER BY val ASC
            LIMIT 200
        ");

        $values = [];
        foreach ($query->result_array() as $row) {
            $val = $row['val'];
            if ($val === null || $val === '') {
                $values[] = '-';
            } else {
                $values[] = (string) $val;
            }
        }

        $values = array_values(array_unique($values));

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'values' => $values]));
    }

    private function build_server_filters($ignoreField = null)
    {
        $filters = [];
        $fields = $this->input->get('filter_field');
        $values = $this->input->get('filter_value');
        $operators = $this->input->get('filter_operator');

        if (!is_array($fields) || !is_array($values)) {
            return $filters;
        }

        $count = min(count($fields), count($values));
        for ($i = 0; $i < $count; $i++) {
            $field = trim($fields[$i]);
            if (!$field || !in_array($field, $this->allowed_filter_fields, true)) {
                continue;
            }
            if ($ignoreField !== null && $field === $ignoreField) {
                continue;
            }

            $value = isset($values[$i]) ? trim($values[$i]) : '';
            $operator = isset($operators[$i]) ? strtolower(trim($operators[$i])) : 'contains';
            $value = $this->db->escape_like_str($value);

            if ($value === '') {
                continue;
            }

            if ($value === '-') {
                $filters[] = "({$field} IS NULL OR {$field} = '' OR {$field} = '-')";
                continue;
            }

            if ($operator === 'equals') {
                $filters[] = "{$field} = '" . $this->db->escape_str($value) . "'";
            } elseif ($operator === 'startsWith') {
                $filters[] = "{$field} LIKE '{$value}%' ESCAPE '!'";
            } elseif ($operator === 'endsWith') {
                $filters[] = "{$field} LIKE '%{$value}' ESCAPE '!'";
            } else {
                $filters[] = "{$field} LIKE '%{$value}%' ESCAPE '!'";
            }
        }

        return $filters;
    }

    private function format_row($row)
    {
        $items = [];
        if (!empty($row['items_json'])) {
            $decoded = json_decode($row['items_json'], true);
            if (is_array($decoded)) {
                $items = array_values(array_filter($decoded, 'is_array'));
            }
        }

        $summaryParts = [];
        foreach ($items as $idx => $entry) {
            $qty = isset($entry['qty']) ? (int) $entry['qty'] : 0;
            $skuParent = trim((string) ($entry['sku_parent'] ?? ''));
            $skuChild = trim((string) ($entry['sku'] ?? ''));
            $nameParent = trim((string) ($entry['name_parent'] ?? ''));
            $nameChild = trim((string) ($entry['name'] ?? ''));

            $displayName = $skuParent ?: ($skuChild ?: ($nameParent ?: $nameChild));
            if ($displayName) {
                $summaryParts[] = ($qty > 0 ? $qty . 'x ' : '') . $displayName;
            }

            $items[$idx]['display_name'] = $displayName;
            $items[$idx]['display_qty'] = $qty;
            $items[$idx]['display_sku'] = $skuParent ?: $skuChild;
            $items[$idx]['display_fallback_name'] = $nameParent ?: $nameChild;
        }

        $row['items'] = $items;
        $row['items_count'] = count($items);
        $row['items_summary'] = implode(' | ', array_slice($summaryParts, 0, 4));
        $row['create_time'] = $this->format_datetime($row['create_time'] ?? null);
        $row['update_time'] = $this->format_datetime($row['update_time'] ?? null);
        $row['ship_by_date'] = $this->format_datetime($row['ship_by_date'] ?? null);
        $row['pickup_done_time'] = $this->format_datetime($row['pickup_done_time'] ?? null);
        $row['rts_at'] = $this->format_datetime($row['rts_at'] ?? null);

        return $row;
    }

    private function format_datetime($value)
    {
        if (empty($value)) {
            return null;
        }
        $ts = strtotime($value);
        if (!$ts) {
            return $value;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function wants_json()
    {
        $accept = $this->input->get_request_header('Accept');
        return $this->input->is_ajax_request() ||
            ($accept && stripos($accept, 'application/json') !== false);
    }

    private function resolve_date_filter()
    {
        $requested = strtolower((string) $this->input->get('date_type'));
        if (!$requested) {
            $requested = 'booking';
        }

        if (!array_key_exists($requested, $this->date_field_map)) {
            $requested = 'booking';
        }

        return [$requested, $this->date_field_map[$requested]];
    }

    public function booking_shipping_process()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        if (!$id_selected) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
            return;
        }

        $id = explode(',', $id_selected);
        
        $rows = $this->db->select('id, booking_sn, shop_id, shop_name')
            ->from('booking_fbs_orders')
            ->where_in('booking_sn', $id)
            ->get()
            ->result_array();

        if (empty($rows)) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada data marketplace yang valid']);
            return;
        }

        $shops = [];
        foreach ($rows as $row) {
            $shopId = trim($row['shop_id'] ?? '');
            $shopName = trim($row['shop_name'] ?? 'Shop ' . $shopId);
            $bookingSn = trim($row['booking_sn'] ?? '');
            $bookingId = (int) ($row['id'] ?? 0);
            
            if ($shopId && $bookingSn) {
                if (!isset($shops[$shopId])) {
                    $shops[$shopId] = [
                        'shop_name' => $shopName,
                        'orders' => [],
                        'selected_ids' => []
                    ];
                }
                $shops[$shopId]['orders'][] = $bookingSn;
                if ($bookingId) {
                    $shops[$shopId]['selected_ids'][] = $bookingId;
                }
            }
        }

        $data['shops'] = $shops;
        $data['id_selected'] = $id_selected;
        
        $this->load->view("booking_fbs/shipping_process", $data);
    }

    public function shipping_process_execute()
    {
        $id_selected = trim((string) ($this->input->post('id_selected') ?? ''));
        $shop_id = trim((string) ($this->input->post('shop_id') ?? ''));
        $pickup_address_id = trim((string) ($this->input->post('pickup_address_id') ?? ''));
        $pickup_time_id = trim((string) ($this->input->post('pickup_time_id') ?? ''));

        if ($id_selected === '' || $shop_id === '') {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        if ($pickup_address_id === '' || $pickup_time_id === '') {
            echo json_encode(['status' => false, 'message' => 'Alamat dan waktu pickup wajib dipilih']);
            return;
        }

        $selectedIds = array_values(array_filter(array_map('trim', explode(',', $id_selected))));
        if (empty($selectedIds)) {
            echo json_encode(['status' => false, 'message' => 'Data booking tidak valid']);
            return;
        }

        $bookingRows = $this->db->select('id, booking_sn, shop_id, shop_name')
            ->from('booking_fbs_orders')
            ->where_in('booking_sn', $selectedIds)
            ->where('shop_id', $shop_id)
            ->get()
            ->result_array();

        if (empty($bookingRows)) {
            echo json_encode(['status' => false, 'message' => 'Booking tidak ditemukan untuk toko ini']);
            return;
        }

        $bookingSnList = [];
        $shopName = $bookingRows[0]['shop_name'] ?? ('Shop ' . $shop_id);

        foreach ($bookingRows as $row) {
            $bookingSn = trim((string) ($row['booking_sn'] ?? ''));
            if ($bookingSn === '') {
                continue;
            }
            $bookingSnList[] = $bookingSn;
        }

        $bookingSnList = array_values(array_unique($bookingSnList));
        if (empty($bookingSnList)) {
            echo json_encode(['status' => false, 'message' => 'Booking tidak memiliki booking_sn yang valid']);
            return;
        }

        $payload = [
            'shop_id' => $shop_id,
            'booking_ids' => implode(',', $selectedIds),
            'booking_sns' => implode(',', $bookingSnList),
            'pickup_address_id' => $pickup_address_id,
            'pickup_time_id' => $pickup_time_id
        ];

        $response = $this->call_marketplace_api('api/shopee_ship_booking_bulk', $payload);
        if (!is_array($response)) {
            echo json_encode(['status' => false, 'message' => 'Respon API tidak valid']);
            return;
        }

        if (!empty($response['status'])) {
            $response = $this->prepareDocumentPrintResponse($response, 'SHOPEE');
        }

        $isSuccess = !empty($response['status']) && $response['status'] === true;
        $apiSummary = isset($response['summary']) && is_array($response['summary']) ? $response['summary'] : [];
        $results = isset($response['results']) && is_array($response['results']) ? $response['results'] : [];

        $notes = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $bookingSn = $result['booking_sn'] ?? '';
                $label = $bookingSn ?: 'Booking';
                $statusIcon = !empty($result['status']) ? '✅' : '❌';
                $notes[] = trim(sprintf(
                    '%s %s %s',
                    $statusIcon,
                    $label ?: 'Order',
                    $result['message'] ?? ''
                ));
            }
        } elseif (!empty($response['message'])) {
            $notes[] = $response['message'];
        }

        $totalOrders = count($bookingSnList ?? []);
        $successOrders = (int) ($apiSummary['success'] ?? 0);
        $failedOrders = (int) ($apiSummary['failed'] ?? 0);

        if (!empty($apiSummary)) {
            $successOrders = (int) ($apiSummary['success'] ?? 0);
            $failedOrders = (int) ($apiSummary['failed'] ?? 0);
        } else {
            $successOrders = $isSuccess ? $totalOrders : 0;
            $failedOrders = $isSuccess ? 0 : $totalOrders;
        }

        $summary = [
            'total_shops' => 1,
            'success' => $isSuccess ? 1 : 0,
            'failed' => $isSuccess ? 0 : 1,
            'orders_total' => $totalOrders,
            'orders_success' => $successOrders,
            'orders_failed' => $failedOrders,
            'shop_id' => $shop_id,
            'shop_name' => $shopName ?? ''
        ];

        $finalResponse = [
            'status' => $isSuccess,
            'message' => $response['message'] ?? ($isSuccess ? 'Berhasil memproses booking Shopee' : 'Gagal memproses booking Shopee'),
            'summary' => $summary,
            'notes' => $notes,
            'results' => $results,
        ];

        if (!empty($response['download_links'])) {
            $finalResponse['download_links'] = $response['download_links'];
        }

        echo json_encode($finalResponse);
    }

    public function shipping_documents()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        if (!$id_selected) {
            echo json_encode(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
            return;
        }

        $data['id_selected'] = $id_selected;
        
        $this->load->view("booking_fbs/shipping_documents", $data);
    }

    public function shipping_documents_execute()
    {
        $id_selected = $_POST['id_selected'] ?? '';
        
        if (!$id_selected) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $id = explode(',', $id_selected);
        
        $rows = $this->db->select('id, booking_sn, shop_id')
            ->from('booking_fbs_orders')
            ->where_in('booking_sn', $id)
            ->get()
            ->result_array();
        if (empty($rows)) {
            echo json_encode(['status' => false, 'message' => 'Booking tidak ditemukan']);
            return;
        }

        $shopGroups = [];

        foreach ($rows as $row) {
            $shopId = $row['shop_id'];
            $marketplace = 'SHOPEE';
            
            if (!isset($shopGroups[$shopId])) {
                $shopGroups[$shopId] = [
                    'marketplace' => $marketplace,
                    'booking_sns' => []
                ];
            }
            $shopGroups[$shopId]['booking_sns'][] = $row['booking_sn'];
        }

        $allResults = [];
        $successCount = 0;
        $errorCount = 0;
        $notes = [];
        $printPayload = [];
        $shopeePrintPayload = [];
        $downloadLinks = [];

        foreach ($shopGroups as $shopId => $group) {
            $marketplace = $group['marketplace'];
            $payload = [
                'booking_sns' => implode(',', $group['booking_sns'])
            ];
            $endpoint = '';

            if (strpos($marketplace, 'SHOPEE') !== false) {
                $payload['shop_id'] = $shopId;
                $endpoint = 'api/shopee_get_booking_document_bulk';
            } else {
                $notes[] = "❌ Toko {$shopId}: Marketplace {$marketplace} belum didukung";
                $errorCount++;
                continue;
            }

            $response = $this->call_marketplace_api($endpoint, $payload);

            if (!empty($response['status'])) {
                $response = $this->prepareDocumentPrintResponse($response, $marketplace);
            }

            if (!empty($response['print_payload']) && is_array($response['print_payload'])) {
                $printPayload = array_merge($printPayload, $response['print_payload']);
            }

            if (!empty($response['download_links']) && is_array($response['download_links'])) {
                $downloadLinks = array_merge($downloadLinks, $response['download_links']);
            }

            if (!empty($response['status'])) {
                $successCount++;
                $notes[] = "✅ Toko {$shopId}: Berhasil generate dokumen untuk " . count($group['booking_sns']) . " booking";
            } else {
                $errorCount++;
                $notes[] = "❌ Toko {$shopId}: Gagal - " . ($response['message'] ?? 'Unknown error');
            }

            $allResults[] = [
                'shop_id' => $shopId,
                'marketplace' => $marketplace,
                'status' => !empty($response['status']),
                'message' => $response['message'] ?? '',
                'booking_count' => count($group['booking_sns']),
            ];
        }

        $finalResponse = [
            'status' => $successCount > 0,
            'message' => $successCount > 0 ? 
                "Berhasil memproses dokumen" : 
                "Gagal memproses dokumen",
            'summary' => [
                'total_shops' => count($shopGroups),
                'success' => $successCount,
                'failed' => $errorCount
            ],
            'notes' => $notes
        ];

        if (!empty($printPayload)) {
            $finalResponse['open_print'] = true;
            $finalResponse['print_payload'] = $printPayload; 
        }

        if (!empty($shopeePrintPayload)) {
            $finalResponse['open_print_shopee'] = true;
            $finalResponse['shopee_print_payload'] = $shopeePrintPayload;
        }

        if (!empty($downloadLinks)) {
            $finalResponse['download_links'] = $downloadLinks;
        }

        $finalResponse = $this->appendBulkResultAlert($finalResponse, 'ship_documents');
        
        echo json_encode($finalResponse);
    }

    private function call_marketplace_api(string $endpoint, array $payload): array
    {
        $url = rtrim($this->template->endpoint_url(), '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT        => 0,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError   = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status'  => false,
                'message' => 'CURL Error: ' . $curlError,
            ];
        }
        $decoded = json_decode($rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status'  => false,
                'message' => 'Respon API tidak valid',
                'raw'     => $rawResponse,
            ];
        }
        return $decoded;
    }

    private function appendBulkResultAlert(array $response, string $context): array
    {
        if (empty($response['status'])) {
            return $response;
        }

        $summary = isset($response['summary']) && is_array($response['summary']) ? $response['summary'] : [];
        $results = isset($response['results']) && is_array($response['results']) ? $response['results'] : [];

        if ($context === 'ship_orders') {
            $counts = $this->resolveBulkCounts($summary, $results, ['success'], ['failed'], ['success']);
            $title = 'Ship bulk order selesai';
        } else {
            $counts = $this->resolveBulkCounts($summary, $results, ['success_download', 'success'], ['failed'], ['success']);
            $title = 'Download dokumen selesai';
        }

        if ($counts === null) {
            return $response;
        }

        [$successCount, $failedCount] = $counts;

        $response['message'] = $message;
        $response['alert_html'] = $this->template->alert_success($message);

        return $response;
    }

    private function resolveBulkCounts(array $summary, array $results, array $successKeys, array $failedKeys, array $successMarkers): ?array
    {
        $success = null;
        foreach ($successKeys as $key) {
            if (isset($summary[$key])) {
                $success = (int)$summary[$key];
                break;
            }
        }

        $failed = null;
        foreach ($failedKeys as $key) {
            if (isset($summary[$key])) {
                $failed = (int)$summary[$key];
                break;
            }
        }

        if ($success === null || $failed === null) {
            $counts = $this->countResultsByStatus($results, $successMarkers);
            if ($counts === null) {
                return null;
            }
            [$success, $failed] = $counts;
        }

        return [$success, $failed];
    }

    private function countResultsByStatus(array $results, array $successMarkers): ?array
    {
        if (empty($results)) {
            return null;
        }

        $successCount = 0;
        $failedCount = 0;
        $hasStatus = false;
        $normalizedMarkers = array_map('strtolower', array_filter($successMarkers, 'is_string'));

        foreach ($results as $row) {
            if (!array_key_exists('status', $row)) {
                continue;
            }

            $hasStatus = true;
            $status = $row['status'];
            $isSuccess = false;

            if ($status === true || $status === 1) {
                $isSuccess = true;
            } elseif (is_string($status)) {
                $isSuccess = in_array(strtolower($status), $normalizedMarkers, true);
            }

            if ($isSuccess) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        if (!$hasStatus) {
            return null;
        }

        return [$successCount, $failedCount];
    }

    private function prepareDocumentPrintResponse(array $response, string $marketplace): array
    {
        $results = $response['results'] ?? [];
        if (!is_array($results) || empty($results)) {
            return $response;
        }

        $downloadLinks = $response['download_links'] ?? [];

        $printPayload = [];
        $shopeePrintPayload = [];
        
        foreach ($results as $row) {
            $status = strtolower((string)($row['status'] ?? ''));
            $filePath = $row['file_path'] ?? '';
            
            if ($status !== 'success' || !$filePath) {
                continue;
            }

            $fileUrl = $this->buildFileUrl($filePath);
            if (!$fileUrl) {
                continue;
            }

            $printEntry = [
                'booking_sn'      => $row['booking_sn'] ?? '',
                'shipping_method' => $row['shipping_method'] ?? $row['shipping_carrier'] ?? '',
                'file_url'        => $fileUrl,
                'file_path'       => $filePath,
                'file_name'       => $row['file_name'] ?? '',
                'download_links'  => [$fileUrl],
                'label_images'    => array_values(array_filter($row['label_images'] ?? [])),
                'download_group'  => $row['download_group'] ?? '',
                'message'         => $row['message'] ?? '',
            ];

            if (!empty($printEntry['label_images'])) {
                foreach ($printEntry['label_images'] as $image) {
                    if (!in_array($image, $printEntry['download_links'])) {
                        $printEntry['download_links'][] = $image;
                    }
                }
            }

            if (!empty($printEntry['booking_sn'])) {
                $printPayload[] = $printEntry;
                $downloadLinks[] = $fileUrl;
                
                if (!empty($printEntry['label_images'])) {
                    $downloadLinks = array_merge($downloadLinks, $printEntry['label_images']);
                }
            }

            $shopeePrintEntry = [
                'booking_sn' => $row['booking_sn'] ?? '',
                'request_id' => $row['request_id'] ?? '',
                'shop_id'    => $_POST['shop_id'] ?? '' 
            ];
            $shopeePrintPayload[] = $shopeePrintEntry;
        }

        if (!empty($printPayload)) {
            $response['print_payload'] = $printPayload;
            $response['open_print'] = true; 
        }

        if (!empty($shopeePrintPayload)) {
            $response['shopee_print_payload'] = $shopeePrintPayload;
            $response['open_print_shopee'] = true;
        }

        if (!empty($downloadLinks)) {
            $response['download_links'] = array_values(array_unique($downloadLinks));
        }

        return $response;
    }

    private function buildFileUrl(string $path): string
    {
        if (!$path) {
            return '';
        }
        $clean = str_replace('\\', '/', $path);
        $clean = ltrim($clean, '/');
        $clean = preg_replace('#^\./#', '', $clean);
        if ($clean === '') {
            return '';
        }
        return base_url($clean);
    }

    public function print_shipping_docs_shopee()
    {
        $payload = $this->input->post('payload');
        if (!$payload) {
            show_error('Payload tidak ditemukan', 400);
            return;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
        } else {
            $decoded = $payload;
        }

        if (!is_array($decoded) || empty($decoded)) {
            show_error('Payload tidak valid', 400);
            return;
        }

        $rawPayload = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (empty($_POST['skip_history'])) {
            $this->saveShippingPrintHistory($rawPayload ?: '', 'booking-fbs/print-shipping-docs-shopee', 'SHOPEE', count($decoded));
        }

        $labels     = [];
        $bookingSns = [];

        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $bookingSn = $row['booking_sn'] ?? null;

            if (!$bookingSn) {
                continue;
            }

            $row['booking_sn']    = $bookingSn;
            $row['label_images']  = array_values(array_filter($row['label_images'] ?? []));
            $row['download_links'] = array_values(array_filter($row['download_links'] ?? []));

            if (!empty($row['file_url'])) {
                $row['download_links'][] = $row['file_url'];
            }

            $row['download_links'] = array_values(array_unique($row['download_links']));

            $labels[]     = $row;
            $bookingSns[] = $bookingSn;
        }

        if (empty($labels)) {
            show_error('Data label tidak ditemukan', 400);
            return;
        }

        $bookingSns = array_values(array_unique($bookingSns));

        $itemsByBooking = [];
        if (!empty($bookingSns)) {
            $rows = $this->db
                ->select('booking_sn, items_json')
                ->where_in('booking_sn', $bookingSns)
                ->get('booking_fbs_orders')
                ->result_array();

            foreach ($rows as $row) {
                $decodedItems = json_decode($row['items_json'], true);
                $itemsByBooking[$row['booking_sn']] = is_array($decodedItems) ? $decodedItems : [];
            }
        }

        foreach ($bookingSns as $bookingSn) {
            if (!isset($itemsByBooking[$bookingSn])) {
                $itemsByBooking[$bookingSn] = [];
            }
        }

        $downloadLinks = [];
        foreach ($labels as $row) {
            if (!empty($row['download_links'])) {
                $downloadLinks = array_merge($downloadLinks, $row['download_links']);
            }
        }

        $data = [
            'labels'         => $labels,
            'itemsByBooking' => $itemsByBooking,
            'downloadLinks'  => array_values(array_unique($downloadLinks)),
        ];

        $this->load->view('print/print_booking_docs', $data);
    }

    public function print_history()
    {
        $id = $this->input->get('id');
        if (empty($id)) {
            show_error('ID history tidak ditemukan', 400);
            return;
        }

        if (!$this->hasShippingPrintHistoryTable()) {
            show_error('Tabel history cetak belum tersedia', 500);
            return;
        }

        $row = $this->db
            ->select('print_url, payload')
            ->from('booking_fbs_print_history')
            ->where('id', $id)
            ->get()
            ->row_array();

        if (empty($row) || empty($row['print_url']) || empty($row['payload'])) {
            show_error('History cetak tidak ditemukan', 404);
            return;
        }

        $action = base_url($row['print_url']);
        $payload = htmlspecialchars($row['payload'], ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html><head><meta charset="utf-8"><title>Redirect</title></head><body>';
        echo '<form id="history-form" method="POST" action="' . $action . '">';
        echo '<input type="hidden" name="payload" value="' . $payload . '">';
        echo '<input type="hidden" name="skip_history" value="1">';
        echo '<noscript><button type="submit">Buka History Cetak</button></noscript>';
        echo '</form>';
        echo '<script>document.getElementById("history-form").submit();</script>';
        echo '</body></html>';
    }

    private function normalize_sku_key(string $sku): string
    {
        $sku = strtoupper(trim($sku));
        $sku = preg_replace('/\s+/', '', $sku);
        return $sku;
    }

    private function extract_product_quantities(array $items, array $productSkuMap, array $productIdMap): array
    {
        $totals = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $baseQty = (int) ($item['qty'] ?? 0);
            if ($baseQty <= 0) {
                $baseQty = 1;
            }

            $rawSku = trim((string) ($item['sku'] ?? ''));
            if ($rawSku === '') {
                $rawSku = trim((string) ($item['sku_parent'] ?? ''));
            }

            $matched = false;
            if ($rawSku !== '') {
                $parts = preg_split('/\s*\\+\\s*/', $rawSku);
                foreach ($parts as $partSku) {
                    $partSku = trim($partSku);
                    if ($partSku === '') {
                        continue;
                    }

                    $multiplier = 1;
                    if (preg_match('/^(\\d+)\\s*-\\s*(.+)$/', $partSku, $m)) {
                        $multiplier = max(1, (int) $m[1]);
                        $partSku = $m[2];
                    }

                    $normalized = $this->normalize_sku_key($partSku);
                    $normalizedNoDash = $this->normalize_sku_key(str_replace('-', '', $partSku));

                    $productId = $productSkuMap[$normalized] ?? ($productSkuMap[$normalizedNoDash] ?? null);
                    if ($productId) {
                        $matched = true;
                        $qtyVal = $baseQty * $multiplier;
                        if (!isset($totals[$productId])) {
                            $totals[$productId] = 0;
                        }
                        $totals[$productId] += $qtyVal;
                    }
                }
            }

            if ($matched) {
                continue;
            }

            foreach (['id_product', 'id_product_parent'] as $idKey) {
                $pid = (int) ($item[$idKey] ?? 0);
                if ($pid > 0 && isset($productIdMap[$pid])) {
                    if (!isset($totals[$pid])) {
                        $totals[$pid] = 0;
                    }
                    $totals[$pid] += $baseQty;
                    break;
                }
            }
        }

        return $totals;
    }
}
