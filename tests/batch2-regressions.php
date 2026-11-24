<?php
/** Disposable WordPress regression checks for import, restore and output boundaries. */
wp_set_current_user(1);
$original=[];foreach(['codeslot_settings','codeslot_global','codeslot_pixels','codeslot_files'] as $key)$original[$key]=get_option($key,null);
$ids=[];$terms=[];$GLOBALS['codeslot_batch2_checks']=0;
function batch2_assert($ok,$label){if(!$ok)throw new RuntimeException('FAIL: '.$label);++$GLOBALS['codeslot_batch2_checks'];WP_CLI::log('PASS: '.$label);}
$prefix='batch2-'.uniqid();
try{
 update_option('codeslot_settings',['safe_mode'=>false,'delete_data'=>true]);
 $base=['name'=>$prefix,'type'=>'javascript','location'=>'footer','code'=>"window.Batch2 = 'unchanged';\r\n",'active'=>true,'locked'=>true,'tags'=>['b','a']];
 $id=CodeSlot\Repository::save($base);$ids[]=$id;$before=CodeSlot\Repository::get($id);$history=get_post_meta($id,'_codeslot_history',true);
 $document=['schema'=>'codeslot/v1','snippets'=>[$before]];
 $report=null;$result=CodeSlot\Repository::import(wp_json_encode($document),'skip',$report);
 batch2_assert($result===[]&&array_intersect_key($report,array_flip(['imported','skipped','conflicts']))===['imported'=>0,'skipped'=>1,'conflicts'=>0],'repeat import skips original active/locked snippet');
 batch2_assert(CodeSlot\Repository::get($id)===$before&&get_post_meta($id,'_codeslot_history',true)===$history,'duplicate import preserves source, active state, lock and history');
 $duplicate=$before;$duplicate['tags']=array_reverse($duplicate['tags']);
 $r=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$duplicate]]),'copy',$report);
 batch2_assert($r===[]&&$report['skipped']===1,'tag order does not create duplicate copies');
 $line_changed=$before;$line_changed['code']=str_replace("\r\n","\n",$line_changed['code']);
 $r=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$line_changed]]),'skip',$report);
 batch2_assert($r===[]&&$report['conflicts']===1&&$report['skipped']===0,'changed source bytes are a version conflict rather than an exact duplicate');
 $different=$before;$different['code']='window.Batch2 = 2;';
 $r=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$different]]),'skip',$report);
 batch2_assert($r===[]&&$report['conflicts']===1&&$report['imported']===0,'name conflict is skipped by default');
 $r=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$different]]),'copy',$report);$ids=array_merge($ids,$r);
 batch2_assert(count($r)===1&&$report['conflicts']===1&&!CodeSlot\Repository::get($r[0])['active']&&!CodeSlot\Repository::get($r[0])['locked'],'explicit conflict choice creates one inactive unlocked version');
 batch2_assert(CodeSlot\Repository::get($id)===$before,'conflict copy never overwrites its original');
 $new=$different;$new['name']=$prefix.'-new';
 $r=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$new,$new]]),'skip',$report);$ids=array_merge($ids,$r);
 batch2_assert(count($r)===1&&$report['imported']===1&&$report['skipped']===1,'duplicates inside the same file are skipped');
 $bad=$new;$bad['code']=[];$count=wp_count_posts('codeslot_snippet');
 batch2_assert(is_wp_error(CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$new,$bad]]))),'all snippets validate before writes');
 batch2_assert(wp_count_posts('codeslot_snippet')==$count,'invalid import creates no partial copies');
 $slots=['header'=>['code'=>"<script>window.Mapping='original';</script>",'enabled'=>true]];
 $post=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$prefix,'post_name'=>$prefix]);$ids[]=$post;update_post_meta($post,'_codeslot_slots',$slots);
 $empty=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$prefix.'-empty','post_name'=>$prefix.'-empty']);$ids[]=$empty;update_post_meta($empty,'_codeslot_slots','');
 $draft=wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>$prefix.' draft']);$ids[]=$draft;update_post_meta($draft,'_codeslot_slots','');
 $term=wp_insert_term($prefix,'category',['slug'=>$prefix]);$terms[]=$term['term_id'];update_term_meta($term['term_id'],'_codeslot_slots','');
 $backup=CodeSlot\Backup::export();
 $own=array_values(array_filter($backup['individual'],static function($row)use($prefix){return strpos($row['path'],$prefix)===0||$row['path']==='';}));
 batch2_assert(count($own)>=4,'full export includes post, term, empty metadata and slug-less draft');
 foreach($own as $row)batch2_assert(is_string($row['path'])&&is_array($row['slots']),'export emits valid individual identity/slots shapes');
 $backup['individual']=$own;$backup['individual'][]=['kind'=>'post','type'=>'page','path'=>'missing-'.$prefix,'slots'=>$slots];
  $backup['snippets']=[$before];$backup['settings']=['safe_mode'=>true,'delete_data'=>true];
 update_post_meta($post,'_codeslot_slots',['header'=>['code'=>'CURRENT DIFFERENT CODE','enabled'=>true]]);
 $restored=CodeSlot\Backup::restore(wp_json_encode($backup),['individual','snippets','settings']);
 batch2_assert(!is_wp_error($restored)&&$restored['mapped']>=3&&$restored['skipped']>=1,'restore maps existing identities and skips missing or slug-less entries');
 batch2_assert(get_post_meta($post,'_codeslot_slots',true)['header']['code']===$slots['header']['code']&&!get_post_meta($post,'_codeslot_slots',true)['header']['enabled'],'individual restore replaces exactly once and disables only restored slot');
 batch2_assert(count(get_post_meta($post,'_codeslot_slots',false))===1,'individual restore does not append duplicate metadata');
 batch2_assert(!CodeSlot\Permissions::safe()&&!get_option('codeslot_settings')['delete_data'],'restoring settings preserves current Safe Mode off and keeps uninstall deletion off');
 batch2_assert(CodeSlot\Repository::get($id)===$before&&$restored['snippets']['skipped']===1,'full backup skips duplicate snippets without pausing active originals');
 update_option('codeslot_settings',['safe_mode'=>true,'delete_data'=>true]);
 $restored=CodeSlot\Backup::restore(wp_json_encode($backup),['snippets']);
 batch2_assert(!is_wp_error($restored)&&CodeSlot\Permissions::safe()&&get_option('codeslot_settings')['delete_data'],'snippet-only restore preserves existing Safe Mode and unrelated settings');
 update_option('codeslot_settings',['safe_mode'=>false]);
 $broken=$backup;$broken['individual']=[['kind'=>'post','type'=>'page','path'=>$prefix,'slots'=>['header'=>['code'=>[]]]]];
 $pre=get_post_meta($post,'_codeslot_slots',true);
 batch2_assert(is_wp_error(CodeSlot\Backup::restore(wp_json_encode($broken),['global','individual']))&&get_post_meta($post,'_codeslot_slots',true)===$pre,'malformed individual code is rejected before any mutation');
 $source="<script>\nwindow.Exact = 'a';\n</script>";
 $marked=CodeSlot\Runtime::marked($source,'Global Header');
 batch2_assert($marked==="\n<!-- CodeSlot: Global Header - Start -->\n".$source."\n<!-- CodeSlot: Global Header - End -->\n",'source labels add boundaries and preserve all authored bytes');
 batch2_assert(strpos(CodeSlot\Runtime::marked('x','hostile --> <script>'),'--><script>')===false,'comment labels cannot inject markup');
 batch2_assert(CodeSlot\Runtime::visual(['id'=>123,'type'=>'text','location'=>'shortcode','code'=>'<b>safe</b>'])==='&lt;b&gt;safe&lt;/b&gt;','plain text shortcode output stays byte-exact');
 batch2_assert(CodeSlot\Runtime::visual(['id'=>123,'type'=>'html','location'=>'shortcode','code'=>'https://example.org/path'])==='https://example.org/path','URL shortcode output stays usable in HTML attributes');
 batch2_assert(strpos(CodeSlot\Runtime::visual(['id'=>123,'type'=>'html','location'=>'shortcode','code'=>'<span>inline</span>']),"\n")===false,'inline markup labels add no visible whitespace');
 batch2_assert(CodeSlot\Runtime::visual(['id'=>123,'type'=>'html','location'=>'selector_prepend','code'=>'<span>target</span>'])==='<span>target</span>','selector payloads preserve existing DOM child-node semantics');
 WP_CLI::success($GLOBALS['codeslot_batch2_checks'].' batch-two assertions passed.');
}finally{
 foreach($ids as $id)wp_delete_post($id,true);foreach($terms as $id)wp_delete_term($id,'category');
 foreach($original as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}
}
