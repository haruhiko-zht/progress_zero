<?php
// ========================
// セッション期限のチェック
// ========================
if(!empty($_SESSION['login_date'])){
  debug('セッションあり');
  if($_SESSION['login_date'] + $_SESSION['login_limit'] < time()){
    debug('セッション期限切れ');

    session_regenerate_id(true);
    setcookie(session_name(), '', 1);
    session_destroy();

    debug('セッション破棄->index.phpへ遷移');
    header('Location:index.php');
    exit();

  } else {
    debug('セッション期限内->ログイン時間更新');
    if(empty($_POST) && empty($_GET)) session_regenerate_id(true);
    $_SESSION['login_date'] = time();
  }

} else {
  debug('セッションなし');
}
?>
