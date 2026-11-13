<?php
/** Explicit opt-in data removal. @package CodeSlot */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; }

function codeslot_uninstall_site() {
	$settings = get_option( 'codeslot_settings', array() );
	if ( empty( $settings['delete_data'] ) ) {
		return; }
	global $wpdb;
	do {
		$ids = get_posts(
			array(
				'post_type'      => 'codeslot_snippet',
				'post_status'    => array_values( get_post_stati() ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true ); }
	} while ( count( $ids ) === 100 );
	delete_post_meta_by_key( '_codeslot_slots' );
	delete_metadata( 'term', 0, '_codeslot_slots', '', true );
	foreach ( array( 'codeslot_category', 'codeslot_tag' ) as $taxonomy ) {
		register_taxonomy( $taxonomy, 'codeslot_snippet', array( 'public' => false ) );
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $taxonomy ); }
		}
	}
	foreach ( array( 'codeslot_global', 'codeslot_settings', 'codeslot_files', 'codeslot_pixels', 'codeslot_logs' ) as $option ) {
		delete_option( $option ); }
	$options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time removal of plugin-owned observation options.
		$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'codeslot_body_' ) . '%', $wpdb->esc_like( 'codeslot_job_' ) . '%' )
	);
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
