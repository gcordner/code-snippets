<?php
/**
 * FILE: fm-include-post-types-in-tag-archives.php
 *
 * WHAT & WHY:
 * - Makes WordPress tag archives (e.g., /tag/foo/) include one or more custom post types.
 * - Use when your CPT shares 'post_tag' and you want those entries to appear on tag archives.
 *
 * WHERE TO PLACE:
 * - Child theme's functions.php, OR a site-specific utility plugin, OR your snippets loader.
 *
 * HOW TO USE:
 * - Configure $fm_tag_archive_post_types below (default ['writing']).
 * - Optional: switch to ['post', 'writing'] if you want mixed archives.
 * - Optional: duplicate the is_tag() clause for categories or custom taxonomies.
 *
 * COMPAT:
 * - Works with pagination, feeds, breadcrumbs, and SEO canonicals (no permalink changes).
 * - If you do NOT include 'post', consider disabling sticky posts for cleaner results.
 *
 * EDGE CASES:
 * - If another plugin also modifies tag queries, this runs on the main query only and is safe.
 * - If you use this with WooCommerce 'product_tag', switch is_tag() -> is_tax( 'product_tag' ).
 *
 * VERSIONING:
 * - @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include specific post types in tag archives.
 *
 * Adjusts the main query on tag archives to include configured post types.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The WP_Query instance (passed by reference by WP Core).
 * @return void
 *
 * @hook pre_get_posts
 */
function fm_include_post_types_in_tag_archives( WP_Query $query ) {

	// Never touch admin/REST or secondary queries.
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// CONFIG: choose which post types appear on tag pages.
	$fm_tag_archive_post_types = array( 'writing' ); // e.g., array( 'post', 'writing' )

	if ( $query->is_tag() ) {
		$query->set( 'post_type', $fm_tag_archive_post_types );

		// Optional: if excluding 'post', ignore sticky posts to avoid unexpected ordering.
		if ( ! in_array( 'post', $fm_tag_archive_post_types, true ) ) {
			$query->set( 'ignore_sticky_posts', true );
		}
	}

	// EXAMPLES:
	// Categories too? Uncomment:
	// if ( $query->is_category() ) {
	// 	$query->set( 'post_type', $fm_tag_archive_post_types );
	// }

	// Custom taxonomy? Replace 'your_tax':
	// if ( $query->is_tax( 'your_tax' ) ) {
	// 	$query->set( 'post_type', $fm_tag_archive_post_types );
	// }
}
add_action( 'pre_get_posts', 'fm_include_post_types_in_tag_archives' );
