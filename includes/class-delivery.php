<?php
/** Device/schedule restrictions and opt-in visual snippet delivery. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Delivery {
	public static function eligible( $snippet ) {
		$now = time();
		if ( ( ! empty( $snippet['starts'] ) && $now < $snippet['starts'] ) || ( ! empty( $snippet['ends'] ) && $now >= $snippet['ends'] ) ) {
			return false; }
		$device = isset( $snippet['device'] ) ? $snippet['device'] : 'any';
		return 'any' === $device || ( 'mobile' === $device ? wp_is_mobile() : ! wp_is_mobile() );
	}

	public static function code( $snippet ) {
		$code = $snippet['code'];
		if ( ! empty( $snippet['compact'] ) && 'javascript' === $snippet['type'] ) {
			require_once __DIR__ . '/minifier/Minifier.php';
			try {
				$code = Minifier\Minifier::minify( $code );
			} catch ( \Throwable $error ) {
				return $snippet['code']; }
		}
		return $code;
	}

	/** Static JS/CSS only. The source remains public like any frontend asset. */
	public static function file_markup( $snippet ) {
		if ( empty( $snippet['load_file'] ) || ! in_array( $snippet['type'], array( 'javascript', 'css' ), true ) ) {
			return ''; }
		$code = self::code( $snippet );
		if ( preg_match( '/^\s*<(script|style)\b/i', $code ) ) {
			return ''; }
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return ''; }
		$extension = 'css' === $snippet['type'] ? 'css' : 'js';
		$name      = 'codeslot-' . hash( 'sha256', $code ) . '.' . $extension;
		$directory = $uploads['basedir'] . '/codeslot-assets';
		$path      = $directory . '/' . $name;
		if ( ! file_exists( $path ) && ( ! wp_mkdir_p( $directory ) || false === file_put_contents( $path, $code, LOCK_EX ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Fixed static .js/.css path under WordPress uploads, never PHP or administrator-supplied filenames.
			return '';
		}
		$url    = set_url_scheme( $uploads['baseurl'] . '/codeslot-assets/' . $name );
		$handle = 'codeslot-snippet-' . absint( $snippet['id'] ) . '-' . substr( hash( 'sha256', $code ), 0, 12 );
		ob_start();
		if ( 'css' === $extension ) {
			wp_enqueue_style( $handle, $url, array(), CODESLOT_VERSION );
			wp_print_styles( array( $handle ) );
		} else {
			wp_enqueue_script( $handle, $url, array(), CODESLOT_VERSION, true );
			wp_print_scripts( array( $handle ) );
		}
		$html = ob_get_clean();
		return $html ? $html : '<!-- CodeSlot asset already loaded -->';
	}

	public static function locations() {
		return array(
			'Global'        => array(
				'header'       => 'Site-wide Header',
				'body'         => 'Site-wide Body',
				'footer'       => 'Site-wide Footer',
				'admin_header' => 'Admin Header',
				'admin_footer' => 'Admin Footer',
			),
			'Page-specific' => array(
				'before_content'   => 'Before main content',
				'after_content'    => 'After main content',
				'before_paragraph' => 'Before paragraph',
				'after_paragraph'  => 'After paragraph',
			),
			'eCommerce'     => array(
				'woocommerce_before_single_product' => 'Before product',
				'woocommerce_after_single_product'  => 'After product',
				'woocommerce_before_shop_loop'      => 'Before shop loop',
				'woocommerce_after_shop_loop'       => 'After shop loop',
			),
			'PHP execution' => array(
				'frontend'   => 'Frontend Only',
				'admin'      => 'Admin Only',
				'everywhere' => 'Run Everywhere',
				'bootstrap'  => 'Early hooks / all requests',
				'once'       => 'Run Once',
				'manual'     => 'On Demand / Manual',
				'shortcode'  => 'Shortcode',
			),
			'CSS selector'  => array(
				'selector_before'  => 'Before HTML element',
				'selector_after'   => 'After HTML element',
				'selector_prepend' => 'Start of HTML element',
				'selector_append'  => 'End of HTML element',
				'selector_replace' => 'Replace HTML element',
			),
		);
	}

	public static function selector_output() {
		static $rendered = false;
		if ( Permissions::safe() || is_admin() || is_feed() || is_preview() ) {
			return; }
		if ( $rendered ) {
			return; }
		$rendered = true;
		$rows     = array();
		foreach ( array( 'before', 'after', 'prepend', 'append', 'replace' ) as $position ) {
			foreach ( Repository::active( 'selector_' . $position ) as $post ) {
				$s = Repository::get( $post->ID );
				if ( $s && $s['active'] && self::eligible( $s ) && Conditions::matches( $s['conditions'] ) ) {
					$rows[] = array(
						'position' => $position,
						'priority' => $s['priority'],
						'id'       => $s['id'],
						'selector' => $s['selector'],
						'html'     => Runtime::visual( $s ),
					); }
			}
		}
		if ( ! $rows ) {
			return; }
		usort(
			$rows,
			static function ( $a, $b ) {
				return $a['priority'] === $b['priority'] ? $a['id'] <=> $b['id'] : $a['priority'] <=> $b['priority'];
			}
		);
		echo '<script id="codeslot-selector-config" type="application/json">' . wp_json_encode( $rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hex-encoded JSON, privileged administrator-authored code.
		wp_enqueue_script( 'codeslot-selectors', plugins_url( 'assets/selectors.js', CODESLOT_FILE ), array(), CODESLOT_VERSION . '-' . substr( hash_file( 'sha256', dirname( CODESLOT_FILE ) . '/assets/selectors.js' ), 0, 12 ), true );
	}
}
