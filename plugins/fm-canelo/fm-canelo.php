<?php
/**
 * Plugin Name: FM Canelo
 * Plugin URI: https://example.com/fm-canelo
 * Description: A WordPress plugin that displays "Viva Mexico, Cabrones!" in the WordPress admin area.
 * Version: 1.0.0
 * Author: Claude
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fm-canelo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display admin notice with the message
 */
function fm_canelo_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php echo esc_html('¡Viva Mexico, Cabrones!'); ?></strong></p>
    </div>
    <?php
}

// Hook into admin notices
add_action('admin_notices', 'fm_canelo_admin_notice');

/**
 * Plugin activation hook
 */
function fm_canelo_plugin_activate() {
    // Nothing special needed on activation for this simple plugin
}
register_activation_hook(__FILE__, 'fm_canelo_plugin_activate');

/**
 * Plugin deactivation hook
 */
function fm_canelo_plugin_deactivate() {
    // Nothing special needed on deactivation for this simple plugin
}
register_deactivation_hook(__FILE__, 'fm_canelo_plugin_deactivate');