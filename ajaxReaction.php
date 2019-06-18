<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxReaction.php');
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
  debug('リアクションPOST確認');
  $pm_id = filter_input(INPUT_POST, 'pm_id');
  $reaction = filter_input(INPUT_POST, 'reaction');
  // フォーム改ざんチェック
  if($reaction === 'good' || $reaction === 'bad') {
  } else {
    debug('フォーム改ざんの恐れ、$reaction='.$reaction);
    $error = 'BadAccess';
    http_response_code(400);
    echo json_encode(compact('error'));
    exit();
  }
  // 自分が参加しているタスクのメッセージかをチェック
  debug('参加権のあるタスクかチェック');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT p.id,p.task,j.user_id FROM progress_message AS p LEFT JOIN join_user AS j ON p.task = j.task WHERE p.id = :pm_id AND j.user_id = :login_id AND p.delete_flag = 0 AND j.delete_flag = 0';
    $data = array(':pm_id'=>$pm_id, ':login_id'=>$_SESSION['login_id']);
    $stmt = queryPost($dbh, $sql, $data);
    if($stmt->rowCount() === 0) {
      debug('参加権のないタスクの進捗報告メッセージ->javascriptでindex.phpへ遷移');
      $error = 'BadAccess';
      http_response_code(400);
      echo json_encode(compact('error'));
      exit();
    }
  } catch(Exception $e) {
    error_log('エラー発生(参加権のある進捗報告チェック):'.$e->getMessage());
  }

  // リアクションの確認
  debug('リアクションのDBチェック');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT message_id,good,bad FROM reaction WHERE message_id = :pm_id AND (good = :login_id_a OR bad = :login_id_b)';
    $data = array(':pm_id'=>$pm_id, ':login_id_a'=>$_SESSION['login_id'], ':login_id_b'=>$_SESSION['login_id']);
    $stmt = queryPost($dbh, $sql, $data);
    $res = $stmt->fetch();
    if(empty($res['message_id'])) {
      debug('リアクションなし');
      $current = 'none';
    } elseif(!empty($res['good'])) {
      debug('goodあり');
      $current = 'good';
    } elseif(!empty($res['bad'])) {
      debug('badあり');
      $current = 'bad';
    }
  } catch(Exception $e) {
    error_log('エラー発生(リアクションDBチェック):'.$e->getMessage());
  }

  // リアクション設定
  debug('リアクション設定');
  try {
    if($current === 'none') {
      debug('現状none');
      if($reaction === 'good') {
        debug('good追加');
        $sql1 = 'INSERT INTO reaction(message_id,good,create_date) VALUES(:pm_id,:login_id,:create_date)';
        $data1 = array(':pm_id'=>$pm_id, ':login_id'=>$_SESSION['login_id'], ':create_date'=>date('Y-m-d H:i:s'));
        $sql2 = 'UPDATE progress_message SET good = good+1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      } else {
        debug('bad追加');
        $sql1 = 'INSERT INTO reaction(message_id,bad,create_date) VALUES(:pm_id,:login_id,:create_date)';
        $data1 = array(':pm_id'=>$pm_id, ':login_id'=>$_SESSION['login_id'], ':create_date'=>date('Y-m-d H:i:s'));
        $sql2 = 'UPDATE progress_message SET bad = bad+1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      }
    } elseif($current === 'good') {
      debug('現状good');
      if($reaction === 'good') {
        debug('good解除');
        $sql1 = 'DELETE FROM reaction WHERE message_id = :pm_id AND good = :login_id';
        $data1 = array(':pm_id'=>$pm_id, ':login_id'=>$_SESSION['login_id']);
        $sql2 = 'UPDATE progress_message SET good = good-1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      } else {
        debug('good解除、bad追加');
        $sql1 = 'UPDATE reaction SET good = NULL,bad = :login_id_a WHERE message_id = :pm_id AND good = :login_id_b';
        $data1 = array(':login_id_a'=>$_SESSION['login_id'], ':pm_id'=>$pm_id, ':login_id_b'=>$_SESSION['login_id']);
        $sql2 = 'UPDATE progress_message SET good = good-1,bad = bad+1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      }
    } elseif($current === 'bad') {
      debug('現状bad');
      if($reaction === 'bad') {
        debug('bad解除');
        $sql1 = 'DELETE FROM reaction WHERE message_id = :pm_id AND bad = :login_id';
        $data1 = array(':pm_id'=>$pm_id, ':login_id'=>$_SESSION['login_id']);
        $sql2 = 'UPDATE progress_message SET bad = bad-1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      } else {
        debug('bad解除、good追加');
        $sql1 = 'UPDATE reaction SET bad = NULL,good = :login_id_a WHERE message_id = :pm_id AND bad = :login_id_b';
        $data1 = array(':login_id_a'=>$_SESSION['login_id'], ':pm_id'=>$pm_id, ':login_id_b'=>$_SESSION['login_id']);
        $sql2 = 'UPDATE progress_message SET bad = bad-1,good = good+1 WHERE id = :pm_id AND delete_flag = 0';
        $data2 = array(':pm_id'=>$pm_id);
      }
    }
    $dbh = dbConnect();
    $stmt1 = queryPost($dbh, $sql1, $data1);
    $stmt2 = queryPost($dbh, $sql2, $data2);
    if($stmt1 && $stmt2) {
      debug('リアクション設定完了');
      $sql = 'SELECT good,bad,id FROM progress_message WHERE id = :pm_id AND delete_flag = 0';
      $data = array(':pm_id'=>$pm_id);
      $stmt = queryPost($dbh, $sql, $data);
      $finish = $stmt->fetch();
      foreach ($finish as $key => $val) {
        $data[] = array('classname'=>$key, 'message'=>$val);
      }
    }
  } catch(Exception $e) {
    error_log('エラー発生(リアクション設定):'.$e->getMessage());
  }

} else {
  debug('チェック項目に何らかの不備');
  $error = ('BadAccess');
}

// 返信
if(!isset($error)) {
  debug('処理が正常に完了');
  echo json_encode(compact('data'));
} else {
  http_response_code(400);
  echo json_encode(compact('error'));
}

debug('================画面処理終了================');
?>
