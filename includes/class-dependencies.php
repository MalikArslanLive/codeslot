<?php
/** Availability checks preserve saved intent while preventing unintended execution. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Dependencies {
	const GUIDE = 'https://github.com/MalikArslanLive/codeslot/blob/main/docs/DEPENDENCIES.md';
	public static function validate( $input ) {
		if ( ! is_array( $input ) || count( $input ) > 50 ) {
			return new \WP_Error( 'dependencies', 'Choose at most 50 required plugins.' );
		}
		foreach ( $input as $file ) {
			if ( ! is_string( $file ) || strlen( $file ) > 200 || ! preg_match( '~^[a-zA-Z0-9_-][a-zA-Z0-9._/-]*\.php$~D', $file ) || in_array( '..', explode( '/', $file ), true ) || false !== strpos( $file, '//' ) ) {
				return new \WP_Error( 'dependencies', 'Invalid required plugin identifier.' );
			}
		}
		$input = array_values( array_unique( $input ) );
		sort( $input );
		return $input;
	}

	public static function plugin_available( $file ) {
		if ( is_wp_error( self::validate( array( $file ) ) ) || ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}
		$active = wp_get_active_and_valid_plugins();
		if ( is_multisite() ) {
			$active = array_merge( $active, wp_get_active_network_plugins() );
		}
		return in_array( WP_PLUGIN_DIR . '/' . $file, $active, true );
	}

	public static function location_reason( $location ) {
		if ( ! is_string( $location ) ) {
			return 'Invalid saved insertion location.';
		}
		return 0 === strpos( $location, 'woocommerce_' ) && ! Pixels::commerce_available() ? 'Requires active WooCommerce.' : '';
	}

	public static function issues( $snippet ) {
		$issues = Conditions::missing( isset( $snippet['conditions'] ) ? $snippet['conditions'] : array() );
		$reason = self::location_reason( isset( $snippet['location'] ) ? $snippet['location'] : '' );
		if ( $reason ) {
			$issues[] = $reason;
		}
		$requires = self::validate( isset( $snippet['requires'] ) ? $snippet['requires'] : array() );
		if ( is_wp_error( $requires ) ) {
			$issues[] = $requires->get_error_message();
		} else {
			foreach ( $requires as $file ) {
				if ( ! self::plugin_available( $file ) ) {
					$issues[] = 'Required plugin unavailable: ' . $file . '.';
				}
			}
		}
		return array_values( array_unique( $issues ) );
	}

	public static function notice( $snippet ) {
		$issues = self::issues( $snippet );
		if ( ! $issues ) {
			return;
		}
		echo '<div class="codeslot-dependency-notice" role="status"><strong>Execution paused — dependency unavailable</strong><ul>';
		foreach ( $issues as $issue ) {
			echo '<li>' . esc_html( $issue ) . '</li>';
		}
		echo '</ul><p>Code, location, conditions and the saved Active switch are retained. No fallback location is used. Restore the dependency or explicitly edit/remove its requirement. Execution can resume when all requirements are available and the snippet is still Active; Safe Mode, schedules and other restrictions still apply.</p><p><a href="' . esc_url( self::GUIDE ) . '" target="_blank" rel="noopener noreferrer">Read dependency guide<span class="screen-reader-text"> (opens in a new tab)</span></a></p></div>';
	}

	public static function fields( $snippet ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins = get_plugins();
		$chosen  = isset( $snippet['requires'] ) && is_array( $snippet['requires'] ) ? $snippet['requires'] : array();
		echo '<section class="codeslot-config-section"><h3>Required plugins (optional)</h3><p>Declare plugins your custom code needs. If any requirement becomes inactive, deleted or paused by WordPress recovery, this snippet pauses without losing its saved settings. CodeSlot cannot infer every dependency inside arbitrary code. <a href="' . esc_url( self::GUIDE ) . '" target="_blank" rel="noopener noreferrer">Read dependency guide<span class="screen-reader-text"> (opens in a new tab)</span></a></p><details class="codeslot-plugin-requirements"><summary>Choose required plugins</summary><div>';
		foreach ( array_unique( array_merge( array_keys( $plugins ), $chosen ) ) as $file ) {
			if ( plugin_basename( CODESLOT_FILE ) === $file ) {
				continue;
			}
			$available = self::plugin_available( $file );
			$selected  = in_array( $file, $chosen, true );
			$label     = isset( $plugins[ $file ] ) ? $plugins[ $file ]['Name'] : $file;
			echo '<div class="codeslot-plugin-requirement"><label><input type="checkbox" name="requires[]" value="' . esc_attr( $file ) . '" ' . checked( $selected, true, false ) . disabled( ! $available, true, false ) . '> <span>' . esc_html( $label ) . ( $available ? '' : ' — unavailable; install and activate to use' ) . '</span></label>';
			if ( $selected && ! $available ) {
				echo '<input type="hidden" name="requires[]" value="' . esc_attr( $file ) . '"><button type="button" class="button codeslot-remove-requirement">Remove requirement</button>';
			}
			echo '</div>';
		}
		echo '</div></details></section>';
	}
}
