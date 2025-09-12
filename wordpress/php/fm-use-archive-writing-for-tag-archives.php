<?php
/**
 * FILE: fm-use-archive-writing-for-tag-archives.php
 *
 * WHAT & WHY:
 * - Routes tag archives to your custom archive template (archive-writing.php)
 *   ONLY when the tag query targets the 'writing' CPT exclusively.
 * - Keeps regular blog tag archives using the theme's default tag/archive template.
 *
 * WHERE TO PLACE:
 * - Child theme's functions.php, OR a site-specific utility plugin, OR your snippets loader.
 *
 * HOW TO USE:
 * - Ensure you also run the companion snippet (pre_get_posts) to set post_type on tag queries.
 * - This snippet detects when the main query is writing-only and swaps the template.
 * - If archive-writing.php isn’t found, it gracefully falls back to the current template.
 *
 * EXTENSIONS:
 * - To support different CPTs → duplicate the check and map to other templates.
 * - To support a custom taxonomy (e.g., product_tag) → swap is_tag() with is_tax( 'product_tag' ).
 *
 * VERSIONING:
 * - @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Use archive-writing.php to render writing-only tag archives.
 *
 * If the main tag archive query targets ONLY the 'writing' post type,
 * switch the chosen template to archive-writing.php. Otherwise, do nothing.
 *
 * @since 1.0.0
 *
 * @param string $template The path to the template WordPress intends to use.
 * @return string The possibly replaced template path.
 *
 * @hook template_include
 */
function fm_use_archive_writing_for_tag_archives( $template ) {
	if ( ! is_tag() ) {
		return $template;
	}

	global $wp_query;

	$post_types = (array) $wp_query->get( 'post_type' );

	// If WP didn't set post_type explicitly, it implicitly means 'post'.
	if ( empty( $post_types ) ) {
		$post_types = array( 'post' );
	}

	$writing_only = ( 1 === count( $post_types ) && in_array( 'writing', $post_types, true ) );

	if ( $writing_only ) {
		$alt = locate_template( 'archive-writing.php' );
		if ( $alt ) {
			return $alt;
		}
	}

	return $template;
}
add_filter( 'template_include', 'fm_use_archive_writing_for_tag_archives' );
