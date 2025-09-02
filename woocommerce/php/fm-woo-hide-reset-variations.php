<?php
/**
 * WooCommerce: Remove the "Reset variations" ("Clear") link on variable products.
 *
 * Paste into: your child theme's functions.php or a site plugin.
 * Purpose: Prevents users from clearing a chosen variation, which can hide pricing/UI.
 * Scope: Global; safe to run always. Make conditional with is_product() if desired.
 *
 * @package FM\Snippets\WooCommerce
 */

// Namespacing-safe: use the global helper to return an empty string.
\add_filter( 'woocommerce_reset_variations_link', '__return_empty_string' );

// Alternative (PHPCS prefer-named-callback style):
// if ( ! \function_exists( 'fm_woo_hide_reset_variations_link' ) ) {
// 	/**
// 	 * Return empty string to suppress the "Reset variations" link.
// 	 *
// 	 * @return string Empty string.
// 	 */
// 	function fm_woo_hide_reset_variations_link() { return ''; }
// }
// \add_filter( 'woocommerce_reset_variations_link', 'fm_woo_hide_reset_variations_link' );
