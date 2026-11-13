<?php
// Disposable-only measurements; all generated rows removed at end.
wp_set_current_user(1);
$ids=[];
foreach([100,1000] as $count){
 $start=microtime(true);
 while(count($ids)<$count){$ids[]=CodeSlot\Repository::save(['name'=>'Benchmark '.count($ids),'type'=>'text','location'=>'footer','code'=>'x','active'=>false]);}
 CodeSlot\Runtime::clear_cache();
 global $wpdb;
 $q=$wpdb->num_queries;$mem=memory_get_usage();$t=microtime(true);
 CodeSlot\Runtime::location('footer');
 WP_CLI::line(wp_json_encode(['inactive_snippets'=>$count,'seed_seconds'=>round(microtime(true)-$start,3),'location_ms'=>round((microtime(true)-$t)*1000,3),'queries'=>$wpdb->num_queries-$q,'memory_bytes'=>memory_get_usage()-$mem]));
}
foreach($ids as $id){wp_delete_post($id,true);}
