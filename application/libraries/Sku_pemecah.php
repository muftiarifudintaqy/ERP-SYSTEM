<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pengenal SKU marketplace untuk perhitungan HPP.
 *
 * Cron generate_stock_items menyalin order ke tabel stock dengan
 * JOIN product -- order yang SKU-nya tidak dikenali dibuang diam-diam,
 * dan harga pokoknya tidak pernah tercatat. Pustaka ini dipakai halaman
 * "SKU belum dikenali" untuk menutup celah itu satu per satu.
 */
class Sku_pemecah
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /** SKU yang pernah terjual tapi tidak masuk HPP, terbanyak dulu. */
    public function belum_dikenali($limit = 300)
    {
        return $this->CI->db->query("
            SELECT sp.brand, sp.sku, COUNT(*) AS baris,
                   MAX(sp.product_text) AS contoh,
                   COUNT(DISTINCT sp.product_text) AS jumlah_nama,
                   GROUP_CONCAT(DISTINCT LEFT(sp.product_text, 70) SEPARATOR '\n') AS semua_nama,
                   GROUP_CONCAT(DISTINCT sp.marketplace) AS marketplace,
                   MIN(DATE(sp.date)) AS pertama, MAX(DATE(sp.date)) AS terakhir
            FROM stock_product_3rd sp
            LEFT JOIN sku_mapping m ON m.marketplace = sp.marketplace AND m.sku_marketplace = sp.sku
            LEFT JOIN product p ON p.sku = COALESCE(m.sku_internal, sp.sku) AND p.brand = sp.brand
            WHERE p.id IS NULL AND sp.sku <> ''
            GROUP BY sp.brand, sp.sku
            ORDER BY baris DESC
            LIMIT " . (int) $limit)->result_array();
    }

    /**
     * Pastikan $sku ada di master produk untuk $brand.
     * Kalau belum ada tapi berpola bundel (3FS, 1LS+1NS) dan semua
     * komponennya punya harga beli, baris AUTO-BUNDEL dibuat.
     * Mengembalikan [berhasil, sku_resmi_atau_pesan_galat].
     */
    public function pastikan_produk($brand, $sku)
    {
        $sku = strtoupper(str_replace(' ', '', trim($sku)));
        $ada = $this->CI->db->where('brand', $brand)->where('sku', $sku)->get('product')->row_array();
        if ($ada) return [true, $ada['sku']];

        $total = 0; $pecah = [];
        foreach (explode('+', $sku) as $b) {
            if (!preg_match('/^(\d*)([A-Z]+)$/', $b, $m)) {
                return [false, "SKU $sku tidak ada di master produk dan bukan pola bundel."];
            }
            $qty = $m[1] === '' ? 1 : (int) $m[1];
            $d = $this->CI->db->where('brand', $brand)->where('sku', '1' . $m[2])->get('product')->row_array();
            if (!$d)                          return [false, "Komponen 1{$m[2]} belum ada di master produk."];
            if ((float) $d['price_buy'] <= 0) return [false, "Harga beli 1{$m[2]} masih 0 - isi dulu di menu Produk."];
            $total  += $qty * (float) $d['price_buy'];
            $pecah[] = "{$qty}x1{$m[2]}";
        }

        $this->CI->db->insert('product', [
            'brand' => $brand, 'brand_text' => $brand, 'sku' => $sku,
            'name' => 'Bundel ' . implode(' + ', $pecah), 'price_buy' => $total,
            'status' => 'Aktif', 'desc' => 'AUTO-BUNDEL: ' . implode(' + ', $pecah),
            'created_by' => 1, 'created_at' => date('Y-m-d H:i:s'), 'is_varian' => 0,
        ]);
        return [true, $sku];
    }

    /**
     * Salin order dengan SKU marketplace ini ke tabel stock -- query yang
     * sama persis dengan cron, dibatasi satu SKU, supaya HPP-nya langsung
     * masuk tanpa menunggu cron besok pagi.
     */
    public function isi_ulang_stock($brand, $sku_marketplace)
    {
        $this->CI->db->query("
            INSERT INTO stock (id_trx, order_id, shop_id, shop_name, type, type_sub, po_id, po_number,
              qty_in, qty_in_pos, qty_out, qty_out_pos, qty_retur, qty_out_retur, qty,
              price, discount, price_total, `desc`, img, created_by, updated_by,
              created_at, updated_at, status, product, product_text, date, sku, brand,
              marketplace, hpp, order_status, rts_at, shipping, awb_number, cs, is_adjustment)
            SELECT sp.id_trx, sp.order_id, sp.shop_id, sp.shop_name, sp.type, sp.type_sub, 0, '',
              sp.qty_in, sp.qty_in_pos, sp.qty_out, sp.qty_out_pos, sp.qty_retur, 0, sp.qty,
              sp.price, sp.discount, sp.price_total, sp.`desc`, sp.img, 1, 1,
              sp.created_at, sp.updated_at, 'Aktif', p.id, sp.product_text, sp.date, sp.sku, sp.brand,
              sp.marketplace, 0, sp.order_status, '', sp.shipping, sp.awb_number, sp.cs, 0
            FROM stock_product_3rd sp
            LEFT JOIN sku_mapping m ON m.marketplace = sp.marketplace AND m.sku_marketplace = sp.sku
            JOIN product p ON p.sku = COALESCE(m.sku_internal, sp.sku) AND p.brand = sp.brand
            LEFT JOIN stock st ON st.id_trx = sp.id_trx AND st.sku = sp.sku AND st.type = sp.type
            WHERE st.id IS NULL AND sp.brand = ? AND sp.sku = ?",
            [$brand, $sku_marketplace]);
        return $this->CI->db->affected_rows();
    }
}
