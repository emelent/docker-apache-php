<?php

function getMeta($modelName){
  return ModelManager::getMeta($modelName . 'Meta');
}

final class ModelManager{

  private static $models = [];
  private static $metas  = [];
  
  public static function register($modelName){
    array_push(ModelManager::$models, $modelName);
  }

  public static function getSqlSchema(){
    $sqlString = '';
    foreach(ModelManager::$models as $model){
      $sqlString .= getMeta($model)->getSqlSchema() . "\n";
    }

    return $sqlString;
  }

  public static function clearDb(){
    $query = '';
    foreach(ModelManager::$models as $model){
      $meta = getMeta($model);
      $query .= 'DROP TABLE IF EXISTS `' . $meta->getTableName() . "`;\n";
    }
    App::getPdo()->query($query);
  }

  public static function createTables(){
    //TODO maybe you can speed things up by doing it in one query
    //but for now I do it separately for easier error handling
    foreach(ModelManager::$models as $model){
      //echo "Creating `$model` table\n";
      App::getPdo()->query(getMeta($model)->getSqlSchema()); 
    }
  }

  public static function recreateTables(){
    ModelManager::clearDb();
    ModelManager::createTables();
  }

  public static function getMeta($metaName){
    if(!isset(ModelManager::$metas[$metaName])){
      ModelManager::$metas[$metaName] = new $metaName();
    }
    return ModelManager::$metas[$metaName];
  }

  public static function createModelRoutes(){
    foreach(ModelManager::$models as $model){
      $modelName = strtolower($model);
      $meta = getMeta($model);
      if($meta->shouldAutoRoute()){
        Router::route_secondary([
          "$modelName/*" => new _ModelController($model)
        ]);
      }
    }
  }

}

