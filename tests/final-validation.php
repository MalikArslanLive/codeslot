<?php
/** Final slot validation regressions against real WordPress metadata. */
wp_set_current_user(1);
$post=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'Final slot audit']);
$term=wp_insert_term('Final slot audit '.uniqid(),'category');$term_id=$term['term_id'];$original_post=$_POST;
$old=['header'=>['code'=>'<!-- retained -->','enabled'=>true]];
try {
 update_post_meta($post,'_codeslot_slots',$old);update_term_meta($term_id,'_codeslot_slots',$old);
 foreach([str_repeat('é',524289),['bad']] as $invalid){
  $_POST=['codeslot_nonce'=>wp_create_nonce('codeslot_slots'),'codeslot_term_nonce'=>wp_create_nonce('codeslot_term'),'codeslot_slots'=>['header'=>['code'=>$invalid]]];
  CodeSlot\Admin::save_post($post,get_post($post));CodeSlot\Admin::save_term($term_id);
  if(get_post_meta($post,'_codeslot_slots',true)!==$old||get_term_meta($term_id,'_codeslot_slots',true)!==$old)throw new RuntimeException('Invalid input changed existing slots');
 }
 $code=str_repeat('a',1048576);$_POST['codeslot_slots']=['header'=>['code'=>$code]];
 CodeSlot\Admin::save_post($post,get_post($post));CodeSlot\Admin::save_term($term_id);
 if(get_post_meta($post,'_codeslot_slots',true)['header']['code']!==$code||get_term_meta($term_id,'_codeslot_slots',true)['header']['code']!==$code)throw new RuntimeException('Exact byte boundary rejected');
 WP_CLI::success('6 post/term validation assertions passed: invalid text/shape retained old code; exact 1 MiB accepted.');
} finally {$_POST=$original_post;wp_delete_post($post,true);wp_delete_term($term_id,'category');delete_transient('codeslot_slot_error_1');}
