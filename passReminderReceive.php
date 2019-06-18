<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('passReminderRecieve.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(isLogin()){
  debug('ログイン済->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// GET送信チェック
if(empty($_GET['token'])) {
  debug('GET送信なし->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// token照合
$token = filter_input(INPUT_GET, 'token');
try {
  $dbh = dbConnect();
  $sql = 'SELECT token_limit FROM pass_reminder WHERE token = :token AND delete_flag = 0 ORDER BY create_date DESC LIMIT 1';
  $data = array(':token'=>$token);
  $stmt = queryPost($dbh, $sql, $data);
  $res = $stmt->fetch();

  if(empty($res)) {
    debug('不正なアクセス');
    header('Location: index.php');
    exit();
  } else {
    $token_limit = $res['token_limit'];
    if($token_limit < time()) {
      debug('トークンの有効期限切れ');
      $_SESSION['msg'] = '期限切れのURLです';
      header('Location: index.php');
      exit();
    }
  }
} catch(Exception $e) {
  error_log('エラー発生(token照合):'.$e->getMessage());
}

// 認証キー照合
if(!empty($_POST['send']) && valid_token(filter_input(INPUT_POST, 'token'))){
  $pass = (string)filter_input(INPUT_POST, 'submit_key');
  try {
    $dbh = dbConnect();
    $sql = 'SELECT email FROM pass_reminder WHERE pass = :pass AND token = :token AND delete_flag = 0 ORDER BY create_date DESC LIMIT 1';
    $data = array(':pass'=>$pass, ':token'=>$token);
    $stmt = queryPost($dbh, $sql, $data);
    $res = $stmt->fetch();

    if(empty($res)) {
      debug('不正な認証キー');
      $_SESSION['msg'] = '無効な認証キーです';
      header("Location: ".$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
      exit();
    } else {
      debug('認証成功');
      $email = $res['email'];
      $new_pass = random(12);

      $sql1 = 'UPDATE users SET pass = :new_pass WHERE email = :email AND delete_flag = 0';
      $data1 = array(':new_pass'=>password_hash($new_pass, PASSWORD_DEFAULT), ':email'=>$email);
      $stmt1 = queryPost($dbh, $sql1, $data1);

      $sql2 = 'UPDATE pass_reminder SET delete_flag = 1 WHERE email = :email AND pass = :pass AND token = :token AND delete_flag = 0';
      $data2 = array(':email'=>$email, ':pass'=>$pass, ':token'=>$token);
      $stmt2 = queryPost($dbh, $sql2, $data2);

      if($stmt1 && $stmt2) {
        $from = 'XXX@gmail.com';
        $to = $email;
        $subject = 'パスワード再発行のお知らせ';
        $comment = <<<EOT
進捗簡易管理サービス『進捗Zero』より、パスワードの再発行を行いました。
下記パスワードを使用し、ログインして下さい。

パスワード：{$new_pass}
ログインURL：http://localhost:8888/progress_zero/login.php?t=login

ログイン後、パスワードを変更されることをお勧めします。

EOT;
        sendMail($from, $to, $subject, $comment);
        $_SESSION['msg'] = 'メールアドレス宛に新しいパスワードを送信しました';
        header('Location: login.php?t=login');
        exit();
      }
    }
  } catch(Exception $e) {
    error_log('エラー発生(token照合):'.$e->getMessage());
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
            <h1>認証キーの入力</h1>
            <p>
              下記フォームに認証キーを入力して下さい
            </p>
            <form action="" method="post">
              <div class="form-g">
                <input type="text" name="submit_key" placeholder="認証キー">
              </div>
              <div class="form-g">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name="send" value="パスワードを再発行する">
              </div>
            </form>
          </div>
        </section>
      </div>
    </main>
    <?php debug('================画面処理終了================'); ?>

  <!-- フッター -->
  <?php require('parts/footer.php'); ?>
