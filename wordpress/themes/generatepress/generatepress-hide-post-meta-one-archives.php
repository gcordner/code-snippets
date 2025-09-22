<?php
/*
* GeneratePress: Hide Post Meta on Archives
*
* Purpose:
* Remove the automatic post meta (author/date/categories, etc.) from archive-style listings
* to keep card layouts clean and scannable—without editing theme templates.
*
* What it does:
* 1) On archive views (category/tag/tax/date/author), the blog posts index (is_home), and search results,
*    unhooks GeneratePress’s `generate_post_meta` from `generate_after_entry_title`.
* 2) Leaves post meta intact on single posts/pages and other views.
*
* Why needed:
* Archive cards often look cluttered and repetitive when every item shows full meta. Unhooking the meta
* at the hook level simplifies the UI and avoids template overrides that are harder to maintain.
*
* Client benefit:
* Cleaner archive grids/lists and easier maintenance—no child-theme template copies required.
*
* Hook timing:
* Uses the `wp` action so conditional tags (`is_archive()`, `is_home()`, `is_search()`) are reliable
* and theme hooks have been registered before unhooking.
*
* Usage:
* Copy this snippet into your child theme’s `functions.php` (or a small must-use plugin). Do not remove
* the `do_action( 'generate_after_entry_title' )` call in templates—this snippet simply detaches the
* `generate_post_meta` callback on targeted views.
*
* Adjustments:
* - To exclude the blog index from this behavior, remove `is_home()` from the condition.
* - To also hide meta on single posts, add `|| is_single()` to the condition.
* - Note: `is_home()` refers to the posts index, not the site’s front page. Use `is_front_page()` for the latter.
*/

/**
 * Hide GeneratePress post meta on archives (category/tag/tax/date/author),
 * the blog posts index, and search results.
 */
add_action(
	'wp',
	function () {
		// Archives only; keep meta on single posts/pages.
		if ( is_archive() || is_home() || is_search() ) {
			remove_action( 'generate_after_entry_title', 'generate_post_meta', 10 );
		}
	}
);