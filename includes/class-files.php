<?php
/** Explicit virtual public files; never overwrites physical files. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Files {
	const NAMES = array( 'ads.txt', 'app-ads.txt', 'service-worker.js', 'robots.txt' );

	public static function boot() {
		add_action( 'init', array( self::class, 'routes' ) );
		add_filter(
			'query_vars',
			static function ( $vars ) {
				$vars[] = 'codeslot_asset';
				return $vars;
			}
		);
		add_action( 'parse_request', array( self::class, 'serve' ), 0 );
		add_filter( 'robots_txt', array( self::class, 'robots' ), 20, 2 );
		add_action( 'wp_footer', array( self::class, 'register_worker' ) );
	}

	public static function routes() {
		add_rewrite_rule( '^(ads\.txt|app-ads\.txt|service-worker\.js)$', 'index.php?codeslot_asset=$matches[1]', 'top' );
	}

	public static function physical( $name ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		return file_exists( ABSPATH . $name ) || file_exists( get_home_path() . $name );
	}

	public static function robots( $output, $is_public ) {
		$files = get_option( 'codeslot_files', array() );
		if ( ! Permissions::safe() && ! self::physical( 'robots.txt' ) && ! empty( $files['robots.txt']['enabled'] ) ) {
			// Preserve WordPress's public/private robots directive; custom rules are appended.
			return $output . "\n# CodeSlot custom directives\n" . $files['robots.txt']['code'] . "\n";
		}
		return $output;
	}

	public static function serve( $wp ) {
		$name = isset( $wp->query_vars['codeslot_asset'] ) ? $wp->query_vars['codeslot_asset'] : '';
		if ( ! in_array( $name, self::NAMES, true ) || 'robots.txt' === $name ) {
			return;
		}
		// Reject direct query-string access: only the configured public file URL is served.
		$expected = wp_parse_url( home_url( '/' . $name ), PHP_URL_PATH );
		$actual   = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$files    = get_option( 'codeslot_files', array() );
		if ( $expected !== $actual || Permissions::safe() || self::physical( $name ) || empty( $files[ $name ]['enabled'] ) ) {
			status_header( 404 );
			exit;
		}
		status_header( 200 );
		header( 'Content-Type: ' . ( 'service-worker.js' === $name ? 'application/javascript' : 'text/plain' ) . '; charset=UTF-8' );
		header( 'X-Content-Type-Options: nosniff' );
		nocache_headers();
		echo $files[ $name ]['code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional privileged public text/JavaScript with fixed MIME type.
		exit;
	}

	public static function register_worker() {
		$files = get_option( 'codeslot_files', array() );
		if ( Permissions::safe() || empty( $files['service-worker.js']['enabled'] ) || empty( $files['register_worker'] ) || self::physical( 'service-worker.js' ) ) {
			return;
		}
		$url = wp_json_encode( home_url( '/service-worker.js' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		echo '<script>if(window.isSecureContext&&"serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register(' . $url . ').catch(function(){console.warn("CodeSlot: service worker registration failed.");});});}</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL safely JSON-encoded for script context.
	}

	public static function page() {
		$files = get_option( 'codeslot_files', array() );
		echo '<section class="codeslot-panel"><h2>Virtual File Editor</h2><p>Publish ads.txt, app-ads.txt, robots.txt directives and service-worker.js without changing server files. Existing physical files take precedence and are never overwritten. Pretty permalinks and server routing to WordPress are required. Purge your cache after changes.</p><p>Service workers are privileged same-origin JavaScript and can affect requests/caches after this plugin is disabled. Registration is off by default; disable/unregister existing workers using browser tools when recovering.</p>';
		Support::form( 'files' );
		foreach ( self::NAMES as $name ) {
			$row = isset( $files[ $name ] ) ? $files[ $name ] : array();
			echo '<fieldset class="codeslot-slot"><legend>' . esc_html( $name ) . '</legend><p><a href="' . esc_url( home_url( '/' . $name ) ) . '" target="_blank" rel="noopener noreferrer">View public URL</a></p>';
			if ( self::physical( $name ) ) {
				echo '<p class="notice notice-warning">Physical file detected: CodeSlot will not replace or serve this file. Edit it through your hosting tools.</p>';
			}
			echo '<label><input type="checkbox" name="files[' . esc_attr( $name ) . '][enabled]" value="1" ' . checked( ! empty( $row['enabled'] ), true, false ) . '> Enable virtual output</label><label class="screen-reader-text" for="codeslot-file-' . esc_attr( $name ) . '">' . esc_html( $name ) . ' content</label><textarea id="codeslot-file-' . esc_attr( $name ) . '" class="large-text code codeslot-code" data-codeslot-mode="' . esc_attr( 'service-worker.js' === $name ? 'text/javascript' : 'text/plain' ) . '" name="files[' . esc_attr( $name ) . '][code]" rows="8">' . esc_textarea( isset( $row['code'] ) ? $row['code'] : '' ) . '</textarea>';
			if ( 'robots.txt' === $name ) {
				echo '<p>Custom directives are appended to WordPress robots.txt; search visibility defaults remain. robots.txt does not protect private information.</p>';
			}
			echo '</fieldset>';
		}
		echo '<p><label><input type="checkbox" name="register_worker" value="1" ' . checked( ! empty( $files['register_worker'] ), true, false ) . '> Automatically register my enabled service worker (HTTPS required)</label></p>';
		submit_button( 'Save virtual files' );
		echo '</form></section>';
	}

	public static function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return new \WP_Error( 'files', 'Invalid file data.' );
		}
		$out = array();
		foreach ( self::NAMES as $name ) {
			$row = isset( $input[ $name ] ) ? $input[ $name ] : array();
			if ( ! is_array( $row ) || ( isset( $row['code'] ) && ( ! is_string( $row['code'] ) || strlen( $row['code'] ) > 204800 ) ) ) {
				return new \WP_Error( 'files', 'Each virtual file must be text no larger than 200 KiB.' );
			}
			$out[ $name ] = array(
				'code'    => isset( $row['code'] ) ? $row['code'] : '',
				'enabled' => ! empty( $row['enabled'] ),
			);
		}
		$out['register_worker'] = ! empty( $input['register_worker'] );
		return $out;
	}
}
