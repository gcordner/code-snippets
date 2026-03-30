/*
* Create temporary table of order ids.
*/
  

DROP TABLE IF EXISTS ca_orders;

CREATE TABLE ca_orders AS
SELECT DISTINCT pm.post_id AS order_id
FROM wp_postmeta pm
JOIN wp_posts p ON p.ID = pm.post_id
WHERE pm.meta_key = '_shipping_state'
  AND pm.meta_value = 'CA'
  AND p.post_type = 'shop_order'
  AND p.post_status IN ('wc-processing','wc-completed','wc-on-hold');

/* 
* Create table of customer info
*/

DROP TABLE IF EXISTS ca_customer_export_full;

CREATE TABLE ca_customer_export_full AS
SELECT
    latest.customer_email AS email,
    CONCAT_WS(' ', fn.meta_value, ln.meta_value) AS customer_name,
    ph.meta_value AS phone,
    city.meta_value AS city,
    zip.meta_value AS zip,
    p.post_date AS last_order_date
FROM (
    SELECT
        t.customer_email,
        MAX(t.order_id) AS latest_order_id
    FROM (
        SELECT
            p.ID AS order_id,
            p.post_date,
            em.meta_value AS customer_email
        FROM ca_orders c
        JOIN wp_posts p
            ON p.ID = c.order_id
        LEFT JOIN wp_postmeta em
            ON p.ID = em.post_id
           AND em.meta_key = '_billing_email'
        WHERE em.meta_value IS NOT NULL
          AND em.meta_value <> ''
    ) t
    GROUP BY t.customer_email
) latest
JOIN wp_posts p
    ON p.ID = latest.latest_order_id
LEFT JOIN wp_postmeta fn
    ON p.ID = fn.post_id
   AND fn.meta_key = '_shipping_first_name'
LEFT JOIN wp_postmeta ln
    ON p.ID = ln.post_id
   AND ln.meta_key = '_shipping_last_name'
LEFT JOIN wp_postmeta ph
    ON p.ID = ph.post_id
   AND ph.meta_key = '_billing_phone'
LEFT JOIN wp_postmeta city
    ON p.ID = city.post_id
   AND city.meta_key = '_shipping_city'
LEFT JOIN wp_postmeta zip
    ON p.ID = zip.post_id
   AND zip.meta_key = '_shipping_postcode';


