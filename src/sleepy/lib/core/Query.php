<?php

abstract class QueryClause{
  
  protected $query;
  protected $data;
  protected $table;
  protected $pdo;

  public function __construct($pdo, $table, $data){
    $this->pdo = $pdo;
    $this->table = $table;
    $this->data = $data;
  }

  public function getClauseString(){
    return $this->query;
  }
  
  protected function getColumnList($data){
    $cols = '';     
    $delim = ', ';
    foreach($data as $key => $value){
      $cols .= "`$key`$delim";
    }
    $cols = substr($cols, 0, strlen($cols) - strlen($delim));
    return $cols;
  }
  
  protected function getColumnListFromArr($data){
    $cols = '';     
    $delim = ', ';
    foreach($data as $key){
      $cols .= "`$key`$delim";
    }
    $cols = substr($cols, 0, strlen($cols) - strlen($delim));
    return $cols;
  }

  private function getAttribList($data, $delim){
    $cols = '';     
    foreach($data as $key => $value){
      if(gettype($value) == 'array'){
        $cols .= "`$key` " . $value[0] . ' ' . $value[1] . $delim;
        continue;
      }
      $cols .= "`$key`$delim";
    }
    $cols = substr($cols, 0, strlen($cols) - strlen($delim));
    return $cols;
  }
  
  protected function getAttribListOR($data){
    return $this->getAttribList($data, ' OR ');
  }
  
  protected function getAttribListAND($data){
    return $this->getAttribList($data, ' AND ');
  }

  protected function getAttribListComma($data){
    return $this->getAttribList($data, ', ');
  }

  protected function prepareStatement($query){
    return $this->pdo->prepare($query);
  }
}


class WhereClause extends QueryClause{

  public function __construct($pdo, $table, $data){
    parent::__construct($pdo, $table, $data);
    $this->query = 'WHERE ' . $this->getAttribListAND($data);
  }

  private function createSelectStatement($columns){
    $query = 'SELECT %s FROM `%s` %s';
    $col_str = '*';
    if($columns){
      $col_str = $this->getColumnListFromArr($columns);
    }
    $query = sprintf($query,
      $col_str,
      $this->table,
      $this->getClauseString()
    );

    $stmnt = $this->prepareStatement($query);
    $stmnt->setFetchMode(PDO::FETCH_OBJ);
    return $stmnt;
  }

  public function fetch($columns=null){
    $stmnt = $this->createSelectStatement($columns);
    $stmnt->execute($this->data);
    return $stmnt->fetch();
  }
  

  public function fetchAll(){
    $stmnt = $this->createSelectStatement($columns);
    $stmnt->execute($this->data);
    return $stmnt->fetchAll();
  }

  public function update($newData){
    $query = sprintf(
      'UPDATE `%s` SET %s %s',
      $this->getAttribListComma($this->data),
      $this->getAttribListComma($newData),
    );
  }

  public function delete(){
    
  }


}

class InsertClause extends QueryClause{
}

class Query{

  private $tableName;
  private $statement;

  private static $pdo = null;

  private function __construct($tableName){
    $this->tableName = $tableName;
    if($this::$pdo == null){
      try{
        $dsn = DSN;
        $dbname = DB_NAME;
        $dbhost = DB_HOST;
        $dbuser = DB_USER;
        $dbpass = DB_PASS;
        $this::$pdo = new PDO("$dsn:dbname=$dbname;host=$dbhost;port=$port", $dbuser, $dbpass);
        $this::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }catch(PDOException $e){
        throw new KnownException('Failed to initialize database => ' . 
          $e->getMessage(), ERR_DB_ERROR);
      }
    }
  }

  public static function on($tableName){
    //create new Query object  
    return new Query();
  }

  public static function customExec($sql){
  }

  public static function customFetch($sql){
  }

  public function insert($record){
    //creates an insert prepared statement and runs it
    $query = "INSERT INTO `%s` (%s) VALUES (%s)"; 

    $value_str = '';
    $col_str = '';
    $delim = ', ';
    $delim_len = strlen($delim);

    foreach($record as $key => $value){
      $col_str .= "`$value`$delim";
      $value_str .= ":$value`$delim";
    }

    //remove last delimiter(', ')
    $col_str = substr($col_str, 0, strlen($col_str) - $delim_len);
    $value_str = substr($value_str, 0, strlen($value_str) - $delim_len);

    sprintf($query,$this->tableName, $col_str, $value_str);
    $this->statement = $this::$pdo->prepare($query);
    return $this;
  }

  public function where($data=null){
    if($data == null){
      //select all
    }
      
  }

  public function delete($data=null){
    $query = "DELETE FROM `%s` (%s) VALUES (%s)"; 

    $value_str = '';
    $col_str = '';
    $delim = ', ';
    $delim_len = strlen($delim);

    foreach($record as $key => $value){
      $col_str .= "`$value`$delim";
      $value_str .= ":$value`$delim";
    }

    //remove last delimiter(', ')
    $col_str = substr($col_str, 0, strlen($col_str) - $delim_len);
    $value_str = substr($value_str, 0, strlen($value_str) - $delim_len);

    sprintf($query, $col_str, $value_str);
    $this->query = $query;
    $this->data = $data;
    return $this;

  }

  public function update($data){
    //creates an insert prepared statement and runs it

  }

  public function select($data=null){
    //creates an insert prepared statement and runs it
    return $this;
  }
  

  //TODO implement this once you've figured out how it's gonna work
  //public function join(){
    //return $this;
  //}

}
