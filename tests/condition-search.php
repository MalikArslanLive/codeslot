<?php
/** Run on a disposable WordPress install. */
wp_set_current_user(1);
function rule_assert($value,$label){if(!$value)throw new RuntimeException($label);echo 'PASS: '.$label.PHP_EOL;}
register_post_type('rule_book',['public'=>true,'label'=>'Travel Books','labels'=>['name'=>'Travel Books','singular_name'=>'Travel Book']]);
register_post_type('rule_hidden',['public'=>false,'label'=>'Hidden Books']);
register_taxonomy('rule_genre','rule_book',['public'=>true,'label'=>'Book Genres']);
add_role('rule_reader','Travel Reader',['read'=>true]);
$ids=[];$terms=[];
try{
 foreach([['Rule Match Atlas','publish','rule_book'],['Rule Match Private','private','rule_book'],['Rule Match Trash','trash','rule_book'],['Unrelated title','publish','rule_book'],['Rule Match Hidden','publish','rule_hidden']] as $row){$ids[]=wp_insert_post(['post_title'=>$row[0],'post_status'=>$row[1],'post_type'=>$row[2],'post_content'=>'Rule Match only in body']);}
 $term=wp_insert_term('Rule Match Fiction','rule_genre');$terms[]=$term['term_id'];
 $result=CodeSlot\Condition_Editor::options('singular','Rule Match');$found=array_column($result,'value');
 rule_assert(in_array((string)$ids[0],$found,true),'registered public plugin content is searchable by title');
 rule_assert(in_array((string)$ids[1],$found,true),'authorized administrator can find private content');
 rule_assert(!in_array((string)$ids[2],$found,true)&&!in_array((string)$ids[3],$found,true)&&!in_array((string)$ids[4],$found,true),'trash, body-only matches and non-public types are excluded');
 rule_assert(CodeSlot\Condition_Editor::options('singular','',(string)$ids[0])[0]['value']===(string)$ids[0],'saved content identity resolves back to a readable name');
 rule_assert(CodeSlot\Condition_Editor::options('singular','','999999999')===[],'deleted or missing selection resolves without a false match');
 rule_assert(CodeSlot\Condition_Editor::options('post_type','Travel')[0]['value']==='rule_book','registered plugin post types searched by label');
 rule_assert(CodeSlot\Condition_Editor::options('post_type','','rule_book')[0]['label']==='Travel Books','saved post-type selection resolves');
 rule_assert(CodeSlot\Condition_Editor::options('role','Travel')[0]['value']==='rule_reader','plugin roles searched by readable name');
 $rows=CodeSlot\Condition_Editor::options('term','Rule Match');
 rule_assert($rows[0]['value']==='rule_genre:'.$term['term_id'],'term search stores taxonomy and ID automatically');
 rule_assert(CodeSlot\Condition_Editor::options('term','','rule_genre:'.$term['term_id'])[0]['label']==='Rule Match Fiction','term selection resolves accurately');
 rule_assert(CodeSlot\Condition_Editor::options('term','','category:0')===[],'invalid term identity does not enumerate unrelated terms');
 for($i=0;$i<23;$i++)$ids[]=wp_insert_post(['post_type'=>'rule_book','post_status'=>'publish','post_title'=>'Rule Limit '.$i]);
 rule_assert(count(CodeSlot\Condition_Editor::options('singular','Rule Limit'))===20,'content searches are bounded to twenty results');
 $defs=CodeSlot\Conditions::definitions();rule_assert($defs['home']['label']==='Blog Posts Page'&&!isset($defs['product']),'friendly blog label and redundant product rule removed');
 rule_assert(is_wp_error(CodeSlot\Conditions::validate(['include'=>[[['key'=>'product']]]])),'removed product rule is not silently accepted');
 foreach(['singular'=>'','term'=>'12','post_type'=>'bad type','role'=>'','url'=>'https://example.org/page/'] as $key=>$value)rule_assert(is_wp_error(CodeSlot\Conditions::validate(['include'=>[[['key'=>$key,'value'=>$value]]]])),'invalid value rejected for '.$key);
 $rules=['include'=>[[['key'=>'singular','value'=>(string)$ids[0]],['key'=>'role','value'=>'rule_reader']],[['key'=>'url','value'=>'/special/']]],'exclude'=>[[['key'=>'logged_in','op'=>'not']]]];
 rule_assert(CodeSlot\Conditions::matches($rules,['singular'=>(string)$ids[0],'role'=>['rule_reader'],'url'=>'/other/','logged_in'=>true]),'selected item and role combine with AND');
 rule_assert(!CodeSlot\Conditions::matches($rules,['singular'=>(string)$ids[0],'role'=>['subscriber'],'url'=>'/other/','logged_in'=>true]),'AND refuses a mismatched role');
 rule_assert(CodeSlot\Conditions::matches($rules,['singular'=>'0','role'=>[],'url'=>'/special/','logged_in'=>true]),'another OR group can match');
 rule_assert(!CodeSlot\Conditions::matches($rules,['singular'=>'0','role'=>[],'url'=>'/special/','logged_in'=>false]),'exclude always overrides include');
 rule_assert(is_wp_error(CodeSlot\Pixels::validate(['google'=>['id'=>'AW-12345678']])),'removed Google Ads migration rejects wrong provider cards');
 rule_assert(is_wp_error(CodeSlot\Repository::validate(['type'=>'javascript','code'=>'<script>void 0;</script>','location'=>'footer'])),'wrapped JavaScript is rejected consistently without legacy bypass');
 $sub=wp_insert_user(['user_login'=>'rule-search-'.uniqid(),'user_pass'=>wp_generate_password(),'role'=>'subscriber']);$ids_user=$sub;wp_set_current_user($sub);
 rule_assert(CodeSlot\Condition_Editor::options('singular','Rule Match')===[],'inaccessible content titles are not disclosed to an unprivileged user');wp_set_current_user(1);
 rule_assert(CodeSlot\Conditions::matches(['include'=>[[['key'=>'cart','op'=>'not']]]],['cart'=>false])===CodeSlot\Pixels::commerce_available(),'unavailable commerce rule cannot match through a negated operator');
 echo 'Success: 27 condition/search assertions passed.'.PHP_EOL;
}finally{wp_set_current_user(1);foreach($ids as $id)wp_delete_post($id,true);foreach($terms as $id)wp_delete_term($id,'rule_genre');if(isset($ids_user)){require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($ids_user);}remove_role('rule_reader');}
