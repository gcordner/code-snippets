/*
* WordPress Tiered Pricing Audit Query
* 
* Purpose: Find all published products that have working fixed tiered pricing rules
* Use case: Audit which products offer bulk/volume discounts
* 
* What it does:
* - Finds variable products with published variations
* - Filters for "fixed" pricing type (not percentage-based)
* - Excludes products with empty pricing rules
* - Returns basic product info + the actual pricing rule data
* 
* Output: Product details with serialized pricing rules in 'fixed_price_rules' column
* 
* Note: This is READ-ONLY - doesn't modify any data
* Compatible with: WooCommerce + tiered pricing plugins
* Last used: [Add date when you use it]
*/

SELECT 
    p.ID, 
    p.post_title, 
    p.post_name, 
    p.post_author, 
    p.post_date, 
    p.post_modified, 
    pm_fixed.meta_value AS fixed_price_rules
FROM wp_posts p
JOIN wp_posts v 
    ON v.post_parent = p.ID 
    AND v.post_status = 'publish' 
    AND v.post_type = 'product_variation'
JOIN wp_postmeta pm_tiered 
    ON v.ID = pm_tiered.post_id 
    AND pm_tiered.meta_key = '_tiered_price_rules_type'
    AND pm_tiered.meta_value = 'fixed'
JOIN wp_postmeta pm_fixed 
    ON v.ID = pm_fixed.post_id 
    AND pm_fixed.meta_key = '_fixed_price_rules'
    AND pm_fixed.meta_value != 'a:0:{}'
WHERE p.post_status = 'publish'
ORDER BY p.post_date DESC;