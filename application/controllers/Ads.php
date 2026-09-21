<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/BaseController.php';

class Ads extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('mymodel');
        $this->load->library('template');

        // Set public methods (no permission required)
        $this->set_public_methods([]);

        // Override method-to-action mapping if needed
        $this->set_method_permissions([
            'remove' => 'delete',
            'action' => 'edit'
        ]);
    }


    public function index()
    {
        $platform = $this->input->get('m') ?? 'shopee';

        $data['checkbox'] = $_SESSION['checkbox_dashboard'] ?? [];
        $data['checkbox_campaign'] = $_SESSION['checkbox_dashboard_campaign'] ?? [];
        $data['title'] = 'Ads - ' . $this->template->title();

        $url = base_url() . '/ads/' . $this->template->get_param();
        $data['url'] = $this->template->get_param_without_keyword_category($url);
        $data['url_2'] = $this->template->get_param_without('status');
        $data['param'] = $this->template->get_param();

        switch (strtolower($platform)) {
            case 'meta':
                $ids_account = $this->input->get('ids_account') ?? [];
                $start_date = $this->input->get('start_date');
                $until_date = $this->input->get('until_date');

                $data['advertiser'] = $this->mymodel->selectWithQuery("
                    SELECT DISTINCT 
                        a.account_id AS id, 
                        a.account_name AS title 
                    FROM `ads_meta_account` a
                    ORDER BY a.account_name ASC
                ");

                $sql_ads = "
                    SELECT  
                        CONCAT(MIN(a.date), ' - ', MAX(a.date)) AS date,
                        a.account_id,
                        b.account_name,
                        a.campaign_name,
                        SUM(a.spend) AS spend,
                        SUM(a.spend_after_tax) AS spend_after_tax,
                        SUM(a.impressions) AS impressions,
                        SUM(a.clicks) AS clicks,
                        ROUND(AVG(a.ctr), 2) AS ctr,
                        SUM(a.purchases) AS purchases,
                        SUM(a.add_to_cart) AS add_to_cart,
                        ROUND(AVG(a.frequency), 2) AS frequency,
                        SUM(a.reach) AS reach
                    FROM meta_ads_data a
                    INNER JOIN ads_meta_account b ON a.account_id = b.account_id
                ";

                $conditions = [];

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $conditions[] = "a.account_id IN ($ids_account_str)";
                }

                if (!empty($start_date) && !empty($until_date)) {
                    $conditions[] = "a.date BETWEEN '$start_date' AND '$until_date'";
                } else {
                    $date_today = date("Y-m-d");
                    $conditions[] = "a.date = '$date_today'";
                }

                if (!empty($conditions)) {
                    $sql_ads .= " WHERE " . implode(" AND ", $conditions);
                }

                $sql_ads .= "
                    GROUP BY a.account_id, b.account_name, a.campaign_name
                    ORDER BY a.date ASC
                ";

                $data['ads'] = $this->mymodel->selectWithQuery($sql_ads);


                $sql_pivot = "
                    SELECT 
                        a.date,
                        SUM(CASE WHEN a.spend_after_tax IS NOT NULL THEN a.spend_after_tax ELSE 0 END) AS total_spends,
                        SUM(CASE WHEN a.add_to_cart IS NOT NULL THEN a.add_to_cart ELSE 0 END) AS total_add_to_cart,
                        SUM(CASE WHEN a.purchases IS NOT NULL THEN a.purchases ELSE 0 END) AS total_purchases
                    FROM `meta_ads_data` a
                ";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_pivot .= " WHERE a.account_id IN ($ids_account_str)";
                }

                if (!empty($start_date) && !empty($until_date)) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false)
                        ? " AND a.date BETWEEN '$start_date' AND '$until_date'"
                        : " WHERE a.date BETWEEN '$start_date' AND '$until_date'";
                } elseif (empty($start_date) && empty($until_date)) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false)
                        ? " AND a.date = CURDATE()"
                        : " WHERE a.date = CURDATE()";
                }

                $sql_pivot .= " 
                    GROUP BY a.date
                    ORDER BY a.date;
                ";

                $data['pivot'] = $this->mymodel->selectWithQuery($sql_pivot);

                $sql_week = "
                    SELECT 
                        CONCAT(
                            DATE_FORMAT(MIN(date), '%d %b'),
                            ' - ', 
                            DATE_FORMAT(MAX(date), '%d %b')
                        ) AS date_range,
                        ROUND(AVG(frequency), 2) AS avg_frequency, 
                        SUM(reach) AS total_reach,
                        SUM(purchases) AS total_purchases,
                        SUM(reach * frequency) AS total_impressions, -- Estimate impressions
                        SUM(clicks) AS total_clicks,
                        ROUND((SUM(clicks) / SUM(reach * frequency)) * 100, 2) AS ctr_percentage -- CTR estimation
                    FROM 
                        meta_ads_data a
                ";

                $sql_week .= "
                    WHERE date BETWEEN CURDATE() - INTERVAL 7 DAY AND CURDATE()
                ";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_week .= " AND a.account_id IN ($ids_account_str)";
                }

                $data['this_week'] = $this->mymodel->selectWithQuery($sql_week);

                $sql_last_week = "
                    SELECT 
                        CONCAT(
                            DATE_FORMAT(MIN(date), '%d %b'),
                            ' - ', 
                            DATE_FORMAT(MAX(date), '%d %b')
                        ) AS date_range,
                        ROUND(AVG(frequency), 2) AS avg_frequency, 
                        SUM(reach) AS total_reach,
                        SUM(purchases) AS total_purchases,
                        SUM(reach * frequency) AS total_impressions, -- Estimate impressions
                        SUM(clicks) AS total_clicks,
                        ROUND((SUM(clicks) / SUM(reach * frequency)) * 100, 2) AS ctr_percentage -- CTR estimation
                    FROM 
                        meta_ads_data a
                    WHERE 
                        date BETWEEN DATE_SUB(DATE_SUB(CURDATE(), INTERVAL 7 DAY), INTERVAL 7 DAY) 
                        AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_last_week .= " AND a.account_id IN ($ids_account_str)";
                }

                $data['last_week'] = $this->mymodel->selectWithQuery($sql_last_week);

                $sql_campaign = "
                    SELECT 
                        CASE 
                            WHEN campaign_name LIKE '%Collagen%' THEN 'Collagen'
                            WHEN campaign_name LIKE '%Toner%' THEN 'Toner'
                            WHEN campaign_name LIKE '%Misc%G%' THEN 'Miscella-G'
                            WHEN campaign_name LIKE '%Misc%V%' THEN 'Miscella-V'
                            ELSE 'Other'
                        END AS keyword_group,
                        CASE 
                            WHEN campaign_name LIKE '%Collagen%' THEN 'assets/img/product/4.png'
                            WHEN campaign_name LIKE '%Toner%' THEN 'assets/img/product/14.png'
                            WHEN campaign_name LIKE '%Misc%G%' THEN 'assets/img/product/3.png'
                            WHEN campaign_name LIKE '%Misc%V%' THEN 'assets/img/product/27.png'
                            ELSE NULL
                        END AS image,
                        GROUP_CONCAT(DISTINCT advertiser_name ORDER BY advertiser_name ASC SEPARATOR ', ') AS advertiser_names,
                        SUM(atc_qty) AS total_atc_qty,
                        SUM(purchase) AS total_purchase,
                        SUM(atc_idr) AS total_atc_idr,
                        MAX(date) AS latest_date
                    FROM (
                        SELECT DISTINCT
                            a.account_name AS advertiser_name,
                            d.campaign_name,
                            d.add_to_cart_qty AS atc_qty,
                            d.purchases AS purchase,
                            d.add_to_cart AS atc_idr,
                            d.date
                        FROM meta_ads_data d
                        INNER JOIN ads_meta_account a
                            ON d.account_id = a.account_id";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_campaign .= " WHERE a.account_id IN ($ids_account_str)";
                }

                if (!empty($start_date) && !empty($until_date)) {
                    $sql_campaign .= (strpos($sql_campaign, 'WHERE') !== false)
                        ? " AND d.date BETWEEN '$start_date' AND '$until_date'"
                        : " WHERE d.date BETWEEN '$start_date' AND '$until_date'";
                } elseif (empty($start_date) && empty($until_date)) {
                    $sql_campaign .= (strpos($sql_campaign, 'WHERE') !== false)
                        ? " AND d.date = CURDATE()"
                        : " WHERE d.date = CURDATE()";
                }

                $sql_campaign .= "
                    ) AS subquery
                    GROUP BY keyword_group, image
                ";

                $data['campaign'] = $this->mymodel->selectWithQuery($sql_campaign);

                $query = $this->mymodel->selectWithQuery("SELECT * FROM config WHERE id = 'TAX'");
                $data['tax'] = $query;

                $view_path = 'dashboard/dashboard-meta';
                break;


            case 'lazada':
                $ids_account = $this->input->get('ids_account') ?? [];
                $start_date = $this->input->get('start_date');
                $until_date = $this->input->get('until_date');

                $data['campaign'] = $this->mymodel->selectWithQuery("
                    SELECT DISTINCT 
                        a.campaignId AS id, 
                        a.campaignName AS title 
                    FROM `lazada_ads_data` a
                    ORDER BY a.campaignName ASC
                ");


                $sql_ads = "
                    SELECT *
                    FROM lazada_ads_data s
                    INNER JOIN marketplace_config c ON s.shop_id = c.shop_id
                ";

                if (!empty($ids_campaign)) {
                    $ids_campaign_str = implode(",", $ids_campaign);
                    $sql_ads .= " WHERE s.account_id IN ($ids_campaign_str)";
                }

                if (
                    !empty($start_date) && !empty($until_date)
                ) {
                    $sql_ads .= (strpos($sql_ads, 'WHERE') !== false)
                        ? " AND s.date BETWEEN '$start_date' AND '$until_date'"
                        : " WHERE s.date BETWEEN '$start_date' AND '$until_date'";
                } elseif (empty($start_date) && empty($until_date)) {
                    $sql_ads .= (strpos($sql_ads, 'WHERE') !== false)
                        ? " AND s.date = CURDATE()"
                        : " WHERE s.date = CURDATE()";
                }

                $sql_ads .= " ORDER BY s.date ASC";

                $data['ads'] = $this->mymodel->selectWithQuery($sql_ads);


                $sql_pivot = "
                    SELECT 
                        a.date,
                        SUM(CASE WHEN a.spend IS NOT NULL THEN a.spend ELSE 0 END) AS total_spends,
                        SUM(CASE WHEN a.storeA2c IS NOT NULL THEN a.storeA2c ELSE 0 END) AS total_add_to_cart,
                        SUM(CASE WHEN a.productOrders IS NOT NULL THEN a.productOrders ELSE 0 END) AS total_purchases
                    FROM `lazada_ads_data` a
                ";

                if (!empty($ids_campaign)) {
                    $ids_campaign_str = implode(",", $ids_campaign);
                    $sql_pivot .= " WHERE a.account_id IN ($ids_campaign_str)";
                }

                if (!empty($start_date) && !empty($until_date)) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false)
                        ? " AND a.date BETWEEN '$start_date' AND '$until_date'"
                        : " WHERE a.date BETWEEN '$start_date' AND '$until_date'";
                } elseif (empty($start_date) && empty($until_date)) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false)
                        ? " AND a.date = CURDATE()"
                        : " WHERE a.date = CURDATE()";
                }

                $sql_pivot .= " 
                    GROUP BY a.date
                    ORDER BY a.date;
                ";

                $data['pivot'] = $this->mymodel->selectWithQuery($sql_pivot);
                $view_path = 'dashboard/dashboard-lazada';
                break;

            case 'tiktok':
                $ids_advertiser_raw = $this->input->get('ids_advertiser') ?? [];
                if (!is_array($ids_advertiser_raw)) {
                    $ids_advertiser_raw = explode(',', (string)$ids_advertiser_raw);
                }
                $ids_advertiser = [];
                foreach ($ids_advertiser_raw as $id_adv) {
                    $id_adv = (int)$id_adv;
                    if ($id_adv > 0) $ids_advertiser[] = $id_adv;
                }
                $ids_advertiser = array_values(array_unique($ids_advertiser));

                $start_date = $this->input->get('start_date');
                $until_date = $this->input->get('until_date');
                if (empty($start_date)) $start_date = date('Y-m-d');
                if (empty($until_date)) $until_date = date('Y-m-d');

                $compare_mode = $this->input->get('compare_mode') ?: 'off';
                $compare_start_date = $this->input->get('compare_start_date');
                $compare_until_date = $this->input->get('compare_until_date');
                $range_days = (new DateTime($start_date))->diff(new DateTime($until_date))->days + 1;
                $auto_compare_start = date('Y-m-d', strtotime("$start_date -$range_days days"));
                $auto_compare_until = date('Y-m-d', strtotime("$start_date -1 day"));

                if ($compare_mode !== 'on') {
                    $compare_start_date = $auto_compare_start;
                    $compare_until_date = $auto_compare_until;
                    $compare_mode = 'off';
                } else if (empty($compare_start_date) || empty($compare_until_date)) {
                    $compare_start_date = $auto_compare_start;
                    $compare_until_date = $auto_compare_until;
                }

                $data['compare_mode'] = $compare_mode;
                $data['compare_start_date'] = $compare_start_date;
                $data['compare_until_date'] = $compare_until_date;

                $ids_advertiser_str = '';
                if (!empty($ids_advertiser)) {
                    $ids_advertiser_str = implode(',', array_map('intval', $ids_advertiser));
                }

                $adv_where_tad = $ids_advertiser_str ? " AND tad.advertiser_id IN ($ids_advertiser_str) " : "";
                $adv_where_asp = $ids_advertiser_str ? " AND asp.advertiser_id IN ($ids_advertiser_str) " : "";
                $adv_where_tac = $ids_advertiser_str ? " AND tac.advertiser_id IN ($ids_advertiser_str) " : "";
                $asp_date_expr = "COALESCE(DATE(asp.report_date), DATE(asp.stat_time_day), DATE(asp.date_start))";

                $safe_div = function ($num, $den) {
                    if ((float)$den == 0.0) return 0.0;
                    return (float)$num / (float)$den;
                };

                $compute_summary = function ($from, $to) use ($adv_where_tad, $adv_where_asp, $asp_date_expr, $safe_div) {
                    $asp = $this->mymodel->selectWithQuery("
                        SELECT
                            COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS cost_after_tax,
                            COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS net_cost,
                            COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS qty
                        FROM advertiser_spend_product asp
                        WHERE $asp_date_expr BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                        $adv_where_asp
                    ");
                    $asp = !empty($asp) ? $asp[0] : ['gmv' => 0, 'cost_after_tax' => 0, 'net_cost' => 0, 'qty' => 0];

                    $tad = $this->mymodel->selectWithQuery("
                        SELECT
                            COALESCE(SUM(COALESCE(tad.total_onsite_shopping_value_idr, tad.total_onsite_shopping_value, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS cost_after_tax,
                            COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS net_cost,
                            COALESCE(SUM(COALESCE(tad.onsite_shopping, 0)), 0) AS qty
                        FROM tiktok_ads_data tad
                        WHERE DATE(tad.date) BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                        $adv_where_tad
                    ");
                    $tad = !empty($tad) ? $tad[0] : ['gmv' => 0, 'cost_after_tax' => 0, 'net_cost' => 0, 'qty' => 0];

                    $gmv_total = (float)$asp['gmv'] + (float)$tad['gmv'];
                    $cost_after_tax_total = (float)$asp['cost_after_tax'] + (float)$tad['cost_after_tax'];
                    $net_cost_total = (float)$asp['net_cost'] + (float)$tad['net_cost'];
                    $qty_total = (float)$asp['qty'] + (float)$tad['qty'];

                    return [
                        'gmv' => $gmv_total,
                        'cost_spent' => $cost_after_tax_total,
                        'qty_purchase' => $qty_total,
                        'roas' => $safe_div($gmv_total, $net_cost_total),
                        'cpa' => $safe_div($net_cost_total, $qty_total),
                        'components' => [
                            'gmv' => ['asp' => (float)$asp['gmv'], 'tad' => (float)$tad['gmv']],
                            'cost_spent' => ['asp' => (float)$asp['cost_after_tax'], 'tad' => (float)$tad['cost_after_tax']],
                            'net_cost' => ['asp' => (float)$asp['net_cost'], 'tad' => (float)$tad['net_cost']],
                            'qty_purchase' => ['asp' => (float)$asp['qty'], 'tad' => (float)$tad['qty']]
                        ]
                    ];
                };

                $build_daily = function ($from, $to) use ($adv_where_tad, $adv_where_asp, $asp_date_expr, $safe_div) {
                    $range = [];
                    $start_obj = new DateTime($from);
                    $end_obj = new DateTime($to);
                    while ($start_obj <= $end_obj) {
                        $range[] = $start_obj->format('Y-m-d');
                        $start_obj->modify('+1 day');
                    }
                    $daily = [];
                    foreach ($range as $d) {
                        $daily[$d] = [
                            'date' => $d,
                            'gmv' => 0.0,
                            'cost_spent' => 0.0,
                            'qty_purchase' => 0.0,
                            'net_cost' => 0.0,
                            'roas' => 0.0,
                            'cpa' => 0.0
                        ];
                    }

                    $asp_rows = $this->mymodel->selectWithQuery("
                        SELECT
                            $asp_date_expr AS dt,
                            COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS cost_spent,
                            COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS net_cost,
                            COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS qty_purchase
                        FROM advertiser_spend_product asp
                        WHERE $asp_date_expr BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                        $adv_where_asp
                        GROUP BY $asp_date_expr
                    ");
                    foreach ($asp_rows as $r) {
                        $dt = $r['dt'];
                        if (!isset($daily[$dt])) continue;
                        $daily[$dt]['gmv'] += (float)$r['gmv'];
                        $daily[$dt]['cost_spent'] += (float)$r['cost_spent'];
                        $daily[$dt]['net_cost'] += (float)$r['net_cost'];
                        $daily[$dt]['qty_purchase'] += (float)$r['qty_purchase'];
                    }

                    $tad_rows = $this->mymodel->selectWithQuery("
                        SELECT
                            DATE(tad.date) AS dt,
                            COALESCE(SUM(COALESCE(tad.total_onsite_shopping_value_idr, tad.total_onsite_shopping_value, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS cost_spent,
                            COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS net_cost,
                            COALESCE(SUM(COALESCE(tad.onsite_shopping, 0)), 0) AS qty_purchase
                        FROM tiktok_ads_data tad
                        WHERE DATE(tad.date) BETWEEN " . $this->db->escape($from) . " AND " . $this->db->escape($to) . "
                        $adv_where_tad
                        GROUP BY DATE(tad.date)
                    ");
                    foreach ($tad_rows as $r) {
                        $dt = $r['dt'];
                        if (!isset($daily[$dt])) continue;
                        $daily[$dt]['gmv'] += (float)$r['gmv'];
                        $daily[$dt]['cost_spent'] += (float)$r['cost_spent'];
                        $daily[$dt]['net_cost'] += (float)$r['net_cost'];
                        $daily[$dt]['qty_purchase'] += (float)$r['qty_purchase'];
                    }

                    foreach ($daily as $k => $v) {
                        $daily[$k]['roas'] = $safe_div($v['gmv'], $v['net_cost']);
                        $daily[$k]['cpa'] = $safe_div($v['net_cost'], $v['qty_purchase']);
                    }

                    return array_values($daily);
                };

                $summary_main = $compute_summary($start_date, $until_date);
                $summary_compare = $compute_summary($compare_start_date, $compare_until_date);
                $daily_main = $build_daily($start_date, $until_date);
                $daily_compare = $build_daily($compare_start_date, $compare_until_date);

                $data['tiktok_summary_main'] = $summary_main;
                $data['tiktok_summary_compare'] = $summary_compare;
                $data['tiktok_daily_main'] = $daily_main;
                $data['tiktok_daily_compare'] = $daily_compare;

                $breakdown_rows = $this->mymodel->selectWithQuery("
                    SELECT
                        product_name,
                        SUM(spent) AS spent,
                        SUM(gmv) AS gmv,
                        SUM(qty) AS qty
                    FROM (
                        SELECT
                            CASE
                                WHEN LOWER(COALESCE(asp.campaign_name, '')) REGEXP 'miscella[- ]?v|miscella v|miscella-v' THEN 'Miscella-V'
                                WHEN LOWER(COALESCE(asp.campaign_name, '')) REGEXP 'lacto[- ]?v|lacto v|lacto-v' THEN 'Lacto V'
                                ELSE 'General'
                            END AS product_name,
                            COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS spent,
                            COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS qty
                        FROM advertiser_spend_product asp
                        WHERE $asp_date_expr BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                        $adv_where_asp
                        GROUP BY
                            CASE
                                WHEN LOWER(COALESCE(asp.campaign_name, '')) REGEXP 'miscella[- ]?v|miscella v|miscella-v' THEN 'Miscella-V'
                                WHEN LOWER(COALESCE(asp.campaign_name, '')) REGEXP 'lacto[- ]?v|lacto v|lacto-v' THEN 'Lacto V'
                                ELSE 'General'
                            END

                        UNION ALL

                        SELECT
                            CASE
                                WHEN LOWER(COALESCE(tac.campaign_name, '')) REGEXP 'miscella[- ]?v|miscella v|miscella-v' THEN 'Miscella-V'
                                WHEN LOWER(COALESCE(tac.campaign_name, '')) REGEXP 'lacto[- ]?v|lacto v|lacto-v' THEN 'Lacto V'
                                ELSE 'General'
                            END AS product_name,
                            COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, 0)), 0) AS spent,
                            COALESCE(SUM(COALESCE(tac.total_onsite_shopping_value, 0)), 0) AS gmv,
                            COALESCE(SUM(COALESCE(tac.onsite_shopping, 0)), 0) AS qty
                        FROM tiktok_ads_campaign tac
                        WHERE DATE(tac.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                        $adv_where_tac
                        GROUP BY
                            CASE
                                WHEN LOWER(COALESCE(tac.campaign_name, '')) REGEXP 'miscella[- ]?v|miscella v|miscella-v' THEN 'Miscella-V'
                                WHEN LOWER(COALESCE(tac.campaign_name, '')) REGEXP 'lacto[- ]?v|lacto v|lacto-v' THEN 'Lacto V'
                                ELSE 'General'
                            END
                    ) x
                    GROUP BY product_name
                    ORDER BY spent DESC, gmv DESC, qty DESC
                ");
                $data['tiktok_product_breakdown'] = $breakdown_rows;

                $accounts_asp = $this->mymodel->selectWithQuery("
                    SELECT
                        asp.advertiser_id,
                        COALESCE(NULLIF(MAX(asp.advertiser_name), ''), CONCAT('Advertiser #', asp.advertiser_id)) AS advertiser_name,
                        COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gmv,
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS cost_spent,
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS net_cost,
                        COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS qty_purchase
                    FROM advertiser_spend_product asp
                    WHERE $asp_date_expr BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_asp
                    GROUP BY asp.advertiser_id
                ");

                $accounts_tad = $this->mymodel->selectWithQuery("
                    SELECT
                        tad.advertiser_id,
                        COALESCE(NULLIF(MAX(tad.advertiser_name), ''), CONCAT('Advertiser #', tad.advertiser_id)) AS advertiser_name,
                        COALESCE(SUM(COALESCE(tad.total_onsite_shopping_value_idr, tad.total_onsite_shopping_value, 0)), 0) AS gmv,
                        COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS cost_spent,
                        COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS net_cost,
                        COALESCE(SUM(COALESCE(tad.onsite_shopping, 0)), 0) AS qty_purchase
                    FROM tiktok_ads_data tad
                    WHERE DATE(tad.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_tad
                    GROUP BY tad.advertiser_id
                ");

                foreach ($accounts_asp as $k_acc => $acc) {
                    $accounts_asp[$k_acc]['roas'] = $safe_div((float)$acc['gmv'], (float)$acc['net_cost']);
                    $accounts_asp[$k_acc]['cpa'] = $safe_div((float)$acc['net_cost'], (float)$acc['qty_purchase']);
                }
                usort($accounts_asp, function ($a, $b) {
                    return ($b['cost_spent'] <=> $a['cost_spent']);
                });

                foreach ($accounts_tad as $k_acc => $acc) {
                    $accounts_tad[$k_acc]['roas'] = $safe_div((float)$acc['gmv'], (float)$acc['net_cost']);
                    $accounts_tad[$k_acc]['cpa'] = $safe_div((float)$acc['net_cost'], (float)$acc['qty_purchase']);
                }
                usort($accounts_tad, function ($a, $b) {
                    return ($b['cost_spent'] <=> $a['cost_spent']);
                });
                $data['tiktok_accounts_asp'] = $accounts_asp;
                $data['tiktok_accounts_tad'] = $accounts_tad;

                $tiktok_tad_table = $this->mymodel->selectWithQuery("
                    SELECT
                        MIN(DATE(tad.date)) AS start_date_ref,
                        MAX(DATE(tad.date)) AS end_date_ref,
                        tad.advertiser_id,
                        COALESCE(NULLIF(MAX(tad.advertiser_name), ''), CONCAT('Advertiser #', tad.advertiser_id)) AS account,
                        COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) AS spend_idr,
                        COALESCE(SUM(COALESCE(tad.clicks, 0)), 0) AS clicks,
                        COALESCE(SUM(COALESCE(tad.onsite_on_web_cart, 0)), 0) AS onsite_add_to_cart,
                        COALESCE(SUM(COALESCE(tad.total_onsite_on_web_cart_value_idr, tad.total_onsite_on_web_cart_value, tad.onsite_on_web_cart, 0)), 0) AS total_onsite_add_to_cart,
                        COALESCE(SUM(COALESCE(tad.onsite_shopping, 0)), 0) AS onsite_shopping,
                        COALESCE(SUM(COALESCE(tad.total_onsite_shopping_value_idr, tad.total_onsite_shopping_value, 0)), 0) AS gross_revenue,
                        ROUND(COALESCE(AVG(COALESCE(tad.frequency, 0)), 0), 2) AS frequency,
                        COALESCE(SUM(COALESCE(tad.reach, 0)), 0) AS reach
                    FROM tiktok_ads_data tad
                    WHERE DATE(tad.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_tad
                    GROUP BY tad.advertiser_id
                    HAVING COALESCE(SUM(COALESCE(tad.spend_idr_after_tax, 0)), 0) > 0
                    ORDER BY spend_idr DESC, account ASC
                ");
                $data['tiktok_tad_table'] = $tiktok_tad_table;

                $tiktok_asp_table = $this->mymodel->selectWithQuery("
                    SELECT
                        MIN($asp_date_expr) AS start_date_ref,
                        MAX($asp_date_expr) AS end_date_ref,
                        asp.advertiser_id,
                        COALESCE(NULLIF(MAX(asp.advertiser_name), ''), CONCAT('Advertiser #', asp.advertiser_id)) AS account,
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS spend_idr,
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS spend_after_tax,
                        COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS orders,
                        CASE
                            WHEN COALESCE(SUM(COALESCE(asp.orders, 0)), 0) = 0 THEN 0
                            ELSE COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) / COALESCE(SUM(COALESCE(asp.orders, 0)), 0)
                        END AS cost_per_order,
                        COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gross_revenue,
                        CASE
                            WHEN COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) = 0 THEN 0
                            ELSE COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) / COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0)
                        END AS roi
                    FROM advertiser_spend_product asp
                    WHERE $asp_date_expr BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_asp
                    GROUP BY asp.advertiser_id
                    ORDER BY spend_idr DESC, account ASC
                ");
                $data['tiktok_asp_table'] = $tiktok_asp_table;

                $asp_campaign_rows = $this->mymodel->selectWithQuery("
                    SELECT
                        asp.advertiser_id,
                        COALESCE(NULLIF(MAX(asp.advertiser_name), ''), CONCAT('Advertiser #', asp.advertiser_id)) AS advertiser_name,
                        MIN($asp_date_expr) AS start_date_ref,
                        MAX($asp_date_expr) AS end_date_ref,
                        COALESCE(NULLIF(asp.campaign_id, ''), '-') AS campaign_id,
                        COALESCE(NULLIF(asp.campaign_name, ''), '-') AS campaign_name,
                        COALESCE(NULLIF(MAX(asp.operation_status), ''), '-') AS operation_status,
                        COALESCE(NULLIF(MAX(asp.schedule_type), ''), '-') AS schedule_type,
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) AS cost_spent,
                        COALESCE(SUM(COALESCE(asp.orders, 0)), 0) AS qty_purchase,
                        COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) AS gmv
                    FROM advertiser_spend_product asp
                    WHERE $asp_date_expr BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_asp
                    GROUP BY asp.advertiser_id, asp.campaign_id, asp.campaign_name
                    HAVING
                        COALESCE(SUM(COALESCE(asp.cost_after_tax, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(asp.gross_revenue, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(asp.orders, 0)), 0) > 0
                    ORDER BY asp.advertiser_id ASC, gmv DESC, cost_spent DESC
                ");

                $asp_campaign_map = [];
                foreach ($asp_campaign_rows as $row) {
                    $id_adv = (string)($row['advertiser_id'] ?? '');
                    if ($id_adv === '') continue;
                    if (!isset($asp_campaign_map[$id_adv])) $asp_campaign_map[$id_adv] = [];
                    $cost = (float)($row['cost_spent'] ?? 0);
                    $qty = (float)($row['qty_purchase'] ?? 0);
                    $gmv = (float)($row['gmv'] ?? 0);
                    $row['cpo'] = $safe_div($cost, $qty);
                    $row['roi'] = $safe_div($gmv, $cost);
                    $asp_campaign_map[$id_adv][] = $row;
                }
                $data['tiktok_asp_campaign_map'] = $asp_campaign_map;

                $accounts_tac = $this->mymodel->selectWithQuery("
                    SELECT
                        tac.advertiser_id,
                        COALESCE(NULLIF(MAX(adv.advertiser_name), ''), CONCAT('Advertiser #', tac.advertiser_id)) AS advertiser_name,
                        COALESCE(SUM(COALESCE(tac.total_onsite_shopping_value, 0)), 0) AS gmv,
                        COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, 0)), 0) AS cost_spent,
                        COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, 0)), 0) AS net_cost,
                        COALESCE(SUM(COALESCE(tac.onsite_shopping, 0)), 0) AS qty_purchase
                    FROM tiktok_ads_campaign tac
                    LEFT JOIN (
                        SELECT advertiser_id, MAX(advertiser_name) AS advertiser_name
                        FROM tiktok_ads_data
                        GROUP BY advertiser_id
                    ) adv ON adv.advertiser_id = tac.advertiser_id
                    WHERE DATE(tac.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_tac
                    GROUP BY tac.advertiser_id
                ");
                foreach ($accounts_tac as $k_acc => $acc) {
                    $accounts_tac[$k_acc]['roas'] = $safe_div((float)$acc['gmv'], (float)$acc['net_cost']);
                    $accounts_tac[$k_acc]['cpa'] = $safe_div((float)$acc['net_cost'], (float)$acc['qty_purchase']);
                }
                usort($accounts_tac, function ($a, $b) {
                    return ($b['cost_spent'] <=> $a['cost_spent']);
                });
                $data['tiktok_accounts_tac'] = $accounts_tac;

                $campaign_rows = $this->mymodel->selectWithQuery("
                    SELECT
                        tac.advertiser_id,
                        COALESCE(NULLIF(MAX(adv.advertiser_name), ''), CONCAT('Advertiser #', tac.advertiser_id)) AS advertiser_name,
                        MIN(DATE(tac.date)) AS start_date_ref,
                        MAX(DATE(tac.date)) AS end_date_ref,
                        COALESCE(NULLIF(tac.campaign_name, ''), '-') AS campaign_name,
                        COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, tac.spend_idr, tac.spend, 0)), 0) AS cost_spent,
                        COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, tac.spend_idr, tac.spend, 0)), 0) AS net_cost,
                        COALESCE(SUM(COALESCE(tac.total_onsite_shopping_value, 0)), 0) AS gmv,
                        COALESCE(SUM(COALESCE(tac.onsite_shopping, 0)), 0) AS qty_purchase,
                        COALESCE(SUM(COALESCE(tac.clicks, 0)), 0) AS clicks,
                        COALESCE(SUM(COALESCE(tac.impressions, 0)), 0) AS impressions,
                        COALESCE(SUM(COALESCE(tac.reach, 0)), 0) AS reach,
                        ROUND(COALESCE(AVG(COALESCE(tac.frequency, 0)), 0), 2) AS frequency
                    FROM tiktok_ads_campaign tac
                    LEFT JOIN (
                        SELECT advertiser_id, MAX(advertiser_name) AS advertiser_name
                        FROM tiktok_ads_data
                        GROUP BY advertiser_id
                    ) adv ON adv.advertiser_id = tac.advertiser_id
                    WHERE DATE(tac.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    $adv_where_tac
                    GROUP BY tac.advertiser_id, tac.campaign_name
                    HAVING
                        COALESCE(SUM(COALESCE(tac.spend_idr_after_tax, tac.spend_idr, tac.spend, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(tac.total_onsite_shopping_value, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(tac.onsite_shopping, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(tac.clicks, 0)), 0) > 0
                        OR COALESCE(SUM(COALESCE(tac.impressions, 0)), 0) > 0
                    ORDER BY tac.advertiser_id ASC, tac.campaign_name ASC
                ");

                $campaign_map = [];
                foreach ($campaign_rows as $row) {
                    $id_adv = (string)($row['advertiser_id'] ?? '');
                    if ($id_adv === '') continue;
                    if (!isset($campaign_map[$id_adv])) $campaign_map[$id_adv] = [];
                    $row['roas'] = $safe_div((float)$row['gmv'], (float)$row['net_cost']);
                    $row['cpa'] = $safe_div((float)$row['net_cost'], (float)$row['qty_purchase']);
                    $row['ctr'] = $safe_div(((float)$row['clicks'] * 100), (float)$row['impressions']);
                    $row['cpc'] = $safe_div((float)$row['cost_spent'], (float)$row['clicks']);
                    $row['cpm'] = $safe_div(((float)$row['cost_spent'] * 1000), (float)$row['impressions']);
                    $campaign_map[$id_adv][] = $row;
                }
                $data['tiktok_campaign_map'] = $campaign_map;

                $data['advertiser'] = $this->mymodel->selectWithQuery("
                    SELECT
                        a.advertiser_id AS id,
                        COALESCE(NULLIF(MAX(a.advertiser_name), ''), CONCAT('Advertiser #', a.advertiser_id)) AS title
                    FROM tiktok_ads_data a
                    WHERE DATE(a.date) BETWEEN " . $this->db->escape($start_date) . " AND " . $this->db->escape($until_date) . "
                    GROUP BY a.advertiser_id
                    HAVING COALESCE(SUM(COALESCE(a.spend_idr_after_tax, 0)), 0) > 0
                    ORDER BY title ASC
                ");

                $view_path = 'dashboard/dashboard-tiktok';
                break;



            case 'shopee':
                $ids_account = $this->input->get('ids_account') ?? [];
                $start_date = $this->input->get('start_date');
                $until_date = $this->input->get('until_date');
                $data['advertiser'] = $this->mymodel->selectWithQuery("
                    SELECT DISTINCT 
                        c.shop_id AS id, 
                        c.shop_name AS title 
                    FROM shopee_ads_data s
                    INNER JOIN marketplace_config c ON s.shop_id = c.shop_id
                    ORDER BY c.shop_name ASC
                ");

                $sql_ads = "
                    SELECT 
                        CONCAT(MIN(s.date), ' - ', MAX(s.date)) AS date,
                        c.shop_name,
                        c.shop_id,
                        SUM(s.impression) AS impression,
                        SUM(s.clicks) AS clicks,
                        ROUND(AVG(s.ctr), 2) AS ctr,
                        SUM(s.broad_order) AS broad_order,
                        SUM(s.broad_conversions) AS broad_conversions,
                        SUM(s.broad_item_sold) AS broad_item_sold,
                        SUM(s.broad_gmv) AS broad_gmv,
                        ROUND(AVG(s.broad_roas), 2) AS broad_roas,
                        SUM(s.expense) AS expense,
                        SUM(s.expense_after_tax) AS expense_after_tax,
                        SUM(s.tax) AS tax
                    FROM shopee_ads_data s
                    INNER JOIN marketplace_config c ON s.shop_id = c.shop_id
                ";

                $conditions = [];

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $conditions[] = "s.shop_id IN ($ids_account_str)";
                }

                if (!empty($start_date) && !empty($until_date)) {
                    $conditions[] = "s.date BETWEEN '$start_date' AND '$until_date'";
                } else {
                    $date_today = date("Y-m-d");
                    $conditions[] = "s.date = '$date_today'";
                }

                if (!empty($conditions)) {
                    $sql_ads .= " WHERE " . implode(" AND ", $conditions);
                }

                $sql_ads .= "
                    GROUP BY c.shop_name, c.shop_id
                    ORDER BY MIN(s.date) DESC
                ";


                $data['ads'] = $this->mymodel->selectWithQuery($sql_ads);

                $sql_pivot = "
                    SELECT 
                        a.date,
                        SUM(CASE WHEN a.expense_after_tax IS NOT NULL THEN a.expense_after_tax ELSE 0 END) AS total_spends,
                        SUM(CASE WHEN a.broad_order IS NOT NULL THEN a.broad_order ELSE 0 END) AS total_orders,
                        SUM(CASE WHEN a.broad_gmv IS NOT NULL THEN a.broad_gmv ELSE 0 END) AS total_gmv
                    FROM `shopee_ads_data` a
                    INNER JOIN marketplace_config c ON a.shop_id = c.shop_id
                ";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_pivot .= " WHERE a.shop_id IN ($ids_account_str)";
                }

                if (
                    !empty($start_date) && !empty($until_date)
                ) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false) ? " AND a.date BETWEEN '$start_date' AND '$until_date'" : " WHERE a.date BETWEEN '$start_date' AND '$until_date'";
                } elseif (empty($start_date) && empty($until_date)) {
                    $sql_pivot .= (strpos($sql_pivot, 'WHERE') !== false) ? " AND a.date = CURDATE()" : " WHERE a.date = CURDATE()";
                }

                $sql_pivot .= " 
                    GROUP BY a.date
                    ORDER BY a.date;
                ";

                $data['pivot'] = $this->mymodel->selectWithQuery($sql_pivot);

                $sql_week = "
                    SELECT 
                        SUM(expense_after_tax) AS total_spend,
                        SUM(broad_order) AS total_purchase,
                        SUM(broad_gmv) AS total_gmv,
                        SUM(broad_roas) AS total_roas,
                        date
                    FROM `shopee_ads_data`
                    WHERE date BETWEEN CURDATE() - INTERVAL 10 DAY AND CURDATE()
                ";

                if (!empty($ids_account)) {
                    $ids_account_str = implode(",", $ids_account);
                    $sql_week .= " AND shop_id IN ($ids_account_str)";
                }

                $sql_week .= " 
                    GROUP BY date
                    ORDER BY date;
                ";

                $data['week'] = $this->mymodel->selectWithQuery($sql_week);

                $view_path = 'dashboard/dashboard-shopee';
                break;

            case 'overview':
                $today = date('Y-m-d');
                $start_date = $this->input->get('start_date');
                $until_date = $this->input->get('until_date');
                $brand_filter = $this->input->get('brand');

                if (empty($start_date)) {
                    $start_date = date('Y-m-d', strtotime("$today -6 days"));
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
                $view_path = 'dashboard/dashboard-overview';

                break;
        }

        $data['content'] = $this->load->view($view_path, $data, true);

        $this->load->view('TemplateDashboard', $data);
    }

    public function update_pajak()
    {
        $user = $_SESSION['user'];

        $dt = $_POST['dt'];
        $pajak = $dt['pajak'] / 100;
        $tanggal_mulai_berlaku = $dt['tanggal_mulai_berlaku'];

        $updated_at = date("Y-m-d H:i:s");
        $updated_by = $user['id'];

        $data = [
            'tax' => $pajak,
            'updated_at' => $updated_at,
            'updated_by' => $updated_by
        ];

        $this->db->where('id', 'TAX');
        $result = $this->db->update('config', $data);

        if ($result) {
            $this->db->set('tax', "{$pajak}", false);
            $this->db->set('spend_after_tax', "spend * {$pajak}", false);
            $this->db->where('date >=', $tanggal_mulai_berlaku);
            $this->db->where('date <=', date("Y-m-d"));
            $this->db->update('meta_ads_data');

            $this->db->set('tax', "0.12", false);
            $this->db->set('spend_idr_after_tax', "spend_idr + (spend_idr * {$pajak})", false);
            $this->db->where('date >=', $tanggal_mulai_berlaku);
            $this->db->where('date <=', date("Y-m-d"));
            $this->db->update('tiktok_ads_data');

            $this->db->set('tax', "{$pajak}", false);
            $this->db->set('expense_after_tax', "expense * {$pajak}", false);
            $this->db->where('date >=', $tanggal_mulai_berlaku);
            $this->db->where('date <=', date("Y-m-d"));
            $this->db->update('shopee_ads_data');

            $msg = 'Update data berhasil!';
            echo $this->template->alert_success($msg);
            redirect(base_url('ads?m=overview'));
        } else {
            $msg = 'Update data tidak berhasil!';
            echo $this->template->alert_danger($msg);
            redirect(base_url('ads?m=overview'));
        }
    }
}
