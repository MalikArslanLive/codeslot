<?php
if(!defined('WP_CLI') || !WP_CLI || getenv('CODESLOT_GALLERY_DEMO')!=='1')throw new RuntimeException('Run only on a disposable site with CODESLOT_GALLERY_DEMO=1.');
if(get_option('codeslot_gallery_fixture'))throw new RuntimeException('Demo already exists; use a fresh disposable database.');
wp_set_current_user(1);
wp_update_user(['ID'=>1,'display_name'=>'CodeSlot Demo','nickname'=>'CodeSlot Demo']);
update_option('show_avatars',0);update_option('blogdescription','A clean demonstration of CodeSlot');
$contact=wp_insert_post(['post_type'=>'page','post_title'=>'Contact our team','post_status'=>'publish','post_name'=>'contact','post_content'=>'<h2>How can we help?</h2><p>Send our team a message about your next project.</p>']);
$privacy=(int)get_option('wp_page_for_privacy_policy');
wp_update_post(['ID'=>$privacy,'post_status'=>'publish','post_content'=>'<p>This sample page illustrates content targeting in the demo.</p>']);
$rows=[
 ['name'=>'Contact page metadata','description'=>'Add a metadata tag only on the contact page.','type'=>'html','location'=>'header','code'=>"<!-- Contact page metadata -->\n<meta name=\"application-name\" content=\"CodeSlot Demo\">",'active'=>true,'priority'=>5,'categories'=>['Marketing'],'tags'=>['contact'],'conditions'=>['include'=>[[['key'=>'singular','value'=>(string)$contact]]]]],
 ['name'=>'Accessible announcement buttons','description'=>'Give announcement links a visible keyboard focus style.','type'=>'css','location'=>'header','code'=>".site-announcement a {\n    color: #183129;\n    text-underline-offset: 0.2em;\n}\n\n.site-announcement a:focus-visible {\n    outline: 3px solid #b8e84e;\n    outline-offset: 4px;\n}",'active'=>true,'priority'=>10,'categories'=>['Accessibility'],'tags'=>['focus','styling']],
 ['name'=>'Footer interaction helper','description'=>'Expand a notice when visitors choose to learn more.','type'=>'javascript','location'=>'footer','code'=>"document.addEventListener('DOMContentLoaded', function () {\n    const button = document.querySelector('[data-notice-toggle]');\n    const notice = document.querySelector('[data-notice-details]');\n\n    if (!button || !notice) return;\n\n    button.addEventListener('click', function () {\n        notice.hidden = !notice.hidden;\n        button.setAttribute('aria-expanded', String(!notice.hidden));\n    });\n});",'active'=>true,'priority'=>20,'categories'=>['Interface'],'tags'=>['footer','interaction']],
 ['name'=>'Reusable delivery notice','description'=>'Place this message using its generated shortcode.','type'=>'content','location'=>'shortcode','code'=>"<aside class=\"delivery-notice\">\n    Free delivery is available on selected orders.\n</aside>",'active'=>false,'priority'=>30,'categories'=>['Content'],'tags'=>['shortcode','notice']],
 ['name'=>'Welcome note for visitors','description'=>'Show a welcome message on pages, except the privacy policy.','type'=>'html','location'=>'before_content','code'=>"<aside class=\"site-announcement\">\n    Welcome! Explore our latest updates.\n</aside>",'active'=>false,'priority'=>40,'categories'=>['Content'],'tags'=>['pages','welcome'],'conditions'=>['include'=>[[['key'=>'post_type','value'=>'page'],['key'=>'logged_in','op'=>'not']]],'exclude'=>[[['key'=>'singular','value'=>(string)$privacy]]]]],
 ['name'=>'Remove WordPress version output','description'=>'An inactive PHP example to review before enabling.','type'=>'php','location'=>'frontend','code'=>"remove_action( 'wp_head', 'wp_generator' );",'active'=>false,'priority'=>50,'categories'=>['Maintenance'],'tags'=>['head','php']],
];
$ids=[];foreach($rows as $row){$id=CodeSlot\Repository::save($row);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());$ids[]=$id;}
update_option('codeslot_global',CodeSlot\Permissions::slots([
 'header'=>['enabled'=>true,'code'=>"<!-- Site-wide presentation -->\n<meta name=\"theme-color\" content=\"#b8e84e\">"],
 'body'=>['enabled'=>false,'code'=>"<!-- Optional announcement markup -->\n<div class=\"site-announcement\" role=\"status\">\n    Welcome to our website.\n</div>"],
 'footer'=>['enabled'=>false,'code'=>"<!-- Site-wide footer message -->\n<p class=\"site-footer-note\">Thank you for visiting.</p>"],
]));
update_post_meta($contact,'_codeslot_slots',[
 'header'=>['enabled'=>true,'code'=>"<!-- Code for this contact page only -->\n<meta name=\"robots\" content=\"index, follow\">"],
 'body'=>['enabled'=>false,'code'=>"<!-- Optional page announcement -->\n<div class=\"page-announcement\">We are here to help.</div>"],
 'footer'=>['enabled'=>false,'code'=>"<!-- Page-specific footer enhancement -->\n<script>\n    document.documentElement.classList.add('contact-page-ready');\n</script>"],
]);
update_option('codeslot_settings',['safe_mode'=>false,'delete_data'=>false]);
update_option('codeslot_pixels',CodeSlot\Pixels::validate(['meta'=>['id'=>'123456789012345','events'=>['page_view']],'google'=>['id'=>'G-DEMO123456','events'=>['page_view']],'google_ads'=>['id'=>'AW-1234567890','label'=>'ExampleLabel123'],'gtm'=>['id'=>'GTM-DEMO1234'],'require_consent'=>true]));
update_user_meta(1,'managetoplevel_page_codeslotcolumnshidden',['updated','created','note','tags']);
update_user_meta(1,'codeslot_list_preferences',['per_page'=>30,'sort'=>'priority','direction'=>'ASC']);
update_option('codeslot_gallery_fixture',['contact'=>$contact,'editor'=>$ids[2],'conditions'=>$ids[4],'ids'=>$ids]);
WP_CLI::success('Gallery demo content created. No external trackers are enabled.');
