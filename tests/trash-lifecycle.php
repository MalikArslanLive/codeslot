<?php
/** Run only on an isolated WordPress site with an initially empty CodeSlot Trash. */
wp_set_current_user( 1 );
$codeslot_trash_ids = array();
$codeslot_trash_assertions = 0;
$codeslot_trash_check = static function ( $condition, $message ) use ( &$codeslot_trash_assertions ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$codeslot_trash_assertions;
	echo 'PASS: ' . $message . PHP_EOL;
};
if ( get_posts( array( 'post_type' => 'codeslot_snippet', 'post_status' => 'trash', 'posts_per_page' => 1, 'fields' => 'ids' ) ) ) {
	throw new RuntimeException( 'Use a disposable site with empty CodeSlot Trash; no existing trashed data will be deleted.' );
}
try {
	for ( $codeslot_trash_index = 0; $codeslot_trash_index < 3; ++$codeslot_trash_index ) {
		$codeslot_trash_id = CodeSlot\Repository::save( array( 'name' => 'Trash lifecycle fixture ' . $codeslot_trash_index, 'type' => 'javascript', 'location' => 'footer', 'code' => 'window.CodeSlotTrashFixture = 1;', 'active' => true ) );
		if ( is_wp_error( $codeslot_trash_id ) ) { throw new RuntimeException( $codeslot_trash_id->get_error_message() ); }
		$codeslot_trash_ids[] = $codeslot_trash_id;
	}
	$codeslot_trash_check( is_wp_error( CodeSlot\Repository::lifecycle( $codeslot_trash_ids[2], 'delete' ) ) && get_post( $codeslot_trash_ids[2] ), 'permanent deletion cannot target an ordinary active snippet' );
	foreach ( array_slice( $codeslot_trash_ids, 0, 2 ) as $codeslot_trash_id ) {
		$codeslot_trash_result = CodeSlot\Repository::lifecycle( $codeslot_trash_id, 'trash' );
		$codeslot_trash_check( ! is_wp_error( $codeslot_trash_result ) && 'trash' === get_post_status( $codeslot_trash_id ), 'WordPress stores the snippet in Trash' );
		$codeslot_trash_check( 'window.CodeSlotTrashFixture = 1;' === get_post( $codeslot_trash_id )->post_content, 'Trash preserves exact source' );
	}
	$codeslot_trash_config = get_post_meta( $codeslot_trash_ids[1], '_codeslot_config', true );
	$codeslot_trash_config['locked'] = true;
	update_post_meta( $codeslot_trash_ids[1], '_codeslot_config', $codeslot_trash_config );
	$codeslot_trash_check( is_wp_error( CodeSlot\Repository::empty_trash() ) && get_post( $codeslot_trash_ids[0] ) && get_post( $codeslot_trash_ids[1] ), 'Empty Trash preflights locked entries before deleting anything' );
	$codeslot_trash_result = CodeSlot\Repository::lifecycle( $codeslot_trash_ids[1], 'restore' );
	$codeslot_trash_check( ! is_wp_error( $codeslot_trash_result ) && 'draft' === get_post_status( $codeslot_trash_ids[1] ), 'locked trashed snippet can be restored inactive' );
	$codeslot_trash_s = CodeSlot\Repository::get( $codeslot_trash_ids[1] );
	$codeslot_trash_s['locked'] = false;
	CodeSlot\Repository::save( $codeslot_trash_s, $codeslot_trash_ids[1], true );
	CodeSlot\Repository::lifecycle( $codeslot_trash_ids[1], 'trash' );
	$codeslot_trash_result = CodeSlot\Repository::empty_trash();
	$codeslot_trash_check( 2 === $codeslot_trash_result && ! get_post( $codeslot_trash_ids[0] ) && ! get_post( $codeslot_trash_ids[1] ), 'confirmed Empty Trash deletes only trashed snippets' );
	$codeslot_trash_check( 'publish' === get_post_status( $codeslot_trash_ids[2] ), 'Empty Trash preserves active snippets' );
	echo 'Success: ' . $codeslot_trash_assertions . ' Trash lifecycle assertions passed.' . PHP_EOL;
} finally {
	foreach ( $codeslot_trash_ids as $codeslot_trash_id ) { wp_delete_post( $codeslot_trash_id, true ); }
}
