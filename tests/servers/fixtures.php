<?php
// Disposable HTTP server checks; production ZIP never includes this file.
wp_set_current_user(1);
$f = get_option('codeslot_fixtures');
foreach (get_posts(['post_type'=>'codeslot_snippet','post_status'=>'any','numberposts'=>-1]) as $post) {
 if (strpos($post->post_title, 'Server fixture ') === 0) { wp_delete_post($post->ID, true); }
}
$rule = ['key'=>'singular','value'=>(string)$f['page-a']['id']];
foreach ([['conditional','header',['include'=>[[$rule]]]],['excluded','footer',['exclude'=>[[$rule]]]],['shortcode','shortcode',[]]] as $spec) {
 $id=CodeSlot\Repository::save(['name'=>'Server fixture '.$spec[0],'type'=>'html','location'=>$spec[1],'code'=>'<!-- CS-SERVER-'.strtoupper($spec[0]).' -->','active'=>true,'conditions'=>$spec[2]]);
 if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
 if ($spec[0]==='shortcode') {
  wp_update_post(['ID'=>$f['page-a']['id'],'post_content'=>'Server fixture [codeslot id="'.$id.'"]']);
 }
}
$probe = '<?php require __DIR__."/wp-load.php"; header("Content-Type: application/json"); echo json_encode(["wordpress"=>get_bloginfo("version"),"php"=>PHP_VERSION,"sapi"=>PHP_SAPI,"server"=>$_SERVER["SERVER_SOFTWARE"]]);';
file_put_contents(ABSPATH.'codeslot-server-probe.php', $probe);
// Virtual files are explicit disposable fixtures; save/restore the pre-test option.
if ( ! get_option( 'codeslot_server_files_saved' ) ) {
 update_option( 'codeslot_server_files_backup', get_option( 'codeslot_files', null ), false );
 update_option( 'codeslot_server_files_saved', true, false );
}
$virtual = [];
foreach ( CodeSlot\Files::NAMES as $name ) {
 $virtual[$name] = ['enabled'=>true,'code'=>$name==='service-worker.js' ? '/* CS-SERVER-VIRTUAL-WORKER */' : ($name==='robots.txt' ? 'Disallow: /codeslot-server-test/' : 'example.com, CS-SERVER-VIRTUAL, DIRECT')];
}
$virtual['register_worker'] = false;
update_option('codeslot_files',$virtual,false);
