<?php
/** Browser tracking integrations without account credentials or server-side APIs. @package CodeSlot */
namespace CodeSlot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Pixels {
	const PROVIDERS = array(
		'meta'       => 'Meta',
		'google'     => 'Google Analytics 4',
		'google_ads' => 'Google Ads',
		'gtm'        => 'Google Tag Manager',
		'pinterest'  => 'Pinterest',
		'tiktok'     => 'TikTok',
		'snapchat'   => 'Snapchat',
	);
	const EVENTS    = array( 'page_view', 'view_content', 'add_to_cart', 'begin_checkout', 'purchase', 'click' );

	const PROVIDER_EVENTS = array(
		'meta'       => self::EVENTS,
		'google'     => self::EVENTS,
		'google_ads' => array( 'purchase' ),
		'gtm'        => self::EVENTS,
		'pinterest'  => array( 'page_view', 'add_to_cart', 'purchase' ),
		'tiktok'     => array( 'page_view', 'view_content', 'add_to_cart', 'begin_checkout', 'purchase', 'click' ),
		'snapchat'   => self::EVENTS,
	);

	const COMMERCE_EVENTS = array( 'view_content', 'add_to_cart', 'begin_checkout', 'purchase' );

	public static function commerce_available() {
		return function_exists( 'WC' ) && function_exists( 'wc_get_product' ) && class_exists( 'WooCommerce' );
	}

	private static $context = array();

	public static function boot() {
		add_action( 'wp_head', array( self::class, 'output' ), 5 );
		add_action( 'woocommerce_thankyou', array( self::class, 'purchase' ) );
		add_action( 'wp', array( self::class, 'received' ), 30 );
		add_action( 'wc_ajax_codeslot_cart_event', array( self::class, 'cart_payload' ) );
		add_action( 'woocommerce_add_to_cart', array( self::class, 'cart' ), 10, 4 );
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
			$id = isset( $row['id'] ) ? trim( $row['id'] ) : '';
			if ( ! empty( $row['enabled'] ) && '' === $id ) {
				return new \WP_Error( 'pixels', 'Enter the public tracking ID for ' . $label . ' before enabling it.' );
			}
			$patterns = array(
				'meta'       => '/^[0-9]{5,30}$/D',
				'google'     => '/^(G|GT)-[A-Z0-9]{4,30}$/D',
				'google_ads' => '/^AW-[0-9]{4,30}$/D',
				'gtm'        => '/^GTM-[A-Z0-9]{4,20}$/D',
				'pinterest'  => '/^[0-9]{5,30}$/D',
				'tiktok'     => '/^[A-Za-z0-9]{5,40}$/D',
				'snapchat'   => '/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/D',
			);
			if ( '' !== $id && ! preg_match( $patterns[ $key ], $id ) ) {
				return new \WP_Error( 'pixels', 'Invalid ' . $label . ' public tracking ID.' );
			}
			$label_id = isset( $row['label'] ) && is_string( $row['label'] ) ? trim( $row['label'] ) : '';
			if ( '' !== $label_id && ! preg_match( '/^[A-Za-z0-9_-]{1,100}$/D', $label_id ) ) {
				return new \WP_Error( 'pixels', 'Invalid Google conversion label.' );
			}
			$events = isset( $row['events'] ) && is_array( $row['events'] ) ? array_values( array_intersect( self::PROVIDER_EVENTS[ $key ], array_filter( $row['events'], 'is_string' ) ) ) : array();
			if ( 'google_ads' === $key && ! empty( $row['enabled'] ) && in_array( 'purchase', $events, true ) && '' === $label_id ) {
				return new \WP_Error( 'pixels', 'Enter the Google Ads purchase conversion label, or deselect Purchase.' ); }
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
		$out['click_selector']  = $selector;
		$out['require_consent'] = ! empty( $input['require_consent'] );
		return $out;
	}

	public static function cart( $cart_key, $product_id, $quantity, $variation_id ) {
		if ( Permissions::safe() || ! function_exists( 'WC' ) || ! function_exists( 'wc_get_product' ) || ! function_exists( 'get_woocommerce_currency' ) || ! WC()->session ) {
			return; }
		$config = get_option( 'codeslot_pixels', array() );
		$needed = false;
		foreach ( self::PROVIDERS as $key => $label ) {
			if ( ! empty( $config[ $key ]['enabled'] ) && in_array( 'add_to_cart', isset( $config[ $key ]['events'] ) ? $config[ $key ]['events'] : array(), true ) ) {
				$needed = true; }
		}
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $needed || ! $product ) {
			return; }
		WC()->session->set(
			'codeslot_cart_event',
			array(
				'content_ids' => array( (string) $product_id ),
				'items'       => array(
					array(
						'item_id'  => (string) $product_id,
						'price'    => (float) $product->get_price(),
						'quantity' => (float) $quantity,
					),
				),
				'value'       => (float) $product->get_price() * (float) $quantity,
				'currency'    => get_woocommerce_currency(),
				'event_id'    => wp_generate_uuid4(),
			)
		);
	}

	/** Read only the current WooCommerce session's most recent cart trigger. */
	public static function cart_payload() {
		nocache_headers();
		if ( Permissions::safe() || ! function_exists( 'WC' ) || ! WC()->session ) {
			wp_send_json( array() );
		}
		$event = WC()->session->get( 'codeslot_cart_event' );
		wp_send_json( is_array( $event ) ? $event : array() );
	}

	public static function received() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			self::purchase( absint( get_query_var( 'order-received' ) ) );
		}
	}

	public static function purchase( $id ) {
		if ( ! function_exists( 'wc_get_order' ) || Permissions::safe() ) {
			return; }
		$order = wc_get_order( $id );
		$key   = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce order URL authorization, not a mutation.
		if ( ! $order || ! $order->is_paid() || ! hash_equals( $order->get_order_key(), $key ) || ( $order->get_customer_id() && (int) $order->get_customer_id() !== get_current_user_id() ) ) {
			return; }
		$ids   = array();
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$ids[]    = (string) $item->get_product_id();
			$quantity = (float) $item->get_quantity();
			$items[]  = array(
				'item_id'  => (string) $item->get_product_id(),
				'price'    => $quantity > 0 ? (float) $item->get_total() / $quantity : 0,
				'quantity' => $quantity,
			);
		}
		self::$context['purchase'] = array(
			'value'       => (float) $order->get_total(),
			'currency'    => $order->get_currency(),
			'content_ids' => $ids,
			'items'       => $items,
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
		if ( function_exists( 'WC' ) && WC()->session ) {
			$cart_event = WC()->session->get( 'codeslot_cart_event' );
			if ( is_array( $cart_event ) ) {
				$context['add_to_cart'] = $cart_event;
				WC()->session->__unset( 'codeslot_cart_event' ); }
		}
		if ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) && function_exists( 'get_woocommerce_currency' ) ) {
			$product = wc_get_product( get_queried_object_id() );
			if ( $product ) {
				$context['view_content'] = array(
					'content_ids' => array( (string) $product->get_id() ),
					'items'       => array(
						array(
							'item_id'  => (string) $product->get_id(),
							'price'    => (float) $product->get_price(),
							'quantity' => 1,
						),
					),
					'value'       => (float) $product->get_price(),
					'currency'    => get_woocommerce_currency(),
				); }
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'is_wc_endpoint_url' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
			$checkout = array();
			if ( function_exists( 'WC' ) && WC()->cart && function_exists( 'get_woocommerce_currency' ) ) {
				$checkout['value']       = (float) WC()->cart->get_total( 'edit' );
				$checkout['currency']    = get_woocommerce_currency();
				$checkout['content_ids'] = array();
				$checkout['items']       = array();
				foreach ( WC()->cart->get_cart() as $item ) {
					$checkout['content_ids'][] = (string) $item['product_id'];
					$quantity                  = (float) $item['quantity'];
					$checkout['items'][]       = array(
						'item_id'  => (string) $item['product_id'],
						'price'    => $quantity > 0 ? (float) $item['line_total'] / $quantity : 0,
						'quantity' => $quantity,
					); }
			}
			$context['begin_checkout'] = $checkout; }
		$data = array(
			'providers'       => $providers,
			'cart_url'        => function_exists( 'WC' ) ? add_query_arg( 'wc-ajax', 'codeslot_cart_event', home_url( '/' ) ) : '',
			'require_consent' => ! empty( $config['require_consent'] ),
			'context'         => $context,
			'click_selector'  => isset( $config['click_selector'] ) ? $config['click_selector'] : '',
			'site'            => substr( hash( 'sha256', home_url() ), 0, 12 ),
		);
		echo '<script id="codeslot-pixel-config" type="application/json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hex-encoded JSON data block.
		wp_enqueue_script( 'codeslot-pixels', plugins_url( 'assets/pixels.js', CODESLOT_FILE ), array(), CODESLOT_VERSION . '-' . substr( md5_file( dirname( CODESLOT_FILE ) . '/assets/pixels.js' ), 0, 8 ), false );
	}

	public static function page() {
		$config   = get_option( 'codeslot_pixels', array() );
		$feedback = get_transient( 'codeslot_feedback_' . get_current_user_id() );
		if ( is_array( $feedback ) && 'codeslot-pixels' === $feedback['page'] ) {
			$config = self::draft( $feedback['values'] );
			delete_transient( 'codeslot_feedback_' . get_current_user_id() );
		}

		$examples = array(
			'meta'       => '123456789012345',
			'google'     => 'G-ABC1234567',
			'google_ads' => 'AW-1234567890',
			'gtm'        => 'GTM-ABC1234',
			'pinterest'  => '1234567890123',
			'tiktok'     => 'C1234567890ABCDEFGHIJ',
			'snapchat'   => '12345678-abcd-1234-abcd-123456789012',
		);
		$help     = array(
			'meta'       => 'Meta Events Manager → Data sources → your Pixel ID.',
			'google'     => 'Use your GA4 measurement ID (G-) or connected Google tag ID (GT-). Configure Ads independently in its own card; both can run together.',
			'google_ads' => 'Use your Google Ads tag ID (AW-). The base Google Ads tag initializes automatically. Purchase conversions need the conversion label from the matching conversion action.',
			'gtm'        => 'Use the Container ID from Google Tag Manager. Configure and publish your tags and custom-event triggers inside that container. CodeSlot pushes selected codeslot_* events to dataLayer.',
			'pinterest'  => 'Use your Pinterest Ads conversion Tag ID.',
			'tiktok'     => 'Use the Pixel ID from TikTok Events Manager.',
			'snapchat'   => 'Use your Snap Pixel ID from Events Manager.',
		);
		$labels   = array(
			'page_view'      => 'Page view',
			'view_content'   => 'View content',
			'add_to_cart'    => 'Add to cart',
			'begin_checkout' => 'Begin checkout',
			'purchase'       => 'Purchase',
			'click'          => 'Click',
		);
		echo '<section class="codeslot-panel"><h2>Conversion Pixels</h2><p>Enter a public tracking ID and enable its integration to install the base tag automatically. Choose additional events below. Each provider is disabled until you enable it.</p>';
		Support::form( 'pixels' );
		echo '<section class="codeslot-config-section"><h3>Loading and consent</h3><label><input type="checkbox" name="require_consent" value="1" ' . checked( ! empty( $config['require_consent'] ), true, false ) . '> Wait for marketing consent before loading tags</label><p class="description">Off: enabled base tags load automatically from the page head. On: your consent solution must grant marketing consent before tags load. Choose the mode appropriate for your website and visitors.</p><details class="codeslot-pixel-guide"><summary>Consent integration and tracking guide</summary><p>When consent mode is selected, set <code>window.CodeSlotConsent = {marketing: true}</code> before the head loader runs, or dispatch <code>window.dispatchEvent(new CustomEvent("codeslot:consent", {detail: {marketing: true}}))</code> after acceptance. A false signal stops new CodeSlot events; your consent solution must also manage existing vendor scripts and cookies.</p><p>Base tags initialize even when no additional events are selected. GA4 Page view sends page_view; Meta sends PageView. Google Ads initializes its base tag and uses a conversion label for Purchase. GTM loads your container; configure and publish its tags/triggers in GTM. GTM event selections push codeslot_* events to dataLayer.</p><p>WooCommerce provides product, cart, checkout and authorized paid-order data. Click uses the selector below. Platform automatic tracking can send additional events independently of these checkboxes. Avoid configuring the same tracking twice, such as direct GA4 plus GA4 in GTM. Verify receipt with the provider testing tools.</p></details></section>';
		echo '<div class="codeslot-pixel-grid">';
		foreach ( self::PROVIDERS as $key => $label ) {
			$row    = isset( $config[ $key ] ) ? $config[ $key ] : array();
			$events = isset( $row['events'] ) ? $row['events'] : ( in_array( 'page_view', self::PROVIDER_EVENTS[ $key ], true ) ? array( 'page_view' ) : array() );
			echo '<fieldset class="codeslot-pixel-card"><legend>' . esc_html( $label ) . '</legend><div class="codeslot-pixel-heading"><span class="codeslot-pixel-state">' . esc_html( ! empty( $row['enabled'] ) ? ( ! empty( $config['require_consent'] ) ? 'Enabled · consent required' : 'Enabled · automatic base code' ) : 'Disabled' ) . '</span><label class="codeslot-toggle-label"><input type="checkbox" name="pixels[' . esc_attr( $key ) . '][enabled]" value="1" ' . checked( ! empty( $row['enabled'] ), true, false ) . '> Enable</label></div>';
			echo '<p class="codeslot-field"><label for="codeslot-pixel-' . esc_attr( $key ) . '">Public tracking ID</label><input id="codeslot-pixel-' . esc_attr( $key ) . '" class="regular-text" name="pixels[' . esc_attr( $key ) . '][id]" placeholder="' . esc_attr( $examples[ $key ] ) . '" value="' . esc_attr( isset( $row['id'] ) ? $row['id'] : '' ) . '" autocomplete="off"><span class="description">' . esc_html( $help[ $key ] ) . '</span></p>';
			if ( 'google_ads' === $key ) {
				echo '<p class="codeslot-field"><label for="codeslot-google-label">Google Ads purchase conversion label</label><input id="codeslot-google-label" class="regular-text" name="pixels[google_ads][label]" placeholder="AbCdEFghijk123456" value="' . esc_attr( isset( $row['label'] ) ? $row['label'] : '' ) . '"><span class="description">Required for AW- purchase conversions. Copy the label from the Google Ads conversion action.</span></p>';
			}
			echo '<h3>Events</h3><div class="codeslot-event-grid">';
			foreach ( self::PROVIDER_EVENTS[ $key ] as $event ) {
				$unavailable = ! self::commerce_available() && in_array( $event, self::COMMERCE_EVENTS, true );
				if ( $unavailable && in_array( $event, $events, true ) ) {
					echo '<input type="hidden" name="pixels[' . esc_attr( $key ) . '][events][]" value="' . esc_attr( $event ) . '">';
				}
				echo '<label' . ( $unavailable ? ' class="codeslot-event-unavailable" title="Install and activate WooCommerce to use this event"' : '' ) . '><input type="checkbox" name="pixels[' . esc_attr( $key ) . '][events][]" value="' . esc_attr( $event ) . '" ' . checked( in_array( $event, $events, true ), true, false ) . disabled( $unavailable, true, false ) . ( $unavailable ? ' aria-describedby="codeslot-woocommerce-' . esc_attr( $key ) . '"' : '' ) . '> ' . esc_html( ( 'click' === $event ? ( 'tiktok' === $key ? 'Click button (ClickButton)' : ( 'snapchat' === $key ? 'Click (CUSTOM_EVENT_1)' : 'Click (custom event)' ) ) : $labels[ $event ] ) ) . '</label>';
			}
			echo '</div>';
			if ( ! self::commerce_available() ) {
				echo '<p id="codeslot-woocommerce-' . esc_attr( $key ) . '" class="description codeslot-woocommerce-required">Install and activate WooCommerce to use the disabled product/cart/checkout/purchase events. Saved selections are retained.</p>'; }
			echo '<p class="description">Product, cart, checkout and purchase triggers require WooCommerce. Selected events are sent when their website trigger occurs. Base tags and platform automatic measurements are independent.</p></fieldset>';
		}
		echo '</div><section class="codeslot-config-section"><h3>Click tracking</h3><label for="codeslot-click-selector">CSS selector</label><input id="codeslot-click-selector" class="regular-text" name="click_selector" placeholder=".track-conversion" value="' . esc_attr( isset( $config['click_selector'] ) ? $config['click_selector'] : '' ) . '"><p class="description">Enable Click on a provider above and add this class/selector to the buttons you want to track. Click events contain no link text, URLs or form values.</p></section>';
		submit_button( 'Save conversion pixels' );
		echo '</form></section>';
	}

	/** Bounded safe redisplay of invalid form input; never persisted as active settings. */
	public static function draft( $input ) {
		$out = array(
			'require_consent' => ! empty( $input['require_consent'] ),
			'click_selector'  => isset( $input['click_selector'] ) && is_string( $input['click_selector'] ) ? substr( sanitize_text_field( $input['click_selector'] ), 0, 200 ) : '',
		);
		foreach ( self::PROVIDERS as $key => $label ) {
			$row         = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
			$out[ $key ] = array(
				'id'      => isset( $row['id'] ) && is_string( $row['id'] ) ? substr( sanitize_text_field( $row['id'] ), 0, 100 ) : '',
				'label'   => isset( $row['label'] ) && is_string( $row['label'] ) ? substr( sanitize_text_field( $row['label'] ), 0, 100 ) : '',
				'enabled' => ! empty( $row['enabled'] ),
				'events'  => isset( $row['events'] ) && is_array( $row['events'] ) ? array_values( array_intersect( self::PROVIDER_EVENTS[ $key ], array_filter( $row['events'], 'is_string' ) ) ) : array(),
			);
		}
		return $out;
	}
}
