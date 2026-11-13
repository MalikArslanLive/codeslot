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
		return 'php' === $type ? array( 'frontend', 'admin', 'everywhere', 'manual' ) : array( 'header', 'body', 'footer', 'before_content', 'after_content', 'shortcode', 'manual', 'woocommerce_before_single_product', 'woocommerce_after_single_product', 'woocommerce_before_shop_loop', 'woocommerce_after_shop_loop' );
	}

	public static function validate( $data ) {
		if ( ! is_array( $data ) || empty( $data['type'] ) || ! in_array( $data['type'], self::types(), true ) || ! isset( $data['code'] ) || ! is_string( $data['code'] ) || strlen( $data['code'] ) > 1048576 ) {
			return new \WP_Error( 'snippet', __( 'Invalid type or code; maximum code size is 1 MiB.', 'codeslot' ) );
		}
		if ( ! isset( $data['location'] ) || ! in_array( $data['location'], self::locations( $data['type'] ), true ) ) {
			return new \WP_Error( 'location', __( 'This location does not support the selected code type.', 'codeslot' ) );
		}
		if ( 'php' === $data['type'] ) {
			if ( preg_match( '/<\?(?:php|=)?|\?>/i', $data['code'] ) ) {
				return new \WP_Error( 'php_tags', __( 'Enter PHP without opening or closing PHP tags.', 'codeslot' ) );
			}
			try {
				token_get_all( '<?php ' . $data['code'], TOKEN_PARSE );
			} catch ( \Throwable $error ) {
				return new \WP_Error( 'syntax', $error->getMessage() );
			}
		}
		$conditions = Conditions::validate( isset( $data['conditions'] ) ? $data['conditions'] : array() );
		if ( is_wp_error( $conditions ) ) {
			return $conditions;
		}
		return array(
			'name'        => sanitize_text_field( isset( $data['name'] ) ? $data['name'] : '' ),
			'description' => sanitize_textarea_field( isset( $data['description'] ) ? $data['description'] : '' ),
			'code'        => $data['code'],
			'type'        => $data['type'],
			'location'    => $data['location'],
			'priority'    => max( -1000, min( 1000, (int) ( isset( $data['priority'] ) ? $data['priority'] : 10 ) ) ),
			'conditions'  => $conditions,
			'locked'      => ! empty( $data['locked'] ),
			'active'      => ! empty( $data['active'] ),
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
			)
		);
	}

	public static function save( $data, $id = 0, $unlock = false ) {
		if ( ! Permissions::allowed() ) {
			return new \WP_Error( 'forbidden', __( 'Executable code access denied.', 'codeslot' ) );
		}
		$old = $id ? self::get( $id ) : null;
		if ( $id && ! $old ) {
			return new \WP_Error( 'missing', __( 'Snippet not found.', 'codeslot' ) );
		}
		if ( $old && ! empty( $old['locked'] ) && ! $unlock ) {
			return new \WP_Error( 'locked', __( 'Unlock this snippet before editing.', 'codeslot' ) );
		}
		$valid = self::validate( $data );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( $old ) {
			$history   = get_post_meta( $id, '_codeslot_history', true );
			$history   = is_array( $history ) ? $history : array();
			$history[] = array(
				'date'    => gmdate( 'c' ),
				'user'    => get_current_user_id(),
				'snippet' => $old,
			);
			update_post_meta( $id, '_codeslot_history', array_slice( $history, -30 ) );
		}
		$result = wp_insert_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_type'    => 'codeslot_snippet',
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
		$config = $valid;
		unset( $config['name'], $config['description'], $config['code'], $config['active'] );
		update_post_meta( $result, '_codeslot_config', wp_slash( $config ) );
		update_post_meta( $result, '_codeslot_location', $valid['location'] );
		wp_set_object_terms( $result, $valid['categories'], 'codeslot_category' );
		wp_set_object_terms( $result, $valid['tags'], 'codeslot_tag' );
		Runtime::clear_cache();
		return $result;
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
				'meta_key'       => '_codeslot_location',
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
			$valid = self::validate( $snippet );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$valid['active'] = false;
			$valid['locked'] = false;
			$validated[]     = $valid;
		}
		$ids = array();
		foreach ( $validated as $valid ) {
			$id = self::save( $valid );
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
