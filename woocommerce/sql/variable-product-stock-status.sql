-- Finds variable products whose parent-level stock status is incorrectly set to
-- 'outofstock' while at least one published variation still has stock available.
-- WooCommerce should keep the parent status in sync with its variations, but this
-- can break after bulk imports, direct DB edits, or certain plugin operations.
-- Any product returned here needs its stock status resynced or manually corrected.

SELECT 
    p.ID,
    p.post_title AS product_name
FROM wp_posts p
JOIN wp_postmeta pm_stock 
    ON p.ID = pm_stock.post_id 
    AND pm_stock.meta_key = '_stock_status'
    AND pm_stock.meta_value = 'outofstock'
WHERE 
    p.post_type = 'product'
    AND p.post_status = 'publish'
    AND EXISTS (
        SELECT 1
        FROM wp_posts v
        JOIN wp_postmeta vm_stock 
            ON v.ID = vm_stock.post_id 
            AND vm_stock.meta_key = '_stock_status'
            AND vm_stock.meta_value = 'instock'
        WHERE 
            v.post_parent = p.ID
            AND v.post_type = 'product_variation'
            AND v.post_status = 'publish'
    )
ORDER BY p.post_title;
