<?php
use PHPUnit\Framework\TestCase;
use CodeSlot\Pixels;
final class PixelsTest extends TestCase {
 public function testIndependentGaAndAdsDestinations(){
  $v=Pixels::validate(['google'=>['id'=>'G-TEST1234','enabled'=>true,'events'=>['page_view']],'google_ads'=>['id'=>'AW-12345678','enabled'=>true,'events'=>['purchase'],'label'=>'Label123']]);
  self::assertSame('G-TEST1234',$v['google']['id']);self::assertSame('AW-12345678',$v['google_ads']['id']);
 }
 public function testAdsIdCannotBeSavedInGa4Card(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google'=>['id'=>'AW-12345678']]));}
 public function testGa4IdCannotBeSavedInAdsCard(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google_ads'=>['id'=>'G-TEST1234']]));}
 public function testAdsPurchaseRequiresLabel(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['google_ads'=>['id'=>'AW-12345678','enabled'=>true,'events'=>['purchase']]]));}

 public function testProviderSpecificEventsDropUnsupportedAndNestedValues(){
  $v=Pixels::validate(['pinterest'=>['events'=>['page_view','begin_checkout','click',['bad']]],'google_ads'=>['events'=>['click','purchase']]]);
  self::assertSame(['page_view'],$v['pinterest']['events']);self::assertSame(['purchase'],$v['google_ads']['events']);
 }
 public function testConsentIsOptionalAndRoundTrips(){self::assertFalse(Pixels::validate([])['require_consent']);self::assertTrue(Pixels::validate(['require_consent'=>true])['require_consent']);}
 public function testSnapchatIdNeedsUuidShape(){self::assertInstanceOf(WP_Error::class,Pixels::validate(['snapchat'=>['id'=>str_repeat('-',36)]]));}
}
