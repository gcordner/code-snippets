/*
====================================================================================================
REPORT: Arizona Order Subtotal Summary (2025)
====================================================================================================

DESCRIPTION:
    Calculates the aggregate "Order Subtotal" for all completed WooCommerce orders 
    shipped to Arizona (AZ) within the calendar year 2025.

DEFINITION OF "SUBTOTAL":
    This query sums the '_line_subtotal' from 'wp_woocommerce_order_itemmeta'. 
    In WooCommerce, this value represents the product price MULTIPLIED by quantity 
    BEFORE any discounts (coupons), taxes, or shipping costs are applied.

TABLE RELATIONSHIPS:
    1. wp_posts (p): Identifies the Order ID, type ('shop_order'), and status.
    2. wp_postmeta (pm_state): Filters by shipping state metadata.
    3. wp_woocommerce_order_items (oi): Links the Order ID to specific line items.
    4. wp_woocommerce_order_itemmeta (oim): Stores the individual subtotal for each item.

FILTERS:
    - State: Arizona (AZ)
    - Status: Completed (wc-completed)
    - Year: 2025
    - Item Type: line_item (Excludes shipping fees, taxes, and fees stored as items)

NOTE: 
    If High-Performance Order Storage (HPOS) is enabled, this query targeting 'wp_posts' 
    may need to be updated to target 'wp_wc_orders' and 'wp_wc_order_addresses'.
====================================================================================================
*/


SELECT 
    SUM(CAST(oim.meta_value AS DECIMAL(10,2))) AS total_combined_subtotal
FROM wp_posts p
-- Filter for shipping state Arizona
INNER JOIN wp_postmeta pm_state 
    ON p.ID = pm_state.post_id 
    AND pm_state.meta_key = '_shipping_state'
    AND pm_state.meta_value = 'AZ'
-- Join to order items table (filters for products only)
INNER JOIN wp_woocommerce_order_items oi 
    ON p.ID = oi.order_id 
    AND oi.order_item_type = 'line_item'
-- Join to item meta to get the '_line_subtotal'
INNER JOIN wp_woocommerce_order_itemmeta oim 
    ON oi.order_item_id = oim.order_item_id
    AND oim.meta_key = '_line_subtotal'
WHERE p.post_type = 'shop_order'
    AND p.post_status = 'wc-completed'
    AND YEAR(p.post_date) = 2025;
