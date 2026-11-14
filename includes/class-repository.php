<?php
/** Snippet persistence, validation and portability. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Repository {
	public static function register() {
		register_post_type(
			'codeslot_snippet',
			array(
				'label'        => 'CodeSlot',
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'supports'     => array( 'title', 'editor', 'revisions' ),
				'can_export'   => false,
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
					'edit_posts'   => 'do_not_allow',
					'delete_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
			)
		);
		foreach ( array( 'codeslot_category', 'codeslot_tag' ) as $taxonomy ) {
			register_taxonomy(
				$taxonomy,
				'codeslot_snippet',
				array(
					'public'       => false,
					'show_ui'      => false,
					'show_in_rest' => false,
				)
			);
		}
		register_post_meta(
			'',
			'_codeslot_slots',
			array(
				'type'          => 'object',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => array( Permissions::class, 'allowed' ),
			)
		);
		register_term_meta(
			'',
			'_codeslot_slots',
			array(
				'type'          => 'object',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => array( Permissions::class, 'allowed' ),
			)
		);
	}

	public static function types() {
		return array( 'php', 'javascript', 'css', 'html', 'text', 'content', 'shortcode' );
	}

	public static function locations( $type ) {
		return 'php' === $type ? array( 'frontend', 'admin', 'everywhere', 'bootstrap', 'manual', 'once' ) : array( 'header', 'body', 'footer', 'admin_header', 'admin_footer', 'selector_before', 'selector_after', 'selector_prepend', 'selector_append', 'selector_replace', 'before_content', 'after_content', 'before_paragraph', 'after_paragraph', 'shortcode', 'manual', 'woocommerce_before_single_product', 'woocommerce_after_single_product', 'woocommerce_before_shop_loop', 'woocommerce_after_shop_loop' );
	}

	public static function validate( $data, $saved_source = false ) {
		if ( ! is_array( $data ) || empty( $data['type'] ) || ! in_array( $data['type'], self::types(), true ) || ! isset( $data['code'] ) || ! is_string( $data['code'] ) || strlen( $data['code'] ) > 1048576 ) {
			return new \WP_Error( 'snippet', __( 'Invalid type or code; maximum code size is 1 MiB.', 'codeslot' ) );
		}
		if ( ! isset( $data['location'] ) || ! in_array( $data['location'], self::locations( $data['type'] ), true ) ) {
			return new \WP_Error( 'location', __( 'This location does not support the selected code type.', 'codeslot' ) );
		}
		if ( ! $saved_source && ( in_array( $data['type'], array( 'javascript', 'css' ), true ) && preg_match( '/^\s*<(script|style)\b/i', $data['code'] ) ) ) {
			return new \WP_Error( 'wrapper', 'Wrappers are added automatically. Choose HTML for a complete embed with script/style tags, or remove the tags from JavaScript/CSS code.' );
		}
		if ( 'php' === $data['type'] ) {
			if ( preg_match( '/<\?(?:php|=)?|\?>/i', $data['code'] ) ) {
				return new \WP_Error( 'php_tags', __( 'Enter PHP without opening or closing PHP tags.', 'codeslot' ) );
			}
			try {
				$tokens = token_get_all( '<?php ' . $data['code'], TOKEN_PARSE );
				if ( ! $tokens ) {
					return new \WP_Error( 'syntax', 'Unable to tokenize PHP.' );
				}
			} catch ( \Throwable $error ) {
				return new \WP_Error( 'syntax', $error->getMessage() );
			}
		}
		$conditions = Conditions::validate( isset( $data['conditions'] ) ? $data['conditions'] : array() );
		if ( is_wp_error( $conditions ) ) {
			return $conditions;
		}
		$paragraph = isset( $data['paragraph'] ) ? $data['paragraph'] : 1;
		if ( in_array( $data['location'], array( 'before_paragraph', 'after_paragraph' ), true ) && ( ! is_scalar( $paragraph ) || ! preg_match( '/^[0-9]{1,4}$/', (string) $paragraph ) || (int) $paragraph < 1 || (int) $paragraph > 1000 ) ) {
			return new \WP_Error( 'paragraph', 'Choose a paragraph number from 1 to 1000.' );
		}
		if ( in_array( $data['location'], array( 'once', 'bootstrap' ), true ) && ( $conditions['include'] || $conditions['exclude'] ) ) {
			return new \WP_Error( 'conditions', 'Run Once and early-hook snippets do not support query conditions; remove automatic conditions.' );
		}
		$device = isset( $data['device'] ) ? $data['device'] : 'any';
		if ( ! in_array( $device, array( 'any', 'desktop', 'mobile' ), true ) ) {
			return new \WP_Error( 'device', 'Choose a valid device type.' ); }
		$dates = array();
		foreach ( array( 'starts', 'ends' ) as $key ) {
			$value = isset( $data[ $key ] ) ? $data[ $key ] : '';
			if ( '' === $value || 0 === $value ) {
				$dates[ $key ] = 0;
				continue; }
			if ( is_int( $value ) && $value > 0 && $value < 32503680000 ) {
				$dates[ $key ] = $value;
				continue; }
			if ( ! is_string( $value ) ) {
				return new \WP_Error( 'schedule', 'Choose a valid schedule date.' ); }
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
			if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $value ) {
				return new \WP_Error( 'schedule', 'Use a valid date and time in the site timezone.' ); }
			$dates[ $key ] = $date->getTimestamp();
		}
		if ( $dates['ends'] && $dates['starts'] && $dates['ends'] <= $dates['starts'] ) {
			return new \WP_Error( 'schedule', 'End time must be after start time.' ); }
		$selector = isset( $data['selector'] ) && is_string( $data['selector'] ) ? trim( $data['selector'] ) : '';
		if ( 0 === strpos( $data['location'], 'selector_' ) && ( '' === $selector || strlen( $selector ) > 200 ) ) {
			return new \WP_Error( 'selector', 'Enter a CSS selector of 1–200 characters.' ); }
		if ( ( ! empty( $data['load_file'] ) && ! in_array( $data['type'], array( 'css', 'javascript' ), true ) ) || ( ! empty( $data['compact'] ) && 'javascript' !== $data['type'] ) ) {
			return new \WP_Error( 'delivery', 'Static-file loading supports JavaScript/CSS. Compaction supports JavaScript only.' ); }
		if ( ( ! empty( $data['load_file'] ) || ! empty( $data['compact'] ) ) && preg_match( '/^\s*<(script|style)\b/i', $data['code'] ) ) {
			return new \WP_Error( 'wrapper', 'Remove wrapper tags before enabling file loading or compaction.' ); }

		return array(
			'device'      => $device,
			'starts'      => $dates['starts'],
			'ends'        => $dates['ends'],
			'selector'    => $selector,
			'load_file'   => ! empty( $data['load_file'] ),
			'compact'     => ! empty( $data['compact'] ),
			'paragraph'   => is_scalar( $paragraph ) ? max( 1, min( 1000, (int) $paragraph ) ) : 1,
			'name'        => sanitize_text_field( isset( $data['name'] ) ? $data['name'] : '' ),
			'description' => sanitize_textarea_field( isset( $data['description'] ) ? $data['description'] : '' ),
			'code'        => $data['code'],
			'type'        => $data['type'],
			'location'    => $data['location'],
			'priority'    => max( -1000, min( 1000, (int) ( isset( $data['priority'] ) ? $data['priority'] : 10 ) ) ),
			'conditions'  => $conditions,
			'locked'      => ! empty( $data['locked'] ),
			'active'      => 'once' !== $data['location'] && ! empty( $data['active'] ),
			'categories'  => self::labels( isset( $data['categories'] ) ? $data['categories'] : '' ),
			'tags'        => self::labels( isset( $data['tags'] ) ? $data['tags'] : '' ),
		);
	}

	private static function labels( $value ) {
		return array_values( array_filter( array_map( 'sanitize_text_field', is_array( $value ) ? $value : explode( ',', (string) $value ) ) ) );
	}

	public static function get( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'codeslot_snippet' !== $post->post_type ) {
			return null;
		}
		$config = get_post_meta( $id, '_codeslot_config', true );
		if ( ! is_array( $config ) ) {
			return null;
		}
		return array_merge(
			$config,
			array(
				'id'          => $post->ID,
				'name'        => $post->post_title,
				'description' => $post->post_excerpt,
				'code'        => $post->post_content,
				'active'      => 'publish' === $post->post_status,
				'trashed'     => 'trash' === $post->post_status,
			)
		);
	}

	public static function save( $data, $id = 0, $unlock = false, $stored_copy = false ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', __( 'Executable code access denied.', 'codeslot' ) );
		}
		$old = $id ? self::get( $id ) : null;
		if ( $id && ! $old ) {
			return new \WP_Error( 'missing', __( 'Snippet not found.', 'codeslot' ) );
		}
		if ( $old && ! empty( $old['trashed'] ) ) {
			return new \WP_Error( 'trash', 'Restore this snippet from Trash before editing it.' ); }
		if ( $old && ! empty( $old['locked'] ) && ! $unlock ) {
			return new \WP_Error( 'locked', __( 'Unlock this snippet before editing.', 'codeslot' ) );
		}
		/* translators: %d: ID of an existing untitled snippet. */
		if ( $old && isset( $data['name'] ) && '' === trim( $old['name'] ) && sprintf( __( 'Untitled snippet #%d', 'codeslot' ), $id ) === $data['name'] ) {
			$data['name'] = ''; }
		if ( $old && isset( $data['code'] ) && is_string( $data['code'] ) && str_replace( array( "\r\n", "\r" ), "\n", $old['code'] ) === str_replace( array( "\r\n", "\r" ), "\n", $data['code'] ) ) {
			$data['code'] = $old['code']; }
		foreach ( array( 'starts', 'ends' ) as $date_key ) {
			if ( $old && ! empty( $old[ $date_key ] ) && isset( $data[ $date_key ] ) && is_string( $data[ $date_key ] ) && wp_date( 'Y-m-d\TH:i', $old[ $date_key ], wp_timezone() ) === $data[ $date_key ] ) {
				$data[ $date_key ] = $old[ $date_key ]; }
		}
		$valid = self::validate( $data, $stored_copy || ( $old && isset( $data['code'], $data['type'] ) && $old['code'] === $data['code'] && $old['type'] === $data['type'] ) );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( $old ) {
			$previous = self::validate( $old, true );
			if ( ! is_wp_error( $previous ) && $previous === $valid ) {
				return $id; }
		}

		$result = wp_insert_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_type'    => 'codeslot_snippet',
					'post_author'  => $id ? (int) get_post_field( 'post_author', $id ) : get_current_user_id(),
					'post_title'   => $valid['name'],
					'post_excerpt' => $valid['description'],
					'post_content' => $valid['code'],
					'post_status'  => $valid['active'] ? 'publish' : 'draft',
					'menu_order'   => $valid['priority'],
				)
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $old ) {
			$comparison = is_wp_error( $previous ) ? $old : $previous;
			$changed    = array();
			foreach ( $valid as $key => $value ) {
				if ( ! array_key_exists( $key, $comparison ) || $comparison[ $key ] !== $value ) {
					$changed[] = $key; }
			}
			self::revision( $id, $old, $changed );
		}
		$config = $valid;
		unset( $config['name'], $config['description'], $config['code'], $config['active'] );
		update_post_meta( $result, '_codeslot_config', wp_slash( $config ) );
		update_post_meta( $result, '_codeslot_location', $valid['location'] );
		wp_set_object_terms( $result, $valid['categories'], 'codeslot_category' );
		wp_set_object_terms( $result, $valid['tags'], 'codeslot_tag' );
		Runtime::clear_cache();
		return $result;
	}

	private static function revision( $id, $old, $changed = array() ) {
		$history   = get_post_meta( $id, '_codeslot_history', true );
		$history   = is_array( $history ) ? $history : array();
		$history[] = array(
			'date'    => gmdate( 'c' ),
			'user'    => get_current_user_id(),
			'snippet' => $old,
			'changed' => $changed,
		);
		update_post_meta( $id, '_codeslot_history', array_slice( $history, -30 ) );
	}

	/** Status writes never rewrite source. Only actual status changes create a revision. Deactivation also works for broken legacy code. */
	public static function status( $id, $active ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Executable code access denied.' );
		}
		$snippet = self::get( $id );
		if ( ! $snippet || ! empty( $snippet['trashed'] ) || $snippet['locked'] || ( $active && 'once' === $snippet['location'] ) ) {
			return new \WP_Error( 'locked', 'Unlock the snippet before changing status. Run Once tasks cannot be activated.' );
		}
		if ( $active ) {
			$valid = self::validate( $snippet, true );
			if ( is_wp_error( $valid ) ) {
				return $valid; }
		}
		if ( (bool) $snippet['active'] === (bool) $active ) {
			return $id; }
		$result = wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => $active ? 'publish' : 'draft',
			),
			true
		);
		if ( ! is_wp_error( $result ) ) {
			self::revision( $id, $snippet, array( 'active' ) ); }
		Runtime::clear_cache();
		return $result;
	}

	/** WordPress Trash lifecycle. Restores are explicitly inactive. */
	public static function lifecycle( $id, $action ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Executable code access denied.' ); }
		$s = self::get( $id );
		if ( ! $s || ( $s['locked'] && 'restore' !== $action ) ) {
			return new \WP_Error( 'locked', 'Missing or locked snippet. Unlock it before moving or deleting it.' ); }
		if ( 'trash' === $action ) {
			if ( $s['trashed'] ) {
				return $id; }
			if ( defined( 'EMPTY_TRASH_DAYS' ) && EMPTY_TRASH_DAYS < 1 ) {
				return new \WP_Error( 'trash_disabled', 'WordPress Trash retention is disabled. Enable it before moving snippets to Trash.' ); }
			$result = wp_trash_post( $id );
		} elseif ( 'restore' === $action && $s['trashed'] ) {
			$inactive = static function ( $status, $post_id ) use ( $id ) {
				return (int) $post_id === (int) $id ? 'draft' : $status;
			};
			add_filter( 'wp_untrash_post_status', $inactive, PHP_INT_MAX, 2 );
			try {
				$result = wp_untrash_post( $id );
			} finally {
				remove_filter( 'wp_untrash_post_status', $inactive, PHP_INT_MAX ); }
		} elseif ( 'delete' === $action && $s['trashed'] ) {
			$result = wp_delete_post( $id, true );
		} else {
			return new \WP_Error( 'trash', 'Only trashed snippets can be restored or permanently deleted.' ); }
		if ( ! $result ) {
			return new \WP_Error( 'trash', 'WordPress could not complete this Trash action.' ); }
		if ( 'delete' !== $action ) {
			self::revision( $id, $s, array( 'trash' === $action ? 'moved_to_trash' : 'restored_inactive' ) ); }
		Runtime::clear_cache();
		return $id;
	}

	public static function empty_trash() {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Executable code access denied.' ); }
		$ids = get_posts(
			array(
				'post_type'      => 'codeslot_snippet',
				'post_status'    => 'trash',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			$config = get_post_meta( $id, '_codeslot_config', true );
			if ( ! is_array( $config ) || ! empty( $config['locked'] ) ) {
				return new \WP_Error( 'locked', 'Trash includes a locked or unreadable snippet. Restore and unlock it first; no snippets were deleted.' ); }
		}
		foreach ( $ids as $id ) {
			$result = self::lifecycle( $id, 'delete' );
			if ( is_wp_error( $result ) ) {
				return $result; }
		}
		return count( $ids );
	}

	public static function active( $location ) {
		return get_posts(
			array(
				'post_type'      => 'codeslot_snippet',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
				),
				'meta_key'       => '_codeslot_location', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Active location-scoped request cache; benchmarked.
				'meta_value'     => $location, // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'      => true,
			)
		);
	}

	public static function export() {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Access denied.' );
		}
		$posts = get_posts(
			array(
				'post_type'      => 'codeslot_snippet',
				'post_status'    => array( 'draft', 'publish' ),
				'posts_per_page' => -1,
			)
		);
		return array(
			'schema'   => 'codeslot/v1',
			'snippets' => array_map(
				static function ( $post ) {
					return self::get( $post->ID );
				},
				$posts
			),
		);
	}

	public static function import( $json ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', 'Access denied.' );
		}
		if ( ! is_string( $json ) || strlen( $json ) > 5242880 ) {
			return new \WP_Error( 'size', 'Import limit is 5 MiB.' );
		}
		$document = json_decode( $json, true, 32 );
		if ( ! is_array( $document ) || ! isset( $document['schema'], $document['snippets'] ) || 'codeslot/v1' !== $document['schema'] || ! is_array( $document['snippets'] ) || count( $document['snippets'] ) > 200 ) {
			return new \WP_Error( 'schema', 'Expected codeslot/v1 with at most 200 snippets.' );
		}
		$validated = array();
		foreach ( $document['snippets'] as $snippet ) {
			$valid = self::validate( $snippet, true );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$valid['active'] = false;
			$valid['locked'] = false;
			$validated[]     = $valid;
		}
		$ids = array();
		foreach ( $validated as $valid ) {
			$id = self::save( $valid, 0, false, true );
			if ( is_wp_error( $id ) ) {
				foreach ( $ids as $created ) {
					wp_delete_post( $created, true );
				}
				return $id;
			}
			$ids[] = $id;
		}
		return $ids;
	}
}
