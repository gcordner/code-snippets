<?php
/*
* Enable Shortcodes in Text Widgets
* 
* Purpose: Allow shortcodes to render properly in WordPress text widgets
* 
* Primary use case: Footer widgets with mailing list signup forms
* - MailChimp for WordPress shortcodes
* - Constant Contact forms  
* - Newsletter plugin shortcodes
* - Contact Form 7 signup forms
* 
* Why needed: Popular themes (Astra, etc.) use TinyMCE text widgets
* that don't process shortcodes by default
* 
* Performance note: Only processes shortcodes when they actually exist
* Important since footer widgets render on every page load
* 
* Usage: Add to functions.php
*/

add_filter('widget_text', function($text) {
    // Only run do_shortcode if shortcodes are present
    if (has_shortcode($text, null)) {
        return do_shortcode($text);
    }
    return $text;
});