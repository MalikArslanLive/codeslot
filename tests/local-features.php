<?php
// Real WordPress regression coverage; disposable sites only.
wp_set_current_user(1);
function cs_feature_assert($condition,$label) { if(!$condition) throw new RuntimeException('FAIL: '.$label); WP_CLI::line('PASS: '.$label); }
$ids=[];
try {
 $page=wp_insert_post(['post_type'=>'page','post_title'=>'Local features fixture','post_status'=>'publish']); $ids[]=$page;
 $rules=['include'=>[[['key'=>'singular','value'=>(string)$page]]]];
 foreach ([['before_paragraph','BEFORE',10],['after_paragraph','AFTER',10],['after_paragraph','FIRST',-10]] as $spec) {
  $id=CodeSlot\Repository::save(['name'=>$spec[1],'type'=>'html','location'=>$spec[0],'paragraph'=>2,'code'=>'<!-- CS-P-'.$spec[1].' -->','priority'=>$spec[2],'active'=>true,'conditions'=>$rules]);
  cs_feature_assert(!is_wp_error($id),'save '.$spec[1].' paragraph insertion'); $ids[]=$id;
 }
 query_posts(['page_id'=>$page]);
 $html='<!-- <p>fake</p> --><script>var x="<p>fake</p>";</script><pre><p>literal</p></pre><p data-x=">">One</p><p>Two</p><p>Three</p>';
 $expected=str_replace('<p>Two</p>','<!-- CS-P-BEFORE --><p>Two</p><!-- CS-P-FIRST --><!-- CS-P-AFTER -->',$html);
 cs_feature_assert(CodeSlot\Runtime::paragraphs($html)===$expected,'byte-preserving paragraph boundary insertion and priority; comments/raw text ignored');
 query_posts(['page_id'=>wp_insert_post(['post_type'=>'page','post_title'=>'Other local fixture','post_status'=>'publish'])]); $ids[]=get_queried_object_id();
 cs_feature_assert(CodeSlot\Runtime::paragraphs($html)===$html,'paragraph conditions isolate other content');
 wp_reset_query();
 $id=CodeSlot\Repository::save(['name'=>'One-time local fixture','type'=>'php','location'=>'once','active'=>true,'code'=>'update_option("codeslot_task_count", (int)get_option("codeslot_task_count",0)+1); echo "task output";']); $ids[]=$id;
 cs_feature_assert(!CodeSlot\Repository::get($id)['active'],'once is never automatically active');
 $result=CodeSlot\Jobs::run($id);
 cs_feature_assert(is_array($result) && $result['status']==='completed' && $result['output']==='task output','explicit task completes and captures output');
 cs_feature_assert(is_wp_error(CodeSlot\Jobs::run($id)) && get_option('codeslot_task_count')===1,'atomic claim refuses repeat execution');
 $bad=CodeSlot\Repository::save(['name'=>'Failed one-time fixture','type'=>'php','location'=>'once','code'=>'throw new RuntimeException("task exception");']); $ids[]=$bad;
 $result=CodeSlot\Jobs::run($bad);
 cs_feature_assert($result['status']==='failed' && strpos($result['error'],'task exception')!==false,'task exception recorded without retry');
 cs_feature_assert(is_wp_error(CodeSlot\Jobs::run($bad)),'failed task cannot be retried');
 $locked=CodeSlot\Repository::save(['name'=>'Locked one-time fixture','type'=>'php','location'=>'once','locked'=>true,'code'=>'echo "blocked";']); $ids[]=$locked;
 cs_feature_assert(is_wp_error(CodeSlot\Jobs::run($locked)) && !CodeSlot\Jobs::record($locked),'locked task cannot be claimed');
 $safe=CodeSlot\Repository::save(['name'=>'Safe-mode fixture','type'=>'php','location'=>'once','code'=>'echo "blocked";']); $ids[]=$safe;
 $settings=get_option('codeslot_settings',[]); update_option('codeslot_settings',array_merge($settings,['safe_mode'=>true]));
 cs_feature_assert(is_wp_error(CodeSlot\Jobs::run($safe)) && !CodeSlot\Jobs::record($safe),'Safe Mode blocks task claim'); update_option('codeslot_settings',$settings);
 $editor=get_user_by('login','codeslot-editor'); wp_set_current_user($editor->ID);
 cs_feature_assert(is_wp_error(CodeSlot\Jobs::run($safe)),'non-code-manager denied one-time execution'); wp_set_current_user(1);
 wp_delete_post($id,true);
 cs_feature_assert(!CodeSlot\Jobs::record($id),'permanent snippet deletion removes its execution record');
 WP_CLI::success('Local feature checks passed.');
} finally {
 wp_set_current_user(1); wp_reset_query();
 foreach($ids as $id) { wp_delete_post($id,true); }
 delete_option('codeslot_task_count');
}
