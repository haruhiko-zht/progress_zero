<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('login.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(isLogin()){
  debug('ログイン済み->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// ユーザー登録
if(!empty($_POST['register']) && valid_token(filter_input(INPUT_POST, 'token'))){
  debug('ユーザー登録POST確認');
  debug('バリデーションチェック');
  // バリデーションチェック
  $name = checkAllName('name');
  $user_id = checkAllUserid('user_id');
  $email = checkAllEmail('email');
  $pass = checkAllPass('pass');
  // 重複チェック
  if(isExistEmail($email)) {
    debug('メールアドレス重複');
    $err_msg['email'] = 'そのメールアドレスは既に登録されています';
  }
  if(isExistUserid($user_id)) {
    debug('ユーザーID重複');
    $err_msg['user_id'] = 'そのユーザーIDは既に登録されている';
  }

  // チェック通過した場合
  if(empty($err_msg)){
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'INSERT INTO users(name,user_id,email,pass,create_date) VALUES(:name,:user_id,:email,:pass,:create_date)';
      $data = array(
        ':name'=>$name,
        ':user_id'=>$user_id,
        ':email'=>$email,
        ':pass'=>password_hash($pass, PASSWORD_DEFAULT),
        ':create_date'=>date('Y-m-d H:i:s')
      );
      $stmt = queryPost($dbh, $sql, $data);
      if($stmt){
        $id = $dbh->lastInsertId();
        $sesLimit = 60 * 60;

        debug('セッション再生成->セッション情報記入');
        session_regenerate_id(true);
        $_SESSION['login_date'] = time();
        $_SESSION['login_limit'] = $sesLimit;
        $_SESSION['login_id'] = $id;
        $_SESSION['login'] = true;

        debug('セッション情報:'.print_r($_SESSION,true));
        debug('index.phpへ遷移');
        header("Location:index.php");
        exit();
      }
    } catch(Exception $e) {
      error_log('エラー発生(ユーザー登録):'.$e->getMessage());
    }
  }
}

// ログイン
if(!empty($_POST['login']) && valid_token(filter_input(INPUT_POST,'token'))){
  debug('ログインPOST確認');
  debug('バリデーションチェック');

  //user_infoがメールアドレスかユーザーIDかのチェック
  if(isEmailAddress($_POST['user_info'])){
    $user_info = checkAllEmail('user_info');
  } else {
    $user_info = checkAllUserid('user_info');
  }
  // その他バリデーションチェック
  $pass = checkAllPass('user_pass');

  if(empty($err_msg)){
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      if(isEmailAddress($_POST['user_info'])){
        $sql = 'SELECT id,pass,delete_time FROM users WHERE email = :user_info AND delete_time > :now_time';
      } else {
        $sql = 'SELECT id,pass,delete_time FROM users WHERE user_id = :user_info AND delete_time > :now_time';
      }
      $data = array(':user_info'=>$user_info, ':now_time'=>time());

      $stmt = queryPost($dbh, $sql, $data);
      $res = $stmt->fetch();

      if($stmt && password_verify($pass, $res['pass'])){

        if($res['delete_time'] !== 1000000000000000000) {
          debug('退会ユーザー');
          try {
            // ユーザー情報の復活
            $dbh = dbConnect();
            $sql1 = 'UPDATE users SET delete_flag = 0, delete_time = 1000000000000000000 WHERE id = :login_id AND delete_flag = 1 AND delete_time > :now_time';
            $data1 = array(':login_id'=>$res['id'], ':now_time'=>time());
            $stmt1 = queryPost($dbh, $sql1, $data1);

            // followの復活
            $sql2 = 'UPDATE follow SET delete_flag = 0 WHERE (user_id = :login_id_a OR favorite_user = :login_id_b) AND delete_flag = 1';
            $data2 = array(':login_id_a'=>$res['id'], ':login_id_b'=>$res['id']);
            $stmt2 = queryPost($dbh, $sql2, $data2);

            if($stmt1 && $stmt2) {
              $_SESSION['msg'] = 'ご利用復帰ありがとうございます';
            }
          } catch(Exception $e) {
            error_log('エラー発生(ユーザー復帰):'.$e->getMessage());
          }
        }

        $sesLimit = 60 * 60;

        debug('セッション再生成->セッション情報記入');
        session_regenerate_id(true);
        $_SESSION['login_date'] = time();
        $_SESSION['login_limit'] = (!empty($_POST['remain'])) ? $sesLimit * 24 * 30 : $sesLimit;
        $_SESSION['login_id'] = $res['id'];
        $_SESSION['login'] = true;

        debug('セッション情報:'.print_r($_SESSION,true));
        debug('index.phpへ遷移');
        header("Location:index.php");
        exit();
      } else {
        $err_msg['all'] = MSG04;
      }
    } catch(Exception $e) {
      error_log('エラー発生(ログイン時):'.$e->getMessage());
    }
  }


}
?>

<!-- ヘッド -->
<?php
$title = '';
require('parts/head.php');
?>

<body>
  <!-- ヘッダー -->
  <?php require('parts/header.php'); ?>

  <!-- メイン -->
  <main class="login">
    <div class="wrap">
      <section class="form-sec-1">
        <input type="radio" name="tab_btn" id="tab1" <?php if(filter_input(INPUT_GET, 't') === 'register')echo 'checked';?>>
        <input type="radio" name="tab_btn" id="tab2" <?php if(filter_input(INPUT_GET, 't') === 'login')echo 'checked';?>>

        <div class="tab">
          <label class="tab1_label" for="tab1">ユーザー登録</label>
        </div>
        <div class="tab">
          <label class="tab2_label" for="tab2">ログイン</label>
        </div>

        <div class="panel_area">
          <!-- 登録フォーム -->
          <div class="tab-content tab_panel" id="panel1">
            <form action="login.php?t=register" method="post">
              <div class="form-g">
                <input type="text" name="name" placeholder="名前" value="<?=returnFormValue('name')?>">
                <div class="err_msg"><?=getErrMsg('name')?></div>
              </div>
              <div class="form-g">
                <input type="text" name="user_id" placeholder="ユーザーID" value="<?=returnFormValue('user_id')?>">
                <div class="err_msg"><?=getErrMsg('user_id')?></div>
              </div>
              <div class="form-g">
                <input type="text" name="email" placeholder="メールアドレス" value="<?=returnFormValue('email')?>">
                <div class="err_msg"><?=getErrMsg('email')?></div>
              </div>
              <div class="form-g">
                <input type="password" name="pass" placeholder="パスワード" value="<?=returnFormValue('pass')?>">
                <div class="err_msg"><?=getErrMsg('pass')?></div>
              </div>
              <div class="form-g">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name="register" value="登録">
              </div>
            </form>
          </div>

          <!-- ログインフォーム -->
          <div class="tab-content tab_panel" id="panel2">
            <form action="login.php?t=login" method="post">
              <div class="err_msg"><?=getErrMsg('all')?></div>
              <div class="form-g">
                <input type="text" name="user_info" placeholder="メールアドレス/ユーザーID" value="<?=returnFormValue('user_info')?>" autocomplete="off">
                <div class="err_msg"><?=getErrMsg('user_info')?></div>
              </div>
              <div class="form-g">
                <input type="password" name="user_pass" placeholder="パスワード" value="<?=returnFormValue('user_pass')?>">
                <div class="err_msg"><?=getErrMsg('user_pass')?></div>
              </div>
              <div class="form-g"><label><input type="checkbox" name="remain">ログインを保存する</label></div>
              <div class="form-g">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name="login" value="ログイン">
              </div>
            </form>
            <div><a href="passReminderSend.php">パスワードを忘れた方はこちら</a></div>
          </div>
        </div>
      </section>
    </div>
  </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
