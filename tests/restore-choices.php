<?php
/** Disposable real-WordPress checks for restore choices, reporting and replacement guards. */
wp_set_current_user(1);
$original=[];foreach(['codeslot_settings','codeslot_global','codeslot_pixels','codeslot_files'] as $key)$original[$key]=get_option($key,null);
$ids=[];$GLOBALS['restore_choices_count']=0;$prefix='restore3-'.uniqid();
function rc_assert($ok,$label){if(!$ok)throw new RuntimeException('FAIL: '.$label);++$GLOBALS['restore_choices_count'];WP_CLI::log('PASS: '.$label);}
function rc_import($rows,$policy,&$report,$state='disabled'){return CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>$rows]),$policy,$report,$state);}
try{
 $before_empty=CodeSlot\Backup::export();$empty_result=CodeSlot\Backup::restore(wp_json_encode($before_empty),[]);
 rc_assert(is_wp_error($empty_result)&&$empty_result->get_error_code()==='restore_sections'&&strpos($empty_result->get_error_message(),'Select at least one section')!==false&&strpos($empty_result->get_error_message(),'Nothing was restored.')!==false&&CodeSlot\Backup::export()===$before_empty,'empty section selection explains what to select and leaves all backup data unchanged');
 update_option('codeslot_settings',['safe_mode'=>false,'delete_data'=>true]);
 $a=['name'=>$prefix,'type'=>'javascript','location'=>'footer','code'=>'window.RestoreChoice=1;','active'=>true,'tags'=>['old']];
 $id=CodeSlot\Repository::save($a);$ids[]=$id;$new=$a;$new['code']='window.RestoreChoice=2;';$new['tags']=['new'];$report=null;
 $r=rc_import([$new],'replace',$report);
 rc_assert($r===[]&&$report['replaced']===1&&$report['disabled']===1,'replace keeps the existing ID and defaults to disabled');
 rc_assert(CodeSlot\Repository::get($id)['code']===$new['code']&&!CodeSlot\Repository::get($id)['active'],'existing code is actually replaced and disabled');
 $h=get_post_meta($id,'_codeslot_history',true);rc_assert(count($h)===1&&$h[0]['snippet']['code']===$a['code'],'replacement creates an old-version history snapshot');
 $s=CodeSlot\Repository::get($id);$s['active']=true;CodeSlot\Repository::save($s,$id);$new['code']='window.RestoreChoice=3;';rc_import([$new],'replace',$report,'preserve');
 rc_assert(CodeSlot\Repository::get($id)['active']&&$report['enabled']===1,'preserve keeps an existing active entry enabled');
 $new['name']=$prefix.'-fresh';$new['active']=true;$r=rc_import([$new],'replace',$report,'preserve');$ids=array_merge($ids,$r);
 rc_assert(count($r)===1&&!CodeSlot\Repository::get($r[0])['active'],'preserve never enables a newly imported snippet');
 $locked=$a;$locked['name']=$prefix.'-locked';$locked['locked']=true;$lid=CodeSlot\Repository::save($locked);$ids[]=$lid;$locked['code']='window.LockedChanged=1;';$prior=CodeSlot\Repository::get($lid);rc_import([$locked],'replace',$report,'preserve');
 rc_assert($report['protected']===1&&CodeSlot\Repository::get($lid)===$prior,'replace skips locked snippets without unlocking or editing them');
 $amb=$a;$amb['name']=$prefix.'-ambiguous';foreach([1,2]as$v){$amb['code']='window.Ambiguous='.$v.';';$ids[]=CodeSlot\Repository::save($amb);}$amb['code']='window.Ambiguous=3;';rc_import([$amb],'replace',$report);
 rc_assert($report['ambiguous']===1&&$report['replaced']===0,'ambiguous duplicate names are not guessed or overwritten');
 $before=CodeSlot\Repository::get($id);$history=get_post_meta($id,'_codeslot_history',true);$changed=$before;$changed['code']='window.WouldReplace=1;';$fail=$a;$fail['name']=$prefix.'-fail';
 $filter=static function($empty,$data)use($fail){return $data['post_type']==='codeslot_snippet'&&$data['post_title']===$fail['name']?true:$empty;};add_filter('wp_insert_post_empty_content',$filter,10,2);
 $r=rc_import([$changed,$fail],'replace',$report);remove_filter('wp_insert_post_empty_content',$filter,10);
 rc_assert(is_wp_error($r)&&CodeSlot\Repository::get($id)===$before&&get_post_meta($id,'_codeslot_history',true)===$history,'a later insert failure restores replaced source, state, metadata and history');
 $post=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$prefix,'post_name'=>$prefix]);$ids[]=$post;
 $old=['header'=>['code'=>'<!-- CURRENT HEADER -->','enabled'=>true],'footer'=>['code'=>'<!-- CURRENT FOOTER -->','enabled'=>false]];update_option('codeslot_global',$old);update_post_meta($post,'_codeslot_slots',$old);
 update_option('codeslot_files',['ads.txt'=>['code'=>'current ads','enabled'=>true],'service-worker.js'=>['code'=>'current worker','enabled'=>true],'register_worker'=>true]);
 update_option('codeslot_pixels',CodeSlot\Pixels::validate(['meta'=>['id'=>'123456789012345','enabled'=>true,'events'=>['page_view']],'require_consent'=>true,'click_selector'=>'.original']));
 $b=CodeSlot\Backup::export();$incoming=['header'=>['code'=>'<!-- BACKUP HEADER -->','enabled'=>true],'body'=>['code'=>'<!-- NEW BODY -->','enabled'=>true],'footer'=>['code'=>'<!-- BACKUP FOOTER -->','enabled'=>true]];
 $b['global']=$incoming;$b['individual']=[['kind'=>'post','type'=>'page','path'=>$prefix,'slots'=>$incoming],['kind'=>'post','type'=>'page','path'=>'missing-'.$prefix,'slots'=>$incoming]];
 $b['files']['ads.txt']['code']='backup ads';$b['files']['app-ads.txt']=['code'=>'new app ads','enabled'=>true];$b['pixels']['meta']['id']='987654321098765';$b['pixels']['google']=['id'=>'G-TEST12345','enabled'=>true,'events'=>['page_view']];$b['pixels']['click_selector']='.backup';
 $kept_pixels=get_option('codeslot_pixels');$r=CodeSlot\Backup::restore(wp_json_encode($b),['global','individual','files','pixels','settings'],'skip',['global'=>'skip','individual'=>'skip','files'=>'skip','pixels'=>'skip','settings'=>'skip'],'preserve');
 rc_assert(!is_wp_error($r)&&get_option('codeslot_global')['header']===$old['header']&&get_post_meta($post,'_codeslot_slots',true)['header']===$old['header'],'Keep existing preserves each existing global and individual field');
 rc_assert(get_option('codeslot_global')['body']['code']===$incoming['body']['code']&&!get_option('codeslot_global')['body']['enabled'],'Keep existing can add a missing slot but always disabled');
 rc_assert(get_option('codeslot_files')['ads.txt']['code']==='current ads'&&get_option('codeslot_files')['ads.txt']['enabled'],'Keep existing does not replace or disable existing virtual files');
 rc_assert(!get_option('codeslot_files')['app-ads.txt']['enabled']&&get_option('codeslot_files')['app-ads.txt']['code']==='new app ads','new virtual files stay disabled in preserve mode');
 rc_assert(get_option('codeslot_pixels')['meta']===$kept_pixels['meta']&&get_option('codeslot_pixels')['click_selector']==='.original'&&get_option('codeslot_pixels')['require_consent'],'Keep existing preserves tracking IDs, flags and shared consent/click preferences');
 rc_assert(get_option('codeslot_pixels')['google']['id']==='G-TEST12345'&&!get_option('codeslot_pixels')['google']['enabled'],'new provider IDs never become enabled automatically');
 rc_assert(get_option('codeslot_settings')['delete_data']===true&&$r['sections']['settings']['skipped']===1,'Keep settings preserves preferences rather than reporting an update');
 rc_assert($r['sections']['global']['updated']===1&&$r['sections']['global']['skipped']===2&&$r['skipped']===1,'result counts distinguish new writes, existing skips and missing mappings');
 $r=CodeSlot\Backup::restore(wp_json_encode($b),['global','individual','files','pixels','settings'],'skip',[],'preserve');
 rc_assert(get_option('codeslot_global')['header']['code']===$incoming['header']['code']&&get_option('codeslot_global')['header']['enabled']&&!get_option('codeslot_global')['footer']['enabled'],'Replace/preserve updates code while retaining enabled and disabled switches');
 rc_assert(get_option('codeslot_pixels')['meta']['id']==='987654321098765'&&get_option('codeslot_pixels')['meta']['enabled'],'Replace/preserve retains an existing provider enabled state');
 rc_assert(!CodeSlot\Permissions::safe()&&!get_option('codeslot_settings')['delete_data'],'restoring settings preserves Safe Mode and disables uninstall deletion');
 $r=CodeSlot\Backup::restore(wp_json_encode($b),['global','individual','files','pixels'],'skip',[],'disabled');
 rc_assert(!get_option('codeslot_global')['header']['enabled']&&!get_post_meta($post,'_codeslot_slots',true)['header']['enabled']&&!get_option('codeslot_pixels')['meta']['enabled']&&!get_option('codeslot_files')['register_worker'],'Disable policy switches off restored entries and worker registration');
 $prior=get_option('codeslot_global');$r=CodeSlot\Backup::restore(wp_json_encode($b),['global'],'skip',['global'=>'invalid']);rc_assert(is_wp_error($r)&&get_option('codeslot_global')===$prior,'invalid choices fail before writes');
 $r=CodeSlot\Backup::restore(wp_json_encode($b),['global']);ob_start();CodeSlot\Backup::notice($r);$notice=ob_get_clean();rc_assert(strpos($notice,'Not selected')!==false&&strpos($notice,'not replaced or updated')!==false&&strpos($notice,'disabled')!==false,'section-wise notice explicitly distinguishes untouched sections and restored states');
 $text=CodeSlot\Backup::import_message(['imported'=>0,'skipped'=>2,'conflicts'=>1,'policy'=>'skip']);rc_assert(strpos($text,'not replaced or updated')!==false&&strpos($text,'unchanged')!==false,'short import notice explicitly describes unchanged originals');
 WP_CLI::success($GLOBALS['restore_choices_count'].' restore-choice assertions passed.');
}finally{foreach($ids as$id)wp_delete_post($id,true);foreach($original as$key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}}
