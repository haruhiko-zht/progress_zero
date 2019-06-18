<?php if(isLogin()): ?>
  <header>
    <div class="wrap">
      <!-- サイトロゴ -->
      <div class="site-logo">
        <h1><a href="index.php">進捗Zero</a></h1>
      </div>
      <!-- ナビゲーション -->
      <nav>
        <ul>
          <li><a href="logout.php">ログアウト</a></li>
          <li><a href="search.php">検索</a></li>
          <li><a href="followList.php">フォローリスト</a></li>
          <li><a href="taskNew.php">新規タスク</a></li>
          <li><a href="profile.php">プロフィール</a></li>
          <li><a href="index.php">ホーム</a></li>
        </ul>
      </nav>
    </div>
  </header>
<?php else: ?>
  <header>
    <div class="wrap">
      <!-- サイトロゴ -->
      <div class="site-logo">
        <h1><a href="index.php">進捗Zero</a></h1>
      </div>
      <!-- ナビゲーション -->
      <nav>
        <ul>
          <li><a href="login.php?t=login">ログイン</a></li>
          <li><a href="login.php?t=register">ユーザー登録</a></li>
        </ul>
      </nav>
    </div>
  </header>
<?php endif; ?>

<!-- スライドメッセージ -->
<div id="js-msg-area" class="js-notification"><?php echo getSesMsg(); ?></div>
