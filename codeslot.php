<?php
/**
 * Plugin Name: CodeSlot – Header, Body & Footer Code + Snippet Manager
 * Description: Free global and individual Header, Body & Footer code with PHP, JavaScript, CSS and HTML snippets.
 * Plugin URI: https://github.com/MalikArslanLive/codeslot
 * Author: Malik Arslan
 * Author URI: https://malikarslan.com/
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: codeslot
 *
 * @package CodeSlot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'CODESLOT_VERSION', '1.0.0' );
define( 'CODESLOT_FILE', __FILE__ );
foreach ( array( 'permissions', 'conditions', 'repository', 'runtime', 'admin' ) as $codeslot_component ) {
	require_once __DIR__ . '/includes/class-' . $codeslot_component . '.php';
}
add_action( 'init', array( 'CodeSlot\\Repository', 'register' ) );
add_action(
	'plugins_loaded',
	static function () {
		\CodeSlot\Runtime::boot();
		if ( is_admin() ) {
			\CodeSlot\Admin::boot();
		}
	}
);
register_activation_hook(
	__FILE__,
	static function () {
		add_option( 'codeslot_global', array(), '', false );
		add_option( 'codeslot_settings', array( 'safe_mode' => false ), '', false );
	}
);
