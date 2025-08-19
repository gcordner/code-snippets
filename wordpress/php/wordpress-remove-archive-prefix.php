<?php
/*
* Remove "Archive:" prefix from WordPress archive page titles
* 
* Purpose: Clean up default WordPress archive titles by removing prefixes like:
* - "Category: News" becomes just "News"
* - "Tag: WordPress" becomes just "WordPress"  
* - "Author: John Doe" becomes just "John Doe"
* - "Archives: Products" becomes just "Products"
* 
* Usage: Add to functions.php or in a plugin
*/

/**
* Remove archive title prefix from WordPress archive pages.
*
* @param string $title The original archive title.
* @return string The cleaned title without prefix.
*/
function remove_archive_title_prefix($title) {
   if (is_category()) {
       $title = single_cat_title('', false);
   } elseif (is_tag()) {
       $title = single_tag_title('', false);
   } elseif (is_author()) {
       $title = '<span class="vcard">' . get_the_author() . '</span>';
   } else {
       $title = post_type_archive_title('', false);
   }
   return $title;
}
add_filter('get_the_archive_title', 'remove_archive_title_prefix');