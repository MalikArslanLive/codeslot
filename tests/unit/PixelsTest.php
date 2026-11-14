<?php
use PHPUnit\Framework\TestCase;
use CodeSlot\Pixels;
final class PixelsTest extends TestCase {
 public function testIndependentGaAndAdsDestinations(){
  $v=Pixels::validate(['google'=>['id'=>'G-TEST1234','enabled'=>true,'events'=>['page_view']],'google_ads'=>['id'=>'AW-12345678','enabled'=>true,'events'=>['purchase'],'label'=>'Label123']],false);
  self::assertSame('G-TEST1234',$v['google']['id']);self::assertSame('AW-12345678',$v['google_ads']['id']);
 }
 public function testLegacyAdsMigrationPreservesSettings(){
  $v=Pixels::validate(['google'=>['id'=>'AW-12345678','enabled'=>true,'events'=>['click'],'label'=>'LegacyLabel']]);
  self::assertTrue($v['google_ads']['enabled']);self::assertSame('LegacyLabel',$v['google_ads']['label']);self::assertFalse($v['google']['enabled']);
 }
 public function testAdsIdCannotBeSavedInGa4Card(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google'=>['id'=>'AW-12345678']],false));}
 public function testGa4IdCannotBeSavedInAdsCard(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google_ads'=>['id'=>'G-TEST1234']],false));}
 public function testAdsPurchaseRequiresLabel(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google_ads'=>['id'=>'AW-12345678','enabled'=>true,'events'=>['purchase']]],false));}
}
