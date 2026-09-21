<?php
$viewMode = isset($view_mode) ? $view_mode : (isset($_GET['view']) ? $_GET['view'] : 'table');
$viewMode = in_array($viewMode, array('card', 'table'), true) ? $viewMode : 'table';
$template_helper = isset($template) ? $template : null;

if (!function_exists('crm_escape')) {
    function crm_escape($value)
    {
        if (function_exists('html_escape')) {
            return html_escape($value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('crm_safe_html')) {
    function crm_safe_html($value, $default = '-')
    {
        if ($value === null) {
            return $default;
        }
        if (is_string($value) && trim($value) === '') {
            return $default;
        }
        if (is_array($value)) {
            return $default;
        }
        return crm_escape($value);
    }
}

if (!function_exists('crm_initials')) {
    function crm_initials($name)
    {
        $name = trim((string) $name);
        if ($name === '' || $name === '-') {
            return 'NA';
        }

        if (function_exists('mb_substr')) {
            $parts = preg_split('/\s+/u', $name) ?: array();
            $first = isset($parts[0]) ? mb_substr($parts[0], 0, 1, 'UTF-8') : '';
            $second = '';
            if (count($parts) > 1) {
                $second = mb_substr($parts[1], 0, 1, 'UTF-8');
            } else {
                $second = mb_substr($parts[0], 1, 1, 'UTF-8');
            }
            $initials = strtoupper($first . $second);
            return $initials !== '' ? $initials : strtoupper(mb_substr($name, 0, 2, 'UTF-8'));
        }

        $parts = preg_split('/\s+/', $name) ?: array();
        $first = isset($parts[0][0]) ? $parts[0][0] : '';
        $second = '';
        if (isset($parts[1][0])) {
            $second = $parts[1][0];
        } elseif (isset($parts[0][1])) {
            $second = $parts[0][1];
        }
        $initials = strtoupper($first . $second);
        return $initials !== '' ? $initials : 'NA';
    }
}

if (!function_exists('crm_format_indo')) {
    function crm_format_indo($template_helper, $date_value)
    {
        if (!$date_value) {
            return '-';
        }
        if ($template_helper && method_exists($template_helper, 'date_format_indo')) {
            return $template_helper->date_format_indo($date_value);
        }
        $timestamp = strtotime($date_value);
        if (!$timestamp) {
            return '-';
        }
        return date('d/m/Y', $timestamp);
    }
}

$monthNames = array(
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

$today = new DateTimeImmutable('today');
$items = array();
$gridRows = array();
$index = $start;

foreach ($data as $row) {
    $id = isset($row['id']) ? (int) $row['id'] : 0;
    $serial = $index + 1;

    $lastOrderRaw = $row['last_order'] ?? '';
    $daysSinceLastOrder = null;
    $orderStatusText = 'Belum memiliki transaksi!';

    if (!empty($lastOrderRaw)) {
        try {
            $lastOrder = new DateTimeImmutable($lastOrderRaw);
            $diff = $today->diff($lastOrder);
            $daysSinceLastOrder = (int) $diff->format('%a');
            $orderStatusText = $daysSinceLastOrder > 0
                ? "Transaksi terakhir $daysSinceLastOrder yang lalu."
                : "Transaksi terakhir hari ini.";
        } catch (Exception $e) {
            $orderStatusText = 'Belum memiliki transaksi!';
        }
    }

    $fullNameRaw = trim($row['full_name'] ?? '');
    $fullNameDisplay = $fullNameRaw !== '' ? $fullNameRaw : '-';
    $initials = crm_initials($fullNameDisplay);

    $shopName = trim($row['shop_name'] ?? '');
    if ($shopName !== '') {
        $shopNameDisplay = $shopName;
    } else {
        $shopNameDisplay = '-';
    }

    $phoneOriginal = trim($row['phone'] ?? '');
    $bgColor = '#ed7881';
    if ($phoneOriginal === '' || strpos($phoneOriginal, '*') === false) {
        $bgColor = '#60bb55';
    }
    $phoneNormalized = $phoneOriginal;
    if ($phoneNormalized !== '' && substr($phoneNormalized, 0, 1) === '0') {
        $phoneNormalized = '62' . substr($phoneNormalized, 1);
    }
    $phoneDisplay = $phoneNormalized !== '' ? $phoneNormalized : '-';
    $phoneDigits = preg_replace('/\D+/', '', $phoneNormalized);
    $waLink = $phoneDigits !== '' ? 'https://wa.me/' . $phoneDigits : '';

    $birthDateDisplay = '-';
    if (!empty($row['birth_date'])) {
        $birthTs = strtotime($row['birth_date']);
        if ($birthTs) {
            $monthIndex = (int) date('n', $birthTs);
            $monthShort = substr(isset($monthNames[$monthIndex]) ? $monthNames[$monthIndex] : '', 0, 3);
            $birthDateDisplay = date('d', $birthTs) . ' ' . $monthShort . ' ' . date('Y', $birthTs);
        }
    }

    $descRaw = trim($row['desc'] ?? '');
    $descDisplay = $descRaw !== '' ? $descRaw : '-';

    $csRaw = trim($row['cs'] ?? '');
    $csDisplay = $csRaw !== '' ? $csRaw : '-';

    $cbClRaw = trim((string)($row['customer_label'] ?? ''));
    if ($cbClRaw === '') {
        $cbClRaw = trim((string)($row['cb_cl'] ?? ''));
    }
    $cbCl = $cbClRaw !== '' ? $cbClRaw : '-';

    $marketplaceRaw = trim($row['marketplace'] ?? '');
    $marketplaceDisplay = $marketplaceRaw !== '' ? $marketplaceRaw : '-';

    $usernameRaw = trim($row['username'] ?? '');
    $usernameDisplay = $usernameRaw !== '' ? $usernameRaw : '-';

    $addressRaw = trim($row['address'] ?? '');
    $addressDisplay = $addressRaw !== '' ? $addressRaw : '-';
    $cityTextRaw = trim($row['city_text'] ?? '');
    $cityTextDisplay = $cityTextRaw !== '' ? $cityTextRaw : '-';

    $createdAtRaw = $row['created_at'] ?? '';
    $createdAtDisplay = $createdAtRaw !== '' ? $createdAtRaw : '-';

    $firstOrderRaw = $row['first_order'] ?? '';
    $firstOrderFormatted = (!empty($firstOrderRaw) && strtotime($firstOrderRaw)) ? date('Y-m-d H:i', strtotime($firstOrderRaw)) : '-';

    $lastOrderFormatted = (!empty($lastOrderRaw) && strtotime($lastOrderRaw)) ? date('Y-m-d H:i', strtotime($lastOrderRaw)) : '-';

    $countOrderRaw = isset($row['count_order']) ? (int) $row['count_order'] : 0;
    $countOrderDisplay = (string) $countOrderRaw;

    $idBuyerRaw = trim($row['id_buyer'] ?? '');
    $idBuyerDisplay = $idBuyerRaw !== '' ? $idBuyerRaw : '-';

    $keluhanRaw = trim((string)($row['keluhan'] ?? ''));
    $keluhanDisplay = $keluhanRaw !== '' ? $keluhanRaw : '-';

    $customerExperienceRaw = trim((string)($row['customer_experience'] ?? ''));
    $customerExperienceDisplay = $customerExperienceRaw !== '' ? $customerExperienceRaw : '-';

    $joinKomunitasRaw = trim((string)($row['join_komunitas'] ?? ''));
    if ($joinKomunitasRaw === '') {
        $joinKomunitasDisplay = '-';
    } else {
        $joinLower = strtolower($joinKomunitasRaw);
        $joinKomunitasDisplay = in_array($joinLower, array('1', 'y', 'yes', 'ya', 'true'), true) ? 'Ya' : 'Tidak';
    }

    $campaignBroadcastRaw = trim((string)($row['campaign_broadcast'] ?? ''));
    $campaignBroadcastDisplay = $campaignBroadcastRaw !== '' ? str_replace(',', ', ', $campaignBroadcastRaw) : '-';
    $campaignBroadcastId = isset($row['campaign_broadcast_id']) ? (int)$row['campaign_broadcast_id'] : 0;
    $campaignBroadcastUrl = $campaignBroadcastId > 0 ? base_url('crm/campaign-detail?id=' . $campaignBroadcastId . '&customer_id=' . $id) : '';

    $giftHtml = '-';
    $giftText = '-';
    $giftPartsHtml = array();
    $giftPartsText = array();
    $giftData = json_decode($row['gift'] ?? '[]', true);
    if (is_array($giftData) && !empty($giftData)) {
        foreach ($giftData as $giftRow) {
            if (!is_array($giftRow) || empty($giftRow)) {
                continue;
            }
            $giftDateRaw = $giftRow['date'] ?? '';
            $giftDateDisplay = $giftDateRaw ? crm_format_indo($template_helper, $giftDateRaw) : '-';
            $giftTitleRaw = $giftRow['title'] ?? '-';
            $giftPartsHtml[] = crm_escape($giftDateDisplay) . ' : ' . crm_escape($giftTitleRaw);
            $giftPartsText[] = ($giftDateDisplay ?: '-') . ' : ' . $giftTitleRaw;
        }
        if (!empty($giftPartsHtml)) {
            $giftHtml = implode('<br>', $giftPartsHtml);
        }
        if (!empty($giftPartsText)) {
            $giftText = implode("\n", array_map('trim', $giftPartsText));
        }
    }

    $testimoniHtml = '-';
    $testimoniText = '-';
    $testimoniPartsHtml = array();
    $testimoniPartsText = array();
    $testimoniData = json_decode($row['testimoni'] ?? '[]', true);

    if (is_array($testimoniData) && !empty($testimoniData)) {
        foreach ($testimoniData as $testimoniRow) {
            if (!is_array($testimoniRow) || empty($testimoniRow)) {
                continue;
            }
            $testimoniDateRaw = $testimoniRow['date'] ?? '';
            $testimoniDateDisplay = $testimoniDateRaw ? crm_format_indo($template_helper, $testimoniDateRaw) : '-';
            $testimoniDescRaw = $testimoniRow['desc'] ?? '-';
            $testimoniPartsHtml[] = crm_escape($testimoniDateDisplay) . ' : ' . crm_escape($testimoniDescRaw);
            $testimoniPartsText[] = ($testimoniDateDisplay ?: '-') . ' : ' . $testimoniDescRaw;
        }
        if (!empty($testimoniPartsHtml)) {
            $testimoniHtml = implode('<br>', $testimoniPartsHtml);
        }
        if (!empty($testimoniPartsText)) {
            $testimoniText = implode("\n", array_map('trim', $testimoniPartsText));
        }
    }

    $historyEntries = array();
    $historyTextParts = array();
    $orderIdParts = array();
    $ordersRaw = json_decode($row['pesanan'] ?? '[]', true);
    if (is_array($ordersRaw) && !empty($ordersRaw)) {
        foreach ($ordersRaw as $orderRow) {
            if (!is_array($orderRow)) {
                continue;
            }

            $orderId = isset($orderRow['order_id']) ? (string) $orderRow['order_id'] : '';
            $orderIdDisplay = $orderId !== '' ? $orderId : '-';
            if ($orderId !== '') {
                $orderIdParts[] = $orderId;
            }

            $orderDateDisplay = '-';
            $orderDateParam = '';
            if (!empty($orderRow['date'])) {
                $orderTs = strtotime($orderRow['date']);
                if ($orderTs) {
                    $orderDateDisplay = date('d/m/Y', $orderTs);
                    $orderDateParam = date('Y-m-d', $orderTs);
                }
            }

            $orderStatus = isset($orderRow['order_status']) ? (string) $orderRow['order_status'] : '-';

            $orderItems = array();
            $orderItemsText = array();
            if (!empty($orderRow['data']) && is_array($orderRow['data'])) {
                foreach ($orderRow['data'] as $orderItemRow) {
                    if (!is_array($orderItemRow)) {
                        continue;
                    }
                    $qty = isset($orderItemRow['qty']) ? (int) $orderItemRow['qty'] : 0;
                    $itemNameRaw = $orderItemRow['item_name'] ?? '-';
                    $orderItems[] = array(
                        'qty' => $qty,
                        'name' => crm_escape($itemNameRaw),
                    );
                    $orderItemsText[] = trim($qty . ' x ' . $itemNameRaw);
                }
            }

            $orderUrl = '';
            if ($orderId !== '') {
                $orderUrl = base_url() . '/transaction?keyword_category=Order ID&keyword=' . urlencode($orderId);
                if ($orderDateParam !== '') {
                    $orderUrl .= '&start_date=' . $orderDateParam . '&until_date=' . $orderDateParam;
                }
            }

            $historyEntries[] = array(
                'order_id' => $orderId,
                'order_url' => $orderUrl,
                'date_display' => $orderDateDisplay,
                'status' => $orderStatus,
                'items' => $orderItems,
            );

            $summary = $orderIdDisplay;
            if ($orderDateDisplay !== '-') {
                $summary .= ' (' . $orderDateDisplay . ')';
            }
            $summary .= ' - ' . $orderStatus;
            if (!empty($orderItemsText)) {
                $summary .= ' | ' . implode(', ', array_filter($orderItemsText));
            }
            $historyTextParts[] = $summary;
        }
    }
    $historyText = !empty($historyTextParts) ? implode("\n", array_map('trim', $historyTextParts)) : '-';
    $noPesananDisplay = !empty($orderIdParts) ? implode("\n", $orderIdParts) : '-';

    // Aggregate all products purchased by customer
    $productsMap = array();
    if (is_array($ordersRaw) && !empty($ordersRaw)) {
        foreach ($ordersRaw as $orderRow) {
            if (!is_array($orderRow) || empty($orderRow['data'])) {
                continue;
            }
            foreach ($orderRow['data'] as $orderItemRow) {
                if (!is_array($orderItemRow)) {
                    continue;
                }
                $itemName = isset($orderItemRow['item_name']) ? trim($orderItemRow['item_name']) : '';
                if ($itemName === '') {
                    continue;
                }
                $qty = isset($orderItemRow['qty']) ? (int) $orderItemRow['qty'] : 0;
                if (!isset($productsMap[$itemName])) {
                    $productsMap[$itemName] = 0;
                }
                $productsMap[$itemName] += $qty;
            }
        }
    }
    $productNameParts = array();
    $productQtyParts = array();
    foreach ($productsMap as $productName => $totalQty) {
        $productNameParts[] = $productName;
        $productQtyParts[] = (string) $totalQty;
    }
    $productsDisplay = !empty($productsMap) ? implode(', ', array_map(function ($name, $qty) {
        return $qty . ' x ' . $name;
    }, $productNameParts, $productQtyParts)) : '-';
    $productNameDisplay = !empty($productNameParts) ? implode("\n", $productNameParts) : '-';
    $productQtyDisplay = !empty($productQtyParts) ? implode("\n", $productQtyParts) : '-';

    $detailUrl = base_url() . 'crm/detail?id=' . $id . '&brand=' . urlencode($row['brand'] ?? '');

    $item = array(
        'id' => $id,
        'index' => $serial,
        'raw_index' => $index,
        'is_manual' => $row['is_manual'],
        'marketplace_raw' => $row['marketplace'],
        'brand' => $row['brand'],
        'order_id_raw' => $row['order_id'],
        'order_status_text' => $orderStatusText,
        'full_name' => $fullNameDisplay,
        'initials' => $initials,
        'bg_color' => $bgColor,
        'phone_display' => $phoneDisplay,
        'phone_digits' => $phoneDigits,
        'wa_link' => $waLink,
        'birth_date' => $birthDateDisplay,
        'description' => crm_escape($descDisplay),
        'description_text' => $descDisplay,
        'cs' => crm_escape($csDisplay),
        'cs_text' => $csDisplay,
        'cb_cl' => $cbCl,
        'marketplace' => crm_escape($marketplaceDisplay),
        'marketplace_text' => $marketplaceDisplay,
        'username' => crm_escape($usernameDisplay),
        'username_text' => $usernameDisplay,
        'address' => crm_escape($addressDisplay),
        'address_text' => $addressDisplay,
        'city_text' => crm_escape($cityTextDisplay),
        'city_text_text' => $cityTextDisplay,
        'created_at' => crm_escape($createdAtDisplay),
        'created_at_raw' => $createdAtRaw,
        'first_order' => $firstOrderFormatted,
        'first_order_raw' => $firstOrderRaw,
        'last_order' => $lastOrderFormatted,
        'last_order_raw' => $lastOrderRaw,
        'count_order' => $countOrderDisplay,
        'count_order_num' => $countOrderRaw,
        'id_buyer' => crm_escape($idBuyerDisplay),
        'id_buyer_text' => $idBuyerDisplay,
        'gift_html' => $giftHtml,
        'gift_text' => $giftText,
        'testimoni_html' => $testimoniHtml,
        'testimoni_text' => $testimoniText,
        'order_history' => $historyEntries,
        'order_history_text' => $historyText,
        'has_order_history' => !empty($historyEntries),
        'detail_url' => $detailUrl,
        'shop_name' => $shopNameDisplay,
        'keluhan' => $keluhanDisplay,
        'customer_experience' => $customerExperienceDisplay,
        'join_komunitas' => $joinKomunitasDisplay,
        'campaign_broadcast' => $campaignBroadcastDisplay,
        'campaign_broadcast_id' => $campaignBroadcastId,
        'campaign_broadcast_url' => $campaignBroadcastUrl,
        'no_pesanan' => $noPesananDisplay,
    );

    $gridRows[] = array(
        'id' => $id,
        'serial' => $serial,
        'full_name' => $fullNameDisplay,
        'initials' => $initials,
        'cb_cl' => $cbCl,
        'marketplace' => $marketplaceDisplay,
        'marketplace_raw' => $row['marketplace'],
        'username' => $usernameDisplay,
        'phone' => $phoneDisplay,
        'phone_digits' => $phoneDigits,
        'wa_link' => $waLink,
        'cs' => $csDisplay,
        'birth_date' => $birthDateDisplay,
        'address' => $addressDisplay,
        'city_text' => $cityTextDisplay,
        'description' => $descDisplay,
        'gift_text' => $giftText,
        'testimoni_text' => $testimoniText,
        'created_at' => $createdAtDisplay,
        'created_at_raw' => $createdAtRaw,
        'first_order' => $firstOrderFormatted,
        'first_order_raw' => $firstOrderRaw,
        'last_order' => $lastOrderFormatted,
        'last_order_raw' => $lastOrderRaw,
        'count_order' => $countOrderRaw,
        'brand' => $row['brand'] ?? '',
        'is_manual' => (int) ($row['is_manual'] ?? 0),
        'id_buyer' => $idBuyerDisplay,
        'order_status_text' => $orderStatusText,
        'order_status_days' => $daysSinceLastOrder,
        'order_history_text' => $historyText,
        'order_history_struct' => $historyEntries,
        'order_history_count' => count($historyEntries),
        'detail_url' => $detailUrl,
        'refresh_id' => $id,
        'order_id_raw' => $row['order_id'],
        'description_html' => crm_escape($descDisplay),
        'shop_name' => $shopNameDisplay,
        'products' => $productsDisplay,
        'product_names' => $productNameDisplay,
        'product_qtys' => $productQtyDisplay,
        'keluhan' => $keluhanDisplay,
        'customer_experience' => $customerExperienceDisplay,
        'join_komunitas' => $joinKomunitasDisplay,
        'campaign_broadcast' => $campaignBroadcastDisplay,
        'campaign_broadcast_id' => $campaignBroadcastId,
        'campaign_broadcast_url' => $campaignBroadcastUrl,
        'no_pesanan' => $noPesananDisplay,
    );

    $items[] = $item;
    $index++;
}

$partialView = $viewMode === 'card' ? 'crm/item_card' : 'crm/item_table';
$this->load->view($partialView, array(
    'items' => $items,
    'view_mode' => $viewMode,
    'grid_rows' => $gridRows,
    'total_rows' => isset($total_rows) ? (int) $total_rows : null,
));
