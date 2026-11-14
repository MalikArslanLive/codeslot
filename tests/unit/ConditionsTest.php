<?php
use PHPUnit\Framework\TestCase;
use CodeSlot\Conditions;
use CodeSlot\Repository;
final class ConditionsTest extends TestCase {
 public function testEmptyRulesIncludeAll(){self::assertTrue(Conditions::matches([],[]));}
 public function testExclusionAlwaysWins(){
  $rule=['key'=>'frontend','value'=>'','op'=>'is'];
  self::assertFalse(Conditions::matches(['include'=>[[$rule]],'exclude'=>[[$rule]]],['frontend'=>true]));
 }
 public function testAndOrGroups(){
  $rules=['include'=>[[['key'=>'frontend'],['key'=>'role','value'=>'administrator']],[['key'=>'404']]]];
  self::assertTrue(Conditions::matches($rules,['frontend'=>true,'role'=>['administrator'],'404'=>false]));
  self::assertFalse(Conditions::matches($rules,['frontend'=>true,'role'=>['subscriber'],'404'=>false]));
  self::assertTrue(Conditions::matches($rules,['frontend'=>false,'role'=>[],'404'=>true]));
 }
 public function testLoggedOut(){self::assertTrue(Conditions::matches(['include'=>[[['key'=>'logged_in','op'=>'not']]]],['logged_in'=>false]));}
 public function testUnknownRulesFailClosed(){self::assertFalse(Conditions::matches(['include'=>[[['key'=>'unknown','op'=>'not']]]],[]));}
 public function testMalformedImportRule(){self::assertInstanceOf(WP_Error::class,Conditions::validate(['include'=>[[['key'=>'url','value'=>[]]]]]));}
 public function testEmptyGroupIsRejected(){self::assertInstanceOf(WP_Error::class,Conditions::validate(['exclude'=>[[]]]));}
 public function testTermMembership(){self::assertTrue(Conditions::matches(['include'=>[[['key'=>'term','value'=>'category:7']]]],['term'=>['category:7','post_tag:2']]));}
 public function testExactUrl(){self::assertFalse(Conditions::matches(['include'=>[[['key'=>'url','value'=>'/a']]]],['url'=>'/ab']));}
 public function testPhpSyntax(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'php','code'=>'if (','location'=>'frontend']));}
 public function testPhpTagsRejected(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'php','code'=>'<?php echo 1;','location'=>'frontend']));}
 public function testTypeLocationMatrix(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'php','code'=>'echo 1;','location'=>'header']));}
 public function testValidPhp(){self::assertIsArray(Repository::validate(['type'=>'php','code'=>'$x = 1;','location'=>'frontend']));}
 public function testPriorityBounded(){self::assertSame(1000,Repository::validate(['type'=>'html','code'=>'<b>x</b>','location'=>'header','priority'=>9000])['priority']);}
 public function testInvalidParagraphRejected(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'html','code'=>'x','location'=>'after_paragraph','paragraph'=>0]));}
 public function testParagraphArrayRejected(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'html','code'=>'x','location'=>'before_paragraph','paragraph'=>[]]));}
 public function testOnceNeverAutoActive(){self::assertFalse(Repository::validate(['type'=>'php','code'=>'echo 1;','location'=>'once','active'=>true])['active']);}
 public function testOnceRejectsAutomaticConditions(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'php','code'=>'echo 1;','location'=>'once','conditions'=>['include'=>[[['key'=>'frontend']]]]]));}
 public function testJavaScriptWrappersRejected(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'javascript','code'=>'<script>window.x=1;</script>','location'=>'footer']));}
 public function testCssWrappersRejected(){self::assertInstanceOf(WP_Error::class,Repository::validate(['type'=>'css','code'=>'<style>body{color:green}</style>','location'=>'header']));}
 public function testHtmlEmbedPreserved(){ $code='<script src="https://example.org/test.js"></script>'; self::assertSame($code,Repository::validate(['type'=>'html','code'=>$code,'location'=>'footer'])['code']);}
 public function testRawJavaScriptAccepted(){self::assertIsArray(Repository::validate(['type'=>'javascript','code'=>'window.example = 1;','location'=>'footer']));}
}
