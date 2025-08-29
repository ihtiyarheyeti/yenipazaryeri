<?php
namespace App\Utils;

final class Image {
  public static function makeThumb(string $src, string $dst, int $maxW=400, int $maxH=400): bool {
    if (!extension_loaded('gd')) return false;
    [$w,$h,$type] = getimagesize($src);
    $ratio = min($maxW/$w, $maxH/$h, 1);
    $tw = (int)($w*$ratio); $th=(int)($h*$ratio);
    $im = self::open($src, $type);
    if (!$im) return false;
    $canvas = imagecreatetruecolor($tw, $th);
    imagealphablending($canvas, false); 
    imagesavealpha($canvas, true);
    imagecopyresampled($canvas, $im, 0, 0, 0, 0, $tw, $th, $w, $h);
    $ok = self::saveAuto($canvas, $dst); 
    imagedestroy($im); 
    imagedestroy($canvas);
    return $ok;
  }

  public static function toWebp(string $src, string $dst, int $quality=82): bool {
    if (!extension_loaded('gd')) return false;
    [$w,$h,$type] = getimagesize($src);
    $im = self::open($src, $type); 
    if (!$im) return false;
    $ok = imagewebp($im, $dst, $quality); 
    imagedestroy($im); 
    return $ok;
  }

  private static function open($src, $type) {
    return match($type) {
      IMAGETYPE_JPEG => imagecreatefromjpeg($src),
      IMAGETYPE_PNG  => imagecreatefrompng($src),
      IMAGETYPE_WEBP => imagecreatefromwebp($src),
      default => null
    };
  }

  private static function saveAuto($im, $dst) {
    $ext = strtolower(pathinfo($dst, PATHINFO_EXTENSION));
    return match($ext) {
      'jpg', 'jpeg' => imagejpeg($im, $dst, 82),
      'png'        => imagepng($im, $dst, 6),
      'webp'       => imagewebp($im, $dst, 82),
      default      => false
    };
  }
}
