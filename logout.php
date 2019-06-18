<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('profile.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログアウト処理
if(!empty($_SESSION['login'])){
  debug('ログアウト->セッション再生成＆破棄->index.phpへ遷移');
  session_regenerate_id(true);
  setcookie(session_name(), '', 1);
  session_destroy();

  header('Location:index.php');
  exit();

} else {
  debug('そもそもログインしていない->index.phpへ遷移');

  header('Location:index.php');
  exit();
}

debug('================画面処理終了================');
?>
