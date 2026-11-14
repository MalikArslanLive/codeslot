<?php
/** Explicit, atomically claimed one-time privileged tasks. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Jobs {
	private static $running = 0;

	public static function remove( $id, $post ) {
		if ( 'codeslot_snippet' === $post->post_type ) {
			delete_option( 'codeslot_job_' . absint( $id ) );
		}
	}

	public static function record( $id ) {
		return get_option( 'codeslot_job_' . absint( $id ), false );
	}

	public static function run( $id ) {
		if ( ! Permissions::allowed() || Permissions::safe() ) {
			return new \WP_Error( 'forbidden', 'Code management access is required and Safe Mode must be off.' );
		}
		$snippet = Repository::get( $id );
		if ( $snippet && ! Delivery::eligible( $snippet ) ) {
			return new \WP_Error( 'schedule', 'This task is outside its scheduled time or device scope.' );
		}
		if ( ! $snippet || $snippet['locked'] || 'php' !== $snippet['type'] || 'once' !== $snippet['location'] || self::$running ) {
			return new \WP_Error( 'job', 'Choose an unlocked PHP snippet with the once location.' );
		}
		$valid = Repository::validate( $snippet );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$option = 'codeslot_job_' . absint( $id );
		$record = array(
			'status' => 'running',
			'user'   => get_current_user_id(),
			'date'   => gmdate( 'c' ),
		);
		// option_name is unique: only one concurrent request can claim this task.
		if ( ! add_option( $option, $record, '', false ) ) {
			return new \WP_Error( 'claimed', 'This task was already claimed. It will not be retried.' );
		}
		self::$running = absint( $id );
		register_shutdown_function( array( self::class, 'interrupted' ) );
		$started = microtime( true );
		$output  = '';
		$level   = ob_get_level();
		ob_start(
			static function ( $chunk ) use ( &$output ) {
				$output .= substr( $chunk, 0, max( 0, 2000 - strlen( $output ) ) );
				return '';
			},
			1024
		);
		try {
			eval( $valid['code'] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged,Generic.PHP.ForbiddenFunctions.Found -- Explicit nonce-confirmed privileged task; not a sandbox.
			$record['status'] = 'completed';
		} catch ( \Throwable $error ) {
			$record['status'] = 'failed';
			$record['error']  = substr( get_class( $error ) . ': ' . $error->getMessage(), 0, 2000 );
		} finally {
			while ( ob_get_level() > $level ) {
				if ( ! ob_end_flush() ) {
					break;
				}
			}
			$record['output']      = wp_check_invalid_utf8( $output, true );
			$record['duration_ms'] = round( ( microtime( true ) - $started ) * 1000, 3 );
			update_option( $option, $record, false );
			self::$running = 0;
		}
		return $record;
	}

	public static function interrupted() {
		if ( ! self::$running ) {
			return;
		}
		$option           = 'codeslot_job_' . self::$running;
		$record           = get_option( $option, array() );
		$error            = error_get_last();
		$record['status'] = 'interrupted';
		$record['error']  = 'Execution ended before completion; side effects may have occurred. This task will not be retried.';
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			$record['status'] = 'failed';
			$record['error']  = substr( $error['message'], 0, 2000 );
		}
		update_option( $option, $record, false );
	}
}
