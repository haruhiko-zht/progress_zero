<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('taskNew.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// フォロー状況取得
try {
  $dbh = dbConnect();
  $sql = 'SELECT f.favorite_user,u.name,u.user_id FROM follow AS f LEFT JOIN users AS u ON f.favorite_user = u.id WHERE f.user_id = :user_id AND f.delete_flag = 0 AND u.delete_flag = 0';
  $data = array(':user_id'=>$_SESSION['login_id']);
  $stmt = queryPost($dbh, $sql, $data);
  $favorite_users = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(フォロー状況取得):'.$e->getMessage());
}
?>

<!-- ヘッド -->
<?php
$title = '新規タスク|';
require_once('parts/head.php');
?>

  <body>
    <!-- ヘッダー -->
    <?php require_once('parts/header.php'); ?>

    <!-- メイン -->
    <main class="taskNew">
      <div class="wrap">
        <section>
          <form action="" method="post" id="js-new-task">
            <!-- カラム１ === タイトル -->
            <div class="taskDetail-col1">
              <input type="text" name="title" placeholder="タイトルを入力" class="xx-large" value="<?=returnFormValue('title')?>">
            </div>
            <div class="err_msg js-title-msg"></div>

            <!-- カラム２ -->
            <div class="taskDetail-col2 small"></div>

            <!-- カラム３ === カテゴリ＆参加者＆完了期限 -->
            <div class="taskDetail-col3">
              <div class="task-option">カテゴリ<br>
                <input type="text" name="category" placeholder="カテゴリを入力" class="middle-large">
                <div class="err_msg js-category-msg"></div>
              </div>
              <div class="task-option">参加者<br>
                <select name="participant[]" multiple="multiple" class="SelectBox">
                  <?php foreach($favorite_users as $user): ?>
                    <option value="<?=h($user['favorite_user'])?>"><?=h($user['name'])?>(<?=h($user['user_id'])?>)</option>
                  <?php endforeach; ?>
                </select>
                <div class="err_msg js-participant-msg"></div>
              </div>
              <div class="task-option">完了期限<br>
                <input type="date" name="date" min="<?=date('Y-m-d')?>" class="middle-large">
                <div class="err_msg js-date-msg"></div>
              </div>
            </div>

            <!-- カラム４ ===タスク内容 -->
            <div class="taskDetail-col4">
              <h2>タスク内容</h2>
              <textarea name="content" class="tinymce" rows="10" placeholder="タスク内容を記入"></textarea>
              <div class="err_msg js-content-msg"></div>
            </div>

            <!-- カラム７ === 登録ボタン -->
            <div class="taskDetail-col7">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name"regist" value="タスクを登録する" class="btn1">
                <a href="index.php" class="btn1">キャンセル</a>
            </div>
         </form>
        </section>
      </div>
    </main>
    <?php debug('================画面処理終了================'); ?>

  <!-- フッター -->
  <?php require('parts/footer.php'); ?>
