<?php
/** Validated inclusion/exclusion expressions. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Conditions {
	/** Rule metadata is shared by the validator and the editor. */
	public static function definitions() {
		static $rules = array(
			'entire_site' => array(
				'label' => 'Entire site',
				'help'  => 'No extra restriction at the selected insertion location.',
			),
			'frontend'    => array(
				'label' => 'Website visitors',
				'help'  => 'Visitor-facing pages, outside the WordPress dashboard.',
			),
			'admin'       => array(
				'label' => 'WordPress dashboard',
				'help'  => 'Requires an admin insertion location for HTML, CSS or JavaScript.',
			),
			'front_page'  => array(
				'label' => 'Front page',
				'help'  => 'Your main website landing page. Check Settings → Reading.',
			),
			'home'        => array(
				'label' => 'Blog Posts Page',
				'help'  => 'Your posts listing page. It may also be the front page, depending on Settings → Reading.',
			),
			'singular'    => array(
				'label' => 'Specific post or page',
				'kind'  => 'search',
				'help'  => 'Search by title to choose a post, page, product or other public content item.',
			),
			'post_type'   => array(
				'label' => 'Post type',
				'kind'  => 'search',
				'help'  => 'Search a registered public type, such as Posts, Pages or Products. Matches individual items of that type.',
			),
			'term'        => array(
				'label' => 'Category or tag',
				'kind'  => 'search',
				'help'  => 'Search a category, tag or other public taxonomy term. Matches assigned items and its archive.',
			),
			'archive'     => array(
				'label' => 'Archive page',
				'help'  => 'Category, tag, author, date and post-type archives.',
			),
			'search'      => array(
				'label' => 'Search results',
				'help'  => 'The website search results page.',
			),
			'404'         => array(
				'label' => 'Page not found (404)',
				'help'  => 'Requests that WordPress identifies as a missing page.',
			),
			'logged_in'   => array(
				'label' => 'Logged-in visitor',
				'help'  => 'Choose “is not” for visitors who are logged out.',
			),
			'role'        => array(
				'label' => 'Visitor role',
				'kind'  => 'search',
				'help'  => 'Search a registered WordPress role, including roles added by plugins.',
			),
			'url'         => array(
				'label' => 'URL path',
				'kind'  => 'path',
				'help'  => 'Enter only the path after your domain, for example /contact/. Match the exact capitalization and final slash of the page URL. Do not enter the domain, query string or fragment.',
			),
			'mobile'      => array(
				'label' => 'Mobile visitor',
				'help'  => 'Uses WordPress mobile detection. Your page cache must vary by device.',
			),
			'shop'        => array(
				'label' => 'WooCommerce Shop',
				'woo'   => true,
				'help'  => 'The main WooCommerce shop page.',
			),
			'cart'        => array(
				'label' => 'WooCommerce Cart',
				'woo'   => true,
				'help'  => 'The WooCommerce cart page.',
			),
			'checkout'    => array(
				'label' => 'WooCommerce Checkout',
				'woo'   => true,
				'help'  => 'Checkout endpoints can include the order-received page. Use URL path for a narrower match.',
			),
			'account'     => array(
				'label' => 'WooCommerce My Account',
				'woo'   => true,
				'help'  => 'The My Account page and its endpoints.',
			),
		);
		return $rules;
	}

	public static function keys() {
		return array_map( 'strval', array_keys( self::definitions() ) );
	}

	public static function validate( $input ) {
		if ( ! is_array( $input ) || count( $input ) > 2 || array_diff( array_keys( $input ), array( 'include', 'exclude' ) ) ) {
			return new \WP_Error( 'conditions', __( 'Invalid condition expression.', 'codeslot-header-body-footer-code-snippet-manager' ) );
		}
		$out = array(
			'include' => array(),
			'exclude' => array(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Expression data, not a database query.
		);
		foreach ( $out as $kind => $unused ) {
			$groups = isset( $input[ $kind ] ) ? $input[ $kind ] : array();
			if ( ! is_array( $groups ) || count( $groups ) > 20 ) {
				return new \WP_Error( 'conditions', __( 'Too many condition groups.', 'codeslot-header-body-footer-code-snippet-manager' ) );
			}
			foreach ( $groups as $group ) {
				if ( ! is_array( $group ) || ! count( $group ) || count( $group ) > 20 ) {
					return new \WP_Error( 'conditions', __( 'Each group needs 1–20 rules.', 'codeslot-header-body-footer-code-snippet-manager' ) );
				}
				$rules = array();
				foreach ( $group as $rule ) {
					if ( ! is_array( $rule ) || ! isset( $rule['key'] ) || ! in_array( $rule['key'], self::keys(), true ) || ( isset( $rule['value'] ) && ! is_scalar( $rule['value'] ) ) || ( isset( $rule['op'] ) && ! in_array( $rule['op'], array( 'is', 'not' ), true ) ) ) {
						return new \WP_Error( 'conditions', __( 'Unknown or malformed condition rule.', 'codeslot-header-body-footer-code-snippet-manager' ) );
					}
					$key         = $rule['key'];
					$value       = isset( $rule['value'] ) ? trim( (string) $rule['value'] ) : '';
					$definitions = self::definitions();
					if ( ! isset( $definitions[ $key ]['kind'] ) ) {
						$value = '';
					} elseif ( ( 'singular' === $key && ! preg_match( '/^[1-9][0-9]*$/D', $value ) ) || ( 'term' === $key && ! preg_match( '/^[a-z0-9_-]+:[1-9][0-9]*$/D', $value ) ) || ( in_array( $key, array( 'post_type', 'role' ), true ) && ! preg_match( '/^[a-z0-9_-]+$/D', $value ) ) || ( 'url' === $key && ( ! preg_match( '~^/(?!/)[^?#\s]*$~D', $value ) || strlen( $value ) > 2048 ) ) ) {
						return new \WP_Error( 'conditions', 'Choose a valid condition value. URL paths start with /; search fields require a selected result.' );
					}
					$rules[] = array(
						'key'   => $rule['key'],
						'op'    => isset( $rule['op'] ) ? $rule['op'] : 'is',
						'value' => $value,
					);
				}
				$out[ $kind ][] = $rules;
			}
		}
		return $out;
	}

	public static function matches( $conditions, $context = null ) {
		$valid = self::validate( $conditions );
		if ( is_wp_error( $valid ) ) {
			return false;
		}
		if ( self::missing( $valid ) ) {
			return false;
		}
		$context = null === $context ? self::context() : $context;
		return ( ! $valid['include'] || self::groups( $valid['include'], $context ) ) && ! self::groups( $valid['exclude'], $context );
	}

	/** A missing reference in Include OR Exclude pauses the whole expression. */
	public static function missing( $conditions ) {
		$conditions = self::validate( $conditions );
		if ( is_wp_error( $conditions ) ) {
			return array( 'Invalid saved conditions: ' . $conditions->get_error_message() );
		}

		$issues      = array();
		$definitions = self::definitions();
		foreach ( array( 'include', 'exclude' ) as $kind ) {
			foreach ( isset( $conditions[ $kind ] ) && is_array( $conditions[ $kind ] ) ? $conditions[ $kind ] : array() as $group ) {
				foreach ( is_array( $group ) ? $group : array() as $rule ) {
					$key   = isset( $rule['key'] ) ? $rule['key'] : '';
					$value = isset( $rule['value'] ) ? (string) $rule['value'] : '';
					if ( ! empty( $definitions[ $key ]['woo'] ) && ! Pixels::commerce_available() ) {
						$issues[] = 'Requires active WooCommerce.';
					} elseif ( 'post_type' === $key && function_exists( 'get_post_type_object' ) ) {
						$type = get_post_type_object( $value );
						if ( ! $type || ! $type->public ) {
							$issues[] = 'Required public post type unavailable: ' . $value . '.'; }
					} elseif ( 'singular' === $key && function_exists( 'get_post' ) ) {
						$post = get_post( (int) $value );
						$type = $post ? get_post_type_object( $post->post_type ) : null;
						if ( ! $post || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) || ! $type || ! $type->public ) {
							$issues[] = 'Selected content item or its public post type unavailable (ID ' . $value . ').'; }
					} elseif ( 'term' === $key && function_exists( 'get_taxonomy' ) ) {
						$parts    = explode( ':', $value );
						$taxonomy = get_taxonomy( $parts[0] );
						$term     = $taxonomy && isset( $parts[1] ) ? get_term( (int) $parts[1], $parts[0] ) : null;
						if ( ! $taxonomy || ! $taxonomy->public || ! $term || is_wp_error( $term ) ) {
							$issues[] = 'Selected taxonomy or term unavailable: ' . $value . '.'; }
					} elseif ( 'role' === $key && function_exists( 'get_role' ) && ! get_role( $value ) ) {
						$issues[] = 'Required visitor role unavailable: ' . $value . '.';
					}
				}
			}
		}
		return array_values( array_unique( $issues ) );
	}

	private static function groups( $groups, $context ) {
		foreach ( $groups as $group ) {
			$match = true;
			foreach ( $group as $rule ) {
				$definitions = self::definitions();
				if ( ! empty( $definitions[ $rule['key'] ]['woo'] ) && ! Pixels::commerce_available() ) {
					$match = false;
					break;
				}
				$value = self::rule( $rule['key'], $rule['value'], $context );
				$match = $match && ( 'not' === $rule['op'] ? ! $value : $value );
			}
			if ( $match ) {
				return true;
			}
		}
		return false;
	}

	private static function rule( $key, $value, $context ) {
		if ( ! array_key_exists( $key, $context ) ) {
			return false;
		}
		$actual = $context[ $key ];
		if ( is_bool( $actual ) ) {
			return $actual;
		}
		if ( is_array( $actual ) ) {
			return in_array( $value, array_map( 'strval', $actual ), true );
		}
		return (string) $actual === $value;
	}

	public static function context() {
		$user = wp_get_current_user();
		$ids  = array();
		if ( is_singular() ) {
			$object_id = get_queried_object_id();
			foreach ( get_object_taxonomies( get_post_type( $object_id ) ) as $taxonomy ) {
				$terms = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term_id ) {
						$ids[] = $taxonomy . ':' . $term_id;
					}
				}
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term  = get_queried_object();
			$ids[] = $term->taxonomy . ':' . $term->term_id;
		}
		// Match exact site-relative path; query strings are deliberately ignored.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return array(
			'entire_site' => true,
			'frontend'    => ! is_admin(),
			'admin'       => is_admin(),
			'front_page'  => is_front_page(),
			'home'        => is_home(),
			'singular'    => is_singular() ? (string) get_queried_object_id() : '',
			'post_type'   => is_singular() ? get_post_type( get_queried_object_id() ) : '',
			'term'        => $ids,
			'archive'     => is_archive(),
			'search'      => is_search(),
			'404'         => is_404(),
			'logged_in'   => is_user_logged_in(),
			'role'        => $user->roles,
			'url'         => (string) wp_parse_url( $uri, PHP_URL_PATH ),
			'mobile'      => wp_is_mobile(),
			'shop'        => function_exists( 'is_shop' ) && is_shop(),
			'cart'        => function_exists( 'is_cart' ) && is_cart(),
			'checkout'    => function_exists( 'is_checkout' ) && is_checkout(),
			'account'     => function_exists( 'is_account_page' ) && is_account_page(),
		);
	}
}
