<?php
/** Consent-gated browser pixels without account credentials or server-side APIs. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pixels {
	const PROVIDERS = array(
		'meta'      => 'Meta',
		'google'    => 'Google Ads / Analytics',
		'pinterest' => 'Pinterest',
		'tiktok'    => 'TikTok',
		'snapchat'  => 'Snapchat',
	);
	const EVENTS    = array( 'page_view', 'view_content', 'add_to_cart', 'begin_checkout', 'purchase', 'click' );

	private static $context = array();

	public static function boot() {
		add_action( 'wp_footer', array( self::class, 'output' ), 5 );
		add_action( 'woocommerce_thankyou', array( self::class, 'purchase' ) );
	}

	public static function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return new \WP_Error( 'pixels', 'Invalid pixel settings.' );
		}
		$out = array();
		foreach ( self::PROVIDERS as $key => $label ) {
			$row = isset( $input[ $key ] ) ? $input[ $key ] : array();
			if ( ! is_array( $row ) || ( isset( $row['id'] ) && ! is_string( $row['id'] ) ) ) {
				return new \WP_Error( 'pixels', 'Pixel IDs must be text.' );
			}
			$id       = isset( $row['id'] ) ? trim( $row['id'] ) : '';
			$patterns = array(
				'meta'      => '/^[0-9]{5,30}$/D',
				'google'    => '/^(G|AW|GT)-[A-Z0-9]{4,30}$/D',
				'pinterest' => '/^[0-9]{5,30}$/D',
				'tiktok'    => '/^[A-Za-z0-9]{5,40}$/D',
				'snapchat'  => '/^[a-fA-F0-9-]{36}$/D',
			);
			if ( '' !== $id && ! preg_match( $patterns[ $key ], $id ) ) {
				return new \WP_Error( 'pixels', 'Invalid ' . $label . ' public tracking ID.' );
			}
			$label_id = isset( $row['label'] ) && is_string( $row['label'] ) ? trim( $row['label'] ) : '';
			if ( '' !== $label_id && ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $label_id ) ) {
				return new \WP_Error( 'pixels', 'Invalid Google conversion label.' );
			}
			$events      = isset( $row['events'] ) && is_array( $row['events'] ) ? array_values( array_intersect( self::EVENTS, $row['events'] ) ) : array();
			$out[ $key ] = array(
				'id'      => $id,
				'enabled' => ! empty( $row['enabled'] ) && '' !== $id,
				'events'  => $events,
				'label'   => $label_id,
			);
		}
		$selector = isset( $input['click_selector'] ) && is_string( $input['click_selector'] ) ? trim( $input['click_selector'] ) : '';
		if ( strlen( $selector ) > 200 ) {
			return new \WP_Error( 'pixels', 'Click selector is limited to 200 characters.' );
		}
		$out['click_selector'] = $selector;
		return $out;
	}

	public static function purchase( $id ) {
		if ( ! function_exists( 'wc_get_order' ) || Permissions::safe() ) {
			return; }
		$order = wc_get_order( $id );
		$key   = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce order URL authorization, not a mutation.
		if ( ! $order || ! $order->is_paid() || ! hash_equals( $order->get_order_key(), $key ) || ( $order->get_customer_id() && (int) $order->get_customer_id() !== get_current_user_id() ) ) {
			return; }
		$ids = array();
		foreach ( $order->get_items() as $item ) {
			$ids[] = (string) $item->get_product_id(); }
		self::$context['purchase'] = array(
			'value'       => (float) $order->get_total(),
			'currency'    => $order->get_currency(),
			'content_ids' => $ids,
			'event_id'    => hash_hmac( 'sha256', 'codeslot-purchase-' . $id, wp_salt( 'nonce' ) ),
		);
	}

	public static function output() {
		if ( Permissions::safe() || is_admin() || is_feed() || is_robots() || is_preview() ) {
			return;
		}
		$config    = get_option( 'codeslot_pixels', array() );
		$providers = array();
		foreach ( self::PROVIDERS as $key => $label ) {
			if ( ! empty( $config[ $key ]['enabled'] ) ) {
				$providers[ $key ] = $config[ $key ];
			}
		}
		if ( ! $providers ) {
			return;
		}
		$context = self::$context;
		if ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) && function_exists( 'get_woocommerce_currency' ) ) {
			$product = wc_get_product( get_queried_object_id() );
			if ( $product ) {
				$context['view_content'] = array(
					'content_ids' => array( (string) $product->get_id() ),
					'value'       => (float) $product->get_price(),
					'currency'    => get_woocommerce_currency(),
				); }
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'is_wc_endpoint_url' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
			$context['begin_checkout'] = array(); }
		$data = array(
			'providers'      => $providers,
			'context'        => $context,
			'click_selector' => isset( $config['click_selector'] ) ? $config['click_selector'] : '',
			'site'           => substr( hash( 'sha256', home_url() ), 0, 12 ),
		);
		echo '<script id="codeslot-pixel-config" type="application/json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hex-encoded JSON data block.
		wp_enqueue_script( 'codeslot-pixels', plugins_url( 'assets/pixels.js', CODESLOT_FILE ), array(), CODESLOT_VERSION . '-' . substr( md5_file( dirname( CODESLOT_FILE ) . '/assets/pixels.js' ), 0, 8 ), true );
	}

	public static function page() {
		$config = get_option( 'codeslot_pixels', array() );
		echo '<section class="codeslot-panel"><h2>Conversion Pixels</h2><p>Free browser-side integrations. Enter public tracking IDs, never access tokens or account passwords. All providers are disabled by default. No tracking requests or scripts load until your consent manager explicitly grants marketing consent. This is not a cookie banner or automatic legal compliance.</p><p>Your consent manager must set <code>window.CodeSlotConsent = {marketing: true}</code> before footer scripts, or dispatch <code>window.dispatchEvent(new CustomEvent("codeslot:consent", {detail: {marketing: true}}))</code> after consent. Send false to stop further CodeSlot events; the consent manager must handle existing vendor cookies/scripts when consent is withdrawn.</p><p>Existing trackers can duplicate events. Base pixels and clicks need no companion. Product/add-to-cart/checkout/purchase events activate only when WooCommerce is available. Purchases require a paid order and authorized order-received URL. Browser blocking, payment redirects and closed tabs can prevent delivery; no server-side conversion API is included.</p>';
		Support::form( 'pixels' );
		foreach ( self::PROVIDERS as $key => $label ) {
			$row = isset( $config[ $key ] ) ? $config[ $key ] : array();
			echo '<fieldset class="codeslot-slot"><legend>' . esc_html( $label ) . '</legend><label><input type="checkbox" name="pixels[' . esc_attr( $key ) . '][enabled]" value="1" ' . checked( ! empty( $row['enabled'] ), true, false ) . '> Enable</label><p><label>Public tracking ID<input class="regular-text" name="pixels[' . esc_attr( $key ) . '][id]" value="' . esc_attr( isset( $row['id'] ) ? $row['id'] : '' ) . '"></label></p>';
			if ( 'google' === $key ) {
				echo '<p><label>Google Ads purchase conversion label (AW- IDs)<input class="regular-text" name="pixels[google][label]" value="' . esc_attr( isset( $row['label'] ) ? $row['label'] : '' ) . '"></label></p>';
			}
			foreach ( self::EVENTS as $event ) {
				echo '<p><label><input type="checkbox" name="pixels[' . esc_attr( $key ) . '][events][]" value="' . esc_attr( $event ) . '" ' . checked( in_array( $event, isset( $row['events'] ) ? $row['events'] : array(), true ), true, false ) . '> ' . esc_html( str_replace( '_', ' ', $event ) ) . '</label></p>';
			}
			echo '</fieldset>';
		}
		echo '<p><label>Click tracking CSS selector (example: .track-conversion)<input class="regular-text" name="click_selector" value="' . esc_attr( isset( $config['click_selector'] ) ? $config['click_selector'] : '' ) . '"></label></p><p>Click events send no link URLs, text or form values. Commerce events send product IDs, value/currency and an opaque purchase event ID, never customer contact details. Classic and block WooCommerce add-to-cart events are supported.</p>';
		submit_button( 'Save conversion pixels' );
		echo '</form></section>';
	}
}
