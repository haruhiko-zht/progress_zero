<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('withdraw.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// 退会申請
if(!empty($_POST['withdraw']) && !empty($_POST['agreement']) && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('退会申請POST確認');
  try {
    // ユーザー情報の論理削除
    $dbh = dbConnect();
    $sql1 = 'UPDATE users SET delete_flag = 1, delete_time = :delete_time WHERE id = :login_id AND delete_flag = 0 AND delete_time > :now_time';
    $data1 = array(
      ':delete_time'=>(time() + 60 * 60 * 24 * 30),
      ':login_id'=>$_SESSION['login_id'],
      ':now_time'=>time()
    );
    $stmt1 = queryPost($dbh, $sql1, $data1);

    // email_changesの未使用予約全削除
    $sql2 = 'UPDATE email_changes SET delete_flag = 1 WHERE user_id = :login_id AND delete_flag = 0';
    $data2 = array(':login_id'=>$_SESSION['login_id']);
    $stmt2 = queryPost($dbh, $sql2, $data2);

    // followの論理削除
    $sql3 = 'UPDATE follow SET delete_flag = 1 WHERE (user_id = :login_id_a OR favorite_user = :login_id_b) AND delete_flag = 0';
    $data3 = array(':login_id_a'=>$_SESSION['login_id'], ':login_id_b'=>$_SESSION['login_id']);
    $stmt3 = queryPost($dbh, $sql3, $data3);

    // pass_reminderの未使用予約全削除
    $sql4 = 'UPDATE pass_reminder SET delete_flag = 1 WHERE user_id = :login_id AND delete_flag = 0';
    $data4 = array(':login_id'=>$_SESSION['login_id']);
    $stmt4 = queryPost($dbh, $sql4, $data4);

    if($stmt1 && $stmt2 && $stmt3 && $stmt4) {
      debug('削除完了');
      session_regenerate_id(true);
      setcookie(session_name(), '', 1);
      session_destroy();

      $_SESSION['msg'] = '退会が完了しました、ご利用ありがとうございました';
      header('Location:index.php');
      exit();
    }
  } catch(Exception $e) {
    error_log('エラー発生(退会):'.$e->getMessage());
  }
}
?>
<!-- ヘッド -->
<?php
$title = '退会|';
require_once('parts/head.php');
?>

  <body>
    <!-- ヘッダー -->
    <?php require_once('parts/header.php'); ?>

    <!-- メイン -->
    <main>
      <div class="wrap">
        <section class="form-sec-1">
          <div class="tab-content">
            <p><span class="x-large">退会しますか？</span><br>退会後は30日以内であれば、ログインすることで復帰可能です<br>また同期間中、同じメールアドレス・ユーザーIDは使用することができません</p>
            <form action="" method="post">
              <div class="form-g tac">
                <label><input type="checkbox" name="agreement"> 同意して退会する</label>
              </div>
              <div class="form-g">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name="withdraw" value="退会する">
              </div>
            </form>
          </div>
        </section>
      </div>
    </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
