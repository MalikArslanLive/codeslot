<?php
// Real WordPress integration assertions. Invoked via wp eval-file.
use CodeSlot\Repository;
use CodeSlot\Permissions;
use CodeSlot\Conditions;
use CodeSlot\Admin;
function cs_assert($value,$label){if(!$value){throw new RuntimeException('FAIL: '.$label);} WP_CLI::line('PASS: '.$label);}
wp_set_current_user(1);
cs_assert(Permissions::allowed(),'administrator privileged access');
$base=['name'=>'Integration sample','code'=>'<!-- CS-SNIPPET -->','type'=>'html','location'=>'header','active'=>false,'conditions'=>[]];
$id=Repository::save($base);
cs_assert(!is_wp_error($id),'create inactive snippet');
cs_assert(!Repository::get($id)['active'],'inactive status');
$base['active']=true; $base['priority']=-10;
Repository::save($base,$id);
cs_assert(Repository::get($id)['active'],'activation');
cs_assert(count(get_post_meta($id,'_codeslot_history',true))===1,'revision snapshot');
$base['code']='<script>const x = "\\\\";</script>';
Repository::save($base,$id);
cs_assert(Repository::get($id)['code']===$base['code'],'code round trip preserves slashes');
$base['locked']=true; Repository::save($base,$id);
cs_assert(is_wp_error(Repository::save($base,$id)),'locked edit rejected');
$base['locked']=false; Repository::save($base,$id,true);
cs_assert(!Repository::get($id)['locked'],'explicit unlock');
$export=Repository::export();
cs_assert($export['schema']==='codeslot/v1','export schema');
$import=Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$base]]));
cs_assert(!is_wp_error($import) && !Repository::get($import[0])['active'],'import forces inactive');
$bad=$base; $bad['type']='php';$bad['location']='frontend';$bad['code']='if (';
$count=wp_count_posts('codeslot_snippet')->draft;
cs_assert(is_wp_error(Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$base,$bad]]))),'malformed import rejected');
cs_assert(wp_count_posts('codeslot_snippet')->draft===$count,'import validates before writes');
cs_assert(is_wp_error(Repository::save($bad)),'PHP syntax rejected');
$nonce=wp_create_nonce('codeslot_slots');
$page=wp_insert_post(['post_type'=>'page','post_title'=>'Security fixture','post_status'=>'publish']);
$_POST=['codeslot_slots'=>['header'=>['enabled'=>true,'code'=>'<!-- secret -->']]];
Admin::save_post($page,get_post($page));
cs_assert(!get_post_meta($page,'_codeslot_slots',true),'missing nonce blocks metadata');
$_POST['codeslot_nonce']=$nonce;
Admin::save_post($page,get_post($page));
cs_assert(get_post_meta($page,'_codeslot_slots',true)['header']['code']==='<!-- secret -->','valid nonce saves metadata');
$editor=wp_insert_user(['user_login'=>'codeslot-editor','user_pass'=>'disposable','role'=>'editor']);
if(is_wp_error($editor)){$editor=get_user_by('login','codeslot-editor')->ID;}
wp_set_current_user($editor);
cs_assert(!Permissions::allowed(),'editor denied code access');
cs_assert(is_wp_error(Repository::save($base)),'editor cannot save snippets');
cs_assert(is_wp_error(Repository::import(wp_json_encode($export))),'editor cannot import');
cs_assert(is_wp_error(Repository::export()),'editor cannot export source');
$_POST=['codeslot_nonce'=>wp_create_nonce('codeslot_slots'),'codeslot_slots'=>['header'=>['enabled'=>true,'code'=>'changed']]];
Admin::save_post($page,get_post($page));
cs_assert(get_post_meta($page,'_codeslot_slots',true)['header']['code']==='<!-- secret -->','editor cannot change raw code');
wp_set_current_user(1);
update_option('codeslot_settings',['safe_mode'=>true]);
cs_assert(Permissions::safe(),'persistent safe mode');
cs_assert(CodeSlot\Runtime::location('header')==='','safe mode suppresses output');
update_option('codeslot_settings',['safe_mode'=>false]);
cs_assert(!Permissions::safe(),'safe mode disabled');
$php=['name'=>'Throws','type'=>'php','location'=>'manual','code'=>'throw new \\RuntimeException("test recovery");','active'=>true];
$pid=Repository::save($php);
do_action('wp');
CodeSlot\Runtime::manual('Throws');
cs_assert(!Repository::get($pid)['active'],'runtime exception disables snippet');
cs_assert(strpos(get_post_meta($pid,'_codeslot_error',true)['message'],'test recovery')!==false,'runtime error recorded');
$short=Repository::save(['name'=>'short','type'=>'text','location'=>'shortcode','code'=>'<b>safe text</b>','active'=>true]);
cs_assert(CodeSlot\Runtime::shortcode(['id'=>$short])==='&lt;b&gt;safe text&lt;/b&gt;','shortcode text escaping');
// Regression: one output per slot, global -> priority snippets -> queried item.
$global_backup=get_option('codeslot_global',[]);
update_option('codeslot_global',Permissions::slots(['header'=>['enabled'=>true,'code'=>'CS-ORDER-GLOBAL']]));
$match_conditions=['include'=>[[['key'=>'singular','value'=>(string)$page]]]];
$second=Repository::save(['name'=>'Order second','type'=>'text','location'=>'header','code'=>'CS-ORDER-SECOND','priority'=>20,'active'=>true,'conditions'=>$match_conditions]);
$first=Repository::save(['name'=>'Order first','type'=>'text','location'=>'header','code'=>'CS-ORDER-FIRST','priority'=>-20,'active'=>true,'conditions'=>$match_conditions]);
query_posts(['page_id'=>$page]);
ob_start();CodeSlot\Runtime::slot('header');CodeSlot\Runtime::slot('header');$output=ob_get_clean();
cs_assert(substr_count($output,'CS-ORDER-GLOBAL')===1,'repeated hook does not duplicate global output');
cs_assert(strpos($output,'CS-ORDER-GLOBAL')<strpos($output,'CS-ORDER-FIRST') && strpos($output,'CS-ORDER-FIRST')<strpos($output,'CS-ORDER-SECOND') && strpos($output,'CS-ORDER-SECOND')<strpos($output,'<!-- secret -->'),'global, snippet priority, individual ordering');
wp_reset_query();update_option('codeslot_global',$global_backup);
wp_delete_post($first,true);wp_delete_post($second,true);
$native=get_post_type_object('codeslot_snippet');
cs_assert(!$native->show_in_rest && !$native->public,'no public snippet REST routes');
if(is_multisite()){
 $site=wp_insert_site(['domain'=>'sub.example.org','path'=>'/']);
 switch_to_blog($site);cs_assert(!get_post_meta($page,'_codeslot_slots',true),'multisite metadata isolation');restore_current_blog();
 wp_update_user(['ID'=>$editor,'role'=>'administrator']);wp_set_current_user($editor);cs_assert(!Permissions::allowed(),'site admin cannot manage executable code on multisite');wp_set_current_user(1);
}
foreach(array_merge([$id,$pid,$short],$import) as $cleanup){wp_delete_post($cleanup,true);}
wp_delete_post($page,true);
$_POST=[];
WP_CLI::success('CodeSlot WordPress integration checks passed.');
