<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxFollow.php');
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
  debug('フォローPOST確認');
  $favorite_user = (int)filter_input(INPUT_POST, 'follow_id');

  try {
    debug('フォロー状況確認');
    $dbh = dbConnect();
    $sql = 'SELECT id FROM follow WHERE user_id = :user_id AND favorite_user = :favorite_user AND delete_flag = 0';
    $data = array(':user_id'=>$_SESSION['login_id'], ':favorite_user'=>$favorite_user);
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt->rowCount() > 0) {
      debug('フォロー済み、解除に移行');
      $sql = 'DELETE FROM follow WHERE user_id = :user_id AND favorite_user = :favorite_user AND delete_flag = 0';
      queryPost($dbh, $sql, $data);
    } else {
      debug('フォロー外、フォロー実行');
      $sql = 'INSERT INTO follow(user_id,favorite_user,create_date) VALUES(:user_id,:favorite_user,:create_date)';
      $data = array(':user_id'=>$_SESSION['login_id'], ':favorite_user'=>$favorite_user, ':create_date'=>date('Y-m-d H:i:s'));
      queryPost($dbh, $sql, $data);
    }
  } catch(Exception $e) {
    error_log('エラ1ー発生(フォロー操作):'.$e->getMessage());
    $error = 'UnknownError';
  }

} else {
  debug('チェック項目に何らかの不備');
  $error = ('BadAccess');
}

if(!isset($error)) {
  debug('フォロー操作完了');
  $data = 'フォロー操作完了';
  echo json_encode(compact('data'));
} else {
  http_response_code(400);
  echo json_encode(compact('error'));
}


debug('================画面処理終了================');
