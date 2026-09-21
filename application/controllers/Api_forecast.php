<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Api_forecast - REST API Controller for stock forecasting data
 *
 * Authentication: Bearer token via Authorization header
 * Authentication key is configured with FORECAST_API_KEY in .env.
 *
 * Endpoints:
 *   GET  /api/forecast/stock-current
 *   GET  /api/forecast/sales-history?days=30
 *   GET  /api/forecast/marketing-impact?platform=all&days=7
 *   GET  /api/forecast/endorse-traffic?days=30
 */
class Api_forecast extends CI_Controller
{
    const FORECAST_API_KEY = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // =========================================================================
    // Auth helper
    // =========================================================================

    private function authenticate()
    {
        $auth_header = '';

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            // Case-insensitive header lookup
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') {
                    $auth_header = $v;
                    break;
                }
            }
        }

        // Fallback: read from $_SERVER
        if (empty($auth_header)) {
            $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        if (empty($auth_header) || strpos(strtolower($auth_header), 'bearer ') !== 0) {
            $this->json_error('Authorization header missing or invalid. Use: Authorization: Bearer <api_key>', 401);
        }

        $token = trim(substr($auth_header, 7));

        if ($token !== $this->forecast_api_key()) {
            $this->json_error('Invalid API key.', 401);
        }
    }

    private function forecast_api_key()
    {
        return app_env('FORECAST_API_KEY', self::FORECAST_API_KEY);
    }

    // =========================================================================
    // Response helpers
    // =========================================================================

    private function json_success($data, $extra = [])
    {
        $response = array_merge(['success' => true], $extra, ['data' => $data]);
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function json_error($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================================================================
    // GET /api/forecast/stock-current
    // =========================================================================

    public function stock_current()
    {
        $this->authenticate();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json_error('Method not allowed.', 405);
        }

        $rows = $this->mymodel->selectWithQuery("
            SELECT
                p.name  AS sku,
                p.name  AS name,
                COALESCE(p.stock, 0) AS stock,
                p.status,
                CAST(p.is_varian AS UNSIGNED) AS is_varian,
                p.updated_at AS last_updated
            FROM product p
            WHERE p.status = 'Aktif'
              AND p.is_varian = 0
              AND (p.is_operational = 0 OR p.is_operational IS NULL)
              AND p.id IN (13, 14, 17, 27, 24, 112, 18)
            ORDER BY p.stock ASC, p.name ASC
        ");

        if (!is_array($rows)) {
            $rows = [];
        }

        // Cast numeric fields
        foreach ($rows as &$r) {
            $r['stock']     = (int) $r['stock'];
            $r['is_varian'] = (int) $r['is_varian'];
        }
        unset($r);

        $this->json_success($rows);
    }

    // =========================================================================
    // GET /api/forecast/sales-history?days=30
    // =========================================================================

    public function sales_history()
    {
        $this->authenticate();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json_error('Method not allowed.', 405);
        }

        $days = (int) ($this->input->get('days') ?? 30);
        if ($days < 1 || $days > 365) {
            $this->json_error('Parameter days must be between 1 and 365.');
        }

        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date   = date('Y-m-d');

        // Per-product per-day breakdown
        $daily_rows = $this->mymodel->selectWithQuery("
            SELECT
                DATE(s.date)                             AS date,
                s.product_text,
                SUM(ABS(s.qty_out + s.qty_out_pos))     AS qty_sold
            FROM stock s
            WHERE s.type = 'Out'
              AND s.product IN (13, 14, 17, 27, 24, 112, 18)
              AND DATE(s.date) BETWEEN '{$start_date}' AND '{$end_date}'
              AND s.type_sub = 'POS' AND s.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED')
            GROUP BY DATE(s.date), s.product_text
            ORDER BY s.product_text ASC, DATE(s.date) ASC
        ");

        if (!is_array($daily_rows)) $daily_rows = [];

        // Group by product, embed daily array
        $product_map = [];
        foreach ($daily_rows as $r) {
            $key = $r['product_text'];
            if (!isset($product_map[$key])) {
                $product_map[$key] = [
                    'product_text' => $key,
                    'total_sold'   => 0,
                    'days_active'  => 0,
                    'avg_daily'    => 0,
                    'daily'        => [],
                ];
            }
            $qty = (int) $r['qty_sold'];
            $product_map[$key]['total_sold']  += $qty;
            $product_map[$key]['days_active'] += 1;
            $product_map[$key]['daily'][]      = [
                'date' => $r['date'],
                'qty'  => $qty,
            ];
        }

        // Compute avg_daily and sort by total_sold desc
        $rows = array_values($product_map);
        foreach ($rows as &$r) {
            $r['avg_daily'] = $r['days_active'] > 0
                ? round($r['total_sold'] / $r['days_active'], 2)
                : 0;
        }
        unset($r);
        usort($rows, fn($a, $b) => $b['total_sold'] <=> $a['total_sold']);

        $this->json_success($rows, [
            'period'     => "last_{$days}_days",
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'total_products' => count($rows),
        ]);
    }

    // =========================================================================
    // GET /api/forecast/marketing-impact?platform=all
    // =========================================================================

    public function marketing_impact()
    {
        $this->authenticate();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json_error('Method not allowed.', 405);
        }

        $platform = strtolower($this->input->get('platform') ?? 'all');
        $days     = (int) ($this->input->get('days') ?? 7);
        if ($days < 1 || $days > 365) {
            $days = 7;
        }

        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date   = date('Y-m-d');

        $result = [];

        // ---- Shopee ----
        if ($platform === 'all' || $platform === 'shopee') {
            $shopee_summary = $this->mymodel->selectWithQuery("
                SELECT
                    ROUND(AVG(daily.total_orders), 2)   AS avg_daily_orders,
                    ROUND(AVG(daily.total_spend), 2)    AS avg_daily_spend,
                    ROUND(
                        SUM(daily.total_gmv) / NULLIF(SUM(daily.total_spend), 0),
                        2
                    ) AS roas
                FROM (
                    SELECT
                        DATE(s.date)              AS dt,
                        SUM(s.broad_item_sold)    AS total_orders,
                        SUM(s.expense_after_tax)  AS total_spend,
                        SUM(s.broad_gmv)          AS total_gmv
                    FROM shopee_ads_data s
                    WHERE DATE(s.date) BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY DATE(s.date)
                ) daily
            ");

            $shopee_daily = $this->mymodel->selectWithQuery("
                SELECT
                    DATE(s.date)            AS date,
                    SUM(s.broad_item_sold)  AS orders,
                    SUM(s.expense_after_tax) AS spend,
                    SUM(s.broad_gmv)        AS gmv
                FROM shopee_ads_data s
                WHERE DATE(s.date) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(s.date)
                ORDER BY DATE(s.date) ASC
            ");

            $shopee_daily = is_array($shopee_daily) ? $shopee_daily : [];
            foreach ($shopee_daily as &$sd) {
                $sd['orders'] = (int)   $sd['orders'];
                $sd['spend']  = (float) $sd['spend'];
                $sd['gmv']    = (float) $sd['gmv'];
            }
            unset($sd);

            $s = is_array($shopee_summary) && !empty($shopee_summary) ? $shopee_summary[0] : [];
            $result['shopee'] = [
                'avg_daily_orders' => (float) ($s['avg_daily_orders'] ?? 0),
                'avg_daily_spend'  => (float) ($s['avg_daily_spend']  ?? 0),
                'roas'             => (float) ($s['roas']             ?? 0),
                'daily'            => $shopee_daily,
            ];
        }

        // ---- TikTok ----
        if ($platform === 'all' || $platform === 'tiktok') {
            $tiktok_summary = $this->mymodel->selectWithQuery("
                SELECT
                    ROUND(AVG(daily.total_orders), 2)  AS avg_daily_orders,
                    ROUND(AVG(daily.total_spend), 2)   AS avg_daily_spend,
                    ROUND(
                        SUM(daily.total_gmv) / NULLIF(SUM(daily.total_spend), 0),
                        2
                    ) AS roas
                FROM (
                    SELECT
                        DATE(t.date)                           AS dt,
                        SUM(t.onsite_shopping)                 AS total_orders,
                        SUM(t.spend_idr_after_tax)             AS total_spend,
                        SUM(t.total_onsite_shopping_value_idr) AS total_gmv
                    FROM tiktok_ads_data t
                    WHERE DATE(t.date) BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY DATE(t.date)
                ) daily
            ");

            $tiktok_daily = $this->mymodel->selectWithQuery("
                SELECT
                    DATE(t.date)                           AS date,
                    SUM(t.onsite_shopping)                 AS orders,
                    SUM(t.spend_idr_after_tax)             AS spend,
                    SUM(t.total_onsite_shopping_value_idr) AS gmv
                FROM tiktok_ads_data t
                WHERE DATE(t.date) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(t.date)
                ORDER BY DATE(t.date) ASC
            ");

            $tiktok_daily = is_array($tiktok_daily) ? $tiktok_daily : [];
            foreach ($tiktok_daily as &$td) {
                $td['orders'] = (int)   $td['orders'];
                $td['spend']  = (float) $td['spend'];
                $td['gmv']    = (float) $td['gmv'];
            }
            unset($td);

            $t = is_array($tiktok_summary) && !empty($tiktok_summary) ? $tiktok_summary[0] : [];
            $result['tiktok'] = [
                'avg_daily_orders' => (float) ($t['avg_daily_orders'] ?? 0),
                'avg_daily_spend'  => (float) ($t['avg_daily_spend']  ?? 0),
                'roas'             => (float) ($t['roas']             ?? 0),
                'daily'            => $tiktok_daily,
            ];
        }

        // ---- Meta ----
        if ($platform === 'all' || $platform === 'meta') {
            $meta_summary = $this->mymodel->selectWithQuery("
                SELECT
                    ROUND(AVG(daily.total_orders), 2)  AS avg_daily_orders,
                    ROUND(AVG(daily.total_spend), 2)   AS avg_daily_spend,
                    ROUND(
                        SUM(daily.total_gmv) / NULLIF(SUM(daily.total_spend), 0),
                        2
                    ) AS roas
                FROM (
                    SELECT
                        DATE(m.date)             AS dt,
                        SUM(m.purchase_qty)      AS total_orders,
                        SUM(m.spend_after_tax)   AS total_spend,
                        SUM(m.purchases)         AS total_gmv
                    FROM meta_ads_data m
                    WHERE DATE(m.date) BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY DATE(m.date)
                ) daily
            ");

            $meta_daily = $this->mymodel->selectWithQuery("
                SELECT
                    DATE(m.date)         AS date,
                    SUM(m.purchase_qty)  AS orders,
                    SUM(m.spend_after_tax) AS spend,
                    SUM(m.purchases)     AS gmv
                FROM meta_ads_data m
                WHERE DATE(m.date) BETWEEN '{$start_date}' AND '{$end_date}'
                GROUP BY DATE(m.date)
                ORDER BY DATE(m.date) ASC
            ");

            $meta_daily = is_array($meta_daily) ? $meta_daily : [];
            foreach ($meta_daily as &$md) {
                $md['orders'] = (int)   $md['orders'];
                $md['spend']  = (float) $md['spend'];
                $md['gmv']    = (float) $md['gmv'];
            }
            unset($md);

            $m = is_array($meta_summary) && !empty($meta_summary) ? $meta_summary[0] : [];
            $result['meta'] = [
                'avg_daily_orders' => (float) ($m['avg_daily_orders'] ?? 0),
                'avg_daily_spend'  => (float) ($m['avg_daily_spend']  ?? 0),
                'roas'             => (float) ($m['roas']             ?? 0),
                'daily'            => $meta_daily,
            ];
        }

        $this->json_success($result, [
            'platform'   => $platform,
            'days'       => $days,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ]);
    }

    // =========================================================================
    // GET /api/forecast/endorse-traffic
    // =========================================================================

    public function endorse_traffic()
    {
        $this->authenticate();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json_error('Method not allowed.', 405);
        }

        $days = (int) ($this->input->get('days') ?? 30);
        if ($days < 1 || $days > 365) {
            $days = 30;
        }

        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date   = date('Y-m-d');

        $rows = $this->mymodel->selectWithQuery("
            SELECT
                ec.product_text,
                SUM(el.views_after)  AS total_views,
                COUNT(DISTINCT e.id) AS active_endorse,
                ROUND(
                    SUM(el.likes_after + el.comment_after + el.share_save_after)
                    / NULLIF(SUM(el.views_after), 0) * 100,
                    2
                ) AS avg_engagement,
                ROUND(SUM(el.views_after) / {$days}, 0) AS avg_daily_views
            FROM endorse_logs el
            INNER JOIN endorse e    ON e.id  = el.id_endorse
            INNER JOIN endorse_campaign ec ON ec.id = e.id_campaign
            WHERE e.status = 'Aktif'
              AND e.status_endorse = 'Posted Content'
              AND DATE(el.date) BETWEEN '{$start_date}' AND '{$end_date}'
            GROUP BY ec.product_text
            ORDER BY total_views DESC
        ");

        if (!is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as &$r) {
            $r['total_views']     = (int)   ($r['total_views']     ?? 0);
            $r['active_endorse']  = (int)   ($r['active_endorse']  ?? 0);
            $r['avg_engagement']  = (float) ($r['avg_engagement']  ?? 0);
            $r['avg_daily_views'] = (int)   ($r['avg_daily_views'] ?? 0);
        }
        unset($r);

        $this->json_success($rows, [
            'period'     => "last_{$days}_days",
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ]);
    }

}
