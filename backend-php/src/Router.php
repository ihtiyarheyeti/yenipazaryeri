<?php
namespace App;
final class Router {
  private array $routes = ['GET'=>[],'POST'=>[],'PUT'=>[],'DELETE'=>[]];
  
  public function get($p,$h){
    $this->routes['GET'][$p]=$h;
    error_log("GET route registered: " . $p);
  }
  
  public function post($p,$h){
    $this->routes['POST'][$p]=$h;
    error_log("POST route registered: " . $p);
  }
  
  public function put($p,$h){
    $this->routes['PUT'][$p]=$h;
    error_log("PUT route registered: " . $p);
  }
  
  public function delete($p,$h){
    $this->routes['DELETE'][$p]=$h;
    error_log("DELETE route registered: " . $p);
  }
  
  // Route'ları getir
  public function getRoutes(string $method): array {
    return $this->routes[$method] ?? [];
  }
  
  public function dispatch(string $method,string $uri):array {
    $path=parse_url($uri,PHP_URL_PATH);
    
    // Base path'i çıkar (yenipazaryeri/backend-php/public)
    $basePath = '/yenipazaryeri/backend-php/public';
    if (strpos($path, $basePath) === 0) {
      $path = substr($path, strlen($basePath));
    }
    
    // index.php'yi path'den çıkar
    if (strpos($path, '/index.php') === 0) {
      $path = substr($path, strlen('/index.php'));
    }
    
    // Debug için path'i logla
    error_log("Requested path: " . $path);
    error_log("Method: " . $method);
    error_log("All routes: " . json_encode($this->routes));
    error_log("Available routes for " . $method . ": " . json_encode($this->routes[$method]));
    
    // Exact match kontrol et
    if(isset($this->routes[$method][$path])){ 
      error_log("Exact match found for: " . $path);
      return $this->invoke($this->routes[$method][$path],[]); 
    }
    
    // Pattern match kontrol et
    foreach($this->routes[$method] as $pattern=>$handler){
      $regex='#^'.preg_replace('#\{[a-zA-Z_]+\}#','([a-zA-Z0-9_-]+)',$pattern).'$#';
      if(preg_match($regex,$path,$m)){
        error_log("Pattern match found: " . $pattern . " for " . $path);
        array_shift($m);
        return $this->invoke($handler,$m);
      }
    }
    
    // Route bulunamadı
    http_response_code(404);
    $response = ['ok'=>false,'error'=>'Not Found','path'=>$path,'method'=>$method,'available_routes'=>$this->routes[$method]];
    echo json_encode($response, JSON_PRETTY_PRINT);
    return $response;
  }
  
  private function invoke($handler,array $params):array {
    if(is_array($handler)){
      [$class,$method]=$handler;
      $ctrl=new $class();
      $body=self::readJson();
      $query=$_GET??[];
      return $ctrl->$method($params,$body,$query);
    } else {
      $body=self::readJson();
      $query=$_GET??[];
      return call_user_func($handler,$params,$body,$query);
    }
  }
  
  public static function readJson():array {
    $raw=file_get_contents('php://input')?:'';
    $d=json_decode($raw,true);
    return is_array($d)?$d:[];
  }
}
