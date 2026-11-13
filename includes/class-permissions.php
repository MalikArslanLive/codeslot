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
			wp_die( esc_html__( 'You cannot manage executable code.', 'codeslot' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}

	public static function safe() {
		$settings = get_option( 'codeslot_settings', array() );
		return ( defined( 'CODESLOT_SAFE_MODE' ) && CODESLOT_SAFE_MODE ) || ! empty( $settings['safe_mode'] );
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
