<?php

Router::route([
  'account/*' => new UserController(),
  'auth/*' => new AuthController(),

  'migrate/' => function($request){
    //TODO remove this after adding proper migration script
    //echo ModelManager::getSqlSchema() . "\n";
    ModelManager::recreateTables();
    App::getPdo()->query("
      DROP TABLE oauth_clients;
      DROP TABLE oauth_access_tokens;
      DROP TABLE oauth_authorization_codes;
      DROP TABLE oauth_refresh_tokens;
      DROP TABLE oauth_scopes;
      DROP TABLE oauth_jwt;

      CREATE TABLE oauth_clients (client_id VARCHAR(80) NOT NULL, client_secret VARCHAR(80), redirect_uri VARCHAR(2000) NOT NULL, grant_types VARCHAR(80), scope VARCHAR(100), user_id VARCHAR(80), CONSTRAINT clients_client_id_pk PRIMARY KEY (client_id));
      CREATE TABLE oauth_access_tokens (access_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT access_token_pk PRIMARY KEY (access_token));
      CREATE TABLE oauth_authorization_codes (authorization_code VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), redirect_uri VARCHAR(2000), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT auth_code_pk PRIMARY KEY (authorization_code));
      CREATE TABLE oauth_refresh_tokens (refresh_token VARCHAR(40) NOT NULL, client_id VARCHAR(80) NOT NULL, user_id VARCHAR(255), expires TIMESTAMP NOT NULL, scope VARCHAR(2000), CONSTRAINT refresh_token_pk PRIMARY KEY (refresh_token));
      CREATE TABLE oauth_scopes (scope TEXT, is_default BOOLEAN);
      CREATE TABLE oauth_jwt (client_id VARCHAR(80) NOT NULL, subject VARCHAR(80), public_key VARCHAR(2000), CONSTRAINT jwt_client_id_pk PRIMARY KEY (client_id));
    ");
    return Response::success("Migration successful");
  },
  'hello/:name/:age' => function($request, $name, $age){
    return Response::success("Hello $name $age");
  },
  'bye/:name/:age' => ['get', function($request, $name, $age){
    return Response::success("Bye $name $age");
  }],
  '/' => function($request){
    return Response::success("Kshhhh. Ground control to Major Tom, we have ReST, I repeat, we have ReST.");
  }
]);

