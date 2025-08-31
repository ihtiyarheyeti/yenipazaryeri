<?php
namespace App\Controllers;
use App\Database;
final class UploadTenantLogoController {
  public function upload(array $p, array $b, array $q): array {
    $tenant=(int)($q['tenant_id']??1);
    if(!isset($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) return ['ok'=>false,'error'=>'file'];
    $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['png','jpg','jpeg','webp','svg'])) return ['ok'=>false,'error'=>'ext'];
    $name='t'.$tenant.'_logo_'.time().'.'.$ext;
    $dst=__DIR__.'/../../public/uploads/'.$name;
    if(!move_uploaded_file($_FILES['file']['tmp_name'],$dst)) return ['ok'=>false,'error'=>'move'];
    $url='/uploads/'.$name;
    
    // Küçük thumbnail üret (128x48)
    $thumb = preg_replace('/(\.\w+)$/','_thumb$1',$url);
    @\App\Utils\Image::makeThumb(__DIR__.'/../../public'.$url, __DIR__.'/../../public'.$thumb, 128, 48);
    
    Database::pdo()->prepare("UPDATE tenants SET logo_url=? WHERE id=?")->execute([$thumb,$tenant]);
    return ['ok'=>true,'url'=>$url];
  }
}
