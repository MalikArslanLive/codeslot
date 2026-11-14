<?php
/** Private diagnostics and bounded local event records. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Support {
	public static function boot() {
		add_action( 'updated_post_meta', array( self::class, 'meta' ), 10, 4 );
		add_action( 'added_post_meta', array( self::class, 'meta' ), 10, 4 );
	}

	public static function meta( $meta_id, $object_id, $key, $value ) {
		if ( '_codeslot_error' === $key ) {
			self::log( 'snippet_error', $object_id );
		}
	}

	public static function log( $event, $id = 0 ) {
		$events   = get_option( 'codeslot_logs', array() );
		$events   = is_array( $events ) ? $events : array();
		$events[] = array(
			'time'       => gmdate( 'c' ),
			'event'      => sanitize_key( $event ),
			'snippet_id' => absint( $id ),
			'user_id'    => get_current_user_id(),
		);
		update_option( 'codeslot_logs', array_slice( $events, -100 ), false );
	}

	public static function info( $details = false ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$theme = wp_get_theme();
		$lines = array( '### Begin CodeSlot System Info ###', '', '-- Site (identifying URLs and filesystem paths redacted)', 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' ), 'HTTPS: ' . ( is_ssl() ? 'Yes' : 'No' ), '', '-- WordPress', 'Version: ' . get_bloginfo( 'version' ), 'Language: ' . get_locale(), 'User language: ' . get_user_locale(), 'Permalinks: ' . get_option( 'permalink_structure' ), 'Theme: ' . $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ), 'Front page mode: ' . get_option( 'show_on_front' ), 'WP_DEBUG: ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' ), 'Memory limit: ' . ( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Default' ), 'Revisions: ' . ( defined( 'WP_POST_REVISIONS' ) ? wp_json_encode( WP_POST_REVISIONS ) : 'Default' ), 'Post statuses: ' . implode( ', ', get_post_stati() ), '', '-- CodeSlot', 'Version: ' . CODESLOT_VERSION, 'Safe Mode: ' . ( Permissions::safe() ? 'Enabled' : 'Disabled' ), 'Constant Safe Mode: ' . ( defined( 'CODESLOT_SAFE_MODE' ) && CODESLOT_SAFE_MODE ? 'Enabled' : 'Disabled' ), '', '-- Server', 'PHP: ' . PHP_VERSION, 'SAPI: ' . PHP_SAPI, 'Database: ' . $wpdb->db_version(), 'Server: ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unavailable' ) );
		foreach ( array( 'memory_limit', 'upload_max_filesize', 'post_max_size', 'max_execution_time', 'max_input_vars', 'display_errors' ) as $key ) {
			$lines[] = $key . ': ' . ini_get( $key );
		}
		$lines[] = '';
		$lines[] = '-- Extensions';
		foreach ( array( 'curl', 'mysqli', 'mbstring', 'soap', 'session' ) as $extension ) {
			$lines[] = $extension . ': ' . ( extension_loaded( $extension ) ? 'Installed' : 'Not installed' );
		}
		$active  = array_merge( get_option( 'active_plugins', array() ), array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		$updates = get_site_transient( 'update_plugins' );
		foreach ( array(
			'Active'   => true,
			'Inactive' => false,
		) as $label => $enabled ) {
			$lines[] = '\n-- ' . $label . ' plugins';
			foreach ( get_plugins() as $file => $plugin ) {
				if ( in_array( $file, $active, true ) !== $enabled ) {
					continue;
				}
				$lines[] = $plugin['Name'] . ': ' . $plugin['Version'] . ( isset( $updates->response[ $file ] ) ? ' (update available: ' . $updates->response[ $file ]->new_version . ')' : '' );
			}
		}
		$lines[] = '\n-- Must-use plugins';
		foreach ( get_mu_plugins() as $plugin ) {
			$lines[] = $plugin['Name'] . ': ' . $plugin['Version'];
		}
		$lines[] = 'fsockopen: ' . ( function_exists( 'fsockopen' ) ? 'Available' : 'Unavailable' );
		$lines[] = 'Session: ' . ( function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === session_status() ? 'Active' : 'Not active' );
		$lines[] = 'Database table prefix length: ' . strlen( $wpdb->prefix );
		if ( $details ) {
			$uploads = wp_upload_dir( null, false );
			$lines[] = '\n-- Identifying site details (review before sharing)';
			foreach ( array(
				'Site URL'               => site_url(),
				'Home URL'               => home_url(),
				'ABSPATH'                => ABSPATH,
				'WP_CONTENT_DIR'         => ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : 'Unavailable' ),
				'WP_CONTENT_URL'         => ( defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL : 'Unavailable' ),
				'Uploads path'           => $uploads['path'],
				'Uploads URL'            => $uploads['url'],
				'Uploads base directory' => $uploads['basedir'],
				'Uploads base URL'       => $uploads['baseurl'],
			) as $label => $value ) {
				$lines[] = $label . ': ' . $value;
			}
		}
		$lines[] = '\n### End CodeSlot System Info ###';
		return str_replace( '\n', "\n", implode( "\n", $lines ) );
	}

	public static function page() {
		$details = isset( $_GET['system_details'] ) && '1' === $_GET['system_details']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Private read-only diagnostics.
		echo '<form method="get" class="codeslot-panel codeslot-system-toolbar"><input type="hidden" name="page" value="codeslot-tools"><label><input type="checkbox" name="system_details" value="1" ' . checked( $details, true, false ) . '> Include identifying site URLs and filesystem paths (review before sharing)</label><button class="button">Refresh report</button></form>';
		echo '<section class="codeslot-panel"><h2>System Info</h2><p>Diagnose compatibility using local server, WordPress and plugin versions. URLs and paths are hidden unless explicitly included above. Credentials and snippet code are always excluded. Review identifying details and plugin names before sharing. No information is sent automatically.</p><textarea id="codeslot-system-info" rows="18" class="large-text code" readonly>' . esc_textarea( self::info( $details ) ) . '</textarea><button type="button" class="button codeslot-copy-info">Copy system info</button> <button type="button" class="button codeslot-download" data-target="codeslot-system-info" data-filename="codeslot-system-info.txt">Download system info</button></section>';
		echo '<section class="codeslot-panel"><h2>Logs</h2><p>Last 100 CodeSlot management/error events on this site. Entries contain event names, time, user and snippet IDs, never code, tracking IDs or credentials. These are not hosting/PHP server logs. Error details remain with the affected snippet.</p><textarea id="codeslot-log-output" rows="10" class="large-text code" readonly>' . esc_textarea( wp_json_encode( get_option( 'codeslot_logs', array() ), JSON_PRETTY_PRINT ) ) . '</textarea>';
		echo '<p><button type="button" class="button codeslot-download" data-target="codeslot-log-output" data-filename="codeslot-logs.json">Download logs</button></p>';
		self::form( 'clear_logs' );
		echo '<button class="button codeslot-clear-logs" type="submit">Clear logs permanently</button></form></section>';
	}

	public static function form( $task ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="codeslot_extra"><input type="hidden" name="task" value="' . esc_attr( $task ) . '">';
		wp_nonce_field( 'codeslot_extra' );
	}
}
