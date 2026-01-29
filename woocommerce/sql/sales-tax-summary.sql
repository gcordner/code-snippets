/*
 * WooCommerce CA Tax Summary — Woo-exact replication
 *
 * This query reproduces the way WooCommerce’s built-in tax summary report
 * buckets California sales tax, but allows it to be run for an arbitrary
 * custom date range.
 *
 * IMPORTANT: This query intentionally mirrors WooCommerce’s *reporting*
 * behavior, not a jurisdiction-accurate “who actually received the tax”
 * breakdown.
 *
 * High-level behavior:
 * --------------------
 * 1. Operates at the granularity of (order × tax priority).
 *    All tax lines applied to an order at the same tax priority are first
 *    summed together.
 *
 * 2. Orders are classified into City / County / Special buckets based on
 *    WooCommerce’s internal tax *application order* (`tax_rate_order`),
 *    NOT by the human-readable tax rate name.
 *
 *    For priorities 1–3:
 *      - If any rate at this priority has tax_rate_order == SPECIAL_SLOT
 *        → bucket = SPECIAL
 *      - Else if any rate at this priority has tax_rate_order == COUNTY_SLOT
 *        → bucket = COUNTY
 *      - Else
 *        → bucket = CITY (remainder bucket)
 *
 *    Priority 4 is always reported as STATE SALES TAX.
 *
 * 3. Dollar allocation follows WooCommerce’s summary report rules:
 *      - City buckets intentionally report $0.00 tax, even though those
 *        orders do carry tax at that priority.
 *      - County and Special buckets receive the full summed tax for that
 *        (order × priority).
 *
 * 4. Final output aggregates by (bucket × priority), producing rows like:
 *      US-CA-CITY TAX-1
 *      US-CA-COUNTY TAX-1
 *      US-CA-SPECIAL TAX-1
 *      …
 *      US-CA-STATE SALES TAX-4
 *
 * 5. The SUM of all rows exactly equals total tax collected for the date
 *    range, and the per-row order counts and dollar amounts match
 *    WooCommerce’s built-in tax report line-for-line.
 *
 * Data sources:
 * -------------
 * - wp_posts                       (order date + status)
 * - wp_woocommerce_order_items     (tax line items)
 * - wp_woocommerce_order_itemmeta  (tax_amount, shipping_tax_amount, rate_id)
 * - wp_woocommerce_tax_rates       (tax_rate_priority, tax_rate_order)
 *
 * Notes:
 * ------
 * - Date filtering is performed on order post_date to match Woo’s
 *   “Order date” reporting mode.
 * - This query is intended for reporting / accounting reconciliation,
 *   not for determining jurisdictional tax liability.
 * - City totals being zero is a deliberate artifact of WooCommerce’s
 *   reporting logic, not a data error.
 */


SELECT
  CONCAT('US-CA-', bucket, '-', priority) AS `Tax`,
  '%' AS `Rate`,
  COUNT(*) AS `Number of orders`,
  ROUND(SUM(bucket_tax), 2) AS `Tax amount`,
  0.00 AS `Shipping tax amount`,
  ROUND(SUM(bucket_tax), 2) AS `Total tax`
FROM (
  SELECT
    pop.order_id,
    pop.priority,
    pop.total_tax,
    CASE
      WHEN pop.priority = 4 THEN 'STATE SALES TAX'
      WHEN pop.has_special = 1 THEN 'SPECIAL TAX'
      WHEN pop.has_county  = 1 THEN 'COUNTY TAX'
      ELSE 'CITY TAX'
    END AS bucket,
    CASE
      WHEN pop.priority IN (1,2,3) AND pop.has_special = 0 AND pop.has_county = 0
        THEN 0
      ELSE pop.total_tax
    END AS bucket_tax
  FROM (
    SELECT
      oi.order_id,
      tr.tax_rate_priority AS priority,
      SUM(COALESCE(tax.meta_value,0) + COALESCE(shiptax.meta_value,0)) AS total_tax,
      MAX(tr.tax_rate_order = /* SPECIAL_ORDER */ 3) AS has_special,
      MAX(tr.tax_rate_order = /* COUNTY_ORDER  */ 1) AS has_county
    FROM wp_posts p
    JOIN wp_woocommerce_order_items oi
      ON oi.order_id = p.ID AND oi.order_item_type='tax'
    JOIN wp_woocommerce_order_itemmeta rate
      ON rate.order_item_id = oi.order_item_id AND rate.meta_key='rate_id'
    JOIN wp_woocommerce_tax_rates tr
      ON tr.tax_rate_id = CAST(rate.meta_value AS UNSIGNED)
    LEFT JOIN wp_woocommerce_order_itemmeta tax
      ON tax.order_item_id = oi.order_item_id AND tax.meta_key='tax_amount'
    LEFT JOIN wp_woocommerce_order_itemmeta shiptax
      ON shiptax.order_item_id = oi.order_item_id AND shiptax.meta_key='shipping_tax_amount'
    WHERE
      p.post_type='shop_order'
      AND p.post_status IN ('wc-processing','wc-completed')
      AND p.post_date >= '2025-12-01 00:00:00'
      AND p.post_date <  '2026-01-01 00:00:00'
      AND tr.tax_rate_country='US'
      AND tr.tax_rate_state='CA'
    GROUP BY oi.order_id, tr.tax_rate_priority
  ) pop
) classified
GROUP BY bucket, priority
ORDER BY FIELD(
  `Tax`,
  'US-CA-CITY TAX-1','US-CA-CITY TAX-2','US-CA-CITY TAX-3',
  'US-CA-COUNTY TAX-1','US-CA-COUNTY TAX-2','US-CA-COUNTY TAX-3',
  'US-CA-SPECIAL TAX-1','US-CA-SPECIAL TAX-2','US-CA-SPECIAL TAX-3',
  'US-CA-STATE SALES TAX-4'
);
