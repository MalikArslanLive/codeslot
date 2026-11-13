<?php
if (!defined('ABSPATH')) { define('ABSPATH', __DIR__); }
class WP_Error {
 private $message;
 public function __construct($code, $message) {$this->message=$message;}
 public function get_error_message(){return $this->message;}
}
function is_wp_error($v){return $v instanceof WP_Error;}
function __($s,$domain=''){return $s;}
function sanitize_text_field($s){return strip_tags((string)$s);}
function sanitize_textarea_field($s){return strip_tags((string)$s);}
require __DIR__.'/../includes/class-conditions.php';
require __DIR__.'/../includes/class-repository.php';
