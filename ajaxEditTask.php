<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxEditTask.php');
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

// POST送信＆ログインチェック
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['login']) && valid_token(filter_input(INPUT_POST, 'token'))){
  debug('タスク編集POST確認');
  debug('送信内容:'.print_r($_POST, true));

  // バリデーションチェック
  debug('バリデーションチェック開始');
  $title = checkAllName('title');
  $content = filter_input(INPUT_POST, 'content');
  letterMinMaxB($content, 10, 1500, 'content');
  $category = checkAllName('category');
  $limit_date = validInput('date');
  $participant = (!empty($_POST['participant'])) ? $_POST['participant'] : 0 ;
  $task = validInput('task_id');
  $status = (int)filter_input(INPUT_POST, 'status');

  // DB登録
  if(empty($err_msg)) {
    debug('バリデーションチェック通過');
    try {
      // カテゴリーID取得
      debug('カテゴリ照合');
      $dbh = dbConnect();
      $sql = 'SELECT id FROM category WHERE name LIKE :category AND delete_flag = 0';
      $data = array(':category'=>$category);
      $stmt = queryPost($dbh, $sql, $data);
      if($stmt->rowCount() > 0) {
        debug('既存のカテゴリ');
        $category = (int)$stmt->fetch()['id'];
      } else {
        debug('新規のカテゴリ');
        $sql2 = 'INSERT INTO category(name,create_date) VALUES(:name,:create_date)';
        $data2 = array(':name'=>$category, ':create_date'=>date('Y-m-d H:i:s'));
        $stmt2 = queryPost($dbh, $sql2, $data2);
        $category = $dbh->lastInsertId();
      }
      debug('カテゴリーID:'.$category);

      // タスク登録
      debug('タスク更新');
      $sql3 = 'UPDATE task SET title = :title, content = :content, category = :category, status = :status, limit_date = :limit_date WHERE id = :task_id AND creator = :login_id AND delete_flag = 0';
      $data3 = array(
        ':title'=>$title,
        ':content'=>$content,
        ':category'=>$category,
        ':status'=>$status,
        ':limit_date'=>$limit_date,
        ':task_id'=>$task,
        ':login_id'=>$_SESSION['login_id']
      );
      $stmt3 = queryPost($dbh, $sql3, $data3);
      debug('タスクID:'.$task);

      // 参加者を一旦全削除
      $sql4 = 'UPDATE join_user SET delete_flag = 1 WHERE task = :task AND delete_flag = 0';
      $data4 = array(':task'=>$task);
      $stmt4 = queryPost($dbh, $sql4, $data4);

      // 参加者登録
      $sql5 = 'INSERT INTO join_user(user_id,task,create_date) VALUES(:creator,:task,:create_date)';
      $data5 = array(':creator'=>$_SESSION['login_id'], ':task'=>$task, ':create_date'=>date('Y-m-d H:i:s'));
      $stmt5 = queryPost($dbh, $sql5, $data5);

      // 作成者以外に参加者がいる場合
      if(!empty($participant)) {
        debug('他の参加者あり');
        $sql6 = 'INSERT INTO join_user(user_id,task,create_date) VALUES';
        $data6 = array();
        $no = 0; //foreachの最後の処理を変えるための変数
        $last = count($participant) - 1; //最後の処理
        foreach($participant as $user_id) {
          switch($no++) {
            case $last:
              $sql6 .= '('.$user_id.', :task'.$user_id.', :create_date'.$user_id.')';
              $data6 += array(':task'.$user_id.''=>$task, ':create_date'.$user_id.''=>date('Y-m-d H:i:s'));
              break;
            default:
              $sql6 .= '('.$user_id.', :task'.$user_id.', :create_date'.$user_id.'),';
              $data6 += array(':task'.$user_id.''=>$task, ':create_date'.$user_id.''=>date('Y-m-d H:i:s'));
              break;
          }
        }
        $stmt6 = queryPost($dbh, $sql6, $data6);
      }

      // 完了後
      if($stmt5 || $stmt6) {
        debug('タスク編集完了、javascriptでtaskDetail.phpへ遷移');
        $_SESSION['msg'] = 'タスクを編集しました';
      }

    } catch(Exception $e) {
      error_log('エラー発生(タスク編集):'.$e->getMessage());
      $error = 'UnknownError';
    }

  } else { //empty($err_msg)
    debug('バリデーションエラー');
    debug('エラー箇所:'.print_r($err_msg,true));
    foreach ($err_msg as $key => $val) {
      $error[] = array('classname'=>$key, 'message'=>$val);
    }
  }

} else { //valid_tokenなど
  debug('不正なアクセスをしている可能性');
  $error = ('BadAccess');
}

// 返信
if(!isset($error)) {
  debug('処理が正常に完了');
  $data = '変更保存完了';
  echo json_encode(compact('data'));
} else {
  http_response_code(400);
  echo json_encode(compact('error'));
}

debug('================画面処理終了================');
?>
