#!/usr/bin/env php
<?php
require __DIR__.'/../vendor/autoload.php';
use App\Integrations\WooAdapter;

$tenant = (int)getenv('TENANT_ID') ?: 1;
$conn = \App\Database::pdo()->query("SELECT * FROM marketplace_connections WHERE tenant_id={$tenant} AND marketplace_id=2 ORDER BY id DESC LIMIT 1")->fetch();
if(!$conn){ fwrite(STDERR,"woo conn missing\n"); exit(2); }
$woo = new WooAdapter($conn);

$page=1; $per=50; $count=0;
while(true){
  $res=$woo->listProducts($page,$per,null);
  if(empty($res['ok'])) break;
  $items=$res['data'] ?: [];
  foreach($items as $p){
    $payload = [
      'origin'=>'woo',
      'origin_id'=>(string)($p['id']??''),
      'product'=>[
        'name'=>$p['name']??'',
        'brand'=>$p['brand']??($p['brands'][0]['name']??null),
        'description'=>$p['description']??null,
        'category_external'=> $p['categories'][0]['id'] ?? null
      ],
      'variants'=>[
        [
          'sku'=>$p['sku']??null,
          'price'=>$p['price']??null,
          'stock'=>$p['stock_quantity']??null,
          'attrs'=>[]
        ]
      ]
    ];
    \App\Utils\Http::json('POST', getenv('STD_ENDPOINT')?:'http://nginx/standardize', ['Content-Type'=>'application/json'], $payload, 30);
    $count++;
  }
  if(count($items)<$per) break; $page++;
}
fwrite(STDOUT,"woo synced: $count\n");

