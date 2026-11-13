<?php
/** Additional privileged administration routes. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Extras {
	public static function boot() {
		Support::boot();
		Files::boot();
		Pixels::boot();
		add_action( 'admin_post_codeslot_extra', array( self::class, 'save' ) );
	}

	public static function save() {
		Permissions::verify( 'codeslot_extra' );
		check_admin_referer( 'codeslot_extra' );
		$task   = isset( $_POST['task'] ) ? sanitize_key( $_POST['task'] ) : '';
		$page   = 'codeslot-tools';
		$result = true;
		switch ( $task ) {
			case 'files':
				$input = isset( $_POST['files'] ) ? wp_unslash( $_POST['files'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by Files::validate; privileged source is intentionally raw.
				if ( is_array( $input ) ) {
					$input['register_worker'] = ! empty( $_POST['register_worker'] );
				}
				$result = Files::validate( $input );
				if ( ! is_wp_error( $result ) ) {
					update_option( 'codeslot_files', $result, false );
					flush_rewrite_rules( false );
					Support::log( 'virtual_files_saved' );
				}
				$page = 'codeslot-files';
				break;
			case 'pixels':
				$input = isset( $_POST['pixels'] ) ? wp_unslash( $_POST['pixels'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by Pixels::validate.
				if ( is_array( $input ) ) {
					$input['click_selector'] = isset( $_POST['click_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['click_selector'] ) ) : '';
				}
				$result = Pixels::validate( $input );
				if ( ! is_wp_error( $result ) ) {
					update_option( 'codeslot_pixels', $result, false );
					Support::log( 'pixels_saved' );
				}
				$page = 'codeslot-pixels';
				break;
			case 'clear_logs':
				delete_option( 'codeslot_logs' );
				break;
			case 'backup_export':
				$result = Backup::export();
				if ( ! is_wp_error( $result ) ) {
					nocache_headers();
					header( 'Content-Type: application/json; charset=UTF-8' );
					header( 'Content-Disposition: attachment; filename="codeslot-full-backup.json"' );
					header( 'X-Content-Type-Options: nosniff' );
					echo wp_json_encode( $result, JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authenticated JSON attachment.
					exit;
				}
				break;
			case 'backup_import':
				if ( empty( $_POST['confirm'] ) ) {
					$result = new \WP_Error( 'confirm', 'Confirm replacement of selected sections.' );
					break;
				}
				$json   = isset( $_POST['json'] ) ? wp_unslash( $_POST['json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Schema and source validation occurs in Backup::restore.
				$scopes = isset( $_POST['scopes'] ) && is_array( $_POST['scopes'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['scopes'] ) ) : array();
				$result = Backup::restore( $json, $scopes );
				if ( ! is_wp_error( $result ) ) {
					set_transient( 'codeslot_restore_' . get_current_user_id(), $result, 300 );
				}
				$page = 'codeslot-transfer';
				break;
			default:
				$result = new \WP_Error( 'task', 'Unknown operation.' );
		}
		if ( is_wp_error( $result ) ) {
			wp_die(
				esc_html( $result->get_error_message() ),
				'',
				array(
					'response'  => 400,
					'back_link' => true,
				)
			);
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&codeslot_saved=1' ) );
		exit;
	}
}
