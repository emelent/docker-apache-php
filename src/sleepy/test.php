<?php

require_once('admin/config.php');


class GlobMeta extends ModelMeta{

  public function __construct(){
    parent::__construct(
      'globs',  //table name
      [ //table columns
        'name' => new CharField(50),
        'age' => new IntegerField(),
        'bio' => new TextField(),
        'created' => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
      ]
    );
  }
}
class Glob extends Model {}

class ThingMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('things', [
      'name'  => new CharField(200),
      'owner_id' => new ForeignKey('globs')
    ]);
  }
}

class Thing extends Model{}

ModelManager::register('Glob');
ModelManager::register('Thing');

echo ModelManager::getSqlSchema() . PHP_EOL;

ModelManager::recreateTables();
$glob = new Glob(['name'=>'Marcus', 'age'=> 5, 'bio'=>'This is the bio']);
$glob->save();


//$glob = Models::create('Glob', ['name'=>'Marcus', 'age'=> 5, 'bio'=>'This is the bio']);

