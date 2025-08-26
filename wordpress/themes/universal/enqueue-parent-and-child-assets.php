<?php
/**
 * Enqueue parent + child theme build assets (frontend + editor).
 *
 * WHAT THIS DOES:
 * - Always enqueues the parent theme's style.css first.
 * - Finds the newest hashed child theme build files:
 *     css/build/theme.min.*.css
 *     js/build/main.min.*.js
 * - Versions them with filemtime for cache-busting.
 * - Enqueues the same CSS/JS inside the block editor for visual parity.
 *
 * WHERE TO USE:
 * - Drop functions into a child theme’s functions.php
 * - Or into an include (e.g., inc/enqueue-assets.php) and require it.
 *
 * ASSUMPTIONS:
 * - Your build process outputs files matching the patterns above.
 * - You want child CSS to load after parent CSS.
 * - Works universally with most parent themes (GeneratePress, Astra, Blocksy, etc).
 *
 * USAGE:
 *   add_action( 'wp_enqueue_scripts', 'enqueue_frontend_assets', 20 );
 *   add_action( 'enqueue_block_editor_assets', 'enqueue_editor_assets' );
 *
 * @package Snippets\EnqueueAssets
 */

function enqueue_frontend_assets() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	// CSS: newest theme.min.*.css
	$css_files = glob( $theme_dir . '/css/build/theme.min.*.css' );
	if ( ! empty( $css_files ) ) {
		usort( $css_files, fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) );
		$file = $css_files[0];
		wp_enqueue_style(
			get_stylesheet() . '-theme',
			$theme_uri . '/css/build/' . basename( $file ),
			array( 'parent-style' ),
			filemtime( $file )
		);
	}

	// JS: newest main.min.*.js
	$js_files = glob( $theme_dir . '/js/build/main.min.*.js' );
	if ( ! empty( $js_files ) ) {
		usort( $js_files, fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) );
		$file = $js_files[0];
		wp_enqueue_script(
			get_stylesheet() . '-main',
			$theme_uri . '/js/build/' . basename( $file ),
			array( 'wp-element', 'wp-hooks' ),
			filemtime( $file ),
			true
		);
	}
}

function enqueue_editor_assets() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	$css_files = glob( $theme_dir . '/css/build/theme.min.*.css' );
	if ( ! empty( $css_files ) ) {
		usort( $css_files, fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) );
		$file = $css_files[0];
		wp_enqueue_style(
			get_stylesheet() . '-editor',
			$theme_uri . '/css/build/' . basename( $file ),
			array(),
			filemtime( $file )
		);
	}

	$js_files = glob( $theme_dir . '/js/build/main.min.*.js' );
	if ( ! empty( $js_files ) ) {
		usort( $js_files, fn( $a, $b ) => filemtime( $b ) <=> filemtime( $a ) );
		$file = $js_files[0];
		wp_enqueue_script(
			get_stylesheet() . '-editor',
			$theme_uri . '/js/build/' . basename( $file ),
			array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post', 'wp-components', 'wp-element', 'wp-compose' ),
			filemtime( $file ),
			true
		);
	}
}
