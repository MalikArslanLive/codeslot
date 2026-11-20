<?php
/** Disposable-only measurements; remove every created fixture on failure too. */
wp_set_current_user(1);
$ids=[];
try {
 foreach([false,true] as $active){
  foreach([100,1000] as $count){
   $start=microtime(true);
   while(count($ids)<$count){
    $id=CodeSlot\Repository::save(['name'=>'Benchmark '.count($ids),'type'=>'text','location'=>$active?'header':'footer','code'=>'x','active'=>$active,'conditions'=>$active?['include'=>[[['key'=>'url','value'=>'/codeslot-benchmark-no-match/']]]]:[]]);
    if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
    $ids[]=$id;
   }
   CodeSlot\Runtime::clear_cache();global $wpdb;$q=$wpdb->num_queries;$mem=memory_get_usage();$t=microtime(true);
   CodeSlot\Runtime::location($active?'header':'footer');
   WP_CLI::line(wp_json_encode(['collection'=>$active?'active_nonmatching':'inactive','count'=>$count,'seed_seconds'=>round($t-$start,3),'location_ms'=>round((microtime(true)-$t)*1000,3),'queries'=>$wpdb->num_queries-$q,'memory_bytes'=>memory_get_usage()-$mem]));
  }
  foreach($ids as $id)wp_delete_post($id,true);$ids=[];
 }
} finally {foreach($ids as $id)wp_delete_post($id,true);CodeSlot\Runtime::clear_cache();}
