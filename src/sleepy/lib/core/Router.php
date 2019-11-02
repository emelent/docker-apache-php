<?php

// ===========================================================================
//
//
// This file contains the class declaration of the Router class
//
// Please note that any methods or classes beginning with an underscore whether
// public or private are not meant to be called directly by a user but are for 
// the inner functioning of the library as a whole.
//
//
// ===========================================================================


/*
 * Router class used to handle the routing of url's to specific controllers
 */
class Router{

  private static $primary_routes = [];
  private static $secondary_routes = [];

  // using '/' as domain, 
  // e.g. if using '/api/' as domain, then
  //      the domain would be '/^api\/'
  // so '/^<domain>\/'
  private static $domain = '/^'; 
  private static $url = null;

  private static function _getController($url, $routes){
    foreach($routes as $regex  => $ctrl){
      if(preg_match($regex, $url)){
        return $ctrl;
      }
    }

    //TODO "I'm a hack, a cheap hack, nobody's gonna fix me, nahahahahaha"
    //did this to solve routing to index on RoutedController so that
    //if a RoutedController(HelloController) is routed to 'helloworld/*', a url of '/helloworld/' 
    //should return HelloController
    $url = implode('\/', explode('/', $url));
    $url = "/^$url(.)*\/$/"; 
  
    if(isset($routes[$url])){
      return $routes[$url];
    }
    return null;
  }

  /*
   * Returns the controller routed to the current request url
   * or null if the url does not match any route
   *
   * @return Controller|null
   */
  public static function getController($url){
    $ctrl = Router::_getController($url, Router::$primary_routes);
    if($ctrl != null)  
      return $ctrl;
    
    return Router::_getController($url, Router::$secondary_routes);
  }


  /*
   * Receives the given routes and turns all url routes into regular 
   * expressions and puts them in a key value store with the given 
   * Controller to be accessed later on
   *
   *
   * @param $routes = Assoc Array containing key value store of the path 
   *                  and controller/function
   *
   * @throws KnownException
   * @return null
   */
  public static function _route($routes, $primary){
    foreach($routes as $url => $controller){
      //always end routing url with '/'
      if(substr($url, -1, 1) != '/')
        $url .= '/';
      $exp = explode('/', $url);
      $regex = Router::$domain;
      $first = true;
      $count =0;
      $params = [];
      foreach($exp as $part){
        if($count != 0){$regex .= '\/';}
        $count ++;
        if(substr($part, 0, 1) == ':'){
          $regex .= '([a-zA-Z0-9_-]+)';
          $params[substr($part, 1)] = $count;
          continue;
        }elseif($part == '*'){
          $regex .= '(.)*';
          continue;
        }
        $regex .= $part;
      }
      $regex .= '$/';

      if($controller instanceof Closure){
        $controller = new _InjectController($controller);
      }

      if(gettype($controller) == 'array'){
        $controller = new _InjectController($controller[1], $controller[0]);
      }

      // just in case the given Controller is not an instance of Controller
      // best to handle it ourselves with a tidy exception than have PHP
      // output warnings and errors
      if(!$controller instanceof Controller)
        throw new KnownException("Invalid object registered as controller", ERR_BAD_ROUTE);
      
      
      Request::getInstance()->_setParams($params);
      if($primary)
        Router::$primary_routes[$regex] = $controller;
      else
        Router::$secondary_routes[$regex] = $controller;
    }
  }

  public static function route($routes){
    Router::_route($routes, true);
  } 

  public static function route_secondary($routes){
    Router::_route($routes, false);
  } 
}
