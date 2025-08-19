<?php

/**
 * Check ACF Field Use
 *
 * Plugin Name: Check ACF Field Use
 * Plugin URI:  https://wordpress.org/plugins/check-acf-field-use/
 * Description: Check how many times an ACF field is used across your site, get useful data, post links, etc.
 * Version:     1.0.0
 * Author:      KMW
 * Author URI:  https://github.com/kmwalshcheck-acf-field-use/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: check-acf-field-use
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * @link https://github.com/bueltge/WordPress-Admin-Style Thank you.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

class CheckACFFieldUse {
	
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_post_check_acf_page_use', [ $this, 'form_action' ] );
	}

	public function add_menu_page() {
		add_menu_page(
			'Check ACF Field Use',
			'Check ACF Field Use',
			'manage_options',
			'check-acf-field-use',
			[ $this, 'check_acf_field_use_page' ],
			'dashicons-list-view',
			99
		);
	}

	public function form_action() {
			if( isset( $_REQUEST['field'] ) ) :
				$http_query = http_build_query( 
						[
							'field' => sanitize_key( $_REQUEST['field'] ), 
						]
				);
			else :
				//@todo this shit ain't working, needs error on empty field
				$http_query = http_build_query( 
						[
							'no-field' => true,
						]
				);
			endif;
			wp_redirect( $_SERVER['HTTP_REFERER'] . '&' . $http_query );
			exit();
	}

	public static function check_acf_field_use_page() {
		?>
		<div id="wp_strip_image_metadata" class="wrap">
			<h1><?php esc_html_e( 'Check ACF Field Use', 'check-acf-field-use' ); ?></h1>
			<p>Check how many times an ACF field is used across your site, get useful data, post links, etc. Useful for building reports, verifying a field is not in public/active use before deleting it, or just seeing how widely used a given ACF field is on your site.</p>

			<p>Enter the <strong>name</strong> of the field you wish to check. Go into ACF, click into your field group, then get your name from the column. Don't use field keys.</p>

			<?php 
			//@todo: pull this out and make a separate function
			if ( ! function_exists( 'get_field' ) ) : ?>
				<div class="notice notice-error inline">
				<p>
					<?php echo esc_html_e( 'It doesn\'t appear you have Advanced Custom Fields installed on this site. This plugin won\'t do you much good.', 'check-acf-field-use' );
					?>
				</p>
			</div>
			<?php endif; ?>

			<?php
				$nonce = wp_create_nonce( 'nonce' );
				$http_query = http_build_query( 
						[
							'nonce' => $nonce, 
							'page' => 'check-acf-field-use',
						]
				);
			?>
			
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) . '?' . $http_query ); ?>" method="post">
				<input type="hidden" name="action" value="check_acf_page_use" />
				<label for="acf-field-to-check">ACF Field to Check (<strong>name</strong> of field)</label>
				<input id="acf-field-to-check" name="field" type="text" value="<?php echo esc_attr( ( isset( $_REQUEST['field'] ) && ! empty( $_REQUEST['field'] ) ) ? sanitize_key( $_REQUEST['field'] ): null ); ?>" placeholder="ACF Field Name" class="regular-text" />
				<?php submit_button(
					'Submit', $type = 'primary', $name = 'submit', $wrap = FALSE, $other_attributes = NULL
				); ?>
			</form>

			<?php if( isset( $_REQUEST['no-field'] ) ) : ?>
				<div class="notice notice-alt notice-error inline">
				<p>You have to input a field. Without a field, there's nothing to check.</p>
			</div>
			<?php endif; ?>

			<?php if( isset( $_REQUEST['field'] ) ) : ?>
				<?php self::get_field_data( sanitize_key( $_REQUEST['field'] ) ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function get_field_data( $field ) {
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT DISTINCT ID, post_title, meta_value from `{$wpdb->base_prefix}posts`
			LEFT JOIN `{$wpdb->base_prefix}postmeta` ON `post_id` = `ID`
			WHERE `post_status` = 'publish' AND `meta_key` LIKE %s AND `meta_value` NOT LIKE %s AND meta_value IS NOT NULL AND meta_value <> '';",
			'%' . $wpdb->esc_like($field) . '%',
			'%field_%'
		);
		$field_uses = $wpdb->get_results( $sql );
		?>
		<br/>
		<?php 
			// @TODO: CSV export
			submit_button(
				'Export Data as CSV', $type = 'primary', $name = 'export', $wrap = FALSE, $other_attributes = NULL
			);
		?>
		<hr>
		<table class="widefat">
			<thead>
			<tr>
				<th class="row-title"><?php esc_attr_e( 'Post', 'WpAdminStyle' ); ?></th>
				<th><?php esc_attr_e( 'Post ID', 'WpAdminStyle' ); ?></th>
				<th><?php esc_attr_e( 'Field Value', 'WpAdminStyle' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $field_uses as $field_use ) :
				?>
				
					<tr>
						<td class="row-title"><label for="tablecell"><a href="<?php echo esc_url( get_edit_post_link( $field_use->ID) ); ?>"><?php echo esc_html( $field_use->post_title ); ?></a></label></td>
						<td><?php echo esc_html( $field_use->ID ); ?></td>
						<td><?php echo esc_html( $field_use->meta_value ); ?></td>
					</tr>

				<?php
			endforeach;
			?>
			</table>

		<?php
	}

}

new CheckACFFieldUse();