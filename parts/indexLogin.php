<?php
// タスク取得
debug('タスク取得');
try {
  $dbh = dbConnect();
  $sql = 'SELECT t.id,t.title,t.status,t.limit_date FROM join_user AS j LEFT JOIN task AS t ON j.task = t.id LEFT JOIN users AS u ON t.creator = u.id LEFT JOIN category AS c ON t.category = c.id WHERE j.user_id = :login_id AND t.delete_flag = 0 AND j.delete_flag = 0 AND c.delete_flag = 0 ORDER BY t.limit_date ASC';
  $data = array(':login_id'=>$_SESSION['login_id']);

  $stmt = queryPost($dbh, $sql, $data);
  $results = $stmt->fetchAll();
} catch(Exception $e) {
  error_log('エラー発生(タスク取得):'.$e->getMessage());
  $_SESSION['msg'] = MSG05;
}
?>
<main class="task-status-list">
  <div class="wrap2">
    <section>
      <h1>遅延中</h1>
      <?php foreach($results as $res): ?>
        <?php if(empty($res['status'])): ?>
          <?php $date1 = new DateTime($res['limit_date']); //期限
                $date2 = new DateTime(date('Y-m-d')); //現在  ?>
          <?php if($date1 < $date2): ?>
              <div class="task-list">
                <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                  <span class="datelimit">期限：<span class="red"><?=h($res['limit_date'])?></span></span>
                </a>
              </div>
          <?php endif; ?>
        <?php endif;?>
      <?php endforeach; ?>
    </section>
    <section>
      <h1>進行中</h1>
      <?php foreach($results as $res): ?>
        <?php if(empty($res['status'])): ?>
          <?php $date1 = new DateTime($res['limit_date']); //期限
                $date2 = new DateTime(date('Y-m-d')); //現在  ?>
          <?php if($date1 >= $date2): ?>
              <div class="task-list">
                <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                  <span class="datelimit">期限：<?=h($res['limit_date'])?></span>
                </a>
              </div>
          <?php endif; ?>
        <?php endif;?>
      <?php endforeach; ?>
    </section>
    <section>
      <h1>完了</h1>
      <?php foreach($results as $res): ?>
        <?php if(!empty($res['status'])): ?>
            <div class="task-list">
              <a href="taskDetail.php?id=<?=h($res['id'])?>"><?=h($res['title'])?>
                <span class="datelimit">期限：<?=h($res['limit_date'])?></span>
              </a>
            </div>
        <?php endif;?>
      <?php endforeach; ?>
    </section>
  </div>
</main>
