<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxEditProgress.php');
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
  debug('進捗報告編集POST確認');
  // バリデーションチェック
  letterMinMax('progress_content_edit', 1, 500);
  $progress_content = (string)filter_input(INPUT_POST, 'progress_content_edit');
  $pmid = validInput('pm_id');
  $task = validInput('task');

  // 参加しているタスクかのチェック
  debug('参加権チェック');
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

  // 自分の進捗報告かをチェック　
  debug('自分の進捗報告かのチェック');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT create_date FROM progress_message WHERE id = :pmid AND send_user = :login_id AND delete_flag = 0';
    $data = array(':pmid'=>$pmid, ':login_id'=>$_SESSION['login_id']);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt->rowCount() === 0) {
      debug('自分の進捗報告ではない->index.phpへ遷移');
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
      $sql = 'UPDATE progress_message SET message = :progress_content, update_date = :update_date WHERE id = :pmid AND send_user = :login_id AND delete_flag = 0';
      $data = array(':progress_content'=>$progress_content, ':update_date'=>date('Y-m-d H:i:s'), ':pmid'=>$pmid, ':login_id'=>$_SESSION['login_id']);
      $stmt = queryPost($dbh, $sql, $data);
      if($stmt) {
        $_SESSION['msg'] = '進捗報告の編集を行いました';
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
