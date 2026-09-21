<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Overview extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        try {
            $this->load->driver('cache', array('adapter' => 'memcached'));
        } catch (Exception $e) {
            try {
                $this->load->driver('cache', array('adapter' => 'file'));
            } catch (Exception $e2) {
                log_message('error', 'Overview cache init failed: ' . $e2->getMessage());
            }
        }
    }

    private function normalize_overview_date($value, $default)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $default;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if ($dt && $dt->format('Y-m-d') === $value) {
            return $value;
        }
        return $default;
    }

    public function index()
    {
        $data['checkbox'] = $_SESSION['checkbox_dashboard'];
        $data['checkbox_campaign'] = $_SESSION['checkbox_dashboard_campaign'];
        $data['title'] = 'Overview - ' . $this->template->title();

        $user = $_SESSION['user'];

        $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
        $data['brands'] = $query;

        $data['channel_2'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        ORDER BY name ASC");

        $data['channel'] = $this->mymodel->selectWithQuery("SELECT *
        FROM marketplace
        WHERE name IN ('SHOPEE','LAZADA','TIKTOK','WA')
        ORDER BY name ASC");


        $default_start_date = date('Y-m-d', strtotime('-7 days'));
        $default_until_date = date('Y-m-d');
        $session_start_date = $this->session->userdata('overview_start_date') ?: $default_start_date;
        $session_until_date = $this->session->userdata('overview_until_date') ?: $default_until_date;
        $start_date = $this->normalize_overview_date($this->input->get('start_date', true), $this->normalize_overview_date($session_start_date, $default_start_date));
        $until_date = $this->normalize_overview_date($this->input->get('until_date', true), $this->normalize_overview_date($session_until_date, $default_until_date));
        if ($start_date > $until_date) {
            $swap = $start_date;
            $start_date = $until_date;
            $until_date = $swap;
        }
        $this->session->set_userdata([
            'overview_start_date' => $start_date,
            'overview_until_date' => $until_date
        ]);
        $data['title_2'] = $this->template->date_format_indo($start_date) . ' - ' . $this->template->date_format_indo($until_date);

        $url = base_url() . '/overview/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['url_2'] = $this->template->get_param_without('status');
        $data['param'] = $this->template->get_param();

        $tab = $_GET['t'] ?? 'general';

        if ($tab == "general") {
            $product_filters = $this->input->get('product_ids');
            if (!is_array($product_filters)) {
                $product_filters = explode(',', (string)$product_filters);
            }
            $selected_product_ids = [];
            foreach ((array)$product_filters as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $selected_product_ids[] = $pid;
                }
            }
            $selected_product_ids = array_values(array_unique($selected_product_ids));
            $data['selected_product_ids'] = $selected_product_ids;

            $data['general_products'] = $this->mymodel->selectWithQuery("
                SELECT id, name
                FROM product
                WHERE is_operational = 0
                  AND status = 'Aktif'
                  AND (
                    is_varian = 1
                    OR (is_varian = 0 AND (parent_id IS NULL OR parent_id = '' OR parent_id = 0))
                  )
                ORDER BY name ASC
            ");

            $compare_mode = $this->input->get('compare_mode');
            if ($compare_mode !== 'on' && $compare_mode !== 'off') {
                $compare_mode = $this->session->userdata('overview_compare_mode') ?: 'off';
            }

            $default_start_date_2 = date('Y-m-d', strtotime($start_date . ' -7 days'));
            $default_until_date_2 = date('Y-m-d', strtotime($start_date . ' -1 day'));
            $session_start_date_2 = $this->session->userdata('overview_start_date_2') ?: $default_start_date_2;
            $session_until_date_2 = $this->session->userdata('overview_until_date_2') ?: $default_until_date_2;

            $start_date_2 = $this->normalize_overview_date($this->input->get('start_date_2', true), $this->normalize_overview_date($session_start_date_2, $default_start_date_2));
            $until_date_2 = $this->normalize_overview_date($this->input->get('until_date_2', true), $this->normalize_overview_date($session_until_date_2, $default_until_date_2));

            if ($compare_mode !== 'on' || empty($start_date_2) || empty($until_date_2)) {
                $range_days = (new DateTime($start_date))->diff(new DateTime($until_date))->days + 1;
                $start_date_2 = date('Y-m-d', strtotime("$start_date -$range_days days"));
                $until_date_2 = date('Y-m-d', strtotime("$start_date -1 day"));
            }
            if ($start_date_2 > $until_date_2) {
                $swap = $start_date_2;
                $start_date_2 = $until_date_2;
                $until_date_2 = $swap;
            }

            $this->session->set_userdata([
                'overview_start_date' => $start_date,
                'overview_until_date' => $until_date,
                'overview_compare_mode' => $compare_mode,
                'overview_start_date_2' => $start_date_2,
                'overview_until_date_2' => $until_date_2
            ]);

            $brand_filter = $this->input->get('brand');

            $data['general_period_1'] = $this->get_general_data($start_date, $until_date, $brand_filter);
            $data['general_period_2'] = $this->get_general_data($start_date_2, $until_date_2, $brand_filter);

            $data['start_date'] = $start_date;
            $data['until_date'] = $until_date;
            $data['start_date_2'] = $start_date_2;
            $data['until_date_2'] = $until_date_2;
            $data['compare_mode'] = $compare_mode;

            $data['content'] = $this->load->view('overview/general', $data, true);
        } else if ($tab == "kol" || $tab == "endorse") {
            $data['campaign'] = $this->mymodel->selectWithQuery("SELECT *
            FROM endorse_campaign
            ORDER BY title ASC");
            $data['endorse_filter_pics'] = $this->mymodel->selectWithQuery("
                SELECT DISTINCT TRIM(full_name) AS pic
                FROM user
                WHERE COALESCE(TRIM(full_name), '') != ''
                  AND LOWER(COALESCE(role_text, '')) LIKE '%marketing%'
                ORDER BY full_name ASC
            ");
            $data['endorse_filter_products'] = [
                ['product_name' => 'Lacto V'],
                ['product_name' => 'Miscella-V'],
            ];
            $data['content'] = $this->load->view('overview/all-kol', $data, true);
        } else if ($tab == "influencer") {
            $data['campaign'] = $this->mymodel->selectWithQuery("
           SELECT *
           FROM endorse_campaign
           ORDER BY title ASC");
            $data['content'] = $this->load->view('overview/all-influencer', $data, true);
        } else if ($tab == "digiads" || $tab == "ads") {
            $compare_mode = $this->input->get('compare_mode');
            if ($compare_mode !== 'on' && $compare_mode !== 'off') {
                $compare_mode = $this->session->userdata('overview_compare_mode') ?: 'off';
            }

            $default_start_date_2 = date('Y-m-d', strtotime($start_date . ' -7 days'));
            $default_until_date_2 = date('Y-m-d', strtotime($start_date . ' -1 day'));
            $session_start_date_2 = $this->session->userdata('overview_start_date_2') ?: $default_start_date_2;
            $session_until_date_2 = $this->session->userdata('overview_until_date_2') ?: $default_until_date_2;

            $start_date_2 = $this->normalize_overview_date($this->input->get('start_date_2', true), $this->normalize_overview_date($session_start_date_2, $default_start_date_2));
            $until_date_2 = $this->normalize_overview_date($this->input->get('until_date_2', true), $this->normalize_overview_date($session_until_date_2, $default_until_date_2));

            if ($compare_mode !== 'on' || empty($start_date_2) || empty($until_date_2)) {
                $range_days = (new DateTime($start_date))->diff(new DateTime($until_date))->days + 1;
                $start_date_2 = date('Y-m-d', strtotime("$start_date -$range_days days"));
                $until_date_2 = date('Y-m-d', strtotime("$start_date -1 day"));
            }
            if ($start_date_2 > $until_date_2) {
                $swap = $start_date_2;
                $start_date_2 = $until_date_2;
                $until_date_2 = $swap;
            }

            $this->session->set_userdata([
                'overview_start_date' => $start_date,
                'overview_until_date' => $until_date,
                'overview_compare_mode' => $compare_mode,
                'overview_start_date_2' => $start_date_2,
                'overview_until_date_2' => $until_date_2
            ]);

            $brand_filter = $this->input->get('brand');
            $data['digiads_period_1'] = $this->get_digiads_data($start_date, $until_date, $brand_filter);
            $data['digiads_period_2'] = $this->get_digiads_data($start_date_2, $until_date_2, $brand_filter);

            $data['start_date'] = $start_date;
            $data['until_date'] = $until_date;
            $data['start_date_2'] = $start_date_2;
            $data['until_date_2'] = $until_date_2;
            $data['compare_mode'] = $compare_mode;

            $data['content'] = $this->load->view('overview/digiads', $data, true);
        } else {
            $today = date('Y-m-d');
            $start_date = $this->input->get('start_date');
            $until_date = $this->input->get('until_date');
            $brand_filter = $this->input->get('brand');

            if (empty($start_date)) {
                $start_date = date('Y-m-d', strtotime("$today -7 days"));
            }
            if (empty($until_date)) {
                $until_date = $today;
            }

            $brand_condition = "";
            if (!empty($brand_filter)) {
                $brand_condition = "AND transaction.brand = " . $this->db->escape($brand_filter);
            }

            $firstLetter = str_split($brand_filter)[0];

            $shopee_brand = "";
            if (!empty($brand_filter)) {
                $shopee_brand = "AND shop_name LIKE '$firstLetter%'";
            }

            $tiktok_brand = "";
            if (!empty($brand_filter)) {
                $tiktok_brand = "AND advertiser_name LIKE '$firstLetter%'";
            }

            $meta_brand = "";
            if ($firstLetter == "P" && $firstLetter == "") {
                $meta_brand = "AND account_name LIKE 'c%'";
                $meta_brand = "OR account_name LIKE 'p%'";
            } else if ($firstLetter == "M") {
                $meta_brand = "AND account_name LIKE 'm%'";
            }

            $sql_pivot = "
                SELECT 
                DATE_FORMAT(dates.dt, '%d-%m-%Y') AS date,

                COALESCE(shopee.expense, 0) AS shopee_spend,
                COALESCE(meta.spend, 0) AS meta_spend,
                COALESCE(tiktok.spend_idr, 0) AS tiktok_spend,

                COALESCE(shopee.expense, 0)
                + COALESCE(meta.spend, 0)
                + COALESCE(tiktok.spend_idr, 0)
                + COALESCE(gmv.spend_idr_after_tax, 0) AS total_spend,

                COALESCE(shopee.purchase_qty, 0)
                + COALESCE(meta.purchase_qty, 0)
                + COALESCE(tiktok.purchase_qty, 0) AS purchase_qty,

                COALESCE(shopee.purchase_idr, 0)
                + COALESCE(meta.purchase_idr, 0)
                + COALESCE(tiktok.purchase_idr, 0) AS purchase_idr,

                COALESCE(
                    ROUND(
                    (COALESCE(shopee.purchase_idr, 0)
                    + COALESCE(meta.purchase_idr, 0)
                    + COALESCE(tiktok.purchase_idr, 0))
                    / NULLIF(
                        (COALESCE(shopee.purchase_qty, 0)
                        + COALESCE(meta.purchase_qty, 0)
                        + COALESCE(tiktok.purchase_qty, 0)), 0
                        ),
                    0
                    ),
                    0
                ) AS avg_penjualan,

                COALESCE(pos.result, 0) AS result,

                CASE 
                    WHEN COALESCE(pos.result, 0) = 0 THEN 0
                    ELSE ROUND(
                    (
                        COALESCE(shopee.expense, 0)
                    + COALESCE(meta.spend, 0)
                    + COALESCE(tiktok.spend_idr, 0)
                    ) / pos.result * 100,
                    2
                    )
                END AS ratio,

                COALESCE(gmv.spend_idr_after_tax, 0) AS tiktok_gmv

                FROM (
                SELECT DISTINCT dt FROM (
                    SELECT DATE(sad.date) AS dt
                    FROM shopee_ads_data sad
                    WHERE sad.date >= '$start_date'
                    AND sad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)

                    UNION
                    SELECT DATE(mad.date) AS dt
                    FROM meta_ads_data mad
                    WHERE mad.date >= '$start_date'
                    AND mad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)

                    UNION
                    SELECT DATE(tad.date) AS dt
                    FROM tiktok_ads_data tad
                    WHERE tad.date >= '$start_date'
                    AND tad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)

                    UNION
                    SELECT DATE(trx.date) AS dt
                    FROM transaction trx
                    WHERE trx.date >= '$start_date'
                    AND trx.date < DATE_ADD('$until_date', INTERVAL 1 DAY)

                    UNION
                    SELECT DATE(adsv.date) AS dt
                    FROM advertiser_spend adsv
                    WHERE adsv.date >= '$start_date'
                    AND adsv.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                ) x
                ) dates

                LEFT JOIN (
                SELECT DATE(sad.date) AS dt,
                        SUM(sad.expense_after_tax) AS expense,
                        SUM(sad.broad_item_sold)  AS purchase_qty,
                        SUM(sad.broad_gmv)        AS purchase_idr
                FROM shopee_ads_data sad
                INNER JOIN marketplace_config mc ON mc.shop_id = sad.shop_id
                WHERE sad.date >= '$start_date'
                    AND sad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                $shopee_brand
                GROUP BY DATE(sad.date)
                ) shopee ON shopee.dt = dates.dt

                LEFT JOIN (
                SELECT DATE(mad.date) AS dt,
                        SUM(mad.spend_after_tax) AS spend,
                        SUM(mad.purchase_qty)   AS purchase_qty,
                        SUM(mad.purchases)      AS purchase_idr
                FROM meta_ads_data mad
                INNER JOIN ads_meta_account ama ON mad.account_id = ama.account_id
                WHERE mad.date >= '$start_date'
                    AND mad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                $meta_brand
                GROUP BY DATE(mad.date)
                ) meta ON meta.dt = dates.dt

                LEFT JOIN (
                SELECT DATE(tad.date) AS dt,
                        SUM(tad.spend_idr_after_tax)             AS spend_idr,
                        SUM(tad.onsite_shopping)                 AS purchase_qty,
                        SUM(tad.total_onsite_shopping_value_idr) AS purchase_idr
                FROM tiktok_ads_data tad
                WHERE tad.date >= '$start_date'
                    AND tad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                $tiktok_brand
                GROUP BY DATE(tad.date)
                ) tiktok ON tiktok.dt = dates.dt

                LEFT JOIN (
                SELECT DATE(adsv.date) AS dt,
                        SUM(adsv.spend_idr_after_tax) AS spend_idr_after_tax
                FROM advertiser_spend adsv
                WHERE adsv.date >= '$start_date'
                    AND adsv.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                GROUP BY DATE(adsv.date)
                ) gmv ON gmv.dt = dates.dt

                LEFT JOIN (
                SELECT DATE(trx.date) AS dt,
                        SUM(trx.omset_kotor - trx.diskon_penjual) AS result
                FROM transaction trx
                WHERE trx.date >= '$start_date'
                    AND trx.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                    AND trx.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
                    AND trx.type_sub = 'POS'
                    $brand_condition
                GROUP BY DATE(trx.date)
                ) pos ON pos.dt = dates.dt

                WHERE dates.dt BETWEEN '$start_date' AND '$until_date'
                ORDER BY dates.dt DESC
                ";

            $data['pivot'] = $this->mymodel->selectWithQuery($sql_pivot);


            $sql_spend = "
                    SELECT
                    SUM(spend_idr) as spend_idr,
                    SUM(spend_idr_after_tax) as spend_idr_after_tax,
                    DATE(date) as date,
                    advertiser_name
                    FROM `advertiser_spend`
                ";
            if (!empty($start_date) && !empty($until_date)) {
                $sql_spend .= " WHERE date BETWEEN '$start_date' AND '$until_date'";
            } else {
                $sql_spend .= " WHERE date = CURDATE()";
            }

            $sql_spend .= $tiktok_brand;

            if (!empty($ids_advertiser)) {
                $ids_advertiser_str = implode(",", $ids_advertiser);
                $sql_spend .= " AND advertiser_id IN ($ids_advertiser_str)";
            }

            $sql_spend .= " GROUP BY advertiser_name, date";

            $data['spend'] = $this->mymodel->selectWithQuery($sql_spend);

            $query = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");
            $data['brands'] = $query;

            $query = $this->mymodel->selectWithQuery("SELECT * FROM config WHERE id = 'TAX'");
            $data['tax'] = $query;
            $view_path = 'overview/all-ads';
            $data['content'] = $this->load->view($view_path, $data, true);
        }
        $this->load->view('TemplateDashboard', $data);
    }

    private function empty_period_data($start_date, $until_date, $metrics)
    {
        $daily = [];
        $totals = ['ratio' => 0];
        foreach ($metrics as $m) {
            $totals[$m] = 0.0;
        }
        $start = new DateTime($start_date);
        $end = new DateTime($until_date);
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $row = ['date' => $date->format('Y-m-d'), 'ratio' => 0];
            foreach ($metrics as $m) {
                $row[$m] = 0.0;
            }
            $daily[] = $row;
        }
        return ['daily' => $daily, 'totals' => $totals];
    }

    private function safe_brand_letter($brand_filter)
    {
        if (empty($brand_filter)) {
            return '';
        }
        $letter = strtoupper(substr($brand_filter, 0, 1));
        return preg_match('/^[A-Z0-9]$/', $letter) ? $letter : '';
    }

    private function get_general_data($start_date, $until_date, $brand_filter)
    {
        $cache_key = 'overview_general_' . md5($start_date . '|' . $until_date . '|' . (string)$brand_filter);
        if (isset($this->cache)) {
            $cached = $this->cache->get($cache_key);
            if ($cached !== FALSE) {
                return $cached;
            }
        }

        $brand_condition = "";
        if (!empty($brand_filter)) {
            $brand_condition = "AND trx.brand = " . $this->db->escape($brand_filter);
        }

        $firstLetter = $this->safe_brand_letter($brand_filter);

        $shopee_brand = $firstLetter !== '' ? "AND mc.shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = $firstLetter !== '' ? "AND tad.advertiser_name LIKE '{$firstLetter}%'" : "";
        $tiktok_product_brand = $firstLetter !== '' ? "AND tpa.shop_name LIKE '{$firstLetter}%'" : "";
        $meta_brand = $firstLetter === 'M' ? "AND ama.account_name LIKE 'm%'" : "";

        $sql_pos = "
            SELECT DATE(trx.date) AS dt,
                   SUM(trx.omset_kotor - trx.diskon_penjual) AS gmv
            FROM transaction trx
            WHERE trx.date >= '$start_date'
              AND trx.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
              AND trx.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
              AND trx.type_sub = 'POS'
              $brand_condition
            GROUP BY DATE(trx.date)
        ";

        $sql_spend = "
            SELECT dt, SUM(spent) AS total_spend FROM (
                SELECT DATE(sad.date) AS dt, SUM(sad.expense_after_tax) AS spent
                FROM shopee_ads_data sad
                INNER JOIN marketplace_config mc ON mc.shop_id = sad.shop_id
                WHERE sad.date >= '$start_date'
                  AND sad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $shopee_brand
                GROUP BY DATE(sad.date)
                UNION ALL
                SELECT DATE(mad.date) AS dt, SUM(mad.spend_after_tax) AS spent
                FROM meta_ads_data mad
                INNER JOIN ads_meta_account ama ON mad.account_id = ama.account_id
                WHERE mad.date >= '$start_date'
                  AND mad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $meta_brand
                GROUP BY DATE(mad.date)
                UNION ALL
                SELECT DATE(tad.date) AS dt, SUM(tad.spend_idr_after_tax) AS spent
                FROM tiktok_ads_data tad
                WHERE tad.date >= '$start_date'
                  AND tad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $tiktok_brand
                GROUP BY DATE(tad.date)
                UNION ALL
                SELECT DATE(adsv.date) AS dt, SUM(adsv.spend_idr_after_tax) AS spent
                FROM advertiser_spend adsv
                WHERE adsv.date >= '$start_date'
                  AND adsv.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                GROUP BY DATE(adsv.date)
            ) s
            GROUP BY dt
        ";

        $sql_traffic = "
            SELECT DATE(tpa.date) AS dt,
                   SUM(
                       COALESCE(tpa.live_impression, 0)
                       + COALESCE(tpa.video_impression, 0)
                       + COALESCE(tpa.pcard_impression, 0)
                   ) AS total_traffic
            FROM tiktok_product_analytics tpa
            WHERE tpa.date >= '$start_date'
              AND tpa.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
              $tiktok_product_brand
            GROUP BY DATE(tpa.date)
        ";

        $pos_map = [];
        foreach ($this->mymodel->selectWithQuery($sql_pos) as $r) {
            $pos_map[$r['dt']] = (float)$r['gmv'];
        }
        $spend_map = [];
        foreach ($this->mymodel->selectWithQuery($sql_spend) as $r) {
            $spend_map[$r['dt']] = (float)$r['total_spend'];
        }
        $traffic_map = [];
        foreach ($this->mymodel->selectWithQuery($sql_traffic) as $r) {
            $traffic_map[$r['dt']] = (float)$r['total_traffic'];
        }

        $daily = [];
        $gmv_total = 0.0;
        $spent_total = 0.0;
        $traffic_total = 0.0;

        $start = new DateTime($start_date);
        $end = new DateTime($until_date);
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $gmv = $pos_map[$key] ?? 0.0;
            $spent = $spend_map[$key] ?? 0.0;
            $traffic = $traffic_map[$key] ?? 0.0;
            $ratio = $gmv > 0 ? ($spent / $gmv) * 100 : 0;

            $daily[] = [
                'date' => $key,
                'gmv' => $gmv,
                'spent' => $spent,
                'traffic' => $traffic,
                'ratio' => $ratio
            ];

            $gmv_total += $gmv;
            $spent_total += $spent;
            $traffic_total += $traffic;
        }

        $ratio_total = $gmv_total > 0 ? ($spent_total / $gmv_total) * 100 : 0;

        $result = [
            'daily' => $daily,
            'totals' => [
                'gmv' => $gmv_total,
                'spent' => $spent_total,
                'traffic' => $traffic_total,
                'ratio' => $ratio_total
            ]
        ];

        if (isset($this->cache)) {
            $this->cache->save($cache_key, $result, 60);
        }

        return $result;
    }

    private function get_digiads_data($start_date, $until_date, $brand_filter)
    {
        $cache_key = 'overview_digiads_' . md5($start_date . '|' . $until_date . '|' . (string)$brand_filter);
        if (isset($this->cache)) {
            $cached = $this->cache->get($cache_key);
            if ($cached !== FALSE) {
                return $cached;
            }
        }

        $brand_condition = "";
        if (!empty($brand_filter)) {
            $brand_condition = "AND trx.brand = " . $this->db->escape($brand_filter);
        }

        $firstLetter = $this->safe_brand_letter($brand_filter);

        $shopee_brand = $firstLetter !== '' ? "AND mc.shop_name LIKE '{$firstLetter}%'" : "";
        $tiktok_brand = $firstLetter !== '' ? "AND tad.advertiser_name LIKE '{$firstLetter}%'" : "";
        $meta_brand = $firstLetter !== '' ? "AND ama.account_name LIKE '{$firstLetter}%'" : "";
        $adv_brand = $firstLetter !== '' ? "AND adsv.advertiser_name LIKE '{$firstLetter}%'" : "";

        $sql_pos = "
            SELECT DATE(trx.date) AS dt,
                   SUM(trx.omset_kotor - trx.diskon_penjual) AS gmv
            FROM transaction trx
            WHERE trx.date >= '$start_date'
              AND trx.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
              AND trx.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
              AND trx.type_sub = 'POS'
              $brand_condition
            GROUP BY DATE(trx.date)
        ";

        $sql_spend = "
            SELECT dt, SUM(spent) AS total_spend FROM (
                SELECT DATE(sad.date) AS dt, SUM(sad.expense_after_tax) AS spent
                FROM shopee_ads_data sad
                INNER JOIN marketplace_config mc ON mc.shop_id = sad.shop_id
                WHERE sad.date >= '$start_date'
                  AND sad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $shopee_brand
                GROUP BY DATE(sad.date)
                UNION ALL
                SELECT DATE(mad.date) AS dt, SUM(mad.spend_after_tax) AS spent
                FROM meta_ads_data mad
                INNER JOIN ads_meta_account ama ON mad.account_id = ama.account_id
                WHERE mad.date >= '$start_date'
                  AND mad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $meta_brand
                GROUP BY DATE(mad.date)
                UNION ALL
                SELECT DATE(tad.date) AS dt, SUM(tad.spend_idr_after_tax) AS spent
                FROM tiktok_ads_data tad
                WHERE tad.date >= '$start_date'
                  AND tad.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $tiktok_brand
                GROUP BY DATE(tad.date)
                UNION ALL
                SELECT DATE(adsv.date) AS dt, SUM(adsv.spend_idr_after_tax) AS spent
                FROM advertiser_spend adsv
                WHERE adsv.date >= '$start_date'
                  AND adsv.date < DATE_ADD('$until_date', INTERVAL 1 DAY)
                  $adv_brand
                GROUP BY DATE(adsv.date)
            ) s
            GROUP BY dt
        ";

        $pos_map = [];
        foreach ($this->mymodel->selectWithQuery($sql_pos) as $r) {
            $pos_map[$r['dt']] = (float)$r['gmv'];
        }
        $spend_map = [];
        foreach ($this->mymodel->selectWithQuery($sql_spend) as $r) {
            $spend_map[$r['dt']] = (float)$r['total_spend'];
        }

        $daily = [];
        $gmv_total = 0.0;
        $spent_total = 0.0;

        $start = new DateTime($start_date);
        $end = new DateTime($until_date);
        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $gmv = $pos_map[$key] ?? 0.0;
            $spent = $spend_map[$key] ?? 0.0;
            $ratio = $gmv > 0 ? ($spent / $gmv) * 100 : 0;

            $daily[] = [
                'date' => $key,
                'gmv' => $gmv,
                'spent' => $spent,
                'ratio' => $ratio
            ];

            $gmv_total += $gmv;
            $spent_total += $spent;
        }

        $ratio_total = $gmv_total > 0 ? ($spent_total / $gmv_total) * 100 : 0;

        $result = [
            'daily' => $daily,
            'totals' => [
                'gmv' => $gmv_total,
                'spent' => $spent_total,
                'ratio' => $ratio_total
            ]
        ];

        if (isset($this->cache)) {
            $this->cache->save($cache_key, $result, 60);
        }

        return $result;
    }
}
