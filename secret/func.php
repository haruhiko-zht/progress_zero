<?php
// ========================
// PHPエラー関連
// ========================
error_reporting(E_ALL);
ini_set('log_errors','on');
ini_set('error_log','php.log');


// ========================
// デバッグ関数
// ========================
// $debug_flag = true;
$debug_flag = false;
function debug($str){
  global $debug_flag;
  if($debug_flag){
    error_log('デバッグ:'.$str);
  }
}

function debugLogStart(){
  debug('================画面処理開始================');
  debug('セッションID:'.session_id());
  debug('セッション情報:'.print_r($_SESSION,true));
  debug('現在日時タイムスタンプ:'.time());
  if(!empty($_SESSION['login_date']) && !empty($_SESSION['login_limit'])){
    debug('セッション期限:'.($_SESSION['login_date'] + $_SESSION['login_limit']));
  }
}

// ========================
// セッション関連
// ========================
session_save_path('/var/tmp/');
ini_set('session.cookie_lifetime', 60*60*24*30);
ini_set('session.gc_maxlifetime', 60*60*24*30);
ini_set('session.name', 'sk8erb01');
ini_set('session.use_strict_mode','1');
session_start();

// ログインチェック
function isLogin(){
  if(!empty($_SESSION['login_date'])){
    return true;
  } else {
    return false;
  }
}

// セッション設定メッセージ受け取り
function getSesMsg() {
  if(!empty($_SESSION['msg'])) {
    $str = $_SESSION['msg'];
    $_SESSION['msg'] = '';
    return h($str);
  }
}

// ========================
// CSRFトークン
// ========================
// トークン生成
function generate_token(){
  return hash('sha256', session_id());
}
// トークン検証
function valid_token($token){
  return $token === generate_token();
}


// ========================
// エラーメッセージ関連
// ========================
$err_msg = array();

define('MSG01','入力されていません');
define('MSG02','半角英数のみ使用可能です');
define('MSG03','形式が正しくありません');
define('MSG04','ユーザー情報もしくはパスワードが正しくありません');
define('MSG05','予期せぬエラーが発生しました、再度お試し下さい');


// ========================
// バリデーションチェック
// ========================
// 未入力チェック（文字列かのチェック）
function validInput($key){
  $res = filter_input(INPUT_POST, $key);
  $res = str_replace( array( " ", "　", "	"), "", $res);
  if(mb_strlen($res) > 0){
    return $res;
  } else {
    global $err_msg;
    $err_msg[$key] = MSG01;
    return false;
  }
}

// 最大文字数＆最小文字数チェック
function letterMinMax($key, $min, $max){
  $count = mb_strlen(validInput($key));
  if($count >= $min && $count <= $max){
    return true;
  } else {
    global $err_msg;
    $err_msg[$key] = $min.'文字以上、'.$max.'文字以下でお願いします';
    return false;
  }
}
// 最大文字数＆最小文字数チェック2
function letterMinMaxB($str, $min, $max, $key =''){
  $count = mb_strlen($str);
  if($count >= $min && $count <= $max){
    return true;
  } else {
    global $err_msg;
    $err_msg[$key] = $min.'文字以上、'.$max.'文字以下でお願いします';
    return false;
  }
}

// 半角英数チェック
function halfAlphanumericCheck($key){
  $str = validInput($key);
  $res = preg_match('/^[a-zA-Z0-9]+$/', $str);
  if($res){
    return true;
  } else {
    global $err_msg;
    $err_msg[$key] = MSG02;
    return false;
  }
}

// Eメールアドレスの形式チェック
function validEmailAddress($key){
  $str = validInput($key);
  $res = preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $str);
  if($res){
    return true;
  } else {
    global $err_msg;
    $err_msg[$key] = MSG03;
    return false;
  }
}
// Eメールアドレスの形式チェック２
function isEmailAddress($str){
  if(preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $str)){
    return true;
  } else {
    return false;
  }
}

// 名前まとめてバリデーション
function checkAllName($key){
  letterMinMax($key, 1, 50);
  $name = validInput($key);
  return $name;
}

// ユーザーIDまとめてバリデーション
function checkAllUserid($key){
  letterMinMax($key, 4, 16);
  halfAlphanumericCheck($key);
  $user_id = validInput($key);
  return $user_id;
}

// パスワードまとめてバリデーション
function checkAllPass($key){
  letterMinMax($key, 8, 16);
  halfAlphanumericCheck($key);
  $pass = validInput($key);
  return $pass;
}

// Eメールアドレスまとめてバリデーション
function checkAllEmail($key){
  letterMinMax($key, 4, 255);
  validEmailAddress($key);
  $email = validInput($key);
  return $email;
}


// ========================
// フォーム関連
// ========================
// 各入力項目のエラーメッセージ取得
function getErrMsg($key){
  global $err_msg;
  if(!empty($err_msg[$key])){
    return $err_msg[$key];
  }
}
// 入力フォーム維持
function returnFormValue($key){
  $str = filter_input(INPUT_POST, $key);
  $str = str_replace( array( " ", "　", "	"), "", $str);
  if(mb_strlen($str) > 0){
    return h($str);
  }
}
function returnFormValueG($key){
  $str = filter_input(INPUT_GET, $key);
  $str = str_replace( array( " ", "　", "	"), "", $str);
  if(mb_strlen($str) > 0){
    return h($str);
  }
}


// ========================
// DB照合関連
// ========================
// 該当のEメールアドレスがあるかのチェック
function isExistEmail($str){
  try {
    $dbh = dbConnect();
    $sql = 'SELECT email FROM users WHERE email = :email AND delete_time > :now_time';
    $data = array(':email'=>$str, ':now_time'=>time());

    $stmt = queryPost($dbh, $sql, $data);
    $res = $stmt->fetch();

    if(!empty($res['email'])){
      debug('該当するメールアドレスあり');
      return true;
    } else {
      debug('該当するメールアドレスなし');
      return false;
    }

  } catch(Exception $e) {
    error_log('エラー発生(Eメール存在チェック):'.$e->getMessage());
  }
}
// 該当のユーザーIDがあるかのチェック
function isExistUserid($str){
  try {
    $dbh = dbConnect();
    $sql = 'SELECT user_id FROM users WHERE user_id = :user_id AND delete_time > :now_time';
    $data = array(':user_id'=>$str, ':now_time'=>time());

    $stmt = queryPost($dbh, $sql, $data);
    $res = $stmt->fetch();

    if(!empty($res['user_id'])){
      debug('該当するユーザーIDあり');
      return true;
    } else {
      debug('該当するユーザーIDなし');
      return false;
    }

  } catch(Exception $e) {
    error_log('エラー発生(ユーザーID存在チェック):'.$e->getMessage());
  }
}
// フォローチェック
function isFollow($pair_id){
  try {
    $dbh = dbConnect();
    $sql = 'SELECT count(*) AS c FROM follow WHERE user_id = :id AND favorite_user = :pair_id AND delete_flag = 0';
    $data = array(':id'=>$_SESSION['login_id'], ':pair_id'=>$pair_id);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt->fetch()['c']) {
      debug('フォロー中');
      return true;
    } else {
      debug('フォロー外');
      return false;
    }
  } catch(Exception $e) {
    error_log('エラー発生(フォローチェック):'.$e->getMessage());
  }
}


// ========================
// その他関数
// ========================
// サニタイズ
function h($str){
  return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
// ランダム認証キー発行
function random($length = 8){
  return substr(bin2hex(random_bytes($length)), 0, $length);
}
// メール送信
function sendMail($from, $to, $subject, $comment){
  if(!empty($to) && !empty($subject) && !empty($comment)) {
    mb_language('japanese');
    mb_internal_encoding('UTF-8');

    $res = mb_send_mail($to, $subject, $comment, "From:".$from);

    if($res) {
      debug('メール送信成功');
    } else {
      debug('メール送信失敗');
    }
  }
}
