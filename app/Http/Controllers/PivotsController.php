<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Cookie;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use App\Http\Middleware\Authtoken;
use App\Option;
use App\Paper;
use App\Question;
use App\Subject;
use App\Type;
use App\User;

/*
|目前测试版本，正式版把增删改的　username改为token验证
*/

class PivotsController extends Controller {
  const FAIL    = 400;
  const REDICT  = 302;
  const SUCCESS = 200;
  public function __construct(){}

  /****工具方法，暂时不知道怎么单独出来一个类，先放这里*****/
  //注册一个JWT(java web token)
  public static function regis_token($username){
    $sign = new Sha256();
    $token = (new Builder())->setIssuer('http://www.seeonce.cn') // Configures the issuer (iss claim)
                      ->setAudience('http://www.seeonce.cn') // Configures the audience (aud claim)
                      ->setId('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
                      ->setIssuedAt(time()) // Configures the time that the token was issue (iat claim)
                      ->setNotBefore(time()) // Configures the time that the token can be used (nbf claim)
                      ->setExpiration(time() + 3600) // Configures the expiration time of the token (nbf claim)
                      ->set('username', $username) // Configures a new claim, called "uid"
                      ->sign($sign, 'dawndevil')
                      ->getToken(); // Retrieves the generated token
    return $token;
  }

  //返回固定格式的json数据
  private static function back($code = 200, $status = "执行成功", $data = []){

    return [
      'code'   => $code,
      'status' => $status,
      'data'   => $data
    ];
  }




  /**不需要特殊验证的**/


  //获取所有科目
  public static function get_subjects(Request $req){
    return static::back(static::SUCCESS, null, Subject::get_subjects());
  }

  //获取所有类型
  public static function get_types(Request $req){
    return static::back(static::SUCCESS, null, Type::get_types());
  }

  //根据id获取问题的详细信息
  public static function get_question_by_id($id){
    $question = Question::get_by_id($id);
    if(empty($question))
      return static::back(static::FAIL, '不存在此题');

    return static::back(static::SUCCESS, null, $question);
  }

  //根据科目找问题
  public static function get_question_by_subject($id){
    $subject  = Subject::find($id);
    if($subject == null)
      return static::back(static::FAIL, '科目不存在');

    return static::back(static::SUCCESS, null, Subject::get_ques_by_subject($id));
  }

  //根据类型找问题
  public static function get_question_by_type($id){
    $type  = Type::find($id);
    if($type == null)
      return static::back(static::FAIL, '类型不存在');

    return static::back(static::SUCCESS, null, Type::get_ques_by_type($id));
  }

  //查询除了选项以外的问题的所有属性
  public static function get_question_part(){
    return static::back(static::SUCCESS, null, Question::get_part());
  }

  //根据科目查找试卷
  public static function get_paper_by_subject($id){
    $subject = Subject::find($id);
    if($subject == null)
      return static::back(FAIL, '没有要查找的信息');

    return static::back(static::SUCCESS, null, $subject->papers);
  }

  //获取id获取试卷详细信息
  public static function get_paper_by_id($id){
    $paper = Paper::get_by_id($id);
    if($paper == null)
      return static::back(static::FAIL, '试卷不存在');

    return static::back(static::SUCCESS, null, $paper);
  }

  //获取试卷除了问题的所有内容，分页
  public static function get_paper_part(){
    return static::back(static::SUCCESS, null, Paper::get_part());
  }


  /*************************************/
  /*******Need Special Verify***********/

  //用户登录
  public static function user_login(Request $req){
    $validate = Validator::make($req->all(), [
      'username' => 'required|alpha_num|between:6,12',
      'password' => 'required|alpha_dash|between:6,18',
    ]);

    if(!$validate->fails()){
      $msg = Teacher::login($req->all());
      if($msg['success'] == true){
        return response()->json(static::back())
               ->cookie(new Cookie('chicken_token', static::regis_token($req['username'])));
      }
      return static::back(static::FAIL, $msg['status']);
    }

    return static::back(static::FAIL, '登录失败');
  }

  //用户注册
  public static function user_register(Request $req){
    $validate = Validator::make($req->all(), [
      'username' => 'required|alpha_num|between:6,12',
      'password' => 'required|alpha_dash|between:6,18',
      'nickname' => 'between:0, 10'
    ]);

    if(!$validate->fails()){
      $msg = Teacher::register($req->all());
      return static::back($msg['success'] ? static::SUCCESS : static::FAIL,
                         $msg['status']);
    }
    return static::back(static::FAIL, '注册失败');
  }

  //判断用户是否登录
  public static function is_login(Request $req){
    $result = Authtoken::verify_token($req);

    return static::back($result ? static::SUCCESS : static::FAIL, null);
  }

  //用户注销
  public static function user_out(Request $req){
    return response()->json(static::back())
                     ->cookie(new Cookie('chicken_token', null, time() - 3600));
  }

  //获取指定用户的题目
  public static function get_question_by_user(Request $req, $username){
    $token_user = Authtoken::getUser($req);
    if($token_user == $username){
      $questions = Teacher::get_question_by_user($token_user);
      return static::back(static::SUCCESS, null, $questions);
    }
    return static::back(static::FAIL, "无权限");
  }

  //给试卷添加题目通过 详细信息
  public static function add_question_to_paper(Request $req){
    $validate = Validator::make($req->all(), [
      'id'      => 'required|numeric',
      'type'    => 'required|numeric',
      'subject' => 'required|numeric',
      'content' => 'required',
      'corrects.*' => 'required|boolean',
      'contents.*'=> 'required',
      "grade"     => 'required|numeric'
    ]);

    if(!$validate->fails()){
      $data = $req->all();
      //$data['username'] = Authtoken::getUser($req);
      $data['username'] = 'dawndevil';
      $result = Paper::add_question($data);
      return static::back(static::SUCCESS, null, $result);
    }

    return static::back(static::FAIL, '填写的数据有误，添加失败');
  }

  //给试卷添加题目 通过id
  public static function add_question_to_paper_by_id(Request $req){
    $validate = Validator::make($req->all(), [
      'pid'   => 'required|numeric',
      'qid'   => 'required|numeric',
      'grade' => 'required|numeric'
    ]);

    if(!$validate->fails()){
      $result = Paper::add_question_by_id($req->all());
      if($result['success'])
        return static::back(static::SUCCESS, $result['status'], $result['data']);

      return static::back(static::FAIL, $result['status']);
    }
    return static::back(static::FAIL, '添加失败');
  }

  public static function add_paper(Request $req){
    $data = $req->all();
    $validate = Validator::make($data, [
      'title' => 'required|max:255',
      'subject' => 'required|numeric'
    ]);

    if(!$validate->fails()){
      //$data['username'] = Authtoken::getUser($req);
      $data['username'] = 'dawndevil';
      $result = Paper::add($data);
      if($result)
        return static::back(static::SUCCESS, null, ['id' => $result]);
    }
    return static::back(static::FAIL, '添加失败');
  }

  public static function delete_paper_by_id(Request $req, $id){
    //$result = Paper::del($id, Authtoken::getUser($req));
    $result = Paper::del($id, 'dawndevil');
    return static::back($result ? static::SUCCESS : static::FAIL,
                        $result ? "删除成功" : "删除失败");
  }
  //添加题目
  public static function add_question(Request $req){
    $validate = Validator::make($req->all(), [
      'type'    => 'required|numeric',
      'subject' => 'required|numeric',
      'content' => 'required',
      'corrects.*' => 'required|boolean',
      'contents.*'=> 'required'
    ]);

    if(!$validate->fails()){
      $data = $req->all();
      //$data['username'] = Authtoken::getUser($req);
      $data['username'] = 'dawndevil';
      $question = Question::add($data);
      return static::back(static::SUCCESS, null, $question);
    }
    return static::back(static::FAIL, null);
  }

  //更新问题
  public static function update_question(Request $req, $id){
    $validate = Validator::make($req->all(), [
      'subject' => 'required|numeric',
      'content' => 'required',
      'options.*.id' => 'required|numeric',
      'options.*.content' => 'required',
      'options.*.correct' => 'required|boolean'
    ]);
    $data = $req->all();
    $data['id'] = $id;

    if(!$validate->fails()){
      Question::modify($data);
      return static::back();
    }
    return static::back(static::FAIL, '更新失败');
  }

  //删除问题
  public static function delete_question(Request $req, $id){
    // $username = Authtoken::getUser($req);
    $username = 'dawndevil';
    $back_bool = Question::del($id, $username);

    return static::back($back_bool ? static::SUCCESS : static::FAIL,
                        $back_bool ? "删除成功" : "删除失败");
  }

  //批改试卷
  public static function verify_question(Request $req){
    $validate = Validator::make($req->all(), [
      'options.questions.*.id' => 'required|numeric',
      'options.pid' => 'required|numeric'
    ]);

    if(!$validate->fails()){
      $result = Paper::verify_question($req->input('options'));
      return static::back(static::SUCCESS, null, $result);
    }

    return static::back(static::FAIL, null);
  }

  //添加选项
  public static function add_option(Request $req){
    $data = $req->all();
    $validate = Validator::make($data, [
      'content'  => 'required',
      'correct' => 'required|boolean',
      'question_id' => 'required|numeric'
    ]);

    if(!$validate->fails()){
      //$data['username'] = Authtoken.getUser($req);
      $data['username'] = 'dawndevil';

      $result = Option::add($data);
      if($result != null){
        return static::back(static::SUCCESS, null, $result);
      }

      return static::back(static::FAIL, '题目不存在');
    }

    return static::back(static::FAIL, '参数错误');
  }

  //删除选项
  public static function delete_option(Request $req, $id){
    //$username = Authtoken.getUser($req);
    $username = 'dawndevi';

    $result = Option::del($id, $username);

    if($result === -1)
      return static::back(static::FAIL, '题目至少有一个选项');
    else if($result === -2)
      return static::back(static::FAIL, '选项不存在');
    else if($result === -3)
      return static::back(static::FAIL, '非法权限无法删除');
    else
      return static::back();
  }

  //更新选项
  public static function modify_option(Request $req, $id){
    $data = $req->all();

    $validate = Validator::make($data, [
      'content'  => 'required',
      'correct' => 'required|boolean'
    ]);

    if(!$validate->fails()){
      $data['id'] = $id;
      //$data['username'] = Authtoken.getUser($req);
      $data['username'] = 'dawndevil';

      $result = Option::modify($data);
      return static::back($result['success'] ? static::SUCCESS : static::FAIL, $result['status']);
    }

    return static::back(static::FAIL, '参数错误');
  }

  public static function rand_question(){
    return Question::inRadomOrder();
  }
}
