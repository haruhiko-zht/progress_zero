<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('taskDetail.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// GET送信されているか
$id = (int)filter_input(INPUT_GET, 'id');
debug('GET送信'.$id);
if(!is_int($id)){
  debug('不正なアクセス->index.phpへ遷移');
  header('Location:index.php');
  exit();
}
// 参加しているタスクか
try {
  $dbh = dbConnect();
  $sql = 'SELECT id FROM join_user WHERE task = :id AND user_id = :login_id AND delete_flag = 0';
  $data = array(':id'=>$id, ':login_id'=>$_SESSION['login_id']);
  $stmt = queryPost($dbh, $sql, $data);
  if($stmt->rowCount() === 0) {
    debug('参加権のないタスク->index.phpへ遷移');
    header('Location:index.php');
    exit();
  }
} catch(Exception $e) {
  error_log('エラー発生(参加権チェック):'.$e->getMessage());
}

// タスクの削除申請があるか
if(!empty($_POST['delete_task']) && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('タスク削除POST確認');
  try {
    $dbh = dbConnect();
    $sql = 'UPDATE task SET delete_flag = 1 WHERE id = :id AND creator = :login_id AND delete_flag = 0';
    $data = array(':id'=>$id, ':login_id'=>$_SESSION['login_id']);
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt) {
      $sql = 'UPDATE join_user SET delete_flag = 1 WHERE task = :id AND delete_flag = 0';
      $data = array(':id'=>$id);
      $stmt2 = queryPost($dbh, $sql, $data);
    }

    if($stmt2) {
      debug('タスク削除完了->index.phpへ遷移');
      $_SESSION['msg'] = 'タスクの削除が完了しました';
      header('Location:index.php');
      exit();
    } else {
      $_SESSION['msg'] = MSG05;
    }
  } catch(Exception $e) {
    error_log('エラー発生(タスク削除):'.$e->getMessage());
  }
}
// 進捗報告の削除申請があるか
if(!empty($_POST['delete_progress']) && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('進捗報告削除POST確認');
  $pmid = (int)filter_input(INPUT_POST, 'pm_id');
  try {
    $dbh = dbConnect();
    $sql = 'UPDATE progress_message SET delete_flag = 1 WHERE id = :pmid AND send_user = :login_id AND task = :id AND delete_flag = 0';
    $data = array(':pmid'=>$pmid, ':login_id'=>$_SESSION['login_id'], ':id'=>$id);
    $stmt1 = queryPost($dbh, $sql, $data);

    if($stmt1) {
      $sql = 'DELETE FROM reaction WHERE message_id = :pmid';
      $data = array(':pmid'=>$pmid);
      $stmt2 = queryPost($dbh, $sql, $data);
    }
    if($stmt2) {
      debug('進捗報告削除完了');
      $_SESSION['msg'] = '進捗報告の削除が完了しました';
      header("Location: ".$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
      exit();
    } else {
      $_SESSION['msg'] = MSG05;
    }

  } catch(Exception $e) {
    error_log('エラー発生(進捗報告削除):'.$e->getMessage());
  }
}

// タスク詳細取得
debug('参加権のあるタスク->タスク詳細取得');
try {
  $dbh = dbConnect();
  $sql = 'SELECT t.title,t.content,c.name,t.creator,t.status,t.limit_date,t.create_date,t.update_date FROM task AS t LEFT JOIN category AS c ON t.category = c.id WHERE t.id = :id AND t.delete_flag = 0 AND c.delete_flag = 0';
  $data = array(':id'=>$id);
  $stmt = queryPost($dbh, $sql, $data);
  $res = $stmt->fetch();
} catch(Exception $e) {
  error_log('エラー発生(タスク詳細取得):'.$e->getMessage());
}

// 参加者取得
debug('参加者取得');
try {
  $dbh = dbConnect();
  $sql = 'SELECT j.user_id AS id,u.name,u.user_id,u.image FROM join_user AS j LEFT JOIN users AS u ON j.user_id = u.id WHERE j.task = :id AND j.delete_flag = 0 AND u.delete_flag = 0';
  $data = array(':id'=>$id);
  $stmt = queryPost($dbh, $sql, $data);
  $join_user = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(参加者取得):'.$e->getMessage());
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

// 進捗報告取得
debug('進捗報告取得');
try {
  $dbh = dbConnect();
  $sql = 'SELECT p.id,p.send_user,p.message,p.good,p.bad,p.create_date,p.update_date,u.name,u.user_id,u.image,u.delete_flag FROM progress_message AS p LEFT JOIN users AS u ON p.send_user = u.id WHERE p.task = :id AND p.delete_flag = 0 ORDER BY p.create_date DESC';
  $data = array(':id'=>$id);
  $stmt = queryPost($dbh, $sql, $data);
  $progresses = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(進捗報告取得):'.$e->getMessage());
}

//リアクションチェック
debug('リアクションチェク');
try {
  $sbh = dbConnect();
  $sql = 'SELECT r.message_id,r.good,r.bad FROM reaction AS r LEFT JOIN progress_message AS p ON r.message_id = p.id WHERE (r.good = :login_id_a OR r.bad = :login_id_b) AND p.task = :id AND p.delete_flag = 0';
  $data = array(':login_id_a'=>$_SESSION['login_id'], ':login_id_b'=>$_SESSION['login_id'], ':id'=>$id);
  $stmt = queryPost($dbh, $sql, $data);
  $reaction = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(リアクションチェック):'.$e->getMessage());
}
?>

<!-- ヘッド -->
<?php
$title = $res['title'].'|';
require_once('parts/head.php');
?>

  <body>
    <!-- ヘッダー -->
    <?php require_once('parts/header.php'); ?>

    <!-- メイン -->
    <main class="taskDetail">
      <div class="wrap">
        <section>
          <!-- タスク編集フォーム -->
          <form action="" method="post" id="js-taskeditor">
            <!-- タスク名 -->
            <div class="taskDetail-col1">
              <h1>
                <span class="x-large js-taskeditor-off"><?=h($res['title'])?></span>
                <input type="text" name="title" value="<?=h($res['title'])?>" class="js-taskeditor-on">
              </h1>
              <?php if((int)$res['creator'] === (int)$_SESSION['login_id']):?>
                <div class="js-taskeditor-on">
                  <div>
                    <label><input type="radio" name="status" value="0" <?php if(empty($res['status'])) echo 'checked';?>> 未完了</label>
                  </div>
                  <div>
                    <label><input type="radio" name="status" value="1" <?php if(!empty($res['status'])) echo 'checked';?>> 完了</label>
                  </div>
                </div>
                <div>
                  <span class="btn js-taskeditor-btn js-taskeditor-off">編集</span>
                  <span class="btn js-delete-task js-taskeditor-off">削除</span>
                  <input type="hidden" name="task_id" value="<?=h($id)?>">
                  <input type="hidden" name="token" value="<?=h(generate_token())?>">
                  <input type="submit" name="taskeditor_done" value="保存" class="btn js-taskeditor-on">
                  <input type="reset" value="キャンセル" class="btn js-taskeditor-on js-taskeditor-cancel">
                </div>
              <?php endif; ?>
            </div>
            <div class="err_msg js-title-msg"></div>

            <div class="taskDetail-col2 small">
              【作成日】<?=h($res['create_date'])?>
              【編集日】<?=h($res['update_date'])?>
            </div>

            <!-- タスク詳細 -->
            <div class="taskDetail-col3">
              <!-- カテゴリ -->
              <div class="task-option">
                カテゴリ<br>
                <span class="middle-large js-taskeditor-off"><?=h($res['name'])?></span>
                <input type="text" name="category" value="<?=h($res['name'])?>" class="js-taskeditor-on">
                <div class="err_msg js-category-msg"></div>
              </div>
              <!-- 参加者 -->
              <div class="task-option">
                参加者<br>
                <select class="js-taskeditor-off">
                  <?php foreach($join_user as $juser): ?>
                    <option>
                      <?php if((int)$res['creator'] === (int)$juser['id']) echo '★' ;?>
                      <?=h($juser['name'])?>(<?=h($juser['user_id'])?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <select name="participant[]" multiple="multiple" class="js-SelectBox js-taskeditor-on">
                  <?php foreach($favorite_users as $fuser): ?>
                    <?php
                    $keyIndex = array_search($fuser['favorite_user'], array_column($join_user, 'id'), true);
                    if($keyIndex !== false){
                      $searchResult = $join_user[$keyIndex];
                    }
                    ?>
                    <option value="<?=h($fuser['favorite_user'])?>" <?php if(isset($searchResult['user_id']) && $keyIndex !== false) echo 'selected';?>>
                      <?=h($fuser['name'])?>(<?=h($fuser['user_id'])?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="err_msg js-participant-msg"></div>
              </div>
              <!-- 完了期限 -->
              <div class="task-option">
                完了期限<br>
                <span class="middle-large js-taskeditor-off"><?=h($res['limit_date'])?></span>
                <input type="date" name="date" value="<?=h($res['limit_date'])?>" class="js-taskeditor-on">
                <div class="err_msg js-date-msg"></div>
              </div>
            </div>

            <!-- タスク内容 -->
            <div class="taskDetail-col4">
              <h2>タスク内容</h2>
              <div class="js-taskeditor-off"><?=$res['content']?></div>
              <div class="none-tinymce"></div>
              <textarea name="content" class="tinymce-task"><?=$res['content']?></textarea>
              <div class="err_msg js-content-msg"></div>
            </div>
          </form> <!-- タスク編集フォーム終 -->

          <!-- 進捗報告 -->
          <div class="taskDetail-col5">
            <h2>進捗報告</h2>
            <div id="js-progress-area">
              <form action="" method="post" id="js-progress-form">
                <div class="none-tinymce"></div>
                <textarea name="progress_content" class="tinymce-progress"></textarea>
                <input type="hidden" name="task" value="<?=h($id)?>">
                <input type="hidden" name="token" value="<?=h(generate_token())?>">
                <input type="submit" name="progress_send" value="送信" class="btn1">
                <input type="reset" value="キャンセル" class="btn1 js-progress-cancel">
              </form>
            </div>
            <div><span class="btn1 js-progress-create">進捗報告をする</span></div>
          </div>

          <!-- 進捗報告一覧 -->
          <?php foreach($progresses as $progress): ?>
            <div class="taskDetail-col6">
              <!-- 報告者アイコン -->
              <div class="taskDetail-image">
                <?php if(empty($progress['delete_flag'])): ?>
                  <img src="<?=h($progress['image'])?>">
                <?php else: ?>
                  <img src="image/anonymous.jpeg">
                <?php endif; ?>
              </div>
              <!-- 報告詳細 -->
              <div class="taskDetail-content">
                <!-- 報告者名 -->
                <div class="both-side">
                  <?php if(empty($progress['delete_flag'])): ?>
                    <?=h($progress['name'])?>(<?=h($progress['user_id'])?>)
                  <?php else: ?>
                    退会済みのユーザー
                  <?php endif; ?>
                </div>
                <!-- 報告内容 -->
                <form action="" method="post" class="js-progresseditor">
                  <div class="both-side">
                    <div class="js-progresseditor-off">
                      <?=$progress['message']?>
                    </div>
                    <div class="both-side">
                      <div class="none-tinymce"></div>
                      <?php if((int)$progress['send_user'] === $_SESSION['login_id']): ?>
                        <textarea name="progress_content_edit" class="tinymce-child"><?=$progress['message']?></textarea>
                      <?php endif; ?>
                    </div>
                    <?php if((int)$progress['send_user'] === $_SESSION['login_id']): ?>
                      <div class="both-side">
                        <span class="btn js-progresseditor-btn js-progresseditor-off">編集</span>
                        <span class="btn js-delete-progress js-progresseditor-off" data-pmid="<?=h($progress['id'])?>">削除</span>
                        <input type="hidden" name="task" value="<?=h($id)?>">
                        <input type="hidden" name="pm_id" value="<?=h($progress['id'])?>">
                        <input type="hidden" name="token" value="<?=h(generate_token())?>">
                        <input type="submit" name="progresseditor_done" value="保存" class="btn js-progresseditor-on">
                        <input type="reset" value="キャンセル" class="btn js-progresseditor-on js-progresseditor-cancel">
                      </div>
                    <?php endif; ?>
                  </div>
                </form>
                <?php
                // リアクションチェック用
                $keyIndex = $searchResult = '';
                $keyIndex = array_search($progress['id'], array_column($reaction, 'message_id'), true);
                if($keyIndex !== false){
                  $searchResult = $reaction[$keyIndex];
                }
                ?>
                <!-- 評価エリア -->
                <div class="both-side">
                  <div class="small" data-pmid="<?=h($progress['id'])?>" data-token="<?=h(generate_token())?>">
                    <span class="js-reaction" data-reaction="good">
                      <i class="far fa-thumbs-up <?php if(isset($searchResult['good']) && $keyIndex !== false) echo h('fas');?>"></i>
                      <span class="js-good-val"><?=h($progress['good'])?></span>
                    </span>
                    <span class="js-reaction" data-reaction="bad">
                      <i class="far fa-thumbs-down <?php if(isset($searchResult['bad']) && $keyIndex !== false) echo h('fas');?>"></i>
                      <span class="js-bad-val"><?=h($progress['bad'])?></span>
                    </span>
                  </div>
                  <div class="small">
                    【作成日】<?=h($progress['create_date'])?>
                    <?php if($progress['create_date'] !== $progress['update_date']) echo'【編集日】'.h($progress['update_date']) ;?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        </section>

      </div>

    </main>
    <!-- モーダルウィンドウ -->
    <div id="js-modal-bg">
      <div id="js-modal-select-area">
        <div>本当に削除しますか？</div>
        <div>
          <form action="" method="post">
            <input type="hidden" name="token" value="<?=h(generate_token())?>">
            <input type="submit" name="delete_task" value="はい" class="btn2">
            <span class="btn2 js-delete-cancel">いいえ</span>
          </form>
        </div>
      </div>
    </div>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
