<?php
/** Explicit full-site CodeSlot backup with inactive restore and identity mapping. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Backup {
	public static function export() {
		$snippets = Repository::export();
		if ( is_wp_error( $snippets ) ) {
			return $snippets;
		}
		$items = array();
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			$posts = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
					'posts_per_page' => -1,
					'meta_key'       => '_codeslot_slots', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Explicit privileged backup; selects only owned slot metadata.
				)
			); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Explicit administrator backup only.
			foreach ( $posts as $post ) {
				$items[] = array(
					'kind'  => 'post',
					'type'  => $type,
					'path'  => get_page_uri( $post ),
					'slots' => get_post_meta( $post->ID, '_codeslot_slots', true ),
				);
			}
		}
		foreach ( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'meta_key'   => '_codeslot_slots', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Explicit privileged backup; selects only owned slot metadata.
				)
			); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Explicit administrator backup only.
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$items[] = array(
					'kind'  => 'term',
					'type'  => $taxonomy,
					'path'  => $term->slug,
					'slots' => get_term_meta( $term->term_id, '_codeslot_slots', true ),
				);
			}
		}
		return array(
			'schema'     => 'codeslot/backup-v1',
			'snippets'   => $snippets['snippets'],
			'global'     => get_option( 'codeslot_global', array() ),
			'individual' => $items,
			'settings'   => get_option( 'codeslot_settings', array() ),
			'files'      => get_option( 'codeslot_files', array() ),
			'pixels'     => get_option( 'codeslot_pixels', array() ),
		);
	}

	public static function restore( $json, $scopes ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Access denied.' );
		}
		if ( ! is_string( $json ) || strlen( $json ) > 5242880 ) {
			return new \WP_Error( 'size', 'Backup import limit is 5 MiB.' );
		}
		$d = json_decode( $json, true, 32 );
		if ( ! is_array( $d ) || ! isset( $d['schema'] ) || 'codeslot/backup-v1' !== $d['schema'] || ! is_array( $scopes ) || ! $scopes ) {
			return new \WP_Error( 'backup', 'Choose sections and supply a codeslot/backup-v1 document.' );
		}
		foreach ( $scopes as $scope ) {
			if ( ! is_string( $scope ) || ! in_array( $scope, array( 'snippets', 'global', 'individual', 'settings', 'files', 'pixels' ), true ) ) {
				return new \WP_Error( 'backup', 'Unknown restore section.' );
			}
		}
		$prepared = array();
		foreach ( array_intersect( array( 'snippets', 'global', 'individual', 'settings', 'files', 'pixels' ), $scopes ) as $scope ) {
			if ( ! isset( $d[ $scope ] ) || ! is_array( $d[ $scope ] ) ) {
				return new \WP_Error( 'backup', 'Missing or invalid section: ' . $scope );
			}
			$prepared[ $scope ] = $d[ $scope ];
		}
		if ( isset( $prepared['snippets'] ) ) {
			if ( count( $prepared['snippets'] ) > 200 ) {
				return new \WP_Error( 'size', 'Maximum 200 snippets per import.' );
			}
			foreach ( $prepared['snippets'] as $snippet ) {
				$valid = Repository::validate( $snippet );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}
			}
		}
		foreach ( array(
			'files'  => Files::class,
			'pixels' => Pixels::class,
		) as $scope => $class ) {
			if ( isset( $prepared[ $scope ] ) ) {
				$valid = $class::validate( $prepared[ $scope ] );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}
				$prepared[ $scope ] = $valid;
			}
		}
		if ( isset( $prepared['global'] ) && ! self::valid_slots( $prepared['global'] ) ) {
			return new \WP_Error( 'backup', 'Invalid global slots.' );
		}
		$mapped  = array();
		$skipped = 0;
		if ( isset( $prepared['individual'] ) ) {
			if ( count( $prepared['individual'] ) > 5000 ) {
				return new \WP_Error( 'size', 'Maximum 5000 individual records.' );
			}
			foreach ( $prepared['individual'] as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['kind'], $row['type'], $row['path'], $row['slots'] ) || ! is_string( $row['path'] ) || ! is_string( $row['type'] ) || ! is_array( $row['slots'] ) ) {
					return new \WP_Error( 'backup', 'Invalid individual mapping.' );
				}
				if ( ! self::valid_slots( $row['slots'] ) ) {
					return new \WP_Error( 'backup', 'Invalid individual slots.' );
				}
				$id = 0;
				if ( 'post' === $row['kind'] && 'attachment' !== $row['type'] && is_post_type_viewable( $row['type'] ) ) {
					$post = get_page_by_path( $row['path'], 'OBJECT', $row['type'] );
					$id   = $post && current_user_can( 'edit_post', $post->ID ) ? $post->ID : 0;
				} elseif ( 'term' === $row['kind'] && is_taxonomy_viewable( $row['type'] ) ) {
					$term = get_term_by( 'slug', $row['path'], $row['type'] );
					$id   = $term && current_user_can( 'edit_term', $term->term_id ) ? $term->term_id : 0;
				}
				if ( ! $id ) {
					++$skipped;
					continue;
				}
				$mapped[] = array( $row['kind'], $id, self::inactive( $row['slots'] ) );
			}
		}
		// All document sections are validated before mutations. Snippet import rolls back newly created copies on error.
		if ( isset( $prepared['snippets'] ) ) {
			$result = Repository::import(
				wp_json_encode(
					array(
						'schema'   => 'codeslot/v1',
						'snippets' => $prepared['snippets'],
					)
				)
			);
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		update_option(
			'codeslot_settings',
			array(
				'safe_mode'   => true,
				'delete_data' => false,
			),
			false
		);
		if ( isset( $prepared['global'] ) ) {
			update_option( 'codeslot_global', self::inactive( $prepared['global'] ), false );
		}
		foreach ( $mapped as $row ) {
			if ( 'post' === $row[0] ) {
				update_post_meta( $row[1], '_codeslot_slots', wp_slash( $row[2] ) );
			} else {
				update_term_meta( $row[1], '_codeslot_slots', wp_slash( $row[2] ) );
			}
		}
		foreach ( array( 'files', 'pixels' ) as $scope ) {
			if ( ! isset( $prepared[ $scope ] ) ) {
				continue;
			}
			foreach ( $prepared[ $scope ] as &$row ) {
				if ( is_array( $row ) ) {
					$row['enabled'] = false;
				}
			}
			unset( $row );
			if ( 'files' === $scope ) {
				$prepared[ $scope ]['register_worker'] = false;
			}
			update_option( 'codeslot_' . $scope, $prepared[ $scope ], false );
		}
		Support::log( 'backup_restored' );
		return array(
			'mapped'  => count( $mapped ),
			'skipped' => $skipped,
		);
	}

	private static function valid_slots( $slots ) {
		foreach ( $slots as $key => $row ) {
			if ( ! in_array( $key, array( 'header', 'body', 'footer' ), true ) || ! is_array( $row ) || ( isset( $row['code'] ) && ( ! is_string( $row['code'] ) || strlen( $row['code'] ) > 1048576 ) ) ) {
				return false;
			}
		}
		return true;
	}

	private static function inactive( $slots ) {
		$slots = Permissions::slots( $slots );
		foreach ( $slots as &$slot ) {
			$slot['enabled'] = false;
		}
		return $slots;
	}

	public static function page() {
		echo '<section class="codeslot-panel"><h2>Full CodeSlot backup</h2><p>The snippets-only export above excludes global and individual slots. Full backup includes snippets, global Header/Body/Footer, individual post/page/product/CPT/term slots, settings, virtual files and pixel configuration. It excludes saved revisions, logs, execution records and WordPress content itself. Store exports securely: authored code may contain secrets.</p>';
		Support::form( 'backup_export' );
		submit_button( 'Download full backup JSON', 'secondary' );
		echo '</form><h3>Restore selected sections</h3><p>Existing global/individual/file/pixel data in selected sections is replaced. Back up first. Snippets become new inactive copies. All restored slots/pixels/files are disabled, service-worker registration is off, Safe Mode is enabled and uninstall deletion is off. Post type + slug/path and taxonomy + term slug map existing content; missing matches are skipped and counted. This is not a whole-site/database backup.</p>';
		Support::form( 'backup_import' );
		echo '<label>Paste full backup JSON<textarea class="large-text code" rows="10" name="json" required></textarea></label>';
		foreach ( array( 'snippets', 'global', 'individual', 'settings', 'files', 'pixels' ) as $scope ) {
			echo '<p><label><input type="checkbox" name="scopes[]" value="' . esc_attr( $scope ) . '"> Restore ' . esc_html( $scope ) . '</label></p>';
		}
		echo '<p><label><input type="checkbox" name="confirm" value="1" required> I backed up current data and confirm replacing the selected sections.</label></p>';
		submit_button( 'Restore selected sections', 'secondary' );
		echo '</form></section>';
	}
}
