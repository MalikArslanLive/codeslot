<?php
if(!defined('CODESLOT_SAFE_MODE'))define('CODESLOT_SAFE_MODE',true);
if(!CodeSlot\Permissions::safe() || CodeSlot\Runtime::location('header')!=='')throw new RuntimeException('Constant safe mode failed');
ob_start();CodeSlot\Runtime::slot('footer');$out=ob_get_clean();
if($out!=='')throw new RuntimeException('Safe mode rendered code');
WP_CLI::success('Constant-based recovery suppresses slot/snippet execution.');
