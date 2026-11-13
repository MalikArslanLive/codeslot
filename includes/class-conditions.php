<?php
/** Validated inclusion/exclusion expressions. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Conditions {
	public static function keys() {
		return array( 'entire_site', 'frontend', 'admin', 'front_page', 'home', 'singular', 'post_type', 'term', 'archive', 'search', '404', 'logged_in', 'role', 'url', 'mobile', 'shop', 'product', 'cart', 'checkout', 'account' );
	}

	public static function validate( $input ) {
		if ( ! is_array( $input ) || count( $input ) > 2 || array_diff( array_keys( $input ), array( 'include', 'exclude' ) ) ) {
			return new \WP_Error( 'conditions', __( 'Invalid condition expression.', 'codeslot' ) );
		}
		$out = array(
			'include' => array(),
			'exclude' => array(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Expression data, not a database query.
		);
		foreach ( $out as $kind => $unused ) {
			$groups = isset( $input[ $kind ] ) ? $input[ $kind ] : array();
			if ( ! is_array( $groups ) || count( $groups ) > 20 ) {
				return new \WP_Error( 'conditions', __( 'Too many condition groups.', 'codeslot' ) );
			}
			foreach ( $groups as $group ) {
				if ( ! is_array( $group ) || ! count( $group ) || count( $group ) > 20 ) {
					return new \WP_Error( 'conditions', __( 'Each group needs 1–20 rules.', 'codeslot' ) );
				}
				$rules = array();
				foreach ( $group as $rule ) {
					if ( ! is_array( $rule ) || ! isset( $rule['key'] ) || ! in_array( $rule['key'], self::keys(), true ) || ( isset( $rule['value'] ) && ! is_scalar( $rule['value'] ) ) || ( isset( $rule['op'] ) && ! in_array( $rule['op'], array( 'is', 'not' ), true ) ) ) {
						return new \WP_Error( 'conditions', __( 'Unknown or malformed condition rule.', 'codeslot' ) );
					}
					$rules[] = array(
						'key'   => $rule['key'],
						'op'    => isset( $rule['op'] ) ? $rule['op'] : 'is',
						'value' => isset( $rule['value'] ) ? (string) $rule['value'] : '',
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
		$context = null === $context ? self::context() : $context;
		return ( ! $valid['include'] || self::groups( $valid['include'], $context ) ) && ! self::groups( $valid['exclude'], $context );
	}

	private static function groups( $groups, $context ) {
		foreach ( $groups as $group ) {
			$match = true;
			foreach ( $group as $rule ) {
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
			'product'     => function_exists( 'is_product' ) && is_product(),
			'cart'        => function_exists( 'is_cart' ) && is_cart(),
			'checkout'    => function_exists( 'is_checkout' ) && is_checkout(),
			'account'     => function_exists( 'is_account_page' ) && is_account_page(),
		);
	}
}
