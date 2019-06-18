<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('importantChange.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// ユーザーID変更
if(!empty($_POST['edit_userid']) && valid_token(filter_input(INPUT_POST, 'token_userid'))) {
  debug('ユーザーID変更POST確認');
  // バリデーションチェック
  $new_userid = checkAllUserid('new_userid');
  if(isExistUserid($new_userid)) {
    $err_msg['new_userid'] = 'そのIDは既に使用されています';
  }

  if(empty($err_msg)) {
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'UPDATE users SET user_id = :new_userid WHERE id = :id AND delete_flag = 0';
      $data = array(':new_userid'=>$new_userid, ':id'=>$_SESSION['login_id']);
      $stmt = queryPost($dbh, $sql, $data);

      if($stmt) {
        debug('変更完了、profile.phpへ遷移');
        $_SESSION['msg'] = 'ユーザーIDを変更しました';
        header('Location:profile.php');
        exit();
      }
    } catch(Exception $e) {
      error_log('エラー発生(ユーザーID変更):'.$e->getMessage());
      $_SESSION['msg'] = MSG05;
    }
  }
}

// メールアドレス変更
if(!empty($_POST['edit_email']) && valid_token(filter_input(INPUT_POST, 'token_email'))) {
  debug('メールアドレス変更POST確認');
  // バリデーションチェック
  $new_email = checkAllEmail('new_email');
  if(isExistEmail($new_email)) {
    $err_msg['new_email'] = 'そのメールアドレスは既に登録されています';
  }
  if(empty($err_msg['new_email'])) {
    debug('繰り返し変更メールを送っていないかチェック');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT token_limit FROM email_changes WHERE user_id = :user_id ORDER BY create_date DESC LIMIT 1';
      $data = array(':user_id'=>$_SESSION['login_id']);
      $stmt = queryPost($dbh, $sql, $data);
      $res = $stmt->fetch();
      if((int)$res['token_limit'] > time()) {
        debug('30分以内に変更リクエスト送信済み->リクエスト却下');
        $err_msg = true;
        $_SESSION['msg'] = '変更リクエストは30分に1回です';
      }
    } catch(Exception $e) {
      error_log('エラー発生(メールアドレス変更リクエストチェック):'.$e->getMessage());
      $_SESSION['msg'] = MSG05;
    }
  }
  $pass = checkAllPass('pass');

  if(empty($err_msg)) {
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT email,pass FROM users WHERE id = :id AND delete_flag = 0';
      $data = array(':id'=>$_SESSION['login_id']);
      $stmt = queryPost($dbh, $sql, $data);
      $user_data = $stmt->fetch();

      if(password_verify($pass, $user_data['pass'])) {
        debug('パスワード照合OK');
        $changeToken = hash('sha256', $new_email.session_id());
        $sql = 'INSERT INTO email_changes(user_id,new_email,token,token_limit,create_date) VALUES(:user_id,:new_email,:token,:token_limit,:create_date)';
        $data = array(
          ':user_id'=>$_SESSION['login_id'],
          ':new_email'=>$new_email,
          ':token'=>$changeToken,
          ':token_limit'=>(time() + 60 * 30),
          ':create_date'=>date('Y-m-d H:i:s')
        );
        $stmt = queryPost($dbh, $sql, $data);
        if($stmt) {
          debug('メールアドレス変更予約成功->メール送信');
          $from = 'XXX@gmail.com';
          $to = $new_email;
          $subject = 'メールアドレス変更の確認';
          $comment = <<<EOT
進捗簡易管理サービス『進捗Zero』より、メールアドレスの変更手続きが行われました。
本メールアドレスを有効にする場合、下記URLをクリックお願いします。

http://localhost:8888/progress_zero/email_changes.php?changetoken={$changeToken}
上記URLは発行より30分のみ有効です。

本メールが身に覚えがない場合、削除して下さい。
EOT;
          sendMail($from, $to, $subject, $comment);
          $err_msg['all'] = '本メールアドレスに変更メールを送信しました。<br>内容を確認し、メールアドレスを有効にして下さい。';
        } else {
          debug('メールアドレス変更予約失敗');
          $_SESSION['msg'] = MSG05;
        }
      } else {
        debug('パスワードが正しくない');
        $err_msg['pass'] = 'パスワードが正しくありません';
      }
    } catch(Exception $e) {
      error_log('エラー発生(メールアドレス変更):'.$e->getMessage());
      $_SESSION['msg'] = MSG05;
    }
  }

}

// パスワード変更
if(!empty($_POST['edit_pass']) && valid_token(filter_input(INPUT_POST, 'token_pass'))) {
  debug('パスワード変更POST確認');
  // バリデーションチェック
  $old_pass = checkAllPass('old_pass');
  $new_pass = checkAllPass('new_pass');
  if((string)$new_pass !== (string)filter_input(INPUT_POST, 'new_pass_re')) {
    $err_msg['new_pass_re'] = 'パスワードが一致していません';
  }

  if(empty($err_msg)) {
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'SELECT email,pass FROM users WHERE id = :id AND delete_flag = 0';
      $data = array(':id'=>$_SESSION['login_id']);
      $stmt = queryPost($dbh, $sql, $data);
      $user_data = $stmt->fetch();

      if(password_verify($old_pass, $user_data['pass'])) {
        $sql = 'UPDATE users SET pass = :new_pass WHERE id = :id AND delete_flag = 0';
        $data = array(':new_pass'=>password_hash($new_pass, PASSWORD_DEFAULT), ':id'=>$_SESSION['login_id']);
        $stmt = queryPost($dbh, $sql, $data);
        if($stmt) {
          debug('パスワード変更完了->変更メール送信->profile.phpへ遷移');
          $from = 'XXX@gmail.com';
          $to = $user_data['email'];
          $subject = 'パスワード変更の確認';
          $comment = <<<EOT
進捗簡易管理サービス『進捗Zero』より、パスワード変更のお知らせをいたします。
EOT;
          sendMail($from, $to, $subject, $comment);

          $_SESSION['msg'] = 'パスワードを変更しました';
          header('Location:profile.php');
          exit();
        } else {
          debug('パスワード変更に失敗');
          $_SESSION['msg'] = MSG05;
        }
      } else {
        debug('パスワードが正しくない');
        $err_msg['old_pass'] = 'パスワードが正しくありません';
      }
    } catch(Exception $e) {
      error_log('エラー発生(パスワード変更):'.$e->getMessage());
      $_SESSION['msg'] = MSG05;
    }
  }
}
?>

<!-- ヘッド -->
<?php
$title = '重要な変更|';
require_once('parts/head.php');
?>

  <body>
    <!-- ヘッダー -->
    <?php require_once('parts/header.php'); ?>

    <!-- メイン -->
    <main class="important">
      <div class="wrap">
        <section class="form-sec-1">
          <!-- タブ -->
          <input type="radio" name="tab_btn" id="tab1" <?php if(empty($_POST) || !empty($_POST['edit_userid'])) echo 'checked'?>>
          <input type="radio" name="tab_btn" id="tab2" <?php if(!empty($_POST['edit_email'])) echo 'checked'?>>
          <input type="radio" name="tab_btn" id="tab3" <?php if(!empty($_POST['edit_pass'])) echo 'checked'?>>

          <!-- タブ用ラジオボタン -->
          <div class="tab tab3">
            <label class="tab1_label" for="tab1">ユーザーID変更</label>
          </div>
          <div class="tab tab3">
            <label class="tab2_label" for="tab2">メールアドレス変更</label>
          </div>
          <div class="tab tab3">
            <label class="tab3_label" for="tab3">パスワード変更</label>
          </div>

          <!-- パネルエリア -->
          <div class="panel_area">
            <!-- パネル１ -->
            <div class="tab-content tab_panel" id="panel1">
              <form action="" method="post">
                <div class="form-g tac">
                  <p>ユーザーIDを変更します</p>
                  <p>新しいユーザーIDを4〜16字で入力して下さい</p>
                </div>
                <div class="form-g">
                  <input type="text" name="new_userid" placeholder="新しいユーザーID" value="<?=returnFormValue('new_userid')?>">
                  <div class="err_msg"><?=getErrMsg('new_userid')?></div>
                </div>
                <div class="form-g">
                  <input type="hidden" name="token_userid" value="<?=h(generate_token())?>">
                  <input type="submit" name="edit_userid" value="変更">
                </div>
              </form>
            </div>

            <!-- パネル２ -->
            <div class="tab-content tab_panel" id="panel2">
              <form action="" method="post">
                <div class="form-g tac">
                  <p>メールアドレスを変更手続きを行います</p>
                  <p>新しいメールアドレスを入力して下さい</p>
                </div>
                <div class="form-g">
                  <input type="text" name="new_email" placeholder="新しいメールアドレス" value="<?=returnFormValue('new_email')?>">
                  <div class="err_msg"><?=getErrMsg('new_email')?></div>
                </div>
                <div class="form-g">
                  <input type="password" name="pass" placeholder="パスワード" value="<?=returnFormValue('pass')?>">
                  <div class="err_msg"><?=getErrMsg('pass')?></div>
                </div>
                <div class="form-g">
                  <input type="hidden" name="token_email" value="<?=h(generate_token())?>">
                  <input type="submit" name="edit_email" value="送信">
                </div>
              </form>
              <div><?=getErrMsg('all')?></div>
            </div>

            <!-- パネル３ -->
            <div class="tab-content tab_panel" id="panel3">
              <form action="" method="post">
                <div class="form-g tac">
                  <p>パスワードを変更します</p>
                  <p>現在のパスワードと新しいパスワードを入力して下さい</p>
                </div>
                <div class="form-g">
                  <input type="password" name="old_pass" placeholder="現在のパスワード" value="<?=returnFormValue('old_pass')?>">
                  <div class="err_msg"><?=getErrMsg('old_pass')?></div>
                </div>
                <div class="form-g">
                  <input type="password" name="new_pass" placeholder="新しいパスワード" value="<?=returnFormValue('new_pass')?>">
                  <div class="err_msg"><?=getErrMsg('new_pass')?></div>
                </div>
                <div class="form-g">
                  <input type="password" name="new_pass_re" placeholder="新しいパスワード(再入力)" value="<?=returnFormValue('new_pass_re')?>">
                  <div class="err_msg"><?=getErrMsg('new_pass_re')?></div>
                </div>
                <div class="form-g">
                  <input type="hidden" name="token_pass" value="<?=h(generate_token())?>">
                  <input type="submit" name="edit_pass" value="変更">
                </div>
              </form>
            </div>

          </div>

        </section>

      </div>
    </main>
    <?php debug('================画面処理終了================'); ?>

  <!-- フッター -->
  <?php require_once('parts/footer.php'); ?>
