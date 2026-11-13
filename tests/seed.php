<?php
// Run only on a disposable site: wp eval-file .../tests/seed.php
wp_set_current_user(1);
register_post_type('codeslot_demo', ['public'=>true,'label'=>'Demo Items','supports'=>['title','editor']]);
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
update_option('codeslot_fixtures',$fixture,false);
WP_CLI::line(wp_json_encode($fixture));
