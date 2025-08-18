/*
* WooCommerce Multi Carrier Shipping Error Analysis
* 
* Purpose: Find orders using Multi Carrier Shipping to identify pricing errors
* Problem: Orders show default shipping cost (hundreds of dollars) when plugin fails
* 
* Root cause: Plugin can't fetch real shipping rates due to missing product data:
* - Missing product weight
* - Missing product dimensions  
* - Other required shipping calculation fields
* 
* What this shows:
* - Orders from last 10 days with Multi Carrier Shipping
* - The shipping cost charged (look for unusually high amounts)
* - Order details for follow-up investigation
* 
* Next steps after running:
* - Check products in high-cost orders for missing weight/dimensions
* - Update product data to prevent future shipping calculation errors
* - Consider refunding customers charged incorrect shipping amounts
* 
* Time range: Last 10 days (adjust INTERVAL as needed)
*/

SELECT orders.ID AS order_id,
      orders.post_status AS order_status,
      orders.post_date AS order_date,
      order_items.order_item_name AS shipping_carrier,
      itemmeta.meta_value AS shipping_cost
FROM wp_posts AS orders
JOIN wp_woocommerce_order_items AS order_items
 ON orders.ID = order_items.order_id
JOIN wp_woocommerce_order_itemmeta AS itemmeta
 ON order_items.order_item_id = itemmeta.order_item_id
WHERE orders.post_type = 'shop_order'
 AND orders.post_date >= DATE_SUB(NOW(), INTERVAL 10 DAY)
 AND order_items.order_item_type = 'shipping'
 AND order_items.order_item_name = 'Multi Carrier Shipping'
 AND itemmeta.meta_key = 'cost'
ORDER BY orders.post_date DESC;