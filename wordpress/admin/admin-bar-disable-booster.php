<?php
/**
 * Booster for WooCommerce — Disable admin-bar items at the source (performance).
 *
 * WHAT IT DOES
 * - Prevents Booster / WCJ / WooJetpack from attaching `admin_bar_menu` callbacks.
 *   This avoids the DB lookups / option reads / HTTP calls they might perform to
 *   build toolbar badges/counters.
 * - Adds very-late sweeps to prune any nodes that slip through (e.g., re-added by
 *   another plugin at extreme priorities).
 *
 * SCOPE
 * - Only affects the black top admin bar for logged-in users.
 * - Does NOT alter WooCommerce/Booster left-hand admin menus or admin screens.
 *
 * HOW TO DEPLOY
 * 1) Code Snippets plugin:
 *    - Paste the code (without the opening `<?php`).
 *    - Set “Run snippet everywhere.”
 *    - Save & activate.
 * 2) Theme/child theme `functions.php` or a shared utility file:
 *    - Place this file in your repo (e.g., `wordpress/admin/`).
 *    - `require_once` it from your bootstrap/autoloader.
 * 3) Must-Use plugin (recommended for global/global-ish behavior):
 *    - Put this file (or a loader that requires it) in `wp-content/mu-plugins/`.
 *
 * SEE ALSO
 * - `admin/admin-bar-prune-keep-essentials.php` (cosmetic prune after build)
 * - `admin/admin-bar-disable-woocommerce.php` (WooCommerce admin-bar prevention)
 *
 * @package FormerModel\WordPress\Admin
 */

add_action( 'init', 'fm_admin_bar_disable_booster_toolbar', 1 );
add_action( 'admin_bar_menu', 'fm_admin_bar_disable_booster_sweep', PHP_INT_MAX );
add_action( 'wp_before_admin_bar_render', 'fm_admin_bar_disable_booster_final_pass', PHP_INT_MAX );

/**
 * Unhook Booster/WCJ/WooJetpack admin-bar callbacks before they run.
 *
 * Runs very early on `init` and inspects all callbacks attached to `admin_bar_menu`,
 * removing any whose callable name/class/file indicates Booster/WCJ/WooJetpack.
 * This prevents their logic from executing, yielding a small performance win
 * for logged-in views where the admin bar is shown.
 *
 * @return void
 */
function fm_admin_bar_disable_booster_toolbar(): void {
	// Micro-guard: if the bar won’t render, skip all work.
	if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
		return;
	}

	global $wp_filter;

	$hook = 'admin_bar_menu';
	if ( empty( $wp_filter[ $hook ] ) || ! ( $wp_filter[ $hook ] instanceof WP_Hook ) ) {
		return;
	}

	foreach ( (array) $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $entry ) {
			if ( empty( $entry['function'] ) ) {
				continue;
			}

			$fn   = $entry['function'];
			$repr = '';
			$file = '';

			// Build a searchable string from callable + file path (best effort).
			try {
				if ( is_string( $fn ) ) {
					$repr = $fn;
					$rf   = new ReflectionFunction( $fn );
					$file = (string) $rf->getFileName();
				} elseif ( is_array( $fn ) && isset( $fn[0], $fn[1] ) ) {
					$cls  = is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0];
					$repr = $cls . '::' . $fn[1];
					$rm   = new ReflectionMethod( $fn[0], $fn[1] );
					$file = (string) $rm->getFileName();
				} elseif ( $fn instanceof Closure ) {
					$rf   = new ReflectionFunction( $fn );
					$file = (string) $rf->getFileName();
					$repr = 'Closure';
				} else {
					$repr = is_object( $fn ) ? get_class( $fn ) : 'callable';
				}
			} catch ( Throwable $e ) {
				// Log only in debug to satisfy PHPCS (non-empty catch) without noisy production logs.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log(
						sprintf(
							'fm_admin_bar_disable_booster_toolbar: Reflection failed for callable. %s in %s:%d',
							method_exists( $e, 'getMessage' ) ? $e->getMessage() : 'Exception',
							method_exists( $e, 'getFile' ) ? $e->getFile() : 'unknown',
							method_exists( $e, 'getLine' ) ? $e->getLine() : 0
						)
					);
				}
			}

			$hay                = strtolower( $repr . ' ' . $file );
			$looks_like_booster =
				strpos( $hay, 'booster' ) !== false ||
				strpos( $hay, 'woocommerce-jetpack' ) !== false || // legacy slug.
				strpos( $hay, 'woojetpack' ) !== false ||
				strpos( $hay, '/wcj' ) !== false;

			if ( $looks_like_booster ) {
				remove_action( $hook, $fn, $priority );
			}
		}
	}
}

/**
 * Very-late sweep on `admin_bar_menu`: remove any Booster/WCJ nodes by ID pattern.
 *
 * Defensive pass in case another plugin re-adds nodes after we unhook.
 *
 * @param \WP_Admin_Bar $bar Admin bar instance.
 * @return void
 */
function fm_admin_bar_disable_booster_sweep( \WP_Admin_Bar $bar ): void {
	if ( ! method_exists( $bar, 'get_nodes' ) ) {
		return;
	}
	foreach ( (array) $bar->get_nodes() as $node ) {
		$id = isset( $node->id ) ? (string) $node->id : '';
		if ( $id && ( stripos( $id, 'wcj' ) !== false || stripos( $id, 'booster' ) !== false ) ) {
			$bar->remove_node( $id );
			if ( method_exists( $bar, 'remove_menu' ) ) {
				$bar->remove_menu( $id ); // Legacy safety.
			}
		}
	}
}

/**
 * Final pass right before render: prune ultra-late Booster/WCJ additions.
 *
 * Some adders attach extremely late; this ensures any remaining nodes are removed.
 *
 * @return void
 */
function fm_admin_bar_disable_booster_final_pass(): void {
	global $wp_admin_bar;
	if ( ! $wp_admin_bar || ! method_exists( $wp_admin_bar, 'get_nodes' ) ) {
		return;
	}
	foreach ( (array) $wp_admin_bar->get_nodes() as $node ) {
		$id = isset( $node->id ) ? (string) $node->id : '';
		if ( $id && ( stripos( $id, 'wcj' ) !== false || stripos( $id, 'booster' ) !== false ) ) {
			$wp_admin_bar->remove_node( $id );
			if ( method_exists( $wp_admin_bar, 'remove_menu' ) ) {
				$wp_admin_bar->remove_menu( $id ); // Legacy safety.
			}
		}
	}
}
