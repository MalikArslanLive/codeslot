<?php
/** Privileged code access. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Permissions {
	public static function allowed() {
		return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' ) && current_user_can( 'edit_plugins' ) && ( ! is_multisite() || is_super_admin() );
	}

	public static function verify( $action = 'codeslot_save' ) {
		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'You cannot manage executable code.', 'codeslot-header-body-footer-code-snippet-manager' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}

	public static function safe() {
		$settings = get_option( 'codeslot_settings', array() );
		return ( defined( 'CODESLOT_SAFE_MODE' ) && CODESLOT_SAFE_MODE ) || ! empty( $settings['safe_mode'] );
	}

	/** Reject invalid writes rather than silently clearing existing code. */
	public static function validate_slots( $input ) {
		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array( 'header', 'body', 'footer' ) ) ) {
			return new \WP_Error( 'slots', 'Invalid Header, Body or Footer fields.' );
		}
		foreach ( $input as $row ) {
			if ( ! is_array( $row ) || ( array_key_exists( 'code', $row ) && ( ! is_string( $row['code'] ) || strlen( $row['code'] ) > 1048576 ) ) || ( isset( $row['enabled'] ) && ! is_scalar( $row['enabled'] ) ) ) {
				return new \WP_Error( 'slots', 'Each Header, Body or Footer code field must be text no larger than 1 MiB. Existing code was retained.' );
			}
		}
		return self::slots( $input );
	}

	public static function slots( $input ) {
		$out = array();
		foreach ( array( 'header', 'body', 'footer' ) as $slot ) {
			$row          = isset( $input[ $slot ] ) && is_array( $input[ $slot ] ) ? $input[ $slot ] : array();
			$out[ $slot ] = array(
				'code'    => isset( $row['code'] ) && is_string( $row['code'] ) ? $row['code'] : '',
				'enabled' => ! empty( $row['enabled'] ),
			);
		}
		return $out;
	}
}
