<?php

class UserController extends RoutedController{

  public function post_create($request){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = strtolower($_POST['email']);
    $pass = $_POST['password'];

    if(!validateEmail($email)){
      return Response::fail("Invalid email address.");
    }

    //prevent duplicate email entries, this is already done by our DDL in
    //the User model's meta class, but to prevent from getting nasty SQL Db
    //exceptions for trying to enter a duplicate unique field we'll return
    //a failed response ourselves instead of letting the API handle the
    //exception thrown by our database so we can have a more detailed and clear
    //failure reason
    if(Models::find('User', ['email'=> $email])){
      return Response::fail('Email already in use.');
    }
    $user = new User([
      'email'     => $email,
      'password'  => $pass,
      'username'  => Auth::generateUsername($email),
      'uid'       => uniqid('uid', true)
    ]);
    $user->save();
    //create new activation code
    $key = hash('sha256', uniqid('c0d3', true));
    $code = new ActivationCode([
      'user_id' => $user->getId(),
      'expires' => date("Y-m-d H:i:s", strtotime("+7 day")),
      'code'    => $key
    ]);
    $code->save();
    $link = HOME_URL . "account/activate/$key/";
    $message = "Click $link to activate your account.";

    if(!mail($email, 'Account Activation Link', $message));
      Response::success("Account created.");
    return Response::success("Account successfully created.");
  }

  public function post_delete($request){
    $user = Auth::currentUser();
    Auth::revokeToken();
    $user->delete();
    return Response::success("Account successfully deleted.");
  }

  public function get_emailAvailable($request, $email){
    if(!isset($email)){
      //TODO put a proper http response code
      return Response::fail("No email address sent.");
    }

    if(!validateEmail($email)){
      return Response::fail("Invalid email address.");
    }
    if(Models::find('User', ['email'=> $email]))
      return Response::fail("Email already in use.");
    return Response::success("Email available.");
  }

  public function get_usernameAvailable($request, $username){
    if(!isset($username)){
      //TODO put a proper http response code
      return Response::fail("No username sent.");
    }

    if(!validateUsername($username)){
      return Response::fail("Invalid username.");
    }
    if(Models::find('User', ['username'=> $username]))
      return Response::fail("Username already in use.");
    return Response::success("Username available.");
  }

  public function post_update($request){
    //TODO sanitize inputs
    $user = Auth::currentUser();
    if(isset($_POST['password'])){
      $user->setPassword($_POST['password']);
    }

    //TODO because token relies on username, changing username requires
    //a token to be revoked, set this up so OAuth storage uses the user model's
    //id instead
    if(isset($_POST['username'])){
      if(!validateUsername($_POST['username'])){
        return Response::fail("Invalid username.");
      }
      $user->setUsername(strtolower($_POST['username']));
      Auth::revokeToken();
    }

    if(isset($_POST['email'])){
      if(!validateEmail($_POST['email'])){
        return Response::fail("Invalid email address.");
      }
      $user->setEmail($_POST['email']);
    }

    if(isset($_POST['first_name'])){
      if(!validateEmail($_POST['first_name'])){
        return Response::fail("Invalid first name.");
      }
      $user->setFirstName($_POST['first_name']);
    }

    if(isset($_POST['last_name'])){
      if(!validateEmail($_POST['last_name'])){
        return Response::fail("Invalid last name.");
      }
      $user->setLastName($_POST['last_name']);
    }
    $user->save();
    return Response::success($user);
  }

  public function get_activate($request, $code){
    if(!isset($code)){
      //TODO put a proper http response code
      return Response::fail("No activation code sent.");
    }
    
    $code = Models::find('ActivationCode', ['code' => $code]);
    if($code == null){
      return Response::fail("Invalid or used activation code.");
    }

    $user = Models::findById('User', $code->getUserId());

    //TODO check if code has expired
    if($user == null){
      return Response::fail("Invalid activation code.");
    }
    $user->setActivationDate(date('Y-m-d H:i:s'));
    $user->save();
    $code->delete();
    return Response::success("Account successfully activated.");
  }

  public function index($request){
    $user = Auth::currentUser();
    return Response::success($user);
  }
}

class AuthController extends RoutedController{

  public function post_token($request){
    if(!isset($_POST['client_id']))
      $_POST['client_id'] = CLIENT_ID;
    if(!isset($_POST['client_secret']))
      $_POST['client_secret'] = CLIENT_SECRET;
    if(!isset($_POST['grant_type']))
      $_POST['grant_type'] = 'password';
    //prevent username from being passed, only use generated username
    //based on email address
    $_POST['username'] = '';
    if(isset($_POST['email'])){
      $_POST['username'] = Auth::generateUsername($_POST['email']);
    }
    return Auth::requestToken();
  }

  public function post_refresh($request){
    if(!isset($_POST['client_id']))
      $_POST['client_id'] = CLIENT_ID;
    if(!isset($_POST['client_secret']))
      $_POST['client_secret'] = CLIENT_SECRET;
    $_POST['grant_type'] = 'refresh_token';
    return Auth::requestToken();
  }

  public function all_revoke($request){
    return Auth::revokeToken();
  }
}

