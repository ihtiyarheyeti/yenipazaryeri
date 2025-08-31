<?php
namespace App\Controllers;
use App\Database;

final class UploadController {
  public function productImage(array $p, array $b, array $q) {
    $pid = (int)($q['product_id'] ?? 0); 
    if($pid <= 0) return ['ok' => false, 'error' => 'product_id'];
    
    if(!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      return ['ok' => false, 'error' => 'file'];
    }
    
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
      return ['ok' => false, 'error' => 'ext'];
    }
    
    $name = 'p' . $pid . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dst = __DIR__ . '/../../public/uploads/products/' . $name;
    
    if(!move_uploaded_file($_FILES['file']['tmp_name'], $dst)) {
      return ['ok' => false, 'error' => 'move'];
    }
    
    $url = '/uploads/products/' . $name;
    
    // Thumbnail üret
    $path = __DIR__.'/../../public'.$url;
    $thumbUrl = preg_replace('/(\.\w+)$/','_thumb$1',$url);
    $thumbPath = __DIR__.'/../../public'.$thumbUrl;
    @\App\Utils\Image::makeThumb($path, $thumbPath, 400, 400);
    
    // WebP versiyonları üret
    $webpUrl = preg_replace('/\.\w+$/','.webp',$url);
    $webpAbs = __DIR__.'/../../public'.$webpUrl;
    @\App\Utils\Image::toWebp($path, $webpAbs, 82);
    
    $thumbWebp = preg_replace('/\.\w+$/','.webp',$thumbUrl);
    $thumbWebpAbs = __DIR__.'/../../public'.$thumbWebp;
    @\App\Utils\Image::toWebp($thumbPath, $thumbWebpAbs, 82);
    
    // Boyut bilgilerini al
    $size = @getimagesize($path); $w=$size[0]??null; $h=$size[1]??null;
    
    $st = Database::pdo()->prepare("
      INSERT INTO product_images (product_id, url, thumb_url, webp_url, thumb_webp, sort_order, width, height) 
      VALUES (?, ?, ?, ?, ?, (SELECT IFNULL(MAX(sort_order), 0) + 1 FROM product_images WHERE product_id = ?), ?, ?)
    ");
    $st->execute([$pid, $url, $thumbUrl, $webpUrl, $thumbWebp, $pid, $w, $h]);
    
    return ['ok' => true, 'url' => $url];
  }

  public function list(array $p, array $b, array $q) {
    $pid = (int)($q['product_id'] ?? 0);
    $st = \App\Database::pdo()->prepare("
      SELECT * FROM product_images 
      WHERE product_id = ? 
      ORDER BY sort_order ASC, id ASC
    ");
    $st->execute([$pid]);
    return ['ok' => true, 'items' => $st->fetchAll()];
  }

  public function delete(array $p) {
    $id = (int)($p[0] ?? 0);
    $pdo = \App\Database::pdo();
    
    $st = $pdo->prepare("SELECT url, thumb_url, webp_url, thumb_webp FROM product_images WHERE id = ?"); 
    $st->execute([$id]); 
    $row = $st->fetch();
    
    if($row) { 
      @unlink(__DIR__ . '/../../public' . $row['url']); 
      @unlink(__DIR__ . '/../../public' . $row['thumb_url']); 
      @unlink(__DIR__ . '/../../public' . $row['webp_url']); 
      @unlink(__DIR__ . '/../../public' . $row['thumb_webp']); 
    }
    
    $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$id]);
    return ['ok' => true];
  }
}
