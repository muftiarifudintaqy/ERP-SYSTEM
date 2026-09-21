<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class Report_aset extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        $this->set_public_methods([]);
        $this->set_method_permissions([
            'aset_item' => 'view'
        ]);
    }

    public function index()
    {
        $data['title'] = 'Report Aset - ' . $this->template->title();

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['content'] = $this->load->view('report_aset/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }

    public function aset_item()
    {
        $brand = trim($_GET['brand'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $keyword = trim($_GET['keyword'] ?? '');
        $stock_min = trim($_GET['stock_min'] ?? '');
        $is_operational = $_GET['is_operational'] ?? null;
        if ($is_operational === null) {
            $is_operational = '0';
        }
        $is_operational = trim((string)$is_operational);
        $filter_model_raw = $this->input->get('filter_model');

        $hpp_expr = "COALESCE(price_buy,0)";
        $stock_expr = "COALESCE(stock,0)";
        $total_value_expr = "($stock_expr * $hpp_expr)";

        $field_map = [
            'name' => 'name',
            'hpp' => $hpp_expr,
            'stock' => $stock_expr,
            'total_value' => $total_value_expr,
        ];

        $conditions = ["is_varian = 0"];
        if ($brand !== '') {
            $conditions[] = "brand = " . $this->db->escape($brand);
        }
        if ($status !== '') {
            $conditions[] = "status = " . $this->db->escape($status);
        }
        if ($keyword !== '') {
            $like = $this->db->escape_like_str($keyword);
            $conditions[] = "(name LIKE '%{$like}%' OR sku LIKE '%{$like}%')";
        }
        if ($stock_min !== '' && is_numeric($stock_min)) {
            $conditions[] = "$stock_expr >= " . (int)$stock_min;
        }
        if ($is_operational !== '' && ($is_operational === '0' || $is_operational === '1')) {
            $conditions[] = "is_operational = " . (int)$is_operational;
        } else {
            $conditions[] = "is_operational IN (0,1)";
        }

        $build_condition = function (string $field_expr, array $model) use (&$build_condition) {
            $filter_type = $model['filterType'] ?? '';
            $type = $model['type'] ?? '';
            $value = $model['filter'] ?? null;

            if (isset($model['operator'], $model['condition1'], $model['condition2'])) {
                $cond1 = $build_condition($field_expr, $model['condition1']);
                $cond2 = $build_condition($field_expr, $model['condition2']);
                if ($cond1 && $cond2) {
                    $op = strtoupper($model['operator']) === 'OR' ? 'OR' : 'AND';
                    return "($cond1 $op $cond2)";
                }
                return $cond1 ?: $cond2;
            }

            if ($filter_type === 'text') {
                $raw = (string)$value;
                if ($type === 'equals') {
                    return "$field_expr = " . $this->db->escape($raw);
                }
                $value = $this->db->escape_like_str($raw);
                if ($type === 'startsWith') {
                    return "$field_expr LIKE '{$value}%'";
                }
                if ($type === 'endsWith') {
                    return "$field_expr LIKE '%{$value}'";
                }
                return "$field_expr LIKE '%{$value}%'";
            }

            if ($filter_type === 'number') {
                if (!is_numeric($value)) {
                    return '';
                }
                $num = (float)$value;
                if ($type === 'equals') {
                    return "$field_expr = {$num}";
                }
                if ($type === 'lessThan') {
                    return "$field_expr < {$num}";
                }
                if ($type === 'greaterThan') {
                    return "$field_expr > {$num}";
                }
                if ($type === 'inRange') {
                    $to = $model['filterTo'] ?? null;
                    if (is_numeric($to)) {
                        $to_num = (float)$to;
                        return "($field_expr >= {$num} AND $field_expr <= {$to_num})";
                    }
                }
            }

            return '';
        };

        $filter_model = [];
        if (!empty($filter_model_raw)) {
            $decoded = json_decode($filter_model_raw, true);
            if (is_array($decoded)) {
                $filter_model = $decoded;
            }
        }

        foreach ($filter_model as $field => $model) {
            if (!isset($field_map[$field]) || !is_array($model)) {
                continue;
            }
            $condition = $build_condition($field_map[$field], $model);
            if ($condition) {
                $conditions[] = $condition;
            }
        }

        $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

        $sort_column = $_GET['sort_column'] ?? 'name';
        $sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');
        if (!isset($field_map[$sort_column])) {
            $sort_column = 'name';
        }
        if (!in_array($sort_order, ['ASC', 'DESC'], true)) {
            $sort_order = 'ASC';
        }
        $order_expr = $field_map[$sort_column];

        $rows = $this->mymodel->selectWithQuery("
            SELECT name, $hpp_expr AS hpp, $stock_expr AS stock, $total_value_expr AS total_value
            FROM product
            $where
            ORDER BY $order_expr $sort_order, name ASC
        ");

        $output = [];
        foreach ($rows as $row) {
            $output[] = [
                'name' => $row['name'] ?? '',
                'hpp' => (float)($row['hpp'] ?? 0),
                'stock' => (float)($row['stock'] ?? 0),
                'total_value' => (float)($row['total_value'] ?? 0),
            ];
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'ok' => true,
                'rows' => $output,
            ]));
    }
}
