/**
 * WooCommerce Sales Report - Product Category Analysis by State (2025)
 * 
 * PURPOSE:
 * Creates a denormalized fact table of WooCommerce order line items for 2025 and generates
 * a summary report showing units sold and revenue by month, shipping state, and product bucket.
 * 
 * PRODUCT BUCKETS:
 * - "Kratom": Products in category 264 or its descendants (depth 0-2), EXCLUDING 7oh products
 * - "7oh": Products in category 637 or its descendants (depth 0-2)
 * Note: Since 7oh (637) is a subcategory of Kratom (264), the Kratom bucket explicitly
 * excludes 7oh products to prevent double-counting.
 * 
 * WORKFLOW:
 * 1. Drop and recreate the fact table `rpt_sales_lines_2025`
 * 2. Extract all line items from completed/processing orders dated 2025-01-01 through 2026-01-31
 * 3. Denormalize order and line item metadata into columns for fast aggregation
 * 4. Normalize shipping state to 2-character uppercase (or '--' if missing)
 * 5. Add indexes for common query patterns (product, month, state, month+state)
 * 6. Join fact table to product bucket definitions
 * 7. Aggregate by month, state, and bucket
 * 
 * DATA SOURCES:
 * - wp_posts: Order records (post_type = 'shop_order')
 * - wp_postmeta: Shipping state (_shipping_state)
 * - wp_woocommerce_order_items: Line items
 * - wp_woocommerce_order_itemmeta: Product ID, quantity, line total
 * - wp_term_relationships + wp_term_taxonomy: Product category hierarchy
 * 
 * OUTPUT COLUMNS:
 * - month: YYYY-MM format
 * - state: 2-character shipping state code (or '--')
 * - bucket: 'Kratom' or '7oh'
 * - units_sold: Total quantity of items sold
 * - revenue: Total sales amount (rounded to 2 decimals)
 * 
 * PERFORMANCE NOTES:
 * - Fact table indexes support fast filtering/grouping
 * - Product bucket query is duplicated in final SELECT to avoid temp table dependencies
 * - Category hierarchy checked at 3 levels (product's category, parent, grandparent)
 * 
 * MAINTENANCE:
 * - Update date range in WHERE clause for different reporting periods
 * - Modify category term_ids (264, 637) if category structure changes
 * - Consider materializing product buckets as a separate table for very large datasets
 */


/**
* Build the Persistent Fact Table
*/

DROP TABLE IF EXISTS rpt_sales_lines_2025;

CREATE TABLE rpt_sales_lines_2025 AS
SELECT
  DATE_FORMAT(p.post_date, '%Y-%m') AS ym,
  COALESCE(NULLIF(UPPER(LEFT(TRIM(pm_state.meta_value), 2)), ''), '--') AS shipping_state,
  CAST(pm_product.meta_value AS UNSIGNED) AS product_id,
  CAST(pm_qty.meta_value AS UNSIGNED) AS quantity,
  CAST(pm_line_total.meta_value AS DECIMAL(18,2)) AS sales_amount
FROM wp_posts p

LEFT JOIN wp_postmeta pm_state
  ON pm_state.post_id = p.ID
 AND pm_state.meta_key = '_shipping_state'

JOIN wp_woocommerce_order_items oi
  ON oi.order_id = p.ID
 AND oi.order_item_type = 'line_item'

JOIN wp_woocommerce_order_itemmeta pm_product
  ON pm_product.order_item_id = oi.order_item_id
 AND pm_product.meta_key = '_product_id'

JOIN wp_woocommerce_order_itemmeta pm_qty
  ON pm_qty.order_item_id = oi.order_item_id
 AND pm_qty.meta_key = '_qty'

JOIN wp_woocommerce_order_itemmeta pm_line_total
  ON pm_line_total.order_item_id = oi.order_item_id
 AND pm_line_total.meta_key = '_line_total'

WHERE p.post_type = 'shop_order'
  AND p.post_status IN ('wc-processing','wc-completed')
  AND p.post_date >= '2025-01-01'
  AND p.post_date <  '2026-02-01';

/**
* Normalize state
*/
ALTER TABLE rpt_sales_lines_2025
  MODIFY shipping_state CHAR(2)
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

/**
* Build Indexes
*/
CREATE INDEX idx_rpt_prod  ON rpt_sales_lines_2025(product_id);
CREATE INDEX idx_rpt_month ON rpt_sales_lines_2025(ym);
CREATE INDEX idx_rpt_state ON rpt_sales_lines_2025(shipping_state);
CREATE INDEX idx_rpt_combo ON rpt_sales_lines_2025(ym, shipping_state);

/**
* Build product Buckets. This is for all Kratom and all 7oh. Since 7oh is a subcategory of Kratom, it substracts 
* 7oh from Kratom to give us unique non 7oh products
*/
SELECT 'Kratom' AS bucket, a.product_id
FROM (
  SELECT DISTINCT tr.object_id AS product_id
  FROM wp_term_relationships tr
  JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
  LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
  WHERE tt.taxonomy = 'product_cat'
    AND (
      tt.term_id = 264 OR p1.term_id = 264 OR p2.term_id = 264
    )
) a
LEFT JOIN (
  SELECT DISTINCT tr.object_id AS product_id
  FROM wp_term_relationships tr
  JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
  LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
  WHERE tt.taxonomy = 'product_cat'
    AND (
      tt.term_id = 637 OR p1.term_id = 637 OR p2.term_id = 637
    )
) b
  ON b.product_id = a.product_id
WHERE b.product_id IS NULL

UNION ALL

SELECT '7oh' AS bucket, b.product_id
FROM (
  SELECT DISTINCT tr.object_id AS product_id
  FROM wp_term_relationships tr
  JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
  LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
  LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
  WHERE tt.taxonomy = 'product_cat'
    AND (
      tt.term_id = 637 OR p1.term_id = 637 OR p2.term_id = 637
    )
) b
ORDER BY bucket, product_id;

/**
* Built report
*/
SELECT
  s.ym AS month,
  s.shipping_state AS state,
  bp.bucket,
  SUM(s.quantity) AS units_sold,
  ROUND(SUM(s.sales_amount), 2) AS revenue
FROM rpt_sales_lines_2025 s
JOIN (
  /* Bucketed product IDs: 7oh, and Kratom minus 7oh (depth 0–2) */

  SELECT 'Kratom' AS bucket, a.product_id
  FROM (
    SELECT DISTINCT tr.object_id AS product_id
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
    LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
    LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
    WHERE tt.taxonomy = 'product_cat'
      AND (tt.term_id = 264 OR p1.term_id = 264 OR p2.term_id = 264)
  ) a
  LEFT JOIN (
    SELECT DISTINCT tr.object_id AS product_id
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
    LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
    LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
    WHERE tt.taxonomy = 'product_cat'
      AND (tt.term_id = 637 OR p1.term_id = 637 OR p2.term_id = 637)
  ) b
    ON b.product_id = a.product_id
  WHERE b.product_id IS NULL

  UNION ALL

  SELECT '7oh' AS bucket, b.product_id
  FROM (
    SELECT DISTINCT tr.object_id AS product_id
    FROM wp_term_relationships tr
    JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
    LEFT JOIN wp_term_taxonomy p1 ON p1.term_id = tt.parent AND p1.taxonomy = 'product_cat'
    LEFT JOIN wp_term_taxonomy p2 ON p2.term_id = p1.parent AND p2.taxonomy = 'product_cat'
    WHERE tt.taxonomy = 'product_cat'
      AND (tt.term_id = 637 OR p1.term_id = 637 OR p2.term_id = 637)
  ) b

) bp
  ON bp.product_id = s.product_id
GROUP BY s.ym, s.shipping_state, bp.bucket
ORDER BY s.ym, s.shipping_state, bp.bucket;
