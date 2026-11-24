<?php
/** Authorized, bounded name searches for the conditions editor. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Condition_Editor {
	public static function config() {
		return array(
			'url'      => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'codeslot_conditions' ),
			'rules'    => Conditions::definitions(),
			'commerce' => Pixels::commerce_available(),
		);
	}

	public static function search() {
		if ( ! Permissions::allowed() || ! check_ajax_referer( 'codeslot_conditions', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Your session expired or access was denied. Reload this page.' ), 403 );
		}
		// Search is read-only and privileged; all values are scalar, bounded and allowlisted below.
		$key   = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_key( $_GET['key'] ) : '';
		$query = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$value = isset( $_GET['value'] ) && is_string( $_GET['value'] ) ? sanitize_text_field( wp_unslash( $_GET['value'] ) ) : '';
		if ( ! in_array( $key, array( 'singular', 'post_type', 'term', 'role' ), true ) || strlen( $query ) > 200 || strlen( $value ) > 200 ) {
			wp_send_json_error( array( 'message' => 'Invalid search.' ), 400 );
		}
		if ( '' === $value && ! preg_match( '/^.{2}/us', $query ) ) {
			wp_send_json_success( array() );
		}
		wp_send_json_success( self::options( $key, $query, $value ) );
	}

	public static function options( $key, $query, $value = '' ) {
		$items = array();
		$types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );
		if ( 'singular' === $key ) {
			$args = array(
				'post_type'      => array_keys( $types ),
				'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page' => 20,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			);
			if ( ! $types ) {
				return array();
			}
			if ( '' !== $value ) {
				if ( ! ctype_digit( $value ) || (int) $value < 1 ) {
					return array();
				}
				$args['p'] = (int) $value;
			} else {
				$args['s']              = $query;
				$args['search_columns'] = array( 'post_title' );
			}
			foreach ( get_posts( $args ) as $post ) {
				if ( current_user_can( 'edit_post', $post->ID ) ) {
					$items[] = self::item( (string) $post->ID, $post->post_title, $types[ $post->post_type ]->labels->singular_name . ' · ID ' . $post->ID . ' · ' . $post->post_status );
				}
			}
		} elseif ( 'term' === $key ) {
			$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
			if ( ! $taxonomies ) {
				return array();
			}
			$args = array(
				'taxonomy'   => array_keys( $taxonomies ),
				'hide_empty' => false,
				'number'     => 20,
				'orderby'    => 'name',
				'order'      => 'ASC',
			);
			if ( '' !== $value ) {
				$parts = explode( ':', $value );
				if ( count( $parts ) !== 2 || ! isset( $taxonomies[ $parts[0] ] ) || ! ctype_digit( $parts[1] ) || (int) $parts[1] < 1 ) {
					return array();
				}
				$args['taxonomy'] = $parts[0];
				$args['include']  = array( (int) $parts[1] );
			} else {
				$args['name__like'] = $query;
			}
			$terms = get_terms( $args );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( current_user_can( 'edit_term', $term->term_id ) ) {
						$items[] = self::item( $term->taxonomy . ':' . $term->term_id, $term->name, $taxonomies[ $term->taxonomy ]->labels->singular_name . ' · ' . $term->taxonomy . ' · ID ' . $term->term_id );
					}
				}
			}
		} else {
			$options = 'post_type' === $key ? $types : wp_roles()->roles;
			foreach ( $options as $id => $option ) {
				$label = 'post_type' === $key ? $option->labels->name : translate_user_role( $option['name'] );
				if ( '' !== $value ? $value === $id : ( false !== stripos( $label, $query ) || false !== stripos( $id, $query ) ) ) {
					$items[] = self::item( $id, $label, $id );
				}
			}
		}
		return array_slice( $items, 0, 20 );
	}

	private static function item( $value, $label, $detail ) {
		return array(
			'value'  => $value,
			'label'  => '' !== $label ? html_entity_decode( wp_strip_all_tags( $label ), ENT_QUOTES, get_bloginfo( 'charset' ) ) : '(Untitled)',
			'detail' => $detail,
		);
	}
}
