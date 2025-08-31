<?php
namespace App\Utils;

final class Http {
  /**
   * @return array [$statusCode, $body, $err, $headers]
   */
  public static function request(string $method, string $url, array $headers=[], $body=null, int $timeout=30): array {
    $ch=curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER => true,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $hdr=[]; foreach($headers as $k=>$v){ $hdr[]="$k: $v"; }
    if($hdr) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
    if($body!==null){
      if(is_array($body)){ $body=json_encode($body, JSON_UNESCAPED_UNICODE); $hdr[]="Content-Type: application/json"; curl_setopt($ch,CURLOPT_HTTPHEADER,$hdr); }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp=curl_exec($ch);
    if($resp===false){ $err=curl_error($ch); curl_close($ch); return [0,null,$err,[]]; }
    $header_size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $rawHead=substr($resp,0,$header_size);
    $body=substr($resp,$header_size);
    $status=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // headers parse
    $headersOut=[];
    foreach(explode("\r\n",$rawHead) as $line){
      if(strpos($line,':')!==false){
        [$k,$v]=explode(':',$line,2);
        $headersOut[trim($k)]=trim($v);
      }
    }
    return [$status,$body,null,$headersOut];
  }

  public static function json(string $method,string $url,array $headers=[], $body=null,int $timeout=30): array{
    $headers=array_merge(['Accept'=>'application/json'], $headers);
    [$code,$raw,$err,$h]=self::request($method,$url,$headers,$body,$timeout);
    $data=null; if($raw!==null){ $json=json_decode($raw,true); $data=$json!==null? $json : $raw; }
    return [$code,$data,$err,$h];
  }
}
