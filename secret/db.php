<?php
// DB接続用関数
function dbConnect(){
  debug('DB接続開始');
  $dsn = 'mysql:dbname=progress_zero;host=localhost;charset=utf8mb4';
  $username = 'root';
  $password = 'root';
  $driver_options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
  );
  $dbh = new PDO($dsn, $username, $password, $driver_options);
  return $dbh;
}

// プリペアードステートメント
function queryPost($dbh, $sql, $data){
  debug('クエリ実行');
  $stmt = $dbh->prepare($sql);

  if($stmt->execute($data)){
    debug('クエリ成功');
    return $stmt;
  } else {
    debug('クエリ失敗');
  }

}
