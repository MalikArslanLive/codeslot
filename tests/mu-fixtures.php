<?php
/** Test-only fixtures; never packaged. */
add_action('init',static function(){
 if (!post_type_exists('product')) { register_post_type('product', ['public'=>true,'label'=>'Product fixture','supports'=>['title','editor']]); }
 register_post_type('codeslot_demo',['public'=>true,'show_in_rest'=>true,'label'=>'Demo Items','supports'=>['title','editor','custom-fields']]);
 register_taxonomy('codeslot_topic','codeslot_demo',['public'=>true,'show_in_rest'=>true,'label'=>'Demo Topics']);
});
