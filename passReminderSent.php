<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('passReminderSent.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(isLogin()){
  debug('ログイン済->index.phpへ遷移');
  header('Location:index.php');
  exit();
}
?>
<!-- ヘッド -->
<?php
$title = 'プロフィール|';
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
          <h1>認証キーを送信しました</h1>
          <p>入力していただいたメールアドレス宛に認証キーを送信しました。</p>
          <p>認証キーは発行より３０分のみ有効です。</p>
        </div>
      </section>
    </div>
  </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
