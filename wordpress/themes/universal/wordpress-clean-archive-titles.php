<?php
/*
* WordPress Clean Archive Titles
* 
* Purpose: Remove prefixes from WordPress archive page titles for cleaner display
* Transforms: "Category: News" → "News", "Tag: WordPress" → "WordPress", etc.
* 
* What it handles:
* - Categories, tags, and custom taxonomy archives
* - Post type archives  
* - Author archives (with safety checks)
* - Date archives (year, month, day with proper formatting)
* 
* Benefits:
* - Cleaner, more professional archive page titles
* - Works with any theme (returns plain text, no HTML)
* - Internationalization ready with translatable date formats
* - Comprehensive coverage of all WordPress archive types
* 
* Usage: Add to functions.php or child theme
* Output: Plain text titles - let your theme handle styling/markup
*/

/**
* Clean archive titles by removing WordPress default prefixes.
*
* Removes "Category:", "Tag:", "Author:", etc. prefixes from archive
* page titles and returns clean, plain text for theme styling.
*
* @param string $title The original archive title with prefix.
* @return string Clean title without prefix or HTML.
*/
function fm_clean_archive_title( $title ) {
   if ( is_category() || is_tag() || is_tax() ) {
       $title = single_term_title( '', false );
   } elseif ( is_post_type_archive() ) {
       $title = post_type_archive_title( '', false );
   } elseif ( is_author() ) {
       $author = get_queried_object();
       $title  = $author && isset( $author->display_name ) ? $author->display_name : '';
   } elseif ( is_year() ) {
       $title = get_the_date( _x( 'Y', 'yearly archives date format' ) );
   } elseif ( is_month() ) {
       $title = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
   } elseif ( is_day() ) {
       $title = get_the_date( _x( 'F j, Y', 'daily archives date format' ) );
   }
   return $title; // return plain text; let templates handle markup/escaping
}
add_filter( 'get_the_archive_title', 'fm_clean_archive_title' );