<?php
namespace App\Controllers;
use App\Database;

final class InvoiceController {
  public function create(array $p,array $b): array {
    $orderId=$b['order_id']??null; 
    $ext=$b['order_external_id']??null;
    Database::pdo()->prepare("INSERT INTO invoices(tenant_id,mp,order_id,order_external_id,status) VALUES(?,?,?,?, 'pending')")
      ->execute([\App\Context::$tenantId,'internal',$orderId,$ext]);
    $id=(int)Database::pdo()->lastInsertId();
    \App\Database::pdo()->prepare("INSERT INTO jobs(type,status,payload,created_at) VALUES('generate_invoice','pending',?,NOW())")
      ->execute([json_encode(['invoice_id'=>$id])]);
    \App\Middleware\Audit::log(null,'invoice.create',"/invoices/$id");
    return ['ok'=>true,'id'=>$id];
  }

  public function attachPdf(array $p,array $b): array {
    $id=(int)$p[0]; 
    $no=$b['number']??null; 
    $pdf=$b['pdf_url']??null; 
    $status=$b['status']??'generated';
    Database::pdo()->prepare("UPDATE invoices SET number=?, pdf_url=?, status=? WHERE id=?")
      ->execute([$no,$pdf,$status,$id]);
    \App\Middleware\Audit::log(null,'invoice.update',"/invoices/$id",['status'=>$status]);
    return ['ok'=>true];
  }
}
