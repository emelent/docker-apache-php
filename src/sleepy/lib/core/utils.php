<?php

function arrayKeysSet($keys, $record){
  foreach($keys as $key){
      if(!isset($record[$key]))
          return false;
  } 
  return true;
}

function snakeToCamel($phrase){
  $parts = explode('_', $phrase);
  $newPhrase = '';
  $first = true;
  foreach($parts as $part){
    if($first){
      $first = false;
      $newPhrase .= $part;
      continue;
    }
    $newPhrase .= ucfirst($part);
  }
  return $newPhrase;
}

function validateUsername($username){
  return preg_match('/^[0-9a-zA-Z_]{4,}$/', $username);
}

function validateName($name){
  return preg_match('/^[a-zA-Z]{2,}$/', $name);
}

function validateEmail($email){
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}


