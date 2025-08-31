<?php
namespace App\Controllers;
use App\Database;

final class PermissionsController {
  public function index(): array {
    $r = \App\Database::pdo()->query("SELECT id,name FROM permissions ORDER BY name ASC")->fetchAll();
    return ['ok'=>true,'items'=>$r];
  }
}
