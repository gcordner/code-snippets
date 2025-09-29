<?php
/*
* GeneratePress: Replace Footer Credits
*
* Purpose: Override the default GeneratePress footer copyright text with a custom message without unhooking or editing theme templates.
*
* What it does:
* 1) Hooks into the `generate_copyright` filter, which controls footer output.
* 2) Dynamically inserts the current year (`date_i18n( 'Y' )`) and the site’s name (`get_bloginfo( 'name' )`).
* 3) Appends custom credit text with a styled link.
*
* Why needed:
* GeneratePress’s default footer credits are generic. This snippet allows site owners to add branding and attribution inline, avoiding the need for a child theme template override.
*
* Client benefit:
* Professional, branded footer credits that update automatically each year—low maintenance and no manual edits required.
*
* Hook timing:
* Runs at the default priority (10) on `generate_copyright`. The original string is passed in as `$original`, but is fully replaced.
*
* Usage:
* Copy this snippet into your child theme’s `functions.php` or a small custom plugin. Edit the return string to suit your branding.
*
* Adjustments:
* - To keep part of the default footer, append `$original` to the returned string instead of replacing it.
* - You can add more links, company info, or icons inside the returned HTML.
*/


/*
* GeneratePress: Replace Footer Credits
*
* Purpose: Override the default GeneratePress footer copyright text via filter—no unhooking or template edits required.
*/

add_filter(
	'generate_copyright',
	function ( $original ) {
		$year = wp_date( 'Y' );
		$site = get_bloginfo( 'name' );

		$brand_label = esc_html__( 'Designed and Built by', 'your-textdomain' );
		$brand_url   = 'https://former-model.com';

		$custom = sprintf(
			'<span class="copyright">&copy; %s %s</span> &bull; %s <a href="%s" target="_blank" rel="noopener noreferrer">Former Model</a>.',
			esc_html( $year ),
			esc_html( $site ),
			$brand_label,
			esc_url( $brand_url )
		);

		// Return $custom to fully replace, or append $original to keep theme defaults:
		// return $custom . ' ' . $original;
		return $custom;
	},
	10
);

