<?php
/** Original local examples, always inactive. @package CodeSlot */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$examples = array(
 'verification' => array( 'name' => 'Site verification placeholder', 'type' => 'html', 'location' => 'header', 'code' => '<meta name="example-verification" content="REPLACE_ME">' ),
 'notice' => array( 'name' => 'Reusable notice', 'type' => 'content', 'location' => 'shortcode', 'code' => '<aside class="site-notice">Example notice — edit before use.</aside>' ),
 'css' => array( 'name' => 'Accessible notice styling', 'type' => 'css', 'location' => 'header', 'code' => '.site-notice { padding: 1rem; border: 2px solid currentColor; }' ),
 'php' => array( 'name' => 'Footer hook example', 'type' => 'php', 'location' => 'frontend', 'code' => "add_action( 'wp_footer', static function () { echo '<!-- CodeSlot PHP example -->'; } );" ),
);

return array_merge( $examples, json_decode( file_get_contents( __DIR__ . "/recipes.json" ), true ) );
