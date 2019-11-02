<?php

//TODO lot of influence from oauth2-server-php by BShaffer
class Response{

  private $statusCode;
  private $success;
  private $data;
  private $headers;

  public static $statusTexts = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      418 => 'I\'m a teapot',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported',
  );

  public function __construct($success, $data, $statusCode=200){
    $this->statusCode = $statusCode;
    $this->success = $success;
    $this->headers = [];
    $this->data = $data;
  }

  public static function success($data=null, $statusCode=200){
    return new Response(true, $data, $statusCode);
  }

  public static function fail($data, $statusCode=200){
    return new Response(false, $data, $statusCode);
  }

  public function toJSON(){
    return json_encode([
      'successful' => $this->success,
      'data' =>  $this->data
    ]);
  }

  public function unwrap(){
    if(!headers_sent()){
      $this->addHeader('Content-Type', 'application/json');
      $this->buildHeaders();
      http_response_code($this->statusCode);
    }
    die($this->toJSON() . PHP_EOL);
  }

  public function setstatusCode($statusCode){
    if(in_array($statusCode, array_keys($this::statusCodeTexts))){
      $this->statusCode = $statusCode;
    }
  }

  protected function buildHeaders(){
    $builtHeaders ='';
    foreach($this->headers as $name => $value){
      header("$name: $value");
    }
  }
  
  public function getStatusCode(){
    return $this->statusCode;
  }

  public function getStatusText(){
    return $this::$statusTexts[$this->statusCode];
  }

  public function isSuccessful(){
    return $this->success;
  }

  public function getData(){
    return $this->data;
  }

  public function setHeaders($headers){
    $this->headers = $headers;
  }

  public function getHeaders(){
    return $this->headers;
  }

  public function addHeader($name, $value){
    $this->headers[$name] = $value;
  }
}
