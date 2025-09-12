/*
* WooCommerce Product Weight Audit
* 
* Purpose: Identify products (simple and variations) that do not have a `_weight` value
* Problem: Products missing weight will fail when using Multi Carrier Shipping (and possibly other shipping methods)
* 
* Root cause: Shipping plugins require weight to calculate rates. If missing:
* - Multi Carrier Shipping falls back to a default very high weight
* - Customers are charged inflated shipping costs (often hundreds of dollars)
* - Other shipping methods may also fail or return inaccurate rates
* 
* What this shows:
* - Simple products with no `_weight` meta
* - Product variations with no `_weight` meta
* - Excludes variable parents (they should not have weight directly)
* - Returns ID, post_name, post_title, and post_status for each product
* 
* Next steps after running:
* - Review listed products in WooCommerce admin
* - Add accurate weight values in the product data tab
* - Re-test checkout with Multi Carrier Shipping enabled
* - Monitor future orders to confirm accurate shipping rates
* 
* Notes:
* - Excludes products in 'trash' or 'auto-draft' status
* - Add similar checks for dimensions if needed (_length, _width, _height)
*/



-- Simple products with no _weight
SELECT p.ID, p.post_name, p.post_title, p.post_status
FROM wp_posts AS p
JOIN wp_term_relationships AS tr     ON tr.object_id = p.ID
JOIN wp_term_taxonomy     AS tt     ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_type'
JOIN wp_terms             AS t      ON t.term_id = tt.term_id AND t.slug = 'simple'
LEFT JOIN wp_postmeta     AS pmw    ON pmw.post_id = p.ID AND pmw.meta_key = '_weight'
WHERE p.post_type = 'product'
  AND p.post_status NOT IN ('trash','auto-draft')
  AND pmw.post_id IS NULL

UNION

-- Variations with no _weight
SELECT v.ID, v.post_name, v.post_title, v.post_status
FROM wp_posts AS v
LEFT JOIN wp_postmeta AS pmw ON pmw.post_id = v.ID AND pmw.meta_key = '_weight'
WHERE v.post_type = 'product_variation'
  AND v.post_status NOT IN ('trash','auto-draft')
  AND pmw.post_id IS NULL

ORDER BY ID;
