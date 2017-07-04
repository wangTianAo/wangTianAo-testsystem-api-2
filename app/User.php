<?php namespace App;
use Illuminate\Database\Eloquent\Model;

class User extends Model {

    protected $fillable = [];

    protected $dates = [];

    public static $rules = [
        // Validation rules
    ];

    protected $table = 'user';

    public function questions(){
       return $this->hasMany('App\Question', 'username', 'username');
    }

    public function papers(){
      return $this->hasMany('App\Paper');
    }

    //判断用户是否存在
    public static function exist($username){
      return count(static::where('username', $username)->get()) != null;
    }

    //判断登录
    public static function login($msg = []){
      if(!static::exist($msg['username'])){
        return ['success' => false, 'status' => '用户不存在'];
      }
      $user = static::where([
        'username' => $msg['username'],
        'password' => sha1($msg['password']),
      ])->get();

      if(count($user) == 0){
        return ['success' => false, 'status' => '密码错误'];
      }else{
        return ['success' => true,  'status' => '登录成功'];
      }
    }

    //注册用户
    public static function register($msg = []){
      if(!static::exist($msg['username'])){
        return ['success' => false, 'status' => '用户不存在'];
      }

      $success = User::insert([
        'username' => $msg['username'],
        'password' => sha1($msg['password']),
        'nickname' => $msg['nickname']
      ]);

      return ['success' => $success, 'status' => ($success ? '注册成功' : '注册失败')];
    }

    public static function get_question_by_user($username){
      $user = static::where('username', $username)->get();

      if(count($user) != 0){
        return Question::question_msgs(
               $user[0]->questions()
               ->where('has_paper', 0)
               ->paginate(Question::PAGE));
      }

      return [];
    }
}
