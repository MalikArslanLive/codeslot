<?php
/** Ordered output and best-effort PHP recovery. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Runtime {
	private static $rendered = array();
	private static $cache    = array();
	private static $running  = 0;

	public static function boot() {
		foreach ( array(
			'header' => 'wp_head',
			'body'   => 'wp_body_open',
			'footer' => 'wp_footer',
		) as $slot => $hook ) {
			add_action(
				$hook,
				static function () use ( $slot ) {
					self::slot( $slot );
				},
				20
			);
		}
		add_action(
			'wp',
			static function () {
				self::php( 'frontend' );
				self::php( 'everywhere' );
			}
		);
		add_action(
			'admin_init',
			static function () {
				self::php( 'admin' );
				self::php( 'everywhere' );
			}
		);
		add_filter( 'the_content', array( self::class, 'content' ), 20 );
		add_shortcode( 'codeslot', array( self::class, 'shortcode' ) );
		add_action( 'codeslot_manual', array( self::class, 'manual' ) );
		foreach ( array( 'woocommerce_before_single_product', 'woocommerce_after_single_product', 'woocommerce_before_shop_loop', 'woocommerce_after_shop_loop' ) as $hook ) {
			add_action(
				$hook,
				static function () use ( $hook ) {
					echo self::location( $hook ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Privileged administrator-authored insertion.
				}
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Deliberately privileged code.
		}
		add_action( 'shutdown', array( self::class, 'body_warning' ) );
		register_shutdown_function( array( self::class, 'fatal' ) );
	}

	public static function clear_cache() {
		self::$cache = array();
	}

	private static function snippets( $location ) {
		if ( ! isset( self::$cache[ $location ] ) ) {
			self::$cache[ $location ] = array_filter(
				array_map(
					static function ( $post ) {
						return Repository::get( $post->ID );
					},
					Repository::active( $location )
				)
			);
		}
		return self::$cache[ $location ];
	}

	private static function frontend() {
		return ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! is_feed() && ! is_embed();
	}

	public static function slot( $slot ) {
		if ( ! self::frontend() || Permissions::safe() || isset( self::$rendered[ $slot ] ) ) {
			return;
		}
		self::$rendered[ $slot ] = true;
		$global                  = get_option( 'codeslot_global', array() );
		self::emit( $global, $slot );
		echo self::location( $slot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$individual = array();
		if ( is_singular() ) {
			$individual = get_post_meta( get_queried_object_id(), '_codeslot_slots', true );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$individual = get_term_meta( get_queried_object_id(), '_codeslot_slots', true );
		}
		self::emit( $individual, $slot );
	}

	private static function emit( $slots, $slot ) {
		if ( is_array( $slots ) && ! empty( $slots[ $slot ]['enabled'] ) && isset( $slots[ $slot ]['code'] ) ) {
			echo $slots[ $slot ]['code'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw output is the feature; writes are privileged.
		}
	}

	public static function location( $location ) {
		if ( Permissions::safe() || ! self::frontend() ) {
			return '';
		}
		$out     = '';
		$context = Conditions::context();
		foreach ( self::snippets( $location ) as $snippet ) {
			if ( 'php' !== $snippet['type'] && Conditions::matches( $snippet['conditions'], $context ) ) {
				$out .= self::format( $snippet );
			}
		}
		return $out;
	}

	private static function format( $snippet ) {
		switch ( $snippet['type'] ) {
			case 'javascript':
				return '<script>' . $snippet['code'] . '</script>';
			case 'css':
				return '<style>' . $snippet['code'] . '</style>';
			case 'text':
				return esc_html( $snippet['code'] );
			case 'shortcode':
				return do_shortcode( $snippet['code'] );
			default:
				return $snippet['code'];
		}
	}

	public static function content( $content ) {
		if ( ! self::frontend() || ! is_singular() || ! in_the_loop() || ! is_main_query() || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}
		return self::location( 'before_content' ) . $content . self::location( 'after_content' );
	}

	public static function shortcode( $attributes ) {
		static $stack = array();
		$id           = isset( $attributes['id'] ) ? absint( $attributes['id'] ) : 0;
		if ( ! $id || isset( $stack[ $id ] ) || count( $stack ) > 10 || Permissions::safe() || ! self::frontend() ) {
			return '';
		}
		$snippet = Repository::get( $id );
		if ( ! $snippet || ! $snippet['active'] || 'shortcode' !== $snippet['location'] || 'php' === $snippet['type'] || ! Conditions::matches( $snippet['conditions'] ) ) {
			return '';
		}
		$stack[ $id ] = true;
		try {
			return self::format( $snippet );
		} finally {
			unset( $stack[ $id ] );
		}
	}

	public static function manual( $name ) {
		if ( Permissions::safe() || ! did_action( is_admin() ? 'admin_init' : 'wp' ) ) {
			return;
		}
		foreach ( self::snippets( 'manual' ) as $snippet ) {
			if ( $snippet['name'] === $name && Conditions::matches( $snippet['conditions'] ) ) {
				if ( 'php' === $snippet['type'] ) {
					self::execute( $snippet );
				} elseif ( self::frontend() ) {
					echo self::format( $snippet ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}
	}

	public static function php( $location ) {
		if ( Permissions::safe() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		foreach ( self::snippets( $location ) as $snippet ) {
			if ( 'php' === $snippet['type'] && Conditions::matches( $snippet['conditions'] ) ) {
				self::execute( $snippet );
			}
		}
	}

	private static function execute( $snippet ) {
		$key = 'php:' . $snippet['id'];
		if ( isset( self::$rendered[ $key ] ) || self::$running ) {
			return;
		}
		self::$rendered[ $key ] = true;
		self::$running          = $snippet['id'];
		try {
			eval( $snippet['code'] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged,Generic.PHP.ForbiddenFunctions.Found -- Trusted administrator PHP; never a sandbox.
		} catch ( \Throwable $error ) {
			self::failure( $snippet['id'], get_class( $error ) . ': ' . $error->getMessage() );
		} finally {
			self::$running = 0;
		}
	}

	public static function failure( $id, $message ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		update_post_meta(
			$id,
			'_codeslot_error',
			array(
				'date'    => gmdate( 'c' ),
				'message' => substr( $message, 0, 2000 ),
			)
		);
		self::clear_cache();
	}

	public static function fatal() {
		$error = error_get_last();
		if ( self::$running && $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			self::failure( self::$running, 'Fatal execution failure: ' . $error['message'] );
		}
	}

	public static function body_warning() {
		if ( ! self::frontend() || ! did_action( 'wp_footer' ) || Permissions::safe() ) {
			return;
		}
		$theme = get_stylesheet();
		$value = did_action( 'wp_body_open' ) ? 'present' : 'missing';
		if ( get_option( 'codeslot_body_' . $theme ) !== $value ) {
			update_option( 'codeslot_body_' . $theme, $value, false );
		}
	}
}
