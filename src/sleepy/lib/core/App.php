<?php

session_start();

class App{

  private static $pdo = null;
  /*
   * Delegates the request to the appropriate Controller
   *
   * @param $method     = Type of request (get, put, post, delete)
   *
   * @throws KnownException
   * @return null
   */

  public static function serve($request){
    try{
      if(!isset($_SERVER['REQUEST_METHOD'])){
        throw new KnownException('No request was made to the server', ERR_BAD_REQ);
      }
      $method = strtolower($_SERVER['REQUEST_METHOD']);
      $url = $request->getUrl();
      $controller = Router::getController($url);
      if($controller == null){
        throw new KnownException("No controller for route '$url'", ERR_BAD_ROUTE);
      }
      App::runControllerMethod($request, $controller)->unwrap();
    }
    catch(KnownException $exception){
      //Catch Known Exceptions
      ErrorResponse::resolveKnownException($exception);
    }
    catch(Exception $exception){
      //Catch Unexpected or Unknown Exceptions
      ErrorResponse::resolveUnknownException($exception);
    }
  }

  /*
   * Delegates the given request to the right controller.
   *
   * @param $url              = Url Route
   * @param $request_method   = Request method (post, get, put, delete)
   * @param $controller       = Controller to be used
   *
   * @throws KnownException
   * @return null
   */

  private static function runControllerMethod($request, $controller){

    $url = $request->getUrl();
    $args = explode('/', substr($url, 0, strlen($url) -1));
    $args = array_reverse($args);
    $verb = strtoupper($request->getRequestMethod());
    array_pop($args);
    if($controller instanceof RoutedController){
      $runIndex = count($args) < 1;
      $hint = array_pop($args);
      $method = strtolower($request->getRequestMethod()) . "_$hint";
      $args = array_reverse($args);
      try{
        if($runIndex){
          return $controller->index($request);
        }else{
          return $controller->$method($request, ...$args);
        }
      }catch(UnknownMethodCallException $e){
        try{
          $method = "all_$hint";
          return $controller->$method($request, ...$args);
        }catch(UnknownMethodCallException $e){
          throw new KnownException("No controller for route or invalid request method $verb => '$url'", ERR_BAD_ROUTE);
        }
      }
    }
    $method = strtolower($request->getRequestMethod());
    $args = array_reverse($args);
    try{
      return $controller->$method($request, ...$args);
    }catch(Exception $e){
      throw new KnownException("No controller for route or invalid request method $verb => '$url'", ERR_BAD_ROUTE);
    }
  }

  public static function getPdo(){
    if(App::$pdo == null){
      $dsn = DSN;
      $dbname = DB_NAME;
      $dbhost = DB_HOST;
      $dbuser = DB_USER;
      $dbpass = DB_PASS;
      $pdo = new PDO("$dsn:dbname=$dbname;host=$dbhost;", $dbuser, $dbpass);
      if(DEBUG)
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      App::$pdo = $pdo;
    }
    return App::$pdo;
  }
}
