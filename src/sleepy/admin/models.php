<?php

//USER META
class UserMeta extends ModelMeta{
  protected $hidden_attr = ['password'];

  public function __construct(){
    parent::__construct('users', [
      'email'           => new CharField(40, ['unique' => true]),
      'username'        => new CharField(255, ['unique' => true]),
      'uid'             => new CharField(255, ['unique' => true]),
      'password'        => new CharField(255),
      'created'         => new DateTimeField(['default'=> 'CURRENT_TIMESTAMP']),
      'activation_date' => new DateTimeField(['null' => true]),
      'group'           => new IntegerField(['default'=> USER]),
      'first_name'      => new CharField(255, ['null'=>true]),  
      'last_name'       => new CharField(255, ['null'=>true]),  
    ]); 
    //$this->acl['READ'] = [ModelMeta::$AUTH_READ];
    $this->acl['WRITE'] = ModelMeta::$AUTH_WRITE;
  }
}

//USER MODEL
class User extends Model{

  public function setPassword($pass){
    $this->password = Auth::hashPassword($pass);
  }

  public function setUsername($username){
    if(Models::find(get_class($this), ['username'=>$username])){
      Response::fail('Username already exists.')->unwrap();
    }
    $this->username = $username;  
  }

  public function setEmail($email){
    if(Models::find(get_class($this), ['email'=>$email])){
      Response::fail('Email already exists.')->unwrap();
    }
    $this->email = $email;  
  }
}

class ActivationCodeMeta extends ModelMeta{

  public function __construct(){
    parent::__construct('activation_codes', [
      'code'      => new CharField(256),
      'user_id'   => new CustomField("INT NOT NULL REFERENCES users(id)"),
      'expires'   => new DateTimeField()
    ]);
  }
}
class ActivationCode extends Model{}

//register models
ModelManager::register('User');
ModelManager::register('ActivationCode');
