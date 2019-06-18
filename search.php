<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('search.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->index.phpへ遷移');
  header('Location:index.php');
  exit();
}

// フォロー検索
if($_SERVER['REQUEST_METHOD'] === 'POST' && valid_token(filter_input(INPUT_POST, 'token'))) {
  debug('フォロー検索POST確認');
  if(!empty($_POST['search_follow'])) {
    // バリデーションチェック
    letterMinMax('keyword_follow', 1, 50);
    $keyword = validInput('keyword_follow');

    if(empty($err_msg)) {
      debug('バリデーションチェック通過');
      try {
        $dbh = dbConnect();
        $sql = 'SELECT id,name,user_id,mood,image FROM users WHERE (name LIKE :keyword1 OR user_id LIKE :keyword2) AND delete_flag = 0 AND publish = 0 AND NOT id = :id';
        $data = array(':keyword1'=>'%'.$keyword.'%', ':keyword2'=>'%'.$keyword.'%', ':id'=>$_SESSION['login_id']);
        $stmt = queryPost($dbh, $sql, $data);
        $resFollow = $stmt->fetchAll();
      } catch(Exception $e) {
        error_log('エラー発生(フォロー検索):'.$e->getMessage());
        $_SESSION['msg'] = MSG05;
      }
    }
  }
}

// タスク検索
if($_SERVER['REQUEST_METHOD'] === 'GET' && valid_token(filter_input(INPUT_GET, 'token'))) {
  debug('タスク検索GET確認');
  if(!empty($_GET['search_task'])) {
    debug(print_r($_GET,true));
    // バリデーションチェック
    $keyword = (string)filter_input(INPUT_GET, 'keyword_task');
    letterMinMaxB($keyword, 0, 50, 'keyword_task');
    $search = (string)filter_input(INPUT_GET, 'search_item');
    $date = (int)filter_input(INPUT_GET, 'date');
    $status = (int)filter_input(INPUT_GET, 'status');

    if(empty($err_msg)) {
      debug('バリデーションチェック通過');
      try {
        $dbh = dbConnect();

        // 検索タイプ
        if($search === 'keyword') {
          debug('キーワード検索');
          $sql = 'SELECT t.id,t.title,t.status,t.limit_date FROM join_user AS j LEFT JOIN task AS t ON j.task = t.id WHERE j.user_id = :login_id AND j.delete_flag = 0 AND t.title LIKE :keyword AND t.delete_flag = 0';
        } elseif($search === 'category') {
          debug('カテゴリ検索');
          $sql = 'SELECT t.id,t.title,t.status,t.limit_date FROM join_user AS j LEFT JOIN task AS t ON j.task = t.id LEFT JOIN category AS c ON t.category = c.id WHERE j.user_id = :login_id AND j.delete_flag = 0 AND c.name LIKE :keyword AND c.delete_flag = 0 AND t.delete_flag = 0';
        }

        // タスク状況
        if($status === 1) {
          debug('未完了オプション');
          $sql .= ' AND t.status = 0';
        } elseif($status === 2) {
          debug('完了オプション');
          $sql .= ' AND t.status = 1';
        }

        // 期限の並び順
        if($date === 1) {
          debug('降順オプション');
          $sql .= ' ORDER BY t.limit_date DESC';
        } elseif($date === 2) {
          debug('昇順オプション');
          $sql .= ' ORDER BY t.limit_date ASC';
        }

        $data = array(':login_id'=>$_SESSION['login_id'], ':keyword'=>'%'.$keyword.'%');
        $stmt = queryPost($dbh, $sql, $data);
        $resTask = $stmt->fetchAll();

      } catch(Exception $e) {
        error_log('エラー発生(キーワード検索):'.$e->getMessage());
      }
    }
  }

}



?>
<!-- ヘッド -->
<?php
$title = '検索|';
require('parts/head.php');
?>

<body>
  <!-- ヘッダー -->
  <?php require('parts/header.php'); ?>

  <!-- メイン -->
  <main class="search">
    <div class="wrap2">
      <!-- 検索フォーム -->
      <section class="search-form">
          <input type="radio" name="tab_btn" id="tab1" <?php if(empty($_REQUEST) || !empty($_GET['search_task'])) echo 'checked';?>>
          <input type="radio" name="tab_btn" id="tab2" <?php if(!empty($_POST['search_follow'])) echo 'checked';?>>
          <div class="search-tab">
            <div>
              <label class="tab1_label" for="tab1">タスク検索</label>
            </div>
            <div>
              <label class="tab2_label" for="tab2">フォロー検索</label>
            </div>
          </div>
          <div class="panel_area">
            <!-- タスク検索 -->
            <div class="search-box tab_panel" id="panel1">
              <form action="" method="get">
                <!-- タスク検索_１行目 -->
                <div class="search-col1">
                  <!-- キーワード入力欄 -->
                  <div>
                    <?php if(empty($_GET) || $_GET['search_item'] === 'keyword'): ?>
                      <input type="text" name="keyword_task" placeholder="キーワード" value="<?=returnFormValueG('keyword_task')?>">
                    <?php elseif($_GET['search_item'] === 'category'): ?>
                      <input type="text" name="keyword_task" placeholder="カテゴリ" value="<?=returnFormValueG('keyword_task')?>">
                    <?php endif; ?>
                  </div>
                  <!-- カテゴリorキーワード -->
                  <?php if(empty($_GET) || $_GET['search_item'] === 'keyword'): ?>
                    <div class="category-btn js-change-search-item js-category">カテゴリ</div>
                  <?php elseif($_GET['search_item'] === 'category'): ?>
                    <div class="category-btn js-change-search-item">キーワード</div>
                  <?php endif; ?>
                  <input type="radio" name="search_item" value="keyword" <?php if(empty($_GET) || $_GET['search_item'] === 'keyword') echo 'checked';?>>
                  <input type="radio" name="search_item" value="category" <?php if(!empty($_GET) && $_GET['search_item'] === 'category') echo 'checked';?>>
                </div>
                <div class="err_msg"><?=getErrMsg('keyword_task')?></div>

                <!-- タスク検索_２行目 -->
                <div class="search-col2">
                  <!-- 検索オプション -->
                  <div>
                    <div class="search-date">
                      <select name="date">
                        <option value="none">期限(昇順＆降順)</option>
                        <option value="1" <?php if(!empty($_GET) && (int)$_GET['date'] === 1) echo 'selected';?>>降順</option>
                        <option value="2" <?php if(!empty($_GET) && (int)$_GET['date'] === 2) echo 'selected';?>>昇順</option>
                      </select>
                    </div>
                    <div class="search-status">
                      <select name="status">
                        <option value="none">タスク状況</option>
                        <option value="1" <?php if(!empty($_GET) && (int)$_GET['status'] === 1) echo 'selected';?>>未完了</option>
                        <option value="2" <?php if(!empty($_GET) && (int)$_GET['status'] === 2) echo 'selected';?>>完了</option>
                      </select>
                    </div>
                  </div>
                  <!-- 検索ボタン -->
                  <div>
                    <input type="hidden" name="token" value="<?=h(generate_token())?>">
                    <input type="submit" name="search_task" value="検索" class="search-btn">
                  </div>
                </div>
              </form>
            </div>

            <!-- フォロー検索 -->
            <div class="search-box tab_panel" id="panel2">
              <form action="" method="post">
                <div class="search-col1">
                  <div><input type="text" name="keyword_follow" placeholder="名前もしくはユーザーIDを入力" value="<?=returnFormValue('keyword_follow')?>"></div>
                  <div class="category-btn disabled">カテゴリ</div>
                </div>
                <div class="err_msg"><?=getErrMsg('keyword_follow')?></div>
                <div class="search-col2">
                  <div>
                    <div class="search-date">
                      <select name="date" disabled>
                        <option value="0">期限(昇順＆降順)</option>
                        <option value="1">降順</option>
                        <option value="2">昇順</option>
                      </select>
                    </div>
                    <div class="search-status">
                      <select name="status" disabled>
                        <option value="0">タスク状況</option>
                        <option value="1">未完了</option>
                        <option value="2">完了</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <input type="hidden" name="token" value="<?=h(generate_token())?>">
                    <input type="submit" name="search_follow" value="検索" class="search-btn">
                  </div>
                </div>
              </form>
            </div>
          </div>
      </section>

      <!-- 検索結果 -->
      <section class="search-result">
        <?php if(!empty($resTask)): ?>
          <h1>検索結果一覧</h1>
          <?php foreach($resTask as $res): ?>
            <?php if(empty($res['status'])): ?>
              <?php $date1 = new DateTime($res['limit_date']); //期限
                    $date2 = new DateTime(date('Y-m-d')); //現在  ?>
              <?php if($date1 >= $date2): ?>
                <div class="task-list">
                  <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                    <span class="datelimit">期限：<?=h($res['limit_date'])?></span>
                  </a>
                </div>
              <?php else: ?>
                <div class="task-list">
                  <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                    <span class="datelimit"><span class="red">【遅延】</span>期限：<span class="red"><?=h($res['limit_date'])?></span></span>
                  </a>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="task-list">
                <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                  <span class="datelimit"><span class="blue">【完了】</span>期限：<?=h($res['limit_date'])?></span>
                </a>
              </div>
            <?php endif;?>
          <?php endforeach; ?>
        <?php elseif(!empty($resFollow)): ?>
          <h1>検索結果一覧</h1>
          <?php foreach($resFollow as $res): ?>
            <div class="follow-list">
              <div class="follow-image">
                <img src="<?=h($res['image'])?>">
              </div>
              <div class="follow-detail">
                <div><?=h($res['name'])?>（<?=h($res['user_id'])?>）</div>
                <div><?=h($res['mood'])?></div>
                <div data-pairid="<?=h($res['id'])?>" data-token="<?=h(generate_token())?>"><?=(isFollow($res['id'])) ? '<span class="js-follow follow-active"><i class="fas fa-user-minus"></i><span>フォロー解除</span></span>' : '<span class="js-follow"><i class="fas fa-user-plus"></i><span>フォローする</span></span>';?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php elseif(!empty($_REQUEST)): ?>
          <h1>検索結果一覧</h1>
          <p>検索結果に一致するものはありませんでした。</p>
        <?php else: ?>
        <?php endif; ?>
      </section>
    </div>
  </main>
  <?php debug('================画面処理終了================'); ?>

<!-- フッター -->
<?php require('parts/footer.php'); ?>
