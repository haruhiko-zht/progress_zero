<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('followList.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// フォロー取得
try {
  $dbh = dbConnect();
  $sql = 'SELECT u.id,u.name,u.user_id,u.mood,u.image FROM follow AS f LEFT JOIN users AS u ON f.favorite_user = u.id WHERE f.user_id = :user_id AND f.delete_flag = 0 AND u.delete_flag = 0';
  $data = array(':user_id'=>$_SESSION['login_id']);
  $stmt = queryPost($dbh, $sql, $data);
  $users = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(フォロー取得):'.$e->getMessage());
}
?>
<!-- ヘッド -->
<?php
$title = 'フォローリスト|';
require_once('parts/head.php');
?>

<body>
  <!-- ヘッダー -->
  <?php require_once('parts/header.php'); ?>

  <!-- メイン -->
  <main class="follow">
    <div class="wrap2">
      <section>
        <h1>フォローリスト</h1>
        <?php if(!empty($users)): ?>
          <?php foreach($users as $user): ?>
            <div class="follow-list">
              <!-- フォロー者の画像 -->
              <div class="follow-image">
                <img src="<?=h($user['image'])?>">
              </div>
              <!-- フォロー者の詳細＆フォロー操作 -->
              <div class="follow-detail">
                <div><?=h($user['name'])?>（<?=h($user['user_id'])?>）</div>
                <div><?=h($user['mood'])?></div>
                <div data-pairid="<?=h($user['id'])?>" data-token="<?=h(generate_token())?>"><?=(isFollow($user['id'])) ? '<span class="js-follow follow-active"><i class="fas fa-user-minus"></i><span>フォロー解除</span></span>' : '<span class="js-follow"><i class="fas fa-user-plus"></i><span>フォローする</span></span>';?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>まだ、誰もフォローしていません</p>
        <?php endif; ?>
      </section>
    </div>
  </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
