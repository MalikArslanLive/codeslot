<?php
/** Run with WP-CLI on a disposable site after integration.php. */
use CodeSlot\Backup;
use CodeSlot\Files;
use CodeSlot\Library;
use CodeSlot\Permissions;
use CodeSlot\Pixels;
use CodeSlot\Repository;
use CodeSlot\Support;

$admin = get_user_by( 'login', 'codeslot-admin' );
wp_set_current_user( $admin->ID );
$option_names = array( 'codeslot_settings', 'codeslot_global', 'codeslot_files', 'codeslot_pixels', 'codeslot_logs' );
$original = array();
foreach ( $option_names as $name ) { $original[ $name ] = get_option( $name, null ); }
$ids = array();
$term_id = 0;
$GLOBALS['codeslot_extended_count'] = 0;
function codeslot_extended_assert( $value, $label ) {
	
	if ( ! $value ) { throw new RuntimeException( 'FAIL: ' . $label ); }
	++$GLOBALS['codeslot_extended_count'];
	WP_CLI::log( 'PASS: ' . $label );
}
try {
	$recipes = Library::all();
	codeslot_extended_assert( count( $recipes ) === 142, '142 bundled original recipes' );
	foreach ( $recipes as $key => $recipe ) {
		$valid = Repository::validate( $recipe );
		codeslot_extended_assert( ! is_wp_error( $valid ) && ! $valid['active'], 'valid inactive recipe: ' . $key );
	}
	codeslot_extended_assert( is_wp_error( Repository::validate( array( 'type' => 'php', 'code' => 'return;', 'location' => 'bootstrap', 'conditions' => array( 'include' => array( array( array( 'key' => 'front_page' ) ) ) ) ) ) ), 'early hooks reject query conditions' );
	codeslot_extended_assert( is_wp_error( Files::validate( array( 'ads.txt' => array( 'code' => array() ) ) ) ), 'malformed virtual file rejected' );
	codeslot_extended_assert( is_wp_error( Files::validate( array( 'ads.txt' => array( 'code' => str_repeat( 'x', 204801 ) ) ) ) ), 'oversized virtual file rejected' );
	$files = Files::validate( array( 'ads.txt' => array( 'code' => "example.com, publisher, DIRECT\n", 'enabled' => true ), 'service-worker.js' => array( 'code' => '/* test */', 'enabled' => true ), 'register_worker' => true ) );
	codeslot_extended_assert( ! is_wp_error( $files ) && $files['ads.txt']['enabled'] && ! $files['robots.txt']['enabled'], 'explicit file allowlist and disabled defaults' );
	codeslot_extended_assert( is_wp_error( Pixels::validate( array( 'meta' => array( 'id' => '"></script><script>alert(1)</script>' ) ) ) ), 'script injection cannot become a tracking ID' );
	$pixels = Pixels::validate( array( 'meta' => array( 'id' => '123456789012345', 'enabled' => true, 'events' => array( 'page_view', 'click', 'invalid' ) ) ) );
	codeslot_extended_assert( ! is_wp_error( $pixels ) && array( 'page_view', 'click' ) === $pixels['meta']['events'] && ! $pixels['google']['enabled'], 'pixel events allowlisted; other providers off' );
	$early_id = Repository::save( array( 'name' => 'Early fixture', 'type' => 'php', 'location' => 'bootstrap', 'code' => '$GLOBALS["codeslot_early_fixture"] = true;', 'active' => true ) );
	$ids[] = $early_id;
	CodeSlot\Runtime::php( 'bootstrap' );
	codeslot_extended_assert( ! empty( $GLOBALS['codeslot_early_fixture'] ), 'explicit bootstrap executes early-hook PHP' );
	wp_delete_post( $early_id, true );
	$gtm = Pixels::validate( array( 'gtm' => array( 'id' => 'GTM-TEST123', 'enabled' => true, 'events' => array( 'page_view', 'purchase' ) ) ) );
	codeslot_extended_assert( ! is_wp_error( $gtm ) && $gtm['gtm']['enabled'], 'GTM container ID and selected events validate' );
	codeslot_extended_assert( is_wp_error( Pixels::validate( array( 'gtm' => array( 'id' => 'G-TEST123', 'enabled' => true ) ) ) ), 'GA4 ID rejected in GTM container field' );
	codeslot_extended_assert( is_wp_error( Pixels::validate( array( 'meta' => array( 'enabled' => true, 'id' => '' ) ) ) ), 'enabled provider requires a public tracking ID' );
	$post_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Extended backup fixture', 'post_name' => 'codeslot-extended-backup' ) );
	$ids[] = $post_id;
	$term = wp_insert_term( 'CodeSlot extended backup', 'category', array( 'slug' => 'codeslot-extended-backup' ) );
	$term_id = $term['term_id'];
	$slots = array( 'header' => array( 'code' => '<!-- full-backup-header -->', 'enabled' => true ) );
	update_option( 'codeslot_global', $slots, false );
	update_option( 'codeslot_files', $files, false );
	update_option( 'codeslot_pixels', $pixels, false );
	update_post_meta( $post_id, '_codeslot_slots', $slots );
	update_term_meta( $term_id, '_codeslot_slots', $slots );
	$snippet_id = Repository::save( array( 'name' => 'Extended backup snippet', 'type' => 'html', 'location' => 'header', 'code' => '<!-- backup snippet -->', 'active' => true ) );
	$ids[] = $snippet_id;
	$snippet_export = Repository::export();
	codeslot_extended_assert( ! isset( $snippet_export['global'] ), 'legacy snippets-only export remains snippets-only' );
	$backup = Backup::export();
	codeslot_extended_assert( 'codeslot/backup-v1' === $backup['schema'] && count( $backup['individual'] ) >= 2 && $backup['global'] === $slots, 'full export includes global plus post/term slots' );
	$bad = $backup;
	$bad['pixels']['meta']['id'] = 'invalid';
	$before = get_option( 'codeslot_global' );
	codeslot_extended_assert( is_wp_error( Backup::restore( wp_json_encode( $bad ), array( 'global', 'pixels' ) ) ) && $before === get_option( 'codeslot_global' ), 'malformed section rejected before replacing valid sections' );
	$backup['individual'] = array_values( array_filter( $backup['individual'], static function ( $row ) { return 'codeslot-extended-backup' === $row['path']; } ) );
	$backup['individual'][] = array( 'kind' => 'post', 'type' => 'page', 'path' => 'missing-codeslot-extended-fixture', 'slots' => $slots );
	$result = Backup::restore( wp_json_encode( $backup ), array( 'global', 'individual', 'settings', 'files', 'pixels' ) );
	codeslot_extended_assert( ! is_wp_error( $result ) && $result['mapped'] >= 2 && $result['skipped'] >= 1, 'restore maps identities and reports unmatched objects' );
	codeslot_extended_assert( ! get_option( 'codeslot_global' )['header']['enabled'] && ! get_post_meta( $post_id, '_codeslot_slots', true )['header']['enabled'] && ! get_term_meta( $term_id, '_codeslot_slots', true )['header']['enabled'], 'restored global/post/term code retained but disabled' );
	codeslot_extended_assert( Permissions::safe() && ! get_option( 'codeslot_settings' )['delete_data'], 'restore enables Safe Mode and retains data on uninstall' );
	codeslot_extended_assert( ! get_option( 'codeslot_files' )['ads.txt']['enabled'] && ! get_option( 'codeslot_files' )['register_worker'] && ! get_option( 'codeslot_pixels' )['meta']['enabled'], 'restored files/pixels/service worker registration off' );
	codeslot_extended_assert( is_wp_error( Backup::restore( wp_json_encode( $backup ), array( 'unknown' ) ) ), 'unknown restore scopes rejected' );
	wp_set_current_user( get_user_by( 'login', 'codeslot-editor' )->ID );
	codeslot_extended_assert( is_wp_error( Backup::restore( wp_json_encode( $backup ), array( 'global' ) ) ), 'editor cannot restore executable code' );
	wp_set_current_user( $admin->ID );
	delete_option( 'codeslot_logs' );
	for ( $i = 0; $i < 105; ++$i ) { Support::log( 'bounded_fixture', $snippet_id ); }
	$logs = get_option( 'codeslot_logs' );
	codeslot_extended_assert( 100 === count( $logs ) && ! isset( $logs[0]['code'] ), 'logs bounded to 100 entries without source code' );
	update_post_meta( $snippet_id, '_codeslot_error', array( 'message' => 'Secret source must not enter logs', 'date' => gmdate( 'c' ) ) );
	$logs = get_option( 'codeslot_logs' );
	codeslot_extended_assert( 'snippet_error' === end( $logs )['event'] && false === strpos( wp_json_encode( $logs ), 'Secret source' ), 'runtime error event recorded without error/source contents' );
	$info = Support::info();
	codeslot_extended_assert( false !== strpos( $info, 'SAPI:' ) && false !== strpos( $info, PHP_VERSION ) && false === strpos( $info, ABSPATH ) && false === strpos( $info, '<!-- full-backup-header -->' ), 'system info includes actual runtime and excludes paths/source' );
	WP_CLI::success( $GLOBALS['codeslot_extended_count'] . ' extended feature assertions passed.' );
} finally {
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
	if ( $term_id ) { wp_delete_term( $term_id, 'category' ); }
	foreach ( $original as $name => $value ) { if ( null === $value ) { delete_option( $name ); } else { update_option( $name, $value, false ); } }
	wp_set_current_user( $admin->ID );
}
