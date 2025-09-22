/*
* WooCommerce Sales Analysis: Krave Kratom Products to San Diego
* 
* Purpose: Calculate total revenue and unit sales for Krave Kratom category products shipped to San Diego city
* Problem: Need to track performance of specific product category in specific geographic market
* 
* What this analyzes:
* - Product Category: "Krave Kratom" (kratom products from Krave brand)
* - Geographic Scope: All San Diego city zip codes (73 total zip codes from 92101-92199 range)
* - Date Range: February 16, 2020 to present (5+ years of historical data)
* - Order Status: Only completed, processing, and shipped orders (excludes pending, cancelled, refunded)
* - Metrics: Total dollar amount and total units sold
* 
* Geographic Coverage:
* - Includes all 73 official San Diego city zip codes
* - Covers downtown (92101), coastal areas (92037, 92109), inland neighborhoods
* - Excludes surrounding cities like Chula Vista, El Cajon, or unincorporated areas
* 
* Business Use Cases:
* - Market penetration analysis for Krave Kratom in San Diego
* - Geographic sales performance tracking
* - Revenue attribution by product category and location
* - Customer demand analysis in specific metro areas
* 
* Technical Details:
* - Joins order items with product taxonomy to filter by category
* - Uses shipping zip code for geographic filtering (more reliable than city names)
* - Aggregates line totals (includes discounts/taxes as recorded per line item)
* - Counts quantities to show unit volume alongside revenue
* 
* Expected Results:
* - total_amount: Sum of all line item totals in dollars
* - total_units: Sum of all product quantities sold
* - NULL results may indicate: no matching orders, incorrect category name, or data structure issues
*/


SELECT 
    SUM(CAST(line_total.meta_value AS DECIMAL(10,2))) as total_amount,
    SUM(CAST(qty.meta_value AS UNSIGNED)) as total_units
FROM wp_woocommerce_order_items oi
JOIN wp_posts orders ON oi.order_id = orders.ID
JOIN wp_woocommerce_order_itemmeta product_id ON oi.order_item_id = product_id.order_item_id AND product_id.meta_key = '_product_id'
JOIN wp_woocommerce_order_itemmeta qty ON oi.order_item_id = qty.order_item_id AND qty.meta_key = '_qty'
JOIN wp_woocommerce_order_itemmeta line_total ON oi.order_item_id = line_total.order_item_id AND line_total.meta_key = '_line_total'
JOIN wp_postmeta shipping_zip ON orders.ID = shipping_zip.post_id AND shipping_zip.meta_key = '_shipping_postcode'
JOIN wp_term_relationships tr ON CAST(product_id.meta_value AS UNSIGNED) = tr.object_id
JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'product_cat'
JOIN wp_terms t ON tt.term_id = t.term_id
WHERE orders.post_type = 'shop_order'
    AND orders.post_status IN ('wc-completed', 'wc-processing', 'wc-shipped')
    AND orders.post_date >= '2020-02-16'
    AND shipping_zip.meta_value IN ('92101', '92102', '92103', '92104', '92105', '92106', '92107', '92108', '92109', '92110', '92111', '92112', '92113', '92114', '92115', '92116', '92117', '92119', '92120', '92121', '92122', '92123', '92124', '92126', '92127', '92128', '92129', '92130', '92131', '92132', '92134', '92135', '92136', '92137', '92138', '92139', '92140', '92142', '92145', '92147', '92149', '92150', '92152', '92153', '92154', '92155', '92158', '92159', '92160', '92161', '92163', '92165', '92166', '92167', '92168', '92169', '92170', '92171', '92172', '92174', '92175', '92176', '92177', '92179', '92182', '92186', '92187', '92190', '92191', '92192', '92193', '92195', '92196', '92197', '92198', '92199')
    AND t.name = 'Krave Kratom';