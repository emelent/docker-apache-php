<?php

class Request{

  private static $INSTANCE = null;

  private $headers;
  private $params;
  private $url;
  //private $apiKey;

  public static function getInstance(){
    if(Request::$INSTANCE == null){
      Request::$INSTANCE = new Request();
    }
    return Request::$INSTANCE;
  }

  private function __construct(){
    if(function_exists('getallheaders')){
      $this->headers = getallheaders();
    }else{
      $this->headers = [];
      //TODO implement the nginx version
    }
    //$this->apiKey = getHeader(API_KEY);
  }

  //public function getApiKey(){
    //return $this->apiKey;
  //}
  public function getRequestMethod(){
    return $_SERVER['REQUEST_METHOD'];
  }

  public function getRemoteIp(){
    return $_SERVER['REMOTE_ADDR'];
  }

  public function getHeader($key){
    if(isset($this->headers[$key])){
      return $this->headers[$key];
    }
    return null;
  }

  public function _setParams($params){
    $this->params = $params;
    $url = $this->getUrl();
    $parts = explode('/', $url);
    $index = 1;
    if(count($parts) <= count($params)){
      return;
    }
    foreach($params as $key => $val){
      $this->params[$key] = $parts[$index++];
    }

    return $this->params;
  }

  public function getParam($key){
    if(isset($this->params[$key])){
      return $this->params[$key];
    return $this->params;
    }
    return null;
  }

  public function getParams(){
    return $this->params;
  }


  public function getHeaders(){
    return $this->headers;
  }

  public function getRawUrl(){
    return $_SERVER['REQUEST_URI'];
  }

  /*
   * Returns the request url written in a neat form with
   * each request parameter following the next after a forward slash,
   * much like what the .htaccess file does. This is to give order to
   * the position of the request parameters so that the request can
   * be routed using regular expressions
   *
   * @return String
   */
  public function getUrl(){
    if($this->url == null){
      $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
      $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
      if (strstr($uri, '?'))
        $uri = substr($uri, 0, strpos($uri, '?'));
      if(substr($uri, -1) != '/')
        $uri .= '/';
      $this->url = $uri;
    }
    return $this->url;
  }
}
