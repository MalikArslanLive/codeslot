<?php
// Disposable sites only. Set CS_DELETE_TEST=1 to test deletion; otherwise retain.
wp_set_current_user(1);
$delete=getenv('CS_DELETE_TEST')==='1';
$id=CodeSlot\Repository::save(['name'=>'Uninstall fixture','type'=>'text','location'=>'header','code'=>'example','categories'=>['Cleanup']]);
$post=wp_insert_post(['post_type'=>'page','post_title'=>'Uninstall slots','post_status'=>'publish']);
update_post_meta($post,'_codeslot_slots',['header'=>['enabled'=>true,'code'=>'example']]);
$term=wp_insert_term('Uninstall term '.wp_generate_password(6,false),'category');
update_term_meta($term['term_id'],'_codeslot_slots',['header'=>['enabled'=>true,'code'=>'example']]);
update_option('codeslot_global',['header'=>['enabled'=>true,'code'=>'example']]);
update_option('codeslot_settings',['delete_data'=>$delete]);
if(is_multisite()){
 foreach(get_sites(['fields'=>'ids']) as $site){if((int)$site===get_current_blog_id())continue;switch_to_blog($site);update_option('codeslot_settings',['delete_data'=>$delete]);update_option('codeslot_global',['fixture'=>'child']);restore_current_blog();}
}
define('WP_UNINSTALL_PLUGIN','codeslot/codeslot.php');
require WP_PLUGIN_DIR.'/codeslot/uninstall.php';
$assert=static function($value,$label){if(!$value)throw new RuntimeException($label);WP_CLI::line('PASS: '.$label);};
$assert((bool)get_post($id)===!$delete,'uninstall snippet retain/delete preference');
$assert((bool)get_post_meta($post,'_codeslot_slots',true)===!$delete,'uninstall post metadata preference');
$assert((bool)get_term_meta($term['term_id'],'_codeslot_slots',true)===!$delete,'uninstall term metadata preference');
$assert((bool)get_option('codeslot_global')===!$delete,'uninstall global preference');
if(is_multisite()){
 foreach(get_sites(['fields'=>'ids']) as $site){if((int)$site===get_current_blog_id())continue;switch_to_blog($site);$assert((bool)get_option('codeslot_global')===!$delete,'multisite uninstall preference site '.$site);restore_current_blog();}
}
wp_delete_post($post,true);wp_delete_term($term['term_id'],'category');
