<?php
/**
 * WooCommerce — Disable admin-bar items at the source (performance).
 *
 * WHAT IT DOES
 * - Prevents WooCommerce / WC Admin from attaching `admin_bar_menu` callbacks.
 *   This skips the DB queries / option lookups / HTTP calls those toolbar items
 *   may perform to build badges and counters.
 * - Adds a very-late fallback that removes known WooCommerce toolbar nodes
 *   if another plugin re-adds them later.
 *
 * SCOPE
 * - Affects only the black top admin bar for logged-in users.
 * - Does NOT change WooCommerce left-hand admin menus or Woo admin screens.
 *
 * HOW TO DEPLOY
 * 1) Code Snippets plugin:
 *    - Paste the code (without the opening `<?php`).
 *    - Set “Run snippet everywhere.”
 *    - Save & activate.
 * 2) Theme/child theme `functions.php` or a shared utility file:
 *    - Place this file in your repo (e.g., `wordpress/admin/`).
 *    - `require_once` it from your bootstrap/autoloader.
 * 3) Must-Use plugin (recommended for global behavior):
 *    - Put this file (or a loader that requires it) in `wp-content/mu-plugins/`.
 *
 * SEE ALSO
 * - `admin/admin-bar-prune-keep-essentials.php` (cosmetic prune after build)
 * - `admin/admin-bar-disable-booster.php` (Booster/WCJ admin-bar prevention)
 *
 * @package FormerModel\WordPress\Admin
 */

add_action( 'init', 'fm_admin_bar_disable_woocommerce_toolbar', 1 );
add_action( 'admin_bar_menu', 'fm_admin_bar_disable_woocommerce_fallback', PHP_INT_MAX );

/**
 * Unhook WooCommerce/WC Admin callbacks from the admin bar before they run.
 *
 * Runs very early on `init` to remove any `admin_bar_menu` callbacks whose callable
 * names/classes indicate WooCommerce or WC Admin. This prevents their logic from
 * executing, providing a small but real performance win for logged-in views.
 *
 * @return void
 */
function fm_admin_bar_disable_woocommerce_toolbar(): void {
	// Optional micro-guard to avoid any overhead when the bar won't render.
	if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
		return;
	}

	global $wp_filter;

	$hook = 'admin_bar_menu';
	if ( empty( $wp_filter[ $hook ] ) || ! ( $wp_filter[ $hook ] instanceof WP_Hook ) ) {
		return;
	}

	$looks_like_woo = static function ( $callable ): bool {
		// Normalize callable to a string we can inspect.
		if ( is_string( $callable ) ) {
			$repr = $callable;
		} elseif ( is_array( $callable ) ) {
			$repr = ( is_object( $callable[0] ) ? get_class( $callable[0] ) : (string) $callable[0] ) . '::' . $callable[1];
		} elseif ( is_object( $callable ) ) {
			$repr = get_class( $callable );
		} else {
			$repr = 'callable';
		}
		$repr = strtolower( $repr );

		return (
			strpos( $repr, 'woocommerce' ) !== false
			|| strpos( $repr, 'automattic\\woocommerce' ) !== false
			|| strpos( $repr, '\\wc_' ) !== false
			|| strpos( $repr, 'wc_' ) !== false
		);
	};

	// Iterate all priorities and remove Woo-looking callbacks.
	foreach ( (array) $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $entry ) {
			if ( isset( $entry['function'] ) && $looks_like_woo( $entry['function'] ) ) {
				remove_action( $hook, $entry['function'], $priority );
			}
		}
	}
}

/**
 * Very-late fallback: remove known WooCommerce admin-bar nodes if anything slipped through.
 *
 * This is defensive: if another plugin re-adds Woo nodes after we unhooked them,
 * prune the survivors here. Safe for legacy environments that still expose remove_menu().
 *
 * @param \WP_Admin_Bar $bar Admin bar instance.
 * @return void
 */
function fm_admin_bar_disable_woocommerce_fallback( \WP_Admin_Bar $bar ): void {
	$ids = array(
		'woocommerce-site-visibility-badge', // Woo "Live" badge.
		'view-store',                         // "Visit Store" under Site Name.
	);

	foreach ( $ids as $id ) {
		$bar->remove_node( $id );
		if ( method_exists( $bar, 'remove_menu' ) ) {
			$bar->remove_menu( $id ); // Legacy safety.
		}
	}
}
