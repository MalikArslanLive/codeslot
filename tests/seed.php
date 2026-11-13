<?php
// Run only on a disposable site: wp eval-file .../tests/seed.php
wp_set_current_user(1);
if (!post_type_exists('codeslot_demo')) { register_post_type('codeslot_demo', ['public'=>true,'label'=>'Demo Items','supports'=>['title','editor']]); }
$fixture = [];
foreach (['page-a'=>'page','page-b'=>'page','post-a'=>'post','product-a'=>'product','demo-a'=>'codeslot_demo'] as $slug=>$type) {
 if (!post_type_exists($type)) { register_post_type($type,['public'=>true]); }
 $existing=get_posts(['name'=>$slug,'post_type'=>$type,'post_status'=>'any','numberposts'=>1]);
 $id=$existing ? $existing[0]->ID : wp_insert_post(['post_title'=>ucwords(str_replace('-',' ',$slug)),'post_name'=>$slug,'post_type'=>$type,'post_status'=>'publish','post_content'=>'CodeSlot fixture '.$slug]);
 $slot=$type==='post'?'footer':($type==='product'?'body':'header');
 update_post_meta($id,'_codeslot_slots',CodeSlot\Permissions::slots([$slot=>['enabled'=>true,'code'=>'<!-- CS-'.strtoupper($slug).' -->']]));
 $fixture[$slug]=['id'=>$id,'url'=>get_permalink($id),'marker'=>'CS-'.strtoupper($slug)];
}
$term=term_exists('codeslot-category','category');
if (!$term) {$term=wp_insert_term('CodeSlot Category','category',['slug'=>'codeslot-category']);}
$tid=(int)$term['term_id'];
wp_set_post_terms($fixture['post-a']['id'],[$tid],'category');
update_term_meta($tid,'_codeslot_slots',CodeSlot\Permissions::slots(['header'=>['enabled'=>true,'code'=>'<!-- CS-CATEGORY-A -->']]));
$fixture['category-a']=['id'=>$tid,'url'=>get_term_link($tid,'category'),'marker'=>'CS-CATEGORY-A'];
update_option('codeslot_global',CodeSlot\Permissions::slots(['header'=>['enabled'=>true,'code'=>'<!-- CS-GLOBAL-HEADER -->'],'footer'=>['enabled'=>true,'code'=>'<!-- CS-GLOBAL-FOOTER -->']]));
foreach (['post-b'=>'post','product-b'=>'product'] as $slug=>$type) {
 $existing=get_posts(['name'=>$slug,'post_type'=>$type,'numberposts'=>1]);
 $other=$existing ? $existing[0]->ID : wp_insert_post(['post_title'=>$slug,'post_name'=>$slug,'post_type'=>$type,'post_status'=>'publish','post_content'=>'Unrelated item']);
 $fixture[$slug]=['id'=>$other,'url'=>get_permalink($other),'marker'=>'CS-'.strtoupper($slug)];
}
$topic=term_exists('demo-topic','codeslot_topic');
if(!$topic){$topic=wp_insert_term('Demo Topic','codeslot_topic',['slug'=>'demo-topic']);}
if(!is_wp_error($topic)){
 $topic_id=(int)$topic['term_id'];
 update_term_meta($topic_id,'_codeslot_slots',CodeSlot\Permissions::slots(['footer'=>['enabled'=>true,'code'=>'<!-- CS-TOPIC-A -->']]));
 wp_set_post_terms($fixture['demo-a']['id'],[$topic_id],'codeslot_topic');
 $fixture['topic-a']=['id'=>$topic_id,'url'=>get_term_link($topic_id,'codeslot_topic'),'marker'=>'CS-TOPIC-A'];
}
update_option('codeslot_fixtures',$fixture,false);
WP_CLI::line(wp_json_encode($fixture));
