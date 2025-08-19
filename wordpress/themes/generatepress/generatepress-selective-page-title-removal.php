<?php
/*
* GeneratePress Flexible Page Title Control
* 
* Purpose: Remove automatic page titles when you want custom control
* 
* What it does:
* 1. Removes page title on homepage (common for custom hero sections)
* 2. Auto-detects when Post Title block is used and removes duplicate theme title
* 
* Why needed: GeneratePress automatically shows page titles, but modern
* designs often need custom title placement or want to use Gutenberg's
* Post Title block without creating duplicates
* 
* Client benefit: They can use Post Title blocks in page builders without
* getting double titles, and homepage designs aren't constrained by automatic titles
* 
* Hook timing: 'wp' ensures page context is fully loaded before checking
* 
* Usage: Add to child theme functions.php
*/

/**
* Remove page titles selectively for better design control.
*
* Removes automatic GeneratePress page titles on homepage and when
* Post Title blocks are detected to prevent duplicate titles.
*
* @return void
*/
function customize_page_titles() {
   // Remove title on home page only
   if (is_front_page()) {
       remove_action('generate_before_content', 'generate_page_header');
       add_filter('generate_show_title', '__return_false');
   }
   
   // Optional: Remove title on specific pages where you want to use the block instead
   global $post;
   if (is_page() && $post) {
       // Check if page content contains the Page Title block
       if (has_block('core/post-title', $post->post_content)) {
           remove_action('generate_before_content', 'generate_page_header');
           add_filter('generate_show_title', '__return_false');
       }
   }
}
add_action('wp', 'customize_page_titles');