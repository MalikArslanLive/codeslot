<?php
use PHPUnit\Framework\TestCase;
use CodeSlot\Permissions;
use CodeSlot\Conditions;
use CodeSlot\Dependencies;
final class ValidationTest extends TestCase {
 public function testSlotByteLimitPreservesExactBoundary(){
  $source=str_repeat('a',1048576);
  self::assertSame($source,Permissions::validate_slots(['header'=>['code'=>$source]])['header']['code']);
  self::assertInstanceOf(WP_Error::class,Permissions::validate_slots(['header'=>['code'=>$source.'a']]));
  self::assertInstanceOf(WP_Error::class,Permissions::validate_slots(['header'=>['code'=>str_repeat('é',524289)]]));
 }
 public function testMalformedSlotsFailBeforeNormalization(){foreach([null,['unknown'=>[]],['header'=>'code'],['header'=>['code'=>null]],['header'=>['enabled'=>[]]]] as $slots)self::assertInstanceOf(WP_Error::class,Permissions::validate_slots($slots));}
 public function testCorruptSavedConditionsPauseSafely(){foreach(['invalid',['include'=>[['invalid']]],['exclude'=>['invalid']]] as $conditions)self::assertNotEmpty(Conditions::missing($conditions));}
 public function testExplicitNullRulesAndRequirementsAreRejected(){foreach(['conditions','requires'] as $key)self::assertInstanceOf(WP_Error::class,CodeSlot\Repository::validate(['type'=>'html','code'=>'x','location'=>'header',$key=>null]));}
 public function testCorruptLocationHasClearReason(){self::assertSame('Invalid saved insertion location.',Dependencies::location_reason(['footer']));}
}
