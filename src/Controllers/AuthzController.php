<?php
namespace App\Controllers;
use App\Database;

final class AuthzController {
  public function roles(array $p, array $b, array $q): array {
    $st=Database::pdo()->query("SELECT * FROM roles");
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
  public function permissions(array $p, array $b, array $q): array {
    $st=Database::pdo()->query("SELECT * FROM permissions");
    return ['ok'=>true,'items'=>$st->fetchAll()];
  }
  public function assignRole(array $p, array $b): array {
    $st=Database::pdo()->prepare("INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (?,?)");
    $st->execute([(int)$b['user_id'],(int)$b['role_id']]);
    return ['ok'=>true];
  }
  public function revokeRole(array $p, array $b): array {
    $st=Database::pdo()->prepare("DELETE FROM user_roles WHERE user_id=? AND role_id=?");
    $st->execute([(int)$b['user_id'],(int)$b['role_id']]);
    return ['ok'=>true];
  }

  public function setRoles(array $p, array $b): array {
    $userId = (int)($b['user_id']??0);
    $roles  = $b['roles']??[];
    if($userId<=0 || !is_array($roles)) return ['ok'=>false,'error'=>'user_id and roles[] required'];

    $pdo = \App\Database::pdo();
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM user_roles WHERE user_id=?")->execute([$userId]);
    $ins = $pdo->prepare("INSERT INTO user_roles (user_id,role_id) VALUES (?,?)");
    foreach($roles as $rid){
      $ins->execute([$userId,(int)$rid]);
    }
    $pdo->commit();
    return ['ok'=>true];
  }

  public function userPermissions(array $p, array $b, array $q): array {
    $userId=(int)($q['user_id']??0);
    if($userId<=0) return ['ok'=>false,'error'=>'user_id required'];
    $pdo=\App\Database::pdo();
    $st=$pdo->prepare("
      SELECT DISTINCT perm.name
      FROM user_roles ur
      JOIN role_permissions rp ON rp.role_id=ur.role_id
      JOIN permissions perm ON perm.id=rp.permission_id
      WHERE ur.user_id=?
    ");
    $st->execute([$userId]);
    $perms=$st->fetchAll(\PDO::FETCH_COLUMN);
    return ['ok'=>true,'permissions'=>$perms];
  }
}
