<?php
wp_set_current_user(1);
$document=['schema'=>'codeslot/v1','snippets'=>[['name'=>'Legacy roundtrip JS','type'=>'javascript','location'=>'footer','code'=>'<script>window.LegacyRoundtrip=1;</script>','active'=>true,'locked'=>true],['name'=>'Legacy roundtrip CSS','type'=>'css','location'=>'header','code'=>'<style>.legacy-roundtrip{color:green}</style>','active'=>true]]];
$result=CodeSlot\Repository::import(wp_json_encode($document));
if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
try{foreach($result as $index=>$id){$s=CodeSlot\Repository::get($id);if($s['code']!==$document['snippets'][$index]['code']||$s['active']||$s['locked'])throw new RuntimeException('Legacy import failed to preserve inactive, unlocked source');}echo 'PASS: legacy JS/CSS backups round-trip exact source into inactive/unlocked copies'.PHP_EOL;}finally{foreach($result as $id)wp_delete_post($id,true);}
$original_options=[];
foreach(['codeslot_settings','codeslot_pixels'] as $key)$original_options[$key]=get_option($key,null);
$prefix='legacy-full-'.uniqid();
$before=get_posts(['post_type'=>'codeslot_snippet','post_status'=>array_values(get_post_stati()),'posts_per_page'=>-1,'fields'=>'ids']);
try {
 $full=CodeSlot\Backup::export();
 if(is_wp_error($full))throw new RuntimeException($full->get_error_message());
 $full['snippets']=$document['snippets'];
 foreach($full['snippets'] as $i=>&$row)$row['name']=$prefix.'-'.$i;
 unset($row);
 $full['pixels']=['google'=>['id'=>'AW-99887766','enabled'=>true,'events'=>['purchase'],'label'=>'']];
 $restored=CodeSlot\Backup::restore(wp_json_encode($full),['snippets','pixels']);
 if(is_wp_error($restored))throw new RuntimeException($restored->get_error_message());
 $pixels=get_option('codeslot_pixels');
 if($pixels['google_ads']['id']!=='AW-99887766'||$pixels['google_ads']['enabled'])throw new RuntimeException('Legacy Ads backup must restore disabled in the Ads card');
 echo 'PASS: full backup restores legacy wrapped source and old Ads settings inactive'.PHP_EOL;
} finally {
 foreach(get_posts(['post_type'=>'codeslot_snippet','post_status'=>array_values(get_post_stati()),'posts_per_page'=>-1]) as $post){if(!in_array($post->ID,$before,true)&&in_array($post->post_title,[$prefix.'-0',$prefix.'-1'],true))wp_delete_post($post->ID,true);}
 foreach($original_options as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}
}
