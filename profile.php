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

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// ユーザーデータ取得
try {
  $dbh = dbConnect();
  $sql = 'SELECT name,user_id,mood,publish,image FROM users WHERE id = :id AND delete_flag = 0';
  $data = array(':id'=>$_SESSION['login_id']);

  $stmt = queryPost($dbh, $sql, $data);
  $user = $stmt->fetch();
} catch(Exception $e) {
  error_log('エラー発生(プロフィールデータ取得):'.$e->getMessage());
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
    <main class="profile">
      <div class="wrap2">

        <!-- プロフィールエリア -->
        <section>
          <form action="" method="post" enctype="multipart/form-data" id="js-prof-edit-form">
            <div class="profile-wrap">
              <!-- プロフィール画像 -->
              <div class="profile-image">
                <!-- 画像 -->
                <img src="<?=h($user['image'])?>">
                <!-- 画像ドロップエリア -->
                <label class="patl js-drop-area js-prof-edit-on">
                  <img src="image/anonymous.jpeg" class="patl js-new-image">
                  <div class="patl js-drag-effect">画像を変更する</div>
                  <input type="file" name="image" class="patl">
                </label>
                <!-- 画像エラーメッセージ -->
                <div class="err_msg js-img-msg"></div>
                <!-- 画像削除ボタン -->
                <div class="js-prof-edit-on">
                  <label class="js-del-prof-img">画像を削除する<input type="checkbox" name="delete-image"></label>
                </div>
              </div>
              <!-- プロフィール詳細 -->
              <div class="profile-detail">
                <table>
                  <tbody>
                    <tr>
                      <td>名前：</td>
                      <td>
                        <!-- 表示 -->
                        <div class="js-prof-edit-off">
                          <?=h($user['name'])?>
                        </div>
                        <!-- 編集 -->
                        <div class="js-prof-edit-on">
                          <input type="text" name="name" value="<?=h($user['name'])?>">
                          <div class="err_msg js-name-msg">エラーの表示位置</div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>ユーザーID：</td>
                      <td><?=h($user['user_id'])?></td>
                    </tr>
                    <tr>
                      <td>ひとこと：</td>
                      <td>
                        <!-- 表示 -->
                        <div class="js-prof-edit-off">
                          <?=h($user['mood'])?>
                        </div>
                        <!-- 編集 -->
                        <div class="js-prof-edit-on">
                          <textarea name="mood"><?=h($user['mood'])?></textarea>
                          <div class="err_msg js-mood-msg">エラーの表示位置</div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td>公開設定：</td>
                      <td>
                        <!-- 表示 -->
                        <div class="js-prof-edit-off">
                          <?php echo ((int)$user['publish'] === 0) ? '検索一覧に表示する' : '検索一覧に表示しない';?>
                        </div>
                        <!-- 編集 -->
                        <div class="js-prof-edit-on">
                          <select name="publish">
                            <option value="0" <?php if((int)$user['publish'] === 0) echo 'selected';?>>検索一覧に表示する</option>
                            <option value="1" <?php if((int)$user['publish'] === 1) echo 'selected';?>>検索一覧に表示しない</option>
                          </select>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 変更保存＆キャンセルボタン -->
            <div class="profile-setting js-prof-edit-on">
              <input type="hidden" name="token" value="<?=h(generate_token())?>" class="btn1">
              <input type="submit" name="edit-done" value="変更を保存する" class="btn1">
              <input type="reset" value="キャンセル" class="js-prof-edit-cancel btn1">
            </div>

          </form>

            <!-- プロフィール内行動選択 -->
            <div class="profile-setting js-prof-edit-off">
              <a href="" class="js-prof-edit-btn btn1">プロフィール編集</a>
              <a href="importantChange.php" class="btn1">重要な変更</a>
              <a href="withdraw.php" class="btn1">退会</a>
            </div>

        </section>
      </div>
    </main>

  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
