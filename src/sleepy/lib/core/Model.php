<?php

require_once 'ModelFieldTypes.php';


abstract class ModelMeta{

  private static $pdo = null;

  //Constants Used in defining ACL's
  public static $ALL_READ    = 0;
  public static $ALL_WRITE   = 1;
  public static $AUTH_READ   = 2;
  public static $AUTH_WRITE  = 3;
  public static $OWN_READ    = 4;
  public static $OWN_WRITE   = 5;
  public static $ADMIN_READ  = 6;
  public static $ADMIN_WRITE = 7;
  protected $acl = [];

  private $tableName; 
  private $modelName;
  private $attr_define;
  protected $hidden_attr = [];
  protected $auto_route = true;

  private $insertStatement;
  private $insertStrictStatement;
  private $updateStatement;
  private $deleteStatement;
  private $selectStatement;
  private $selectAllStatement;

  //TODO delete all statement, delete multiple, update multiple

  private $sqlSchema;


  public function __construct($tableName, $attr_define){
    if(ModelMeta::$pdo == null)
      ModelMeta::$pdo = App::getPdo();
    if(count($this->attr_define)){
        throw new KnownException('ModelMeta created without any attributes', ERR_UNEXPECTED);
    }
    $this->tableName = $tableName;
    $this->modelName = substr(get_class($this), 0, strlen(get_class($this)) - strlen('Meta'));
    $this->attr_define = $attr_define;
    $this->prepareStatements();
    $this->acl['READ'] = $this::$ALL_READ;
    $this->acl['WRITE'] = $this::$ALL_WRITE;
    $this->acl['CREATE'] = $this::$ALL_WRITE;
  }
  
  public function shouldAutoRoute(){
    return $this->auto_route;
  }

  public function getAcl(){
    return $this->acl;
  }

  public function getSafeAttributesKeys(){
    return array_merge(['id'], array_diff($this->getAttributeKeys(), $this->hidden_attr));
  }

  public function getHiddenAttributeKeys(){
    return $this->hidden_attr;
  }

  private function prepareStatements(){
    $tn = $this->tableName;
    $pdo = $this->getPdo();

    $insert = "INSERT INTO `$tn` (%s) VALUES (%s)";
    $insert_strict = "INSERT INTO `$tn` (%s) VALUES (%s)";
    $update = "UPDATE `$tn` SET %s WHERE id = :id";
    $delete = "DELETE FROM `$tn` WHERE id = :id";
    $select = "SELECT %s FROM `$tn` WHERE id = :id";
    $selectAll = "SELECT %s FROM `$tn`";

    $col_str = '';
    $insert_col_str = '';
    $insert_str  = '';
    $insert_strict_col_str = '';
    $insert_strict_str  = '';
    $update_str = '';
    $comma_delim = ', ';
    $len_comma_delim = strlen($comma_delim);

    foreach($this->getAttributeKeysWithoutDefaultsAndNullables() as $key){
      $insert_str .= ":$key$comma_delim";
      $insert_col_str .= "`$key`$comma_delim";
    }
    foreach($this->getAttributeKeys() as $key){
      $col_str .= "`$key`$comma_delim";
      $update_str .= "`$key` = :$key$comma_delim";
      $insert_strict_str .= ":$key$comma_delim";
      $insert_strict_col_str .= "`$key`$comma_delim";
    }

    //remove last ', '
    $col_str = substr($col_str, 0, strlen($col_str) - $len_comma_delim);
    $insert_str = substr($insert_str, 0, strlen($insert_str) - $len_comma_delim);
    $insert_col_str = substr($insert_col_str, 0, strlen($insert_col_str) - $len_comma_delim);
    $insert_strict_str = substr($insert_strict_str, 0, strlen($insert_strict_str) - $len_comma_delim);
    $insert_strict_col_str = substr($insert_strict_col_str, 0, strlen($insert_strict_col_str) - $len_comma_delim);
    $update_str = substr($update_str, 0, strlen($update_str) - $len_comma_delim);

    $insert = sprintf($insert, $insert_col_str, $insert_str);
    $insert_strict = sprintf($insert_strict, $insert_strict_col_str, $insert_strict_str);
    $select = sprintf($select, "`id`, $col_str");
    $selectAll = sprintf($selectAll, "`id`, $col_str");
    $update = sprintf($update, $update_str);

    //create pdo statements
    $this->insertStatement = $pdo->prepare($insert);
    $this->insertStrictStatement = $pdo->prepare($insert_strict);
    $this->updateStatement = $pdo->prepare($update);
    $this->deleteStatement = $pdo->prepare($delete);
    $this->selectStatement = $pdo->prepare($select);
    $this->selectAllStatement = $pdo->prepare($selectAll);

    //set fetch modes
    $this->selectAllStatement->setFetchMode(PDO::FETCH_CLASS, $this->modelName);
    $this->selectStatement->setFetchMode(PDO::FETCH_CLASS, $this->modelName);
  }

  public function getSqlSchema(){
    $tn = $this->tableName;
    $indent='   ';
    $sqlSchema ="CREATE TABLE IF NOT EXISTS `$tn`(\n"
     . "$indent`id` INT PRIMARY KEY AUTO_INCREMENT,\n" 
      ;
    foreach($this->attr_define as $key => $value){
      if ($value instanceof BaseFieldType){
        if($value instanceof TableProperty)
          $sqlSchema .= "$indent$value,\n"; 
        else
         $sqlSchema .= "$indent`$key` $value,\n"; 
      }else{
        throw new KnownException('Invalid ModelMeta DataType', ERR_UNEXPECTED);
      }
    }

    //remove last ',\n'
    $sqlSchema = substr($sqlSchema, 0, strlen($sqlSchema) -2);
    $sqlSchema .= "\n);\n";

    return $sqlSchema;
  }

  public function getTableName(){
    return $this->tableName;
  }

  public function getModelName(){
    return $this->modelName;
  }

  public function getInsertStatement(){
    return $this->insertStatement;
  }

  public function getInsertStrictStatement(){
    return $this->insertStrictStatement;
  }

  public function getDeleteStatement(){
    return $this->deleteStatement;
  }

  public function getUpdateStatement(){
    return $this->updateStatement;
  }

  public function getSelectStatement(){
    return $this->selectStatement;
  }

  public function getSelectAllStatement(){
    return $this->selectAllStatement;
  }
  public function getAttributeDefinitions(){
    return $this->attr_define;
  }

  public function getAttributeKeys(){
    return array_keys($this->attr_define);
  }

  public function getDefaultAttributeKeys(){
    $keys = [];
    foreach($this->attr_define as $key => $value){
      if($value->isDefault()){
        array_push($keys, $key);
      }
    }
    return $keys;
  }

  public function getAttributeKeysWithoutDefaults(){
    $keys = [];
    foreach($this->attr_define as $key => $value){
      if(!$value->isDefault()){
        array_push($keys, $key);
      }
    }
    return $keys;
  }
  public function getAttributeKeysWithoutDefaultsAndNullables(){
    return array_diff($this->getAttributeKeysWithoutDefaults(), $this->getNullableAttributeKeys());
  }

  public function getNullableAttributeKeys(){
    $keys = [];
    foreach($this->attr_define as $key => $value){
      if($value->isNullable()){
        array_push($keys, $key);
      }
    }
    return $keys;
  }

  public static function getPdo(){
    return ModelMeta::$pdo;
  }
}

final class Models{

  private static function throwInvalidData($method){
      throw new KnownException("Invalid data passed to Models::$method()", ERR_BAD_REQ);
  }
  ///TODO implement another create function that requires all value fields to be given,
  
  public static function createStrict($modelName, $data){
    $meta = getMeta($modelName);
    //Models::assertKeys($meta->getAttributeKeys(), $data, 'createStrict');
    $stmnt = $meta->getInsertStrictStatement();
    //var_dump($data);
    //echo $stmnt->queryString . PHP_EOL;
    $stmnt->execute($data);
    $id = $meta->getPdo()->lastInsertId();
    return Models::findById($modelName, $id);
  }

  private static function assertKeys($keys, $data, $methodName){
    if(!arrayKeysSet($keys, $data)){
      Models::throwInvalidData($methodName);
    }
  }

  private static function nullifyExcess($keys, &$data){
    //TODO implement me
    foreach($data as $key => $value){
      if(!isset($data[$key]))
        unset($data[$key]);
    }
  }

  public static function create($modelName, $data){
    $meta = getMeta($modelName);

    //set nullables and defaults to null so they are not considered
    //add nullables if they are not present and set them to null
    $keys = $meta->getAttributeKeysWithoutDefaultsAndNullables();
    Models::nullifyExcess($keys, $data);
    Models::assertKeys($keys, $data, 'create');

    //TODO remove excess key value pairs, to prevent PDO errors, not sure if 
    $stmnt = $meta->getInsertStatement();
    //echo $stmnt->queryString . PHP_EOL;
    $stmnt->execute($data);
    $id = $meta->getPdo()->lastInsertId();

    return Models::findById($modelName, $id);
  }

  public static function delete($modelName, $data){
    $meta = getMeta($modelName);
    Models::assertKeys(['id'], $data, 'update');
    $stmnt = $meta->getDeleteStatement();
    $stmnt->execute($data);
  }

  public static function update($modelName, $data){
    $meta = getMeta($modelName);
    Models::assertKeys(['id'], $data, 'update');
    $stmnt = $meta->getUpdateStatement();
    $stmnt->execute($data);
  }

  public static function updateAll($modelName, $newData, $oldData){
    
    $meta = getMeta($modelName);
    $set = '';
    $where = '';
    $comma_delim = ', '; 
    $len_comma_delim = strlen($comma_delim);

    foreach(array_keys($newData) as $key){
      $set .= "`$key` = :$key$comma_delim";
    }

    foreach(array_keys($oldData) as $key){
      $where .= "`$key` = :old_$key$comma_delim";
      $data["old_$key"] = $oldData[$key];
    }

    //remove last ', '
    $set = substr($set, 0, strlen($set) - $len_comma_delim);
    $where = substr($where, 0, strlen($where) - $len_comma_delim);

    $query = sprintf('UPDATE `%s` SET %s WHERE %s',
      $meta->getTableName(), 
      $set, 
      $where
    );
    
    $data = array_merge($data, $newData);
    $stmnt = $meta->getPdo()->prepare($query);
    $stmnt->execute($data);
  }

  public static function find($modelName, $data=null){
    if($data == null){
      $stmnt = getMeta($modelName)->getSelectAllStatement();
      $stmnt->execute();
    }else{
      $stmnt = Models::createSelectStatement($modelName, $data);
      $stmnt->execute($data);
    }

    return $stmnt->fetch();
  } 

  public static function findAll($modelName, $data=null){
    if($data == null){
      $stmnt = getMeta($modelName)->getSelectAllStatement();
      $stmnt->execute();
    }else{
      $stmnt = Models::createSelectStatement($modelName, $data);
      $stmnt->execute($data);
    }

    return $stmnt->fetchAll();
  }

  public static function findAllSafe($modelName, $data=null){
    $models = Models::findAll($modelName, $data);
    $safe = getMeta($modelName)->getSafeAttributesKeys();
    $safeModels = [];
    foreach($models as $model){
      $model = json_decode(json_encode($model), true);
      foreach($model as $key){
        if(!in_array($key, $safe)){
          unset($model[$key]);
        }
        unset($model["0"]);
      }
      array_push($safeModels, $model);
    }
    return $safeModels;
  }

  private static function createCustomFindStatement($modelName, $query){
    $pdo = getMeta($modelName)->getPdo();
    $stmnt = $pdo->prepare($query);
    $stmnt->setFetchMode(PDO::FETCH_CLASS, $modelName);
    return $stmnt;
  }

  public static function findAllCustom($modelName, $query, $data){
    $stmnt = Models::createCustomFindStatement($modelName, $query);
    $stmnt-execute($data);
    return $stmnt->fetchAll();
  }

  public static function findCustom($modelName, $query, $data){
    $stmnt = Models::createCustomFindStatement($modelName, $query);
    $stmnt-execute($data);
    return $stmnt->fetch();
  }

  public static function findById($modelName, $id){
    $meta = getMeta($modelName);
    $stmnt = $meta->getSelectStatement();
    $stmnt->execute(['id' => $id]);

    return $stmnt->fetch();
  }

  private static function createSelectStatement($modelName, $data){
    $meta = getMeta($modelName);
    //create query str
    $query = 'SELECT * FROM `' . $meta->getTableName() . '` WHERE %s';
    $str = '';
    $delim = ' AND ';
    foreach($data as $key => $value){
      $str .= "`$key` = :$key$delim";
    }
    $str = substr($str, 0, strlen($str) - strlen($delim));
    $query = sprintf($query, $str);

    $stmnt = $meta->getPdo()->prepare($query);
    $stmnt->setFetchMode(PDO::FETCH_CLASS, $meta->getModelName());
    return $stmnt;
  }
}

abstract class Model implements JsonSerializable {

  protected $className;
  protected $meta;


  public function __construct($data=null){
    $this->className = get_class($this);
    $this->meta = getMeta($this->className);
    $this->createGettersAndSetters();

    //set values
    if($data != null){
      foreach($this->meta->getAttributeKeys() as $attr_name){
        if(isset($data[$attr_name])){
          $this->{"set" . $this->getAttributeMethodName($attr_name)}($data[$attr_name]);
        }
      }
    }
  }

  private function getAttributeMethodName($attr){
    return ucfirst(snakeToCamel($attr));
  }

  public function jsonSerialize(){
    $data = ['id'=> $this->id];
    foreach($this->getMeta()->getSafeAttributesKeys() as $key){
      $data[$key] = $this->{"get" . $this->getAttributeMethodName($key)}();
    }
    return $data;
  }

  public function getId(){
    return $this->id;
  }

  public final function __call($method, $args){
    $classname = get_class($this);
    $args = array_merge(array($classname => $this), $args);
    if(isset($this->{$method}) && is_callable($this->{$method})){
      return call_user_func($this->{$method}, $args);
    }else{
      throw new UnknownMethodCallException(
        "$classname error: call to undefined method $classname::{$method}()");
    }
  }
  
  private function createGettersAndSetters(){
    foreach($this->meta->getAttributeKeys() as $attr_name){
      $this->{"set" . $this->getAttributeMethodName($attr_name)} = function ($args) use ($attr_name) {
          $this->$attr_name = $args[0];
      };

      $this->{"get" . $this->getAttributeMethodName($attr_name)} = function () use ($attr_name) {
          return $this->$attr_name;
      };
    }
  }

  private function allAttributesSet(){
    foreach($this->meta->getAttributeKeys() as $key){
      if(!isset($this->$key))
        return false;
    }
    return true;
  }

  public final function save(){
    //save model to database
    if(isset($this->id)){
      Models::update($this->className, $this->toArray());
    }else{
      if($this->allAttributesSet()){
        //echo "using strict\n";
        $obj = Models::createStrict($this->className, $this->toArrayNoId());
      }else{
        $obj = Models::create($this->className, $this->toArrayNoId());
      }
      //sync all values with those from the database
      foreach($obj as $key => $value){
        $this->$key = $value;
      }
    }
  }

  public final function delete(){
    //delete model from database
    Models::delete($this->className, ['id' => $this->id]);

    //clear id, since the record is no longer in the database
    $this->id = null;
  }

  public final function toArray(){
    $record = ['id' => $this->id];
    foreach($this->meta->getAttributeKeys() as $key){
      $record[$key] = $this->{$key};
    }
    return $record;
  }

  public final function toArrayNoId(){
    $record = [];
    foreach($this->meta->getAttributeKeys() as $key){
      if($key == 'id') continue;
      if(isset($this->$key))
        $record[$key] = $this->$key;
      else
        $record[$key] = null;
    }
    return $record;
  }

  public final function getMeta(){
    return $this->meta;
  }


}
