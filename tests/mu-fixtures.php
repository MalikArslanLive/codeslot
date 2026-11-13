<?php
/** Test-only fixtures; never packaged. */
add_action('init',static function(){
 register_post_type('codeslot_demo',['public'=>true,'show_in_rest'=>true,'label'=>'Demo Items','supports'=>['title','editor','custom-fields']]);
 register_taxonomy('codeslot_topic','codeslot_demo',['public'=>true,'show_in_rest'=>true,'label'=>'Demo Topics']);
});
