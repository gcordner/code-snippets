/* This gives a total number of orders and the total dollar amount for each of the last 3 months */


SELECT
  DATE(paid.meta_value) AS order_day,
  COUNT(*) AS completed_orders,
  ROUND(SUM(total.meta_value + 0), 2) AS gross_sales
FROM wp_posts o
JOIN wp_postmeta paid
  ON paid.post_id = o.ID AND paid.meta_key = '_paid_date'
JOIN wp_postmeta total
  ON total.post_id = o.ID AND total.meta_key = '_order_total'
WHERE o.post_type = 'shop_order'
  AND o.post_status = 'wc-completed'
  AND paid.meta_value >= (NOW() - INTERVAL 3 MONTH)
GROUP BY order_day
ORDER BY order_day;

/* By week */

SELECT
  DATE_SUB(DATE(paid.meta_value), INTERVAL WEEKDAY(paid.meta_value) DAY) AS week_start,
  COUNT(*) AS completed_orders,
  ROUND(SUM(total.meta_value + 0), 2) AS gross_sales
FROM wp_posts o
JOIN wp_postmeta paid
  ON paid.post_id = o.ID AND paid.meta_key = '_paid_date'
JOIN wp_postmeta total
  ON total.post_id = o.ID AND total.meta_key = '_order_total'
WHERE o.post_type = 'shop_order'
  AND o.post_status = 'wc-completed'
  AND paid.meta_value >= (NOW() - INTERVAL 3 MONTH)
GROUP BY week_start
ORDER BY week_start;
