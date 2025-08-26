<?php
/*
* Custom Body Classes for Post Type Archives
* 
* Purpose: Add specific CSS classes to body element on custom post type archive pages
* Use case: Apply different styling/backgrounds to specific archive types
* 
* Example: Writing archive gets 'archive-writing' and 'bg-brand-primary' classes
* allowing for targeted CSS styling and brand-consistent backgrounds
* 
* Usage: Add to functions.php or child theme functions.php
* Output: Classes added to <body> element for CSS targeting
*/

/**
* Add custom CSS classes to body element on post type archive pages.
*
* @param array $classes Existing body classes.
* @return array Modified body classes array.
*/
function add_post_type_archive_body_classes($classes) {
   if (is_post_type_archive('writing')) {
       $classes[] = 'archive-writing';
       $classes[] = 'bg-brand-primary'; // Semantic, won't break
   }
   return $classes;
}
add_filter('body_class', 'add_post_type_archive_body_classes');