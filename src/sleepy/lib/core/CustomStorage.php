<?php

/// Just overrides OAuth2\Storage\Pdo's password hash method
class CustomStorage extends OAuth2\Storage\Pdo{
  
  public function __construct($connection, $config = []){
    parent::__construct($connection, $config);
    $this->config = array_merge(array(
        'client_table' => 'oauth_clients',
        'access_token_table' => 'oauth_access_tokens',
        'refresh_token_table' => 'oauth_refresh_tokens',
        'code_table' => 'oauth_authorization_codes',
        'user_table' => 'users',
        'jwt_table'  => 'oauth_jwt',
        'jti_table'  => 'oauth_jti',
        'scope_table'  => 'oauth_scopes',
        'public_key_table'  => 'oauth_public_keys',
    ), $config);
  } 

  protected function checkPassword($user, $password){
    return Auth::verifyPassword($password, $user['password']);
  }

  public function setUser($username, $password, $firstName = null, $lastName = null)
  {
      $password = Auth::hashPassword($password);

      if ($this->getUser($username)) {
          $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET password=:password, first_name=:firstName, last_name=:lastName where username=:username', $this->config['user_table']));
      } else {
          $stmt = $this->db->prepare(sprintf('INSERT INTO %s (username, password, first_name, last_name) VALUES (:username, :password, :firstName, :lastName)', $this->config['user_table']));
      }

      return $stmt->execute(compact('username', 'password', 'firstName', 'lastName'));
  }
}
