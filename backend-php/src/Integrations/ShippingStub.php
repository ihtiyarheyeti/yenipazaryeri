<?php
namespace App\Integrations;

final class ShippingStub {
  public static function createLabel(string $carrier, array $payload): array {
    // gerçek entegrasyon yerine sabit bir URL üret
    $id = $payload['shipment_id'] ?? rand(1000,9999);
    $fakeUrl = "https://labels.example.com/{$carrier}/LBL-".$id.".pdf";
    $track = strtoupper(substr($carrier,0,3))."-".date('ymd')."-".$id;
    return ['ok'=>true,'label_url'=>$fakeUrl,'tracking_no'=>$track,'raw'=>['carrier'=>$carrier]];
  }
}
