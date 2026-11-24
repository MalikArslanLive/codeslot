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
		if ( ! Permissions::allowed() ) {
			wp_die( esc_html__( 'You cannot manage executable code.', 'codeslot-header-body-footer-code-snippet-manager' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'codeslot_extra' );
		$task   = isset( $_POST['task'] ) ? sanitize_key( $_POST['task'] ) : '';
		$page   = 'codeslot-tools';
		$result = true;
		$input  = array();
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
					$input['require_consent'] = ! empty( $_POST['require_consent'] );
					$input['click_selector']  = isset( $_POST['click_selector'] ) ? sanitize_text_field( wp_unslash( $_POST['click_selector'] ) ) : '';
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
				$page = 'codeslot-transfer';
				if ( empty( $_POST['scopes'] ) ) {
					$result = new \WP_Error( 'restore_sections', 'Select at least one section to restore, then click Restore selected sections. Nothing was restored.' );
					break;
				}
				if ( empty( $_POST['confirm'] ) ) {
					$result = new \WP_Error( 'confirm', 'Review and confirm the selected restore sections and choices. Nothing was restored.' );
					break;
				}
				$json    = Support::uploaded_json();
				$scopes  = is_array( $_POST['scopes'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['scopes'] ) ) : array();
				$policy  = isset( $_POST['snippet_conflicts'] ) && is_string( $_POST['snippet_conflicts'] ) ? sanitize_key( $_POST['snippet_conflicts'] ) : 'skip';
				$choices = isset( $_POST['restore_existing'] ) && is_array( $_POST['restore_existing'] ) ? wp_unslash( $_POST['restore_existing'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Backup::restore validates section names and exact skip/replace choices before writes.
				$state   = isset( $_POST['restore_state'] ) && is_string( $_POST['restore_state'] ) ? sanitize_key( $_POST['restore_state'] ) : 'disabled';
				$result  = is_wp_error( $json ) ? $json : Backup::restore( $json, $scopes, $policy, $choices, $state );
				if ( ! is_wp_error( $result ) ) {
					set_transient( 'codeslot_restore_' . get_current_user_id(), $result, 300 );
				}
				$page = 'codeslot-transfer';
				break;
			default:
				$result = new \WP_Error( 'task', 'Unknown operation.' );
		}
		if ( is_wp_error( $result ) ) {
			Admin::feedback( $result, $page, 0, 'pixels' === $task && is_array( $input ) ? Pixels::draft( $input ) : array() );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . $page . '&codeslot_saved=1' ) );
		exit;
	}
}
