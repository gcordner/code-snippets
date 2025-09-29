<?php
/**
 * Remove GeneratePress footer meta from archives/posts.
 *
 * This disables the tags, categories, and author info
 * added by the `generate_footer_meta` callback on the
 * `generate_after_entry_content` hook.
 *
 * Place in your child theme’s functions.php or include as a snippet.
 */

/**
 * Remove GeneratePress footer meta site-wide.
 */
add_action( 'wp', function () {
    // Remove footer meta output from GeneratePress
    remove_action( 'generate_after_entry_content', 'generate_footer_meta', 10 );
}, 50);



/**
 * Remove GeneratePress footer meta only on archive pages.
 *
 * This disables the tags, categories, and author info
 * (from generate_footer_meta) only when viewing an archive.
 */

add_action( 'wp', function () {
    if ( is_archive() ) {
        remove_action( 'generate_after_entry_content', 'generate_footer_meta', 10 );
    }
}, 50);