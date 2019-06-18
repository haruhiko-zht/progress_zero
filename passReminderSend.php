<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('passReminderSend.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(isLogin()){
  debug('ログイン済->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

//パスワード再発行申請POST
if(!empty($_POST['send']) && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('パスワード再発行POST確認');
  // バリデーションチェック
  $user_info = checkAllEmail('user_info');

  if(empty($err_msg)){
    debug('バリデーションチェック通過');
    // DB照合
    try {
      $dbh = dbConnect();
      $sql = 'SELECT email,id FROM users WHERE email = :user_info AND delete_flag = 0';
      $data = array(':user_info'=>$user_info);
      $stmt = queryPost($dbh, $sql, $data);
      $res = $stmt->fetch();

      if(!empty($res)) {
        debug('該当のユーザー情報を発見');
        $email = $res['email'];
        $user_id = $res['id'];
        $token = hash('sha256', $email);

        // 重複リクエストのチェック
        $sql = 'SELECT token_limit FROM pass_reminder WHERE token = :token ORDER BY create_date DESC LIMIT 1';
        $data = array(':token'=>$token);
        $stmt = queryPost($dbh, $sql, $data);
        $res = $stmt->fetch();

        if(!empty($res)){
          $token_limit = $res['token_limit'];
          if((int)$token_limit > time()) {
            debug('30分以内に複数リクエスト、却下');
            $_SESSION['msg'] = '変更リクエストは30分に１回までです';
            header('Location: passReminderSend.php');
            exit();
          }
        }

        $pass = random();
        $sql = 'INSERT INTO pass_reminder(user_id,email,pass,token,token_limit,create_date) VALUES(:user_id,:email,:pass,:token,:token_limit,:create_date)';
        $data = array(
          ':user_id'=>$user_id,
          ':email'=>$email,
          ':pass'=>$pass,
          ':token'=>$token,
          ':token_limit'=>(time() + 60 * 30),
          ':create_date'=>date('Y-m-d H:i:s')
        );
        $stmt = queryPost($dbh, $sql, $data);

        if($stmt) {
          $from = 'XXX@gmail.com';
          $to = $email;
          $subject = 'パスワード再発行の手続き';
          $comment = <<<EOT
進捗簡易管理サービス『進捗Zero』より、パスワード再発行の手続きが行われました。
下記URLより、認証キーを入力して下さい。

http://localhost:8888/progress_zero/passReminderReceive.php?token={$token}
認証キー：{$pass}

上記URLおよび認証キーは発行より30分のみ有効です。

本メールが身に覚えがない場合、削除して下さい。
EOT;
          sendMail($from, $to, $subject, $comment);
          header('passReminderSent.php');
          exit();
        }
      } else {
        debug('ユーザー情報が存在しない');
        $err_msg['user_info'] = 'ユーザー情報が存在しません';
      }
    } catch(Exception $e) {
      error_log('エラー発生(パスワード再発行):'.$e->getMessage());
    }

  }

}
?>
<!-- ヘッド -->
<?php
$title = 'パスワードの再発行|';
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
          <h1>パスワードの再発行</h1>
          <p>
            パスワードを再発行するための認証キーを送信します。<br>
            フォームに「メールアドレス」を入力して下さい。
          </p>
          <form action="" method="post">
            <div class="form-g">
              <input type="text" name="user_info" placeholder="メールアドレス" value="<?=returnFormValue('user_info')?>">
              <div class="err_msg"><?=getErrMsg('user_info')?></div>
            </div>
            <div class="form-g">
              <input type="hidden" name="token" value="<?=h(generate_token())?>">
              <input type="submit" name="send" value="認証キーを送信する">
            </div>
          </form>
        </div>
      </section>
    </div>
  </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
