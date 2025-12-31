<?php
/*
Plugin Name: Jetpack CRM + WP Upload Types Integration
Plugin URI: https://github.com/ashleybroom/jetpack-crm-upload-types
Description: Adds a settings screen to manage allowed upload file extensions and MIME types, integrates safely with Jetpack CRM, and displays global allowed/excluded upload types.
Version: 1.2.0
Author: Ashley Broom
Author URI: https://github.com/ashleybroom
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: jpcrm-upload-types
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JPCRM_UPLOAD_TYPES_OPT', 'jpcrm_upload_types_map' );

/**
 * Load translations.
 */
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain(
		'jpcrm-upload-types',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
} );

/* ======================================================
   Defaults
   ====================================================== */

/**
 * Default allowed extensions + mime lists.
 * Edit these defaults if you want a different first-run set.
 */
function jpcrm_upload_types_default_map() {
	return array(
		'stl'  => array( 'model/stl', 'application/sla', 'application/vnd.ms-pki.stl', 'application/octet-stream' ),
		'step' => array( 'application/step', 'model/step', 'application/octet-stream' ),
		'stp'  => array( 'application/step', 'model/step', 'application/octet-stream' ),
		'smf'  => array( 'model/smf', 'application/octet-stream' ),
		'3mf'  => array( 'application/3mf', 'model/3mf', 'application/octet-stream' ),
	);
}

/* ======================================================
   Helpers
   ====================================================== */

function jpcrm_upload_types_get_map() {
	$opt = get_option( JPCRM_UPLOAD_TYPES_OPT );

	if ( is_array( $opt ) && ! empty( $opt ) ) {
		$out = array();

		foreach ( $opt as $ext => $mimes ) {
			$ext = strtolower( trim( (string) $ext ) );
			if ( $ext === '' ) {
				continue;
			}
			if ( ! is_array( $mimes ) ) {
				continue;
			}

			$clean_mimes = array();
			foreach ( $mimes as $m ) {
				$m = strtolower( trim( (string) $m ) );
				if ( $m !== '' ) {
					$clean_mimes[] = $m;
				}
			}
			$clean_mimes = array_values( array_unique( $clean_mimes ) );

			if ( ! empty( $clean_mimes ) ) {
				$out[ $ext ] = $clean_mimes;
			}
		}

		if ( ! empty( $out ) ) {
			return $out;
		}
	}

	return jpcrm_upload_types_default_map();
}

/**
 * Parse textarea where each line is:
 * ext=mime1,mime2,mime3
 * Lines starting with # are ignored.
 */
function jpcrm_upload_types_parse_textarea_map( $raw ) {
	$map   = array();
	$lines = preg_split( "/\r\n|\n|\r/", (string) $raw );

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		if ( str_starts_with( $line, '#' ) ) {
			continue;
		}

		$parts = explode( '=', $line, 2 );
		if ( count( $parts ) !== 2 ) {
			continue;
		}

		$ext = strtolower( trim( $parts[0] ) );

		// ext only [a-z0-9] to keep it simple/safe.
		if ( ! preg_match( '/^[a-z0-9]+$/', $ext ) ) {
			continue;
		}

		$mimes = explode( ',', $parts[1] );
		$clean = array();

		foreach ( $mimes as $m ) {
			$m = strtolower( trim( (string) $m ) );
			if ( $m !== '' ) {
				$clean[] = $m;
			}
		}

		$clean = array_values( array_unique( $clean ) );
		if ( ! empty( $clean ) ) {
			$map[ $ext ] = $clean;
		}
	}

	return $map;
}

/**
 * WordPress-known extension list, derived from wp_get_mime_types().
 */
function jpcrm_upload_types_wp_known_extensions() {
	$wp_mimes = wp_get_mime_types(); // ['jpg|jpeg|jpe' => 'image/jpeg', ...]
	$exts     = array();

	foreach ( $wp_mimes as $ext_group => $mime ) {
		$parts = explode( '|', (string) $ext_group );
		foreach ( $parts as $ext ) {
			$ext = strtolower( trim( (string) $ext ) );
			if ( $ext !== '' ) {
				$exts[ $ext ] = true;
			}
		}
	}

	$exts = array_keys( $exts );
	sort( $exts );

	return $exts;
}

/**
 * Settings page URL.
 */
function jpcrm_upload_types_settings_url() {
	return admin_url( 'options-general.php?page=jpcrm-upload-types' );
}

/* ======================================================
   Admin UI
   ====================================================== */

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Upload Types', 'jpcrm-upload-types' ),
		__( 'Upload Types', 'jpcrm-upload-types' ),
		'manage_options',
		'jpcrm-upload-types',
		'jpcrm_upload_types_render_settings_page'
	);
} );

/**
 * Add "Settings" link on Plugins page.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$settings_link = '<a href="' . esc_url( jpcrm_upload_types_settings_url() ) . '">' . esc_html__( 'Settings', 'jpcrm-upload-types' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
} );

function jpcrm_upload_types_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Save
	if (
		isset( $_POST['jpcrm_upload_types_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jpcrm_upload_types_nonce'] ) ), 'jpcrm_upload_types_save' )
	) {
		$raw    = wp_unslash( $_POST['jpcrm_upload_types_text'] ?? '' );
		$parsed = jpcrm_upload_types_parse_textarea_map( $raw );

		if ( empty( $parsed ) ) {
			update_option( JPCRM_UPLOAD_TYPES_OPT, jpcrm_upload_types_default_map(), false );
			add_settings_error(
				'jpcrm_upload_types_messages',
				'jpcrm_upload_types_saved',
				__( 'Saved defaults (input was empty or invalid).', 'jpcrm-upload-types' ),
				'updated'
			);
		} else {
			update_option( JPCRM_UPLOAD_TYPES_OPT, $parsed, false );
			add_settings_error(
				'jpcrm_upload_types_messages',
				'jpcrm_upload_types_saved',
				__( 'Settings saved.', 'jpcrm-upload-types' ),
				'updated'
			);
		}
	}

	// Build textarea from current map
	$map   = jpcrm_upload_types_get_map();
	$lines = array();

	foreach ( $map as $ext => $mimes ) {
		$lines[] = $ext . '=' . implode( ',', $mimes );
	}
	$text = implode( "\n", $lines );

	$allowed_exts = array_keys( $map );
	sort( $allowed_exts );

	$wp_known_exts = jpcrm_upload_types_wp_known_extensions();
	$excluded_exts = array_values( array_diff( $wp_known_exts, $allowed_exts ) );
	sort( $excluded_exts );

	settings_errors( 'jpcrm_upload_types_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Upload Types', 'jpcrm-upload-types' ); ?></h1>

		<p>
			<?php echo esc_html__( 'One per line. Format:', 'jpcrm-upload-types' ); ?>
			<code>ext=mime1,mime2,mime3</code>
		</p>
		<p>
			<?php echo esc_html__( 'Lines starting with # are ignored.', 'jpcrm-upload-types' ); ?>
		</p>

		<form method="post">
			<?php wp_nonce_field( 'jpcrm_upload_types_save', 'jpcrm_upload_types_nonce' ); ?>

			<textarea name="jpcrm_upload_types_text" rows="14" style="width:100%;max-width:900px;font-family:monospace;"><?php echo esc_textarea( $text ); ?></textarea>

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php echo esc_html__( 'Save Changes', 'jpcrm-upload-types' ); ?>
				</button>
			</p>

			<div style="display:flex;gap:20px;flex-wrap:wrap;max-width:900px;">
				<div style="flex:1;min-width:280px;padding:12px;border:1px solid #ccd0d4;background:#fff;">
					<h2 style="margin-top:0;"><?php echo esc_html__( 'Currently allowed', 'jpcrm-upload-types' ); ?></h2>
					<ul style="columns:3;column-gap:24px;">
						<?php foreach ( $allowed_exts as $ext ) : ?>
							<li><code><?php echo esc_html( $ext ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div style="flex:1;min-width:280px;padding:12px;border:1px solid #ccd0d4;background:#fff;">
					<h2 style="margin-top:0;"><?php echo esc_html__( 'Currently excluded (known to WordPress)', 'jpcrm-upload-types' ); ?></h2>
					<ul style="columns:3;column-gap:24px;">
						<?php foreach ( $excluded_exts as $ext ) : ?>
							<li><code><?php echo esc_html( $ext ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<hr style="max-width:900px;margin:20px 0;" />
			<p style="max-width:900px;">
				<?php echo esc_html__( 'Note: For some binary formats, servers may detect the MIME as application/octet-stream. If uploads fail, include application/octet-stream in the MIME list for that extension.', 'jpcrm-upload-types' ); ?>
			</p>
		</form>
	</div>
	<?php
}

/* ======================================================
   WordPress Upload Handling
   ====================================================== */

/**
 * Allow our extensions at WordPress level.
 * WP expects a single MIME per extension; we use the first MIME as "primary".
 */
add_filter( 'upload_mimes', function ( $mimes ) {
	$map = jpcrm_upload_types_get_map();
	foreach ( $map as $ext => $mime_list ) {
		$mimes[ $ext ] = $mime_list[0] ?? 'application/octet-stream';
	}
	return $mimes;
} );

/**
 * Bypass strict sniffing for our configured extensions.
 * This prevents WP from blocking files even if fileinfo reports them as generic.
 */
add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
	$ext = strtolower( pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
	$map = jpcrm_upload_types_get_map();

	if ( isset( $map[ $ext ] ) ) {
		return array(
			'ext'             => $ext,
			'type'            => $mimes[ $ext ] ?? ( $map[ $ext ][0] ?? 'application/octet-stream' ),
			'proper_filename' => false,
		);
	}

	return $data;
}, 10, 4 );

/* ======================================================
   Jetpack CRM Integration
   ====================================================== */

/**
 * Merge our accepted mime types into Jetpack CRM at runtime.
 * Does NOT overwrite JPCRM files; it only extends $zbs->acceptable_mime_types in memory.
 */
add_action( 'init', function () {
	if ( ! isset( $GLOBALS['zbs'] ) || ! is_object( $GLOBALS['zbs'] ) ) {
		return;
	}

	$zbs = $GLOBALS['zbs'];

	if ( ! isset( $zbs->acceptable_mime_types ) || ! is_array( $zbs->acceptable_mime_types ) ) {
		return;
	}

	$map = jpcrm_upload_types_get_map();

	foreach ( $map as $ext => $mime_list ) {
		if ( ! isset( $zbs->acceptable_mime_types[ $ext ] ) ) {
			$zbs->acceptable_mime_types[ $ext ] = array();
		}

		$zbs->acceptable_mime_types[ $ext ] = array_values(
			array_unique(
				array_merge(
					(array) $zbs->acceptable_mime_types[ $ext ],
					(array) $mime_list
				)
			)
		);
	}
}, 20 );
