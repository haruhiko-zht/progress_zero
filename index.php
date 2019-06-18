<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('index.php');
debug('//////////////////////////////////////////');
debugLogStart();
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
  <?php
  // ログイン時
  if(isLogin()): ?>
    <?php require_once('parts/indexLogin.php'); ?>
  <?php
  // 非ログイン時
  else: ?>
    <main class="col-2-layout">
      <div class="wrap">
        <!-- 左カラム -->
        <div class="left-col">
          <span class="service-detail">進捗管理サービス</span>
          <span class="service-name">進捗Zero</span>
        </div>

        <!-- 右カラム -->
        <div class="right-col">
          <p>今すぐ進捗を管理する！</p>
          <!-- 登録フォーム -->
          <form action="login.php?t=register" method="post">
            <input type="text" name="name" placeholder="名前">
            <input type="text" name="user_id" placeholder="ユーザーID">
            <input type="text" name="email" placeholder="メールアドレス">
            <input type="password" name="pass" placeholder="パスワード">
            <input type="hidden" name="token" value="<?=h(generate_token())?>">
            <input type="submit" name="register" value="登録">
          </form>
        </div>
      </div>
    </main>
  <?php endif; ?>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
