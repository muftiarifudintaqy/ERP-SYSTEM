-- 26 Sep 2026: query cron penjaga WA 7,86s -> 0,16s; resi per toko 3,67s -> 0,01s; backfill harga 3,25s -> 0,48s
ALTER TABLE transaction
  ADD INDEX idx_mp_brand_date (marketplace, brand, date),
  ADD INDEX idx_shop_status_manual (shop_id, order_status, is_manual),
  ALGORITHM=INPLACE, LOCK=NONE;
-- 25 Sep 2026
ALTER TABLE transaction ADD INDEX idx_pay_at (pay_at);
ALTER TABLE webhook ADD INDEX idx_created_mp (created_at, marketplace);
