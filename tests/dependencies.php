<?php
/** Disposable WordPress dependency lifecycle regressions. */
wp_set_current_user(1);
$GLOBALS['dep_checks']=0;$ids=[];$dir=WP_PLUGIN_DIR.'/codeslot-dependency-fixture';$file='codeslot-dependency-fixture/fixture.php';$path=$dir.'/fixture.php';$old_active=get_option('active_plugins');$old_settings=get_option('codeslot_settings',[]);$old_network=get_site_option('active_sitewide_plugins',[]);
function dep_assert($value,$label){if(!$value)throw new RuntimeException($label);++$GLOBALS['dep_checks'];echo 'PASS: '.$label.PHP_EOL;}
function dep_rule($key,$value='',$op='is'){return ['key'=>$key,'value'=>$value,'op'=>$op];}
function dep_conditions($rule,$kind='include'){return [$kind=>[[$rule]]];}
require_once ABSPATH.'wp-admin/includes/plugin.php';
try{
 wp_mkdir_p($dir);file_put_contents($path,"<?php\n/* Plugin Name: CodeSlot Dependency Fixture */\nfunction codeslot_dependency_fixture_value(){return 'DEP-PHP-READY';}\n");wp_clean_plugins_cache();activate_plugin($file);update_option('codeslot_settings',['safe_mode'=>false]);
 dep_assert(CodeSlot\Dependencies::plugin_available($file),'required active plugin is available');
 if(is_multisite()){deactivate_plugins($file,false,false);activate_plugin($file,'',true);dep_assert(CodeSlot\Dependencies::plugin_available($file),'network-activated dependencies are recognized');deactivate_plugins($file,false,true);activate_plugin($file);}
 $base=['name'=>'Dependency fixture','type'=>'html','location'=>'shortcode','code'=>'<span>DEP-VISUAL-READY</span>','active'=>true,'requires'=>[$file]];$id=CodeSlot\Repository::save($base);$ids[]=$id;$saved=CodeSlot\Repository::get($id);
 dep_assert(!is_wp_error($id)&&$saved['requires']===[$file],'required plugin stored on snippet');
 dep_assert(strpos(CodeSlot\Runtime::shortcode(['id'=>$id]),'DEP-VISUAL-READY')!==false,'active dependency permits shortcode output');
 deactivate_plugins($file);CodeSlot\Runtime::clear_cache();
 dep_assert(!CodeSlot\Dependencies::plugin_available($file),'deactivation makes dependency unavailable');
 dep_assert(CodeSlot\Runtime::shortcode(['id'=>$id])==='','missing dependency prevents shortcode execution');
 dep_assert(CodeSlot\Repository::get($id)===$saved,'deactivation preserves complete saved setup and Active preference');
 dep_assert(count(CodeSlot\Dependencies::issues($saved))===1,'precise missing-plugin reason reported');
 activate_plugin($file);CodeSlot\Runtime::clear_cache();
 dep_assert(strpos(CodeSlot\Runtime::shortcode(['id'=>$id]),'DEP-VISUAL-READY')!==false,'reactivation resumes saved active setup');
 unlink($path);CodeSlot\Runtime::clear_cache();
 dep_assert(!CodeSlot\Dependencies::plugin_available($file)&&CodeSlot\Runtime::shortcode(['id'=>$id])==='','deleted plugin files pause execution even with a stale active option');
 file_put_contents($path,"<?php\n/* Plugin Name: CodeSlot Dependency Fixture */\n");CodeSlot\Runtime::clear_cache();
 dep_assert(CodeSlot\Dependencies::plugin_available($file),'restoring same plugin identity restores availability');
 $php=CodeSlot\Repository::save(['name'=>'Dependency PHP fixture','type'=>'php','location'=>'manual','code'=>'echo codeslot_dependency_fixture_value();','active'=>true,'requires'=>[$file]]);$ids[]=$php;ob_start();CodeSlot\Runtime::php('manual');$output=ob_get_clean();
 dep_assert(strpos($output,'DEP-PHP-READY')!==false,'declared dependency allows general manual PHP execution');
 deactivate_plugins($file);CodeSlot\Runtime::clear_cache();ob_start();CodeSlot\Runtime::php('manual');$output=ob_get_clean();
 dep_assert(strpos($output,'DEP-PHP-READY')===false,'general manual PHP is skipped when its plugin is inactive');
 $once=CodeSlot\Repository::save(['name'=>'Dependency Run Once fixture','type'=>'php','location'=>'once','code'=>'echo "unsafe";','requires'=>[$file]]);$ids[]=$once;$run=CodeSlot\Jobs::run($once);
 dep_assert(is_wp_error($run)&&strpos($run->get_error_message(),'paused')!==false&&!CodeSlot\Jobs::record($once),'Run Once refuses unavailable dependency before claiming execution');
 $export=CodeSlot\Repository::export();$row=current(array_filter($export['snippets'],static function($s)use($id){return $s['id']===$id;}));
 dep_assert($row['requires']===[$file],'export retains plugin requirements');
 $row['name']='Dependency imported copy';$report=null;$copies=CodeSlot\Repository::import(wp_json_encode(['schema'=>'codeslot/v1','snippets'=>[$row]]),'copy',$report);$ids=array_merge($ids,$copies);
 dep_assert(!is_wp_error($copies)&&CodeSlot\Repository::get($copies[0])['requires']===[$file]&&!CodeSlot\Repository::get($copies[0])['active'],'import retains unmet requirements without enabling new code');
 $history=get_post_meta($id,'_codeslot_history',true);CodeSlot\Repository::save($saved,$id);dep_assert(get_post_meta($id,'_codeslot_history',true)===$history,'unchanged dependency settings create no revision');
 $updated=$saved;$updated['requires']=[];CodeSlot\Repository::save($updated,$id);dep_assert(count(get_post_meta($id,'_codeslot_history',true))===1,'explicit requirement removal records a real revision');
 register_post_type('dep_item',['public'=>true,'label'=>'Dependency Items']);register_taxonomy('dep_topic','dep_item',['public'=>true]);add_role('dep_role','Dependency Role',['read'=>true]);
 $post=wp_insert_post(['post_type'=>'dep_item','post_status'=>'publish','post_title'=>'Dependency item']);$ids[]=$post;$term=wp_insert_term('Dependency term','dep_topic');$term_id=$term['term_id'];
 $checks=[dep_rule('singular',(string)$post),dep_rule('post_type','dep_item'),dep_rule('term','dep_topic:'.$term_id),dep_rule('role','dep_role')];
 foreach($checks as $rule)dep_assert(CodeSlot\Conditions::missing(dep_conditions($rule))===[],'available structured reference accepted: '.$rule['key']);
 unregister_post_type('dep_item');unregister_taxonomy('dep_topic');remove_role('dep_role');
 foreach($checks as $rule){dep_assert((bool)CodeSlot\Conditions::missing(dep_conditions($rule)),'missing structured reference detected: '.$rule['key']);foreach(['is','not'] as $op){$rule['op']=$op;foreach(['include','exclude'] as $kind){$rules=dep_conditions($rule,$kind);$rules['include'][]=[dep_rule('entire_site')];dep_assert(!CodeSlot\Conditions::matches($rules,['entire_site'=>true]),'missing '.$rule['key'].' blocks '.$kind.' '.$op.' despite an alternative group');}}}
 register_post_type('dep_item',['public'=>true]);register_taxonomy('dep_topic','dep_item',['public'=>true]);add_role('dep_role','Dependency Role',['read'=>true]);
 foreach($checks as $rule)dep_assert(CodeSlot\Conditions::missing(dep_conditions($rule))===[],'same registered reference resumes after restoration: '.$rule['key']);
 wp_trash_post($post);dep_assert((bool)CodeSlot\Conditions::missing(dep_conditions(dep_rule('singular',(string)$post))),'trashed content pauses rules');wp_untrash_post($post);
 wp_delete_term($term_id,'dep_topic');dep_assert((bool)CodeSlot\Conditions::missing(dep_conditions(dep_rule('term','dep_topic:'.$term_id))),'deleted term pauses rules without retargeting by name');
 foreach(['../escape.php','/absolute.php','https://evil/plugin.php','valid/../escape.php','x//plugin.php'] as $bad)dep_assert(is_wp_error(CodeSlot\Dependencies::validate([$bad])),'invalid plugin path rejected: '.$bad);
 dep_assert(is_wp_error(CodeSlot\Dependencies::validate('plugin.php')),'malformed requirement shape rejected');
 if(!CodeSlot\Pixels::commerce_available()){foreach(['shop','cart','checkout','account'] as $key){foreach(['include','exclude'] as $kind){$rules=dep_conditions(dep_rule($key,'','not'),$kind);$rules['include'][]=[dep_rule('entire_site')];dep_assert(!CodeSlot\Conditions::matches($rules,['entire_site'=>true]),'missing WooCommerce blocks '.$kind.' '.$key);}}dep_assert(!CodeSlot\Delivery::eligible(['location'=>'woocommerce_after_single_product']),'WooCommerce location is ineligible without WooCommerce');}
 echo 'Success: '.$GLOBALS['dep_checks'].' dependency assertions passed.'.PHP_EOL;
}finally{wp_set_current_user(1);foreach($ids as $id)if(is_int($id))wp_delete_post($id,true);if(isset($term_id)&&taxonomy_exists('dep_topic'))wp_delete_term($term_id,'dep_topic');remove_role('dep_role');update_option('active_plugins',$old_active);if(is_multisite())update_site_option('active_sitewide_plugins',$old_network);update_option('codeslot_settings',$old_settings);if(file_exists($path))unlink($path);if(is_dir($dir))rmdir($dir);wp_clean_plugins_cache();}
