<?php

const GUEST = 2;
const USER = 1;
const ADMIN = 0;

class Auth{

  public static $GROUP = [
    ADMIN => 'admin',
    USER  => 'user',
    GUEST  => 'guest',
  ];

  private static $server = null;
  private static $user;
  private static $auth;
  private static $storage = null;

  public static function configOAuth($clientID, $clientSecret, $redirectURI, $grantTypes=[]){
    $storage = new CustomStorage(App::getPdo());
    $storage->setClientDetails($clientID, $clientSecret, $redirectURI);

    // Pass a storage object or array of storage objects to the OAuth2 server class
    $server = new OAuth2\Server($storage);

    //foreach($grantTypes as $grantType){
      //$server->addGrantType($grantType);
    //}
    
    // Add the "Client Credentials" grant type (it is the simplest of the grant types)
    $server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

    // Add the "Authorization Code" grant type (this is where the oauth magic happens)
    $server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

    // Add "RefreshToken" grant type
    $server->addGrantType(new OAuth2\GrantType\RefreshToken($storage, array(
      'always_issue_new_refresh_token' => true)));

    //Add "Password" grant type
    $server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));

    Auth::$server = $server;
    Auth::$storage = $storage;
  }

  private static function createResponseFromOAuth($response){
    $data = $response->getParameters();
    $statusCode = $response->getStatusCode();
    $success = true;
    if(isset($data['error_description'])){
      return Response::fail("[OAuth] {$data['error_description']}", $statusCode);
    }
    if(isset($data['error'])){
      return Response::fail("[OAuth] {$data['error']}", $statusCode);
    }
    return Response::success($data, $statusCode);
  }

  public static function generateUsername($email){
    return urlencode($email);
  }
  public static function getOAuthRequest(){
    return OAuth2\Request::createFromGlobals();
  }

  //public static function addGrantType($grantType){
    //Auth::$server->addGrantType($grantType);
  //}

  public static function requestToken(){
    return Auth::createResponseFromOAuth(
      Auth::$server->handleTokenRequest(Auth::getOAuthRequest())
    );
  }

  public static function revokeToken(){
    return Auth::createResponseFromOAuth(
      Auth::$server->handleRevokeRequest(Auth::getOAuthRequest())
    );
  }

  public static function requireAuth(){
    // Handle a request to a resource and authenticate the access token
    if (!Auth::$server->verifyResourceRequest(Auth::getOAuthRequest())) {
      throw new KnownException('Not authorised', ERR_UNAUTHORISED);
    }
  }

  public static function requireAdminAuth(){
    $user = Auth::currentUser();
    if(!$user){
      throw new KnownException('Not authorised', ERR_UNAUTHORISED);
    }
    if($user->group != ADMIN)
      throw new KnownException('Not authorised', ERR_UNAUTHORISED);
  }

  public static function getTokenData(){
    return Auth::$server->getAccessTokenData(Auth::getOAuthRequest());
  }

  public static function hashPassword($password){
    return password_hash($password, PASSWORD_BCRYPT);
  }

  public static function verifyPassword($password, $hash){
    return password_verify($password, $hash);
  }

  public static function requireUserGroup($group){
    //TODO require user to be of certain group.. maybe this can
    //be done with oauth
    $user = Auth::currentUser();
    if($user == null)
      throw new KnownException('Not authorised', ERR_UNAUTHORISED);

    if($user->group != $group && $user->group != ADMIN){
      throw new KnownException('Not authorised', ERR_UNAUTHORISED);
    }
  }

  public static function currentUser(){
    Auth::requireAuth();
    if(Auth::$user == null){
      //TODO get user from db using api key
      $tokenData = Auth::getTokenData();
      Auth::$user = Models::find('User', [
        'username'=> $tokenData['user_id']
      ]);
    }
    return Auth::$user;
  }
}
