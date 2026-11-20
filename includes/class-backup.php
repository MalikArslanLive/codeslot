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
					'path'  => '' !== $post->post_name ? (string) get_page_uri( $post ) : '',
					'slots' => self::export_slots( get_post_meta( $post->ID, '_codeslot_slots', true ) ),
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
					'slots' => self::export_slots( get_term_meta( $term->term_id, '_codeslot_slots', true ) ),
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

	public static function restore( $json, $scopes, $conflict_policy = 'skip', $existing = array(), $state_policy = 'disabled' ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Access denied.' );
		}
		if ( ! is_array( $scopes ) || ! $scopes ) {
			return new \WP_Error( 'restore_sections', 'Select at least one section to restore, then click Restore selected sections. Nothing was restored.' );
		}
		if ( ! is_string( $json ) || strlen( $json ) > 5242880 ) {
			return new \WP_Error( 'size', 'Backup import limit is 5 MiB.' );
		}
		if ( ! is_array( $existing ) || ! in_array( $state_policy, array( 'disabled', 'preserve' ), true ) || ! in_array( $conflict_policy, array( 'skip', 'copy', 'replace' ), true ) ) {
			return new \WP_Error( 'restore_options', 'Invalid restore choices.' ); }
		foreach ( $existing as $scope => $policy ) {
			if ( ! in_array( $scope, array( 'global', 'individual', 'settings', 'files', 'pixels' ), true ) || ! in_array( $policy, array( 'skip', 'replace' ), true ) ) {
				return new \WP_Error( 'restore_options', 'Choose Keep existing or Replace for each section.' ); }
		}
		$d = json_decode( $json, true, 32 );
		if ( ! is_array( $d ) || ! isset( $d['schema'] ) || 'codeslot/backup-v1' !== $d['schema'] ) {
			return new \WP_Error( 'backup', 'Choose a valid CodeSlot full backup JSON file. Nothing was restored.' );
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
				if ( 'pixels' === $scope ) {
					foreach ( $prepared[ $scope ] as &$row ) {
						if ( is_array( $row ) ) {
							$row['enabled'] = false;
						}
					} unset( $row ); }
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
			$entry = 0;
			foreach ( $prepared['individual'] as $row ) {
				++$entry;
				if ( ! is_array( $row ) || ! isset( $row['kind'], $row['type'], $row['path'], $row['slots'] ) || ! is_string( $row['path'] ) || ! is_string( $row['type'] ) || ! is_array( $row['slots'] ) ) {
					return new \WP_Error( 'backup', 'Invalid individual mapping at entry ' . $entry . ': expected kind, type, path and slots.' );
				}
				if ( ! self::valid_slots( $row['slots'] ) ) {
					return new \WP_Error( 'backup', 'Invalid individual slots at entry ' . $entry . '.' );
				}
				if ( '' === $row['path'] || '/' === substr( $row['path'], -1 ) ) {
					++$skipped;
					continue; }
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
				$mapped[] = array( $row['kind'], $id, $row['slots'] );
			}
		}
		$sections = array();
		foreach ( array( 'snippets', 'global', 'individual', 'settings', 'files', 'pixels' ) as $scope ) {
			$sections[ $scope ] = array(
				'selected'    => isset( $prepared[ $scope ] ),
				'updated'     => 0,
				'skipped'     => 0,
				'unchanged'   => 0,
				'enabled'     => 0,
				'disabled'    => 0,
				'preferences' => 0,
			);
		}
		$writes = array();
		if ( isset( $prepared['global'] ) ) {
			$current = get_option( 'codeslot_global', array() );
			$value   = self::merge_slots( $prepared['global'], $current, isset( $existing['global'] ) ? $existing['global'] : 'replace', $state_policy, $sections['global'] );
			if ( $sections['global']['updated'] ) {
				$writes[] = array( 'option', 'codeslot_global', $value ); }
		}
		$targets = array();
		foreach ( $mapped as $row ) {
			$key = $row[0] . ':' . $row[1];
			if ( isset( $targets[ $key ] ) ) {
				return new \WP_Error( 'mapping', 'The backup repeats an individual target; restore one version per post or term.' ); }
			$targets[ $key ] = true;
			$current         = 'post' === $row[0] ? get_post_meta( $row[1], '_codeslot_slots', true ) : get_term_meta( $row[1], '_codeslot_slots', true );
			$prior           = $sections['individual']['updated'];
			$value           = self::merge_slots( $row[2], $current, isset( $existing['individual'] ) ? $existing['individual'] : 'replace', $state_policy, $sections['individual'] );
			if ( $prior !== $sections['individual']['updated'] ) {
				$writes[] = array( $row[0], $row[1], $value ); }
		}
		foreach ( array( 'files', 'pixels' ) as $scope ) {
			if ( ! isset( $prepared[ $scope ] ) ) {
				continue; }
			$current = get_option( 'codeslot_' . $scope, array() );
			$current = is_array( $current ) ? $current : array();
			$value   = $current;
			$policy  = isset( $existing[ $scope ] ) ? $existing[ $scope ] : 'replace';
			$keys    = 'files' === $scope ? Files::NAMES : array_keys( Pixels::PROVIDERS );
			foreach ( $keys as $key ) {
				$old   = isset( $current[ $key ] ) && is_array( $current[ $key ] ) ? $current[ $key ] : array();
				$data  = $prepared[ $scope ][ $key ];
				$field = 'files' === $scope ? 'code' : 'id';
				if ( 'skip' === $policy && ( ( isset( $old[ $field ] ) && '' !== $old[ $field ] ) || ! empty( $old['enabled'] ) ) ) {
					++$sections[ $scope ]['skipped'];
					continue; }
				$data['enabled'] = 'preserve' === $state_policy && ! empty( $old['enabled'] ) && ( 'files' === $scope || '' !== $data['id'] );
				if ( $data === $old ) {
					++$sections[ $scope ]['unchanged'];
					continue; }
				$value[ $key ] = $data;
				++$sections[ $scope ]['updated'];
				++$sections[ $scope ][ $data['enabled'] ? 'enabled' : 'disabled' ];
			}
			if ( 'replace' === $policy ) {
				$extras = 'files' === $scope ? array( 'register_worker' => 'preserve' === $state_policy && ! empty( $current['register_worker'] ) && ! empty( $value['service-worker.js']['enabled'] ) ) : array(
					'click_selector'  => $prepared['pixels']['click_selector'],
					'require_consent' => $prepared['pixels']['require_consent'],
				);
				foreach ( $extras as $key => $data ) {
					if ( ! array_key_exists( $key, $value ) || $value[ $key ] !== $data ) {
						$value[ $key ] = $data;
						++$sections[ $scope ]['preferences']; }
				}
			}
			if ( 'pixels' === $scope ) {
				$valid = Pixels::validate( $value );
				if ( is_wp_error( $valid ) ) {
					return $valid; }
			}
			if ( 'files' === $scope ) {
				$sections['files']['registration'] = ! empty( $value['register_worker'] ); }
			if ( $sections[ $scope ]['updated'] || $sections[ $scope ]['preferences'] ) {
				$writes[] = array( 'option', 'codeslot_' . $scope, $value ); }
		}
		if ( isset( $prepared['settings'] ) ) {
			$current = get_option( 'codeslot_settings', array() );
			if ( isset( $existing['settings'] ) && 'skip' === $existing['settings'] ) {
				++$sections['settings']['skipped']; } else {
				$value = array(
					'safe_mode'   => ! empty( $current['safe_mode'] ),
					'delete_data' => false,
				);
				if ( $value === $current ) {
					++$sections['settings']['unchanged'];
				} else {
					++$sections['settings']['updated'];
					$writes[] = array( 'option', 'codeslot_settings', $value ); }
				}
		}
		$snippet_report = null;
		// All sections and execution choices are checked before the first mutation.
		if ( isset( $prepared['snippets'] ) ) {
			$result = Repository::import(
				wp_json_encode(
					array(
						'schema'   => 'codeslot/v1',
						'snippets' => $prepared['snippets'],
					)
				),
				$conflict_policy,
				$snippet_report,
				$state_policy
			);
			if ( is_wp_error( $result ) ) {
				return $result; }
		}
		foreach ( $writes as $write ) {
			if ( 'option' === $write[0] ) {
				update_option( $write[1], $write[2], false ); } elseif ( 'post' === $write[0] ) {
				update_post_meta( $write[1], '_codeslot_slots', wp_slash( $write[2] ) ); } else {
					update_term_meta( $write[1], '_codeslot_slots', wp_slash( $write[2] ) ); }
		}

		Support::log( 'backup_restored' );
		return array(
			'snippets'  => isset( $prepared['snippets'] ) ? $snippet_report : null,
			'safe_mode' => Permissions::safe(),
			'sections'  => $sections,
			'mapped'    => count( $mapped ),
			'skipped'   => $skipped,
		);
	}

	/** WordPress returns an empty scalar when no slot metadata exists. */
	private static function export_slots( $slots ) {
		return null === $slots || '' === $slots || false === $slots ? array() : $slots;
	}

	public static function import_message( $report ) {
		$r        = array_merge(
			array(
				'imported'  => 0,
				'skipped'   => 0,
				'conflicts' => 0,
				'replaced'  => 0,
				'protected' => 0,
				'ambiguous' => 0,
				'enabled'   => 0,
				'disabled'  => 0,
				'policy'    => 'skip',
			),
			$report
		);
		$message  = sprintf( 'Import complete: %d snippets imported, %d exact duplicates skipped, %d name conflicts. ', $r['imported'], $r['skipped'], $r['conflicts'] );
		$message .= sprintf( 'Existing snippets replaced: %d. Updated/new snippets: %d enabled, %d disabled. ', $r['replaced'], $r['enabled'], $r['disabled'] );
		if ( 'replace' !== $r['policy'] ) {
			$message .= 'Existing snippet code and states were not replaced or updated. '; }
		$message .= sprintf( 'Exact duplicates remain unchanged. Locked entries skipped: %d; ambiguous name matches skipped: %d. ', $r['protected'], $r['ambiguous'] );
		if ( 'skip' === $r['policy'] ) {
			$message .= 'Different versions with the same name were skipped and remain unchanged.'; }
		if ( 'copy' === $r['policy'] ) {
			$message .= 'Different versions were kept as new disabled copies; originals remain unchanged.'; }
		return $message;
	}

	public static function notice( $result ) {
		echo '<div class="notice notice-success is-dismissible codeslot-restore-result"><p>' . esc_html( 'Backup restore complete. Site-wide Safe Mode remains ' . ( ! empty( $result['safe_mode'] ) ? 'on' : 'off' ) . '.' ) . '</p><ul>';
		foreach ( array(
			'snippets'   => 'Snippets',
			'global'     => 'Global code',
			'individual' => 'Individual code',
			'settings'   => 'Settings',
			'files'      => 'Virtual files',
			'pixels'     => 'Conversion pixels',
		) as $scope => $label ) {
			$row = isset( $result['sections'][ $scope ] ) ? $result['sections'][ $scope ] : array( 'selected' => false );
			if ( empty( $row['selected'] ) ) {
				$text = 'Not selected — existing data was not replaced or updated.'; } elseif ( 'snippets' === $scope ) {
				$text = self::import_message( $result['snippets'] ); } elseif ( 'settings' === $scope ) {
					$text = $row['updated'] ? 'Preferences updated; Safe Mode preserved and uninstall deletion off.' : 'Existing preferences were not replaced or updated.'; } else {
					$text = sprintf( '%d entries restored/replaced (%d enabled, %d disabled); %d existing entries skipped; %d identical entries unchanged. Skipped/unchanged entries were not replaced or updated.', $row['updated'], $row['enabled'], $row['disabled'], $row['skipped'], $row['unchanged'] );
					if ( $row['preferences'] ) {
						$text .= sprintf( ' Shared preferences updated: %d.', $row['preferences'] ); }
					if ( 'individual' === $scope ) {
						$text .= sprintf( ' Matched records: %d; missing/unsupported records skipped: %d.', $result['mapped'], $result['skipped'] ); }
					if ( 'files' === $scope ) {
						$text .= ' Service-worker registration: ' . ( ! empty( $row['registration'] ) ? 'on.' : 'off.' ); }
					}
					echo '<li data-restore-section="' . esc_attr( $scope ) . '"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $text ) . '</li>';
		}
		echo '</ul><p>Review restored entries before enabling them. WordPress post/page content and physical server files were not overwritten; no new posts/pages were created.</p></div>';
	}

	public static function conflict_field() {
		echo '<p><label>Different versions with the same name<select name="snippet_conflicts"><option value="skip">Skip conflicting names</option><option value="copy">Keep both — import a disabled copy</option><option value="replace">Replace one matching unlocked snippet</option></select></label></p><p class="description">Exact duplicates are always skipped unchanged. Replace requires one unambiguous unlocked match. Locked entries and ambiguous names are never overwritten.</p><div class="codeslot-replace-confirm"><label><input type="checkbox" name="confirm_replace" value="1"> I understand that Replace overwrites existing snippet code/settings and have downloaded a backup if needed.</label></div>';
	}

	public static function state_field() {
		echo '<p><label>State after restoring/replacing code<select name="restore_state"><option value="disabled">Disable restored/replaced entries</option><option value="preserve">Preserve each existing entry’s current state</option></select></label></p><p class="description">New snippets/copies and new entries always start disabled. Preserve applies only to replaced existing entries and can execute changed code immediately if they were enabled. Skipped and unselected entries keep their current code and state.</p>';
	}

	private static function valid_slots( $slots ) {
		return ! is_wp_error( Permissions::validate_slots( $slots ) );
	}

	private static function merge_slots( $incoming, $current, $policy, $state, &$report ) {
		$raw     = is_array( $current ) ? $current : array();
		$current = Permissions::slots( $raw );
		foreach ( Permissions::slots( $incoming ) as $slot => $data ) {
			$old = $current[ $slot ];
			if ( 'skip' === $policy && ( '' !== $old['code'] || $old['enabled'] ) ) {
				++$report['skipped'];
				continue; }
			$data['enabled'] = 'preserve' === $state && $old['enabled'];
			if ( $data === $old ) {
				++$report['unchanged'];
				continue; }
			$current[ $slot ] = $data;
			++$report['updated'];
			++$report[ $data['enabled'] ? 'enabled' : 'disabled' ];
		}
		return $current;
	}

	public static function page() {
		echo '<section class="codeslot-panel"><h2>Full CodeSlot backup</h2><p>The snippets-only export above excludes global and individual slots. Full backup includes snippets, global Header/Body/Footer, individual post/page/product/CPT/term slots, settings, virtual files and pixel configuration. It excludes saved revisions, logs, execution records and WordPress content itself. Store exports securely: authored code may contain secrets.</p>';
		Support::form( 'backup_export' );
		submit_button( 'Download full backup JSON', 'secondary' );
		echo '</form><h3>Restore selected sections</h3><p>Choose which sections to restore and whether to keep existing entries or replace them. Replace overwrites the selected existing content with backup content; download a current full backup first if you may need it. Unselected sections, skipped entries and exact duplicate snippets are not replaced or updated. New entries start disabled; choose the state policy for replaced entries. Your site-wide Safe Mode state is preserved. Restoring settings keeps uninstall deletion off. Post type + slug/path and taxonomy + term slug map existing content; missing matches are skipped and counted. This is not a whole-site/database backup.</p>';
		Support::form( 'backup_import' );
		echo '<label for="codeslot-backup-file">Upload full backup JSON</label><input id="codeslot-backup-file" type="file" name="json_file" accept=".json,application/json" required><p class="description">Choose a CodeSlot full backup (.json), up to 5 MiB.</p>';
		foreach ( array( 'snippets', 'global', 'individual', 'settings', 'files', 'pixels' ) as $scope ) {
			echo '<div class="codeslot-restore-scope"><label><input type="checkbox" name="scopes[]" value="' . esc_attr( $scope ) . '"> Restore ' . esc_html( $scope ) . '</label>';
			if ( 'snippets' !== $scope ) {
				echo '<label>Existing entries in ' . esc_html( $scope ) . '<select name="restore_existing[' . esc_attr( $scope ) . ']"><option value="replace">Replace from backup</option><option value="skip">Keep existing entries unchanged</option></select></label>'; }
			echo '</div>';
		}
		self::conflict_field();
		self::state_field();
		echo '<div class="codeslot-restore-preview" aria-live="polite"></div><p><label><input type="checkbox" name="confirm" value="1" required> I reviewed the selected sections and replacement/state choices and downloaded a backup if needed.</label></p>';
		submit_button( 'Restore selected sections', 'secondary' );
		echo '</form></section>';
	}
}
