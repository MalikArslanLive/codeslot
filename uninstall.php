<?php
/** Explicit opt-in data removal. @package CodeSlot */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; }

function codeslot_uninstall_site() {
	$settings = get_option( 'codeslot_settings', array() );
	if ( empty( $settings['delete_data'] ) ) {
		return; }
	global $wpdb;
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'codeslot_snippet'" );
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true ); }
	delete_post_meta_by_key( '_codeslot_slots' );
	$wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_codeslot_slots' ) );
	foreach ( array( 'codeslot_global', 'codeslot_settings' ) as $option ) {
		delete_option( $option ); }
	$options = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'codeslot_body_' ) . '%' ) );
	foreach ( $options as $option ) {
		delete_option( $option ); }
}
if ( is_multisite() ) {
	$codeslot_offset = 0;
	do {
		$codeslot_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $codeslot_offset,
			)
		);
		foreach ( $codeslot_sites as $codeslot_site ) {
			switch_to_blog( $codeslot_site );
			codeslot_uninstall_site();
			restore_current_blog(); }
		$codeslot_offset += 100;
	} while ( count( $codeslot_sites ) === 100 );
} else {
	codeslot_uninstall_site(); }
