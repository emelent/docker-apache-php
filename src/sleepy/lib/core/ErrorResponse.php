<?php

class ErrorResponse{

  /// error code values for debugging
  private static $DEBUG_ERROR_MESSAGES = array(
              ERR_UNK_REQ => "UNKNOWN REQUEST METHOD",
              ERR_BAD_REQ => "INVALID REQUEST FORMAT",
              ERR_INCOMP_REQ => "INCOMPLETE REQUEST",
              ERR_BAD_TOKEN => "INVALID FORM TOKEN",
              ERR_BAD_AUTH => "INVALID AUTH KEY",
              ERR_EXP_AUTH => "EXPIRED AUTH KEY",
              ERR_UNAUTHORISED => "UNAUTHORISED ACCESS ATTEMPT",
              ERR_UNEXPECTED => "UNKNOWN ERROR",
              ERR_DB_ERROR => "DATABASE ERROR",
              ERR_BAD_ROUTE => "UNMATCHED ROUTE"
  );

  ///error messages for production
  private static $PRODUCTION_ERROR_MESSAGES = array(
              ERR_UNK_REQ => "Bad request",
              ERR_BAD_REQ => "Bad request",
              ERR_INCOMP_REQ => "Bad request",
              ERR_BAD_TOKEN => "Your token has expired, please login and try again",
              ERR_BAD_AUTH => "Your token has expired, please login and try again",
              ERR_EXP_AUTH => "Your token has expired, please login and try again",
              ERR_UNAUTHORISED => "You are not authorised to access that resource",
              ERR_UNEXPECTED => "Sorry, something unexpected happend, please try again.",
              ERR_DB_ERROR => "Sorry, there was a problem with the server, please try again later.",
              ERR_BAD_ROUTE => "The page you are looking for does not exist"
  );

  private static function getMessage($exception, $code){
    $msg = ErrorResponse::$PRODUCTION_ERROR_MESSAGES[$code];
    if(DEBUG){
      $msg = '[' . ErrorResponse::$DEBUG_ERROR_MESSAGES[$code] 
        . "] {$exception->getMessage()}";
    }
    return $msg;
  }

  public static function resolveKnownException($exception){
    //TODO make this a proper HTTP response code
    $status = 400;
    $msg = ErrorResponse::getMessage($exception, $exception->getCode());
    Response::fail($msg, $status)->unwrap();
  }

  public static function resolveUnknownException($exception){
    //TODO make this a proper HTTP response code
    $status = 400; 
    $msg = ErrorResponse::getMessage($exception, ERR_UNEXPECTED);
    Response::fail($msg, $status)->unwrap();
  }
}
