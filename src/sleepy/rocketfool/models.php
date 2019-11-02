<?php

class ProfileMeta extends ModelMeta{
  
  protected $actions = [
    'follow' => 'follow',
    'unfollow' => 'unfollow'
  ];

  public function __construct(){
    parent::__construct('profiles',[
      'name'        => new CharField(80),
      'email'       => new CharField(255),
      'telephone'   => new CharField(10),
      'description' => new TextField(),
      'owner'       => new ForeignKey('users'),
      'bgColour'    => new CharField(7, ['default' => '"#fffff"']),
      'bgURI'       => new CharField(255, ['null' => true]),
      'geo'         => new CharField(255, ['null' => true]),
      'website'     => new CharField(255, ['null' => true]),
      'created'     => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
    ]);

    $this->acl['WRITE'] = ModelMeta::$OWN_WRITE;
  }
}

class Profile extends Model{
  public function follow($user_id){
    if(isset($this->id)){
      new ProfileFollower([
        'user_id'       => $user_id,
        'profile_id'    => $this->getId(),
        'active'        => new BooleanField(['default' => 'TRUE']),
        'last_modified' => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
        'created'       => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
      ]);
      return Response::success($this->name . ' followed.');
    }
    return Response::fail('Tried to follow invalid profile');
  }

  public function unfollow($user_id){
    if(isset($this->id)){
      $follow = Models::find('profile_followers', [
        'user_id' => $user_id,
        'profile_id' => $this->getId(),
        'created'     => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
      ]);
      if($follow){
        $follow->setActive(false);
        $follow->setLastModified(date('Y-m-d H:i:s'));
        $follow->save();
      }
      return Response::success($this->name . ' unfollowed.');
    }
    return Response::fail('Tried to unfollow invalid profile');
  }
}

class ProfileFollowerMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('profile_followers', [
      'user_id'  => new ForeignKey('users'),
      'profile_id'  => new ForeignKey('profiles'),
      new CompositeKey(['user_id', 'profile_id'])
    ]);
  }
}class ProfileFollower extends Model{}


class ProfileMediaMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('profile_media', [
      'profile_id'  => new ForeignKey('profiles'),
      'uri'         => new CharField(255),
      'extension'   => new CharField(3),
    ]);
  }
}class ProfileMedia extends Model{}

ModelManager::register('Profile');
ModelManager::register('ProfileFollower');
ModelManager::register('ProfileMedia');
