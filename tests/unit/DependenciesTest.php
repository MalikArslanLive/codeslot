<?php
use PHPUnit\Framework\TestCase;
use CodeSlot\Dependencies;
final class DependenciesTest extends TestCase {
 public function testRequirementsNormalizeAsASet(){self::assertSame(['a/a.php','b/b.php'],Dependencies::validate(['b/b.php','a/a.php','a/a.php']));}
 public function testInvalidPathsFail(){foreach(['../x.php','/x.php','a/../b.php','https://example.org/x.php','a//b.php','a\\b.php'] as $file)self::assertInstanceOf(WP_Error::class,Dependencies::validate([$file]));}
 public function testMalformedAndOversizedValuesFail(){foreach(['x.php',[[1]],array_fill(0,51,'a/a.php')] as $value)self::assertInstanceOf(WP_Error::class,Dependencies::validate($value));}
 public function testMissingWooCommerceBlocksExclusionsAndAlternatives(){foreach(['is','not'] as $op)self::assertFalse(CodeSlot\Conditions::matches(['include'=>[[['key'=>'entire_site']]],'exclude'=>[[['key'=>'cart','op'=>$op]]]],['entire_site'=>true,'cart'=>false]));}
}
