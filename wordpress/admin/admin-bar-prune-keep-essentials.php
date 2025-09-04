<?php
/**
 * Admin Bar — Prune clutter, keep essentials.
 *
 * WHAT IT DOES
 * - Removes common noise from the top admin bar while keeping:
 *   - Front-end “Edit” link
 *   - Query Monitor (and its submenus)
 *   - WP Engine quick links
 *   - NitroPack menu
 *   - Site Name → “Visit Site” (but removes “Visit Store”)
 * - This is cosmetic: it hides items after they’re added. For real performance,
 *   unhook plugins at `admin_bar_menu` before they build nodes.
 *
 * HOW TO DEPLOY
 * 1) Code Snippets plugin:
 *    - Paste the code (without the opening `<?php`).
 *    - Set “Run snippet everywhere.”
 *    - Save & activate.
 * 2) Theme/child theme `functions.php` or a shared utility file:
 *    - `require_once` it from `functions.php` or your bootstrap/autoloader.
 * 3) Must-Use plugin (recommended for global behavior):
 *    - Put this file (or a loader that requires it) in `wp-content/mu-plugins/`.
 *
 * SEE ALSO (performance / prevention at source)
 * - `admin/admin-bar-disable-woocommerce.php`
 *     Prevents WooCommerce from attaching its admin bar items (unhooks callbacks).
 *
 *     @see fm_admin_bar_disable_woocommerce_toolbar()
 *
 * - `admin/admin-bar-disable-booster.php`
 *     Prevents Booster for WooCommerce (WCJ/WooJetpack) from attaching admin bar items.
 *     @see fm_admin_bar_disable_booster_toolbar()
 *
 * NOTES
 * - Uses a very late priority to beat late-adding plugins.
 * - Adjust `$remove_ids` as desired.
 *
 * @package FormerModel\WordPress\Admin
 */

add_action( 'admin_bar_menu', 'fm_admin_bar_prune_keep_essentials', PHP_INT_MAX );

/**
 * Remove non-essential admin bar nodes while keeping key tools.
 *
 * @param \WP_Admin_Bar $bar Admin bar instance.
 * @return void
 *
 * @see fm_admin_bar_disable_woocommerce_toolbar() For preventing WooCommerce nodes at source.
 * @see fm_admin_bar_disable_booster_toolbar()     For preventing Booster/WCJ nodes at source.
 */
function fm_admin_bar_prune_keep_essentials( \WP_Admin_Bar $bar ): void {

	// Keep "Visit Site" but drop the WooCommerce "Visit Store" child.
	$bar->remove_node( 'view-store' );
	if ( method_exists( $bar, 'remove_menu' ) ) {
		$bar->remove_menu( 'view-store' ); // Legacy safety.
	}

	// Remove top-level clutter (children go with their parent).
	$remove_ids = array(
		'wp-logo',                           // About WP group.
		'woocommerce-site-visibility-badge', // Woo "Live" badge.
		'updates',                           // Updates bubble.
		'comments',                          // Comments bubble.
		'new-content',                       // "+ New" and children.
		'rank-math',                         // Rank Math and children.
		'wc_twilio_sms_admin_bar_menu',      // Twilio SMS menu.
		'btn-wcabe-admin-bar',               // Woo Advanced Bulk Edit button.
		// Intentionally NOT removing: 'wpengine_adminbar', Query Monitor, NitroPack, user menu.
	);

	foreach ( $remove_ids as $id ) {
		$bar->remove_node( $id );
		if ( method_exists( $bar, 'remove_menu' ) ) {
			$bar->remove_menu( $id ); // Legacy safety.
		}
	}
}
