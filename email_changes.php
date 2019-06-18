<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('email_changes.php');
debug('//////////////////////////////////////////');
debugLogStart();

// GET送信チェック
if(empty($_GET['changetoken'])) {
  debug('GET送信なし->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// changetoken照合
$changeToken = filter_input(INPUT_GET, 'changetoken');

try {
  $dbh = dbConnect();
  $sql = 'SELECT user_id,new_email,token,token_limit FROM email_changes WHERE token = :changetoken AND delete_flag = 0 ORDER BY create_date DESC LIMIT 1';
  $data = array(':changetoken'=>$changeToken);

  $stmt = queryPost($dbh, $sql, $data);
  $res = $stmt->fetch();

  if(!empty($res) && (int)$res['token_limit'] > time()) {
    debug('有効な変更トークン->DB変更');
    $sql = 'UPDATE users SET email = :email WHERE id = :id AND delete_flag = 0';
    $data = array(':email'=>$res['new_email'], ':id'=>$res['user_id']);
    $stmt1 = queryPost($dbh, $sql, $data);

    debug('変更トークン無効化');
    $sql2 = 'UPDATE email_changes SET delete_flag = 1 WHERE token = :changetoken';
    $data2 = array(':changetoken'=>$changeToken);
    $stmt2 = queryPost($dbh, $sql2, $data2);

    if($stmt1 && $stmt2) {
      debug('メールアドレス変更完了->index.phpへ遷移');
      $_SESSION['msg'] = 'メールアドレスの変更が完了しました';
      header('Location:index.php');
      exit();
    } else {
      debug('メールアドレス変更失敗->index.phpへ遷移');
      $_SESSION['msg'] = 'メールアドレス変更に失敗しました、再度お試し下さい';
      header('Location:index.php');
      exit();
    }

  } else {
    debug('無効な変更トークン->index.phpへ遷移');
    $_SESSION['msg'] = '無効なURLです';
    header('Location:index.php');
    exit();
  }

} catch(Exception $e) {
  error_log('エラー発生(メールアドレス変更トークンチェック):'.$e->getMessage());
  $_SESSION['msg'] = '予期せぬエラーが発生しました、再度お試し下さい';
  header('Location:index.php');
  exit();
}
?>
