<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxValidProgress.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->javascriptでindex.phpへ遷移');
  $error = 'NoLoginSession';
  http_response_code(400);
  echo json_encode(compact('error'));
  exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['login']) && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('進捗報告POST確認');
  // バリデーションチェック
  letterMinMax('progress_content', 1, 500);
  $progress_content = (string)filter_input(INPUT_POST, 'progress_content');
  $task = validInput('task');

  // 参加しているタスクかのチェック
  try {
    $dbh = dbConnect();
    $sql = 'SELECT id FROM join_user WHERE task = :id AND user_id = :login_id AND delete_flag = 0';
    $data = array(':id'=>$task, ':login_id'=>$_SESSION['login_id']);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt->rowCount() === 0) {
      debug('参加権のないタスク->index.phpへ遷移');
      $error = 'BadAccess';
      http_response_code(400);
      echo json_encode(compact('error'));
      exit();
    }
  } catch(Exception $e) {
    error_log('エラー発生(参加権チェック):'.$e->getMessage());
  }

  if(empty($err_msg) && empty($error)) {
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'INSERT INTO progress_message(send_user,task,message,create_date,update_date) VALUES(:send_user,:task,:message,:create_date,:update_date)';
      $data = array(':send_user'=>$_SESSION['login_id'], ':task'=>$task, ':message'=>$progress_content, ':create_date'=>date('Y-m-d H:i:s'), ':update_date'=>date('Y-m-d H:i:s'));
      $stmt = queryPost($dbh, $sql, $data);
      if($stmt) {
        $_SESSION['msg'] = '進捗報告！';
      }
    } catch(Exception $e) {
      error_log('エラー発生(進捗報告):'.$e->getMessage());
      $error = 'UnknownError';
    }
  } else {
    debug('バリデーションエラー');
    debug('エラー箇所:'.print_r($err_msg,true));
    foreach ($err_msg as $key => $val) {
      $error[] = array('classname'=>$key, 'message'=>$val);
    }
  }

} else {
  debug('チェック項目に何らかの不備');
  $error = ('BadAccess');
}

if(!isset($error)) {
  debug('進捗報告完了');
  $data = '進捗報告完了';
  echo json_encode(compact('data'));
} else {
  http_response_code(400);
  echo json_encode(compact('error'));
}


debug('================画面処理終了================');
