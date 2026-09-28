<?php
/**
 * Plugin Name:       DreamVisa AdSense
 * Description:       Adds Google AdSense (site verification, Auto ads, manual ad units via shortcode) and serves ads.txt for dreamvisaguide.com.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dream Visa Guide
 * License:           GPL-2.0-or-later
 * Text Domain:       dreamvisa-adsense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DVA_OPTION = 'dva_settings';

/**
 * Settings with defaults.
 */
function dva_get_settings() {
	$defaults = array(
		'publisher_id'   => '',   // Stored as pub-XXXXXXXXXXXXXXXX.
		'auto_ads'       => 1,
		'serve_ads_txt'  => 1,
		'hide_for_admin' => 1,
		'label'          => 1,
		'excluded_slugs' => 'contact-us, contact, privacy-policy, terms-and-conditions, disclaimer',
	);
	return wp_parse_args( (array) get_option( DVA_OPTION, array() ), $defaults );
}

/**
 * Publisher ID in the "ca-pub-XXXX" form used by ad code, or '' if unset.
 */
function dva_client_id() {
	$s = dva_get_settings();
	return $s['publisher_id'] ? 'ca-' . $s['publisher_id'] : '';
}

/**
 * Whether ads may be output on the current request.
 */
function dva_ads_allowed() {
	if ( ! dva_client_id() || is_admin() || is_feed() || is_404() || is_search() ) {
		return false;
	}
	$s = dva_get_settings();
	if ( $s['hide_for_admin'] && current_user_can( 'manage_options' ) ) {
		// Prevents accidental self-clicks/impressions, which violate AdSense policy.
		return false;
	}
	if ( is_page() ) {
		$slugs = array_filter( array_map( 'trim', explode( ',', $s['excluded_slugs'] ) ) );
		if ( $slugs && is_page( $slugs ) ) {
			return false;
		}
	}
	return (bool) apply_filters( 'dva_ads_allowed', true );
}

/**
 * Head output: account verification meta (always, so Google can verify the site)
 * and the Auto ads / ad library script (only where ads are allowed).
 */
add_action(
	'wp_head',
	function () {
		$client = dva_client_id();
		if ( ! $client ) {
			return;
		}
		printf( '<meta name="google-adsense-account" content="%s">' . "\n", esc_attr( $client ) );

		if ( dva_ads_allowed() ) {
			printf(
				'<script async src="%s" crossorigin="anonymous"></script>' . "\n",
				esc_url( 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( $client ) )
			);
		}
	},
	1
);

/**
 * [dv_ad slot="1234567890" format="auto"] — manual display ad unit.
 * Create the unit in AdSense (Ads > By ad unit) and copy its data-ad-slot number.
 */
add_shortcode(
	'dv_ad',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'slot'   => '',
				'format' => 'auto',
				'layout' => '',
			),
			$atts,
			'dv_ad'
		);
		$slot = preg_replace( '/\D/', '', $atts['slot'] );
		if ( ! $slot || ! dva_ads_allowed() ) {
			return '';
		}

		$layout = $atts['layout'] ? sprintf( ' data-ad-layout="%s"', esc_attr( $atts['layout'] ) ) : '';
		$label  = dva_get_settings()['label']
			? '<div class="dva-ad-label" style="font-size:11px;color:#777;text-align:center;letter-spacing:.05em;text-transform:uppercase;">' . esc_html__( 'Advertisement', 'dreamvisa-adsense' ) . '</div>'
			: '';

		return sprintf(
			'<div class="dva-ad" style="margin:24px 0;clear:both;">%1$s<ins class="adsbygoogle" style="display:block" data-ad-client="%2$s" data-ad-slot="%3$s" data-ad-format="%4$s"%5$s data-full-width-responsive="true"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>',
			$label,
			esc_attr( dva_client_id() ),
			esc_attr( $slot ),
			esc_attr( $atts['format'] ),
			$layout
		);
	}
);

/**
 * Serve /ads.txt when no physical file exists in the web root.
 */
add_action(
	'init',
	function () {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		if ( '/ads.txt' !== $path ) {
			return;
		}
		$s = dva_get_settings();
		if ( ! $s['serve_ads_txt'] || ! $s['publisher_id'] ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=86400' );
		// f08c47fec0942fa0 is Google's fixed certification authority ID.
		echo 'google.com, ' . $s['publisher_id'] . ", DIRECT, f08c47fec0942fa0\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- validated digits only.
		exit;
	},
	0
);

/* ------------------------------------------------------------------------- */
/* Settings page: Settings > DreamVisa AdSense                                */
/* ------------------------------------------------------------------------- */

add_action(
	'admin_init',
	function () {
		register_setting(
			'dva',
			DVA_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'dva_sanitize',
			)
		);
	}
);

function dva_sanitize( $input ) {
	$out = array();

	$raw = isset( $input['publisher_id'] ) ? trim( (string) $input['publisher_id'] ) : '';
	if ( preg_match( '/(?:ca-)?pub-(\d{16})/', $raw, $m ) ) {
		$out['publisher_id'] = 'pub-' . $m[1];
	} else {
		$out['publisher_id'] = '';
		if ( '' !== $raw ) {
			add_settings_error( DVA_OPTION, 'dva_pub', __( 'Publisher ID must look like pub-1234567890123456.', 'dreamvisa-adsense' ) );
		}
	}

	foreach ( array( 'auto_ads', 'serve_ads_txt', 'hide_for_admin', 'label' ) as $key ) {
		$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
	}

	$slugs                 = isset( $input['excluded_slugs'] ) ? explode( ',', (string) $input['excluded_slugs'] ) : array();
	$out['excluded_slugs'] = implode( ', ', array_filter( array_map( 'sanitize_title', $slugs ) ) );

	return $out;
}

// Auto ads are controlled from the AdSense dashboard; the script tag is required for them
// and for manual units, so "auto_ads" off simply means the script is only emitted with shortcodes.
add_filter(
	'dva_ads_allowed',
	function ( $allowed ) {
		if ( ! $allowed || dva_get_settings()['auto_ads'] ) {
			return $allowed;
		}
		global $post;
		return $post instanceof WP_Post && has_shortcode( $post->post_content, 'dv_ad' );
	}
);

add_action(
	'admin_menu',
	function () {
		add_options_page( 'DreamVisa AdSense', 'DreamVisa AdSense', 'manage_options', 'dreamvisa-adsense', 'dva_render_settings' );
	}
);

function dva_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s   = dva_get_settings();
	$opt = DVA_OPTION;
	?>
	<div class="wrap">
		<h1>DreamVisa AdSense</h1>
		<?php settings_errors( DVA_OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'dva' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="dva_pub">Publisher ID</label></th>
					<td>
						<input id="dva_pub" class="regular-text" name="<?php echo esc_attr( $opt ); ?>[publisher_id]" value="<?php echo esc_attr( $s['publisher_id'] ); ?>" placeholder="pub-1234567890123456">
						<p class="description">AdSense &rarr; Account &rarr; Account information.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Options</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[auto_ads]" value="1" <?php checked( $s['auto_ads'] ); ?>> Load AdSense script on all eligible pages (needed for Auto ads)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[serve_ads_txt]" value="1" <?php checked( $s['serve_ads_txt'] ); ?>> Serve <code>/ads.txt</code> automatically</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[hide_for_admin]" value="1" <?php checked( $s['hide_for_admin'] ); ?>> Hide ads for logged-in administrators (avoids accidental self-clicks)</label><br>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[label]" value="1" <?php checked( $s['label'] ); ?>> Show &ldquo;Advertisement&rdquo; label above manual ad units</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="dva_ex">No ads on these page slugs</label></th>
					<td>
						<input id="dva_ex" class="large-text" name="<?php echo esc_attr( $opt ); ?>[excluded_slugs]" value="<?php echo esc_attr( $s['excluded_slugs'] ); ?>">
						<p class="description">Comma-separated. Low-content pages (contact, legal) should not show ads.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php if ( $s['publisher_id'] ) : ?>
			<p>Check: <a href="<?php echo esc_url( home_url( '/ads.txt' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/ads.txt' ) ); ?></a></p>
		<?php endif; ?>
		<h2>Manual ad unit</h2>
		<p>Add a Shortcode block with <code>[dv_ad slot="YOUR_SLOT_ID"]</code>. In-article: <code>[dv_ad slot="YOUR_SLOT_ID" format="fluid" layout="in-article"]</code>.</p>
	</div>
	<?php
}
