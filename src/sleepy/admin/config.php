<?php

//=====================================
// CONFIGURATION
//=====================================

/* Resource constants */
$temp = explode('/', dirname(__FILE__));
array_pop($temp);

/* Turn this to false for Production */
define('DEBUG', true);


/* Database constants */
define('DSN', 'mysql');
define('DB_HOST', 'mariadb');
define('DB_NAME', 'sleepy');
define('DB_USER', 'root');
define('DB_PASS', 'toor');

/* Directory constants */
define('ROOT_DIR', implode('/', $temp) . '/');
define('LIB_DIR', ROOT_DIR . 'lib/');
define('HOME_URL', 'http://localhost/sleepy/');

/* Error codes */
define('ERR_UNK_REQ', 0);  //unknown request method
define('ERR_BAD_REQ',  1); //invalid data
define('ERR_INCOMP_REQ', 2); //missing data
define('ERR_BAD_TOKEN',  3); //bad form token
define('ERR_BAD_AUTH',  4); //bad auth key
define('ERR_EXP_AUTH', 5);  //expired auth key
define('ERR_UNAUTHORISED', 6); //unauthorised access attempt
define('ERR_UNEXPECTED', 7); //unexpected error
define('ERR_DB_ERROR', 8); //database and sql errors
define('ERR_BAD_LOGIN', 9); //thrown upon login failure
define('ERR_BAD_ROUTE',  10); //bad auth key


/* Set timezone */
date_default_timezone_set("UTC");

/* Use this to wrap exceptions thrown by library */
class KnownException extends Exception{}

class UnknownMethodCallException extends Exception{}


if(DEBUG){
  //Turn on error reporting
  ini_set('display_errors',1);
  error_reporting(E_ALL);
}else{
  // Turn off all error reporting
  error_reporting(0);
}


//Create db if not exist
function checkDb(){
  $dsn = DSN;
  $dbname = DB_NAME;
  $dbhost = DB_HOST;
  $dbuser = DB_USER;
  $dbpass = DB_PASS;
  try{
    $pdo= new PDO("$dsn:host=$dbhost", $dbuser, $dbpass);
    $results = $pdo->query("SHOW DATABASES LIKE $dbname");
    if(!$results){
      $query = "
        CREATE DATABASE $dbname;
        USE $dbname;
        CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80), redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT clients_client_id_pk PRIMARY KEY (client_id));
        CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
        CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));
        CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
        CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);
        CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT jwt_client_id_pk PRIMARY KEY (client_id));
      ";
      $pdo->query($query);
    }else{
        $pdo = new PDO("$dsn:dbname=$dbname;host=$dbhost;", $dbuser, $dbpass);
        $query = "
        CREATE TABLE IF NOT EXIST oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80), redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT clients_client_id_pk PRIMARY KEY (client_id));
        CREATE TABLE IF NOT EXIST oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
        CREATE TABLE IF NOT EXIST oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));
        CREATE TABLE IF NOT EXIST oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
        CREATE TABLE IF NOT EXIST oauth_scopes (scope TEXT, is_default BOOLEAN);
        CREATE TABLE IF NOT EXIST oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT jwt_client_id_pk PRIMARY KEY (client_id));
      ";
      $pdo->query($query);
    }
  }catch(Exception $e){
    echo $e->getMessage();
  }
}

//best to comment this out when db is ready
checkDb();

//Load OAuth2 modules
require_once(LIB_DIR . 'oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();


//=============================================
// LOAD CORE LIB
//=============================================

$CORE = [
  'App',
  'Auth',
  'Controller',
  'CustomStorage',
  'ErrorResponse',
  'Model',
  'ModelManager',
  'Request',
  'Response',
  'Router',
  'utils',
];

foreach($CORE as $src){
  require_once(LIB_DIR . "core/$src.php");
}



//=============================================
// OAUTH2 Config
//=============================================

define('CLIENT_ID', 'client');
define('CLIENT_SECRET', 'secret');
define('REDIRECT_URI', HOME_URL);

Auth::configOAuth(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

//=============================================
// LOAD APP MODULES
//=============================================

$MODULES = [
  'admin',
  'rocketfool'
];

foreach($MODULES as $module){
  require_once(ROOT_DIR . $module . '/models.php');
  require_once(ROOT_DIR . $module . '/controllers.php');
  require_once(ROOT_DIR . $module . '/routes.php');
}

//create generic model routes
ModelManager::createModelRoutes();
