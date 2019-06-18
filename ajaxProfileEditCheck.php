<?php
// ファイル呼び出し
require_once('secret/func.php');
require_once('secret/db.php');
require_once('secret/auth.php');

// デバッグ
debug('//////////////////////////////////////////');
debug('ajaxProfileEditCheck.php');
debug('//////////////////////////////////////////');
debugLogStart();

// ログイン確認
if(!isLogin()){
  debug('未ログイン->javascriptでindex.phpへ遷移');
  $error = 'NoLoginSession';
  http_response_code(400);
  echo json_encode(compact('error'));
  exit();
}

// POST送信＆ログインチェック
debug('プロフィール変更保存POST送信チェック');
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['login']) && valid_token(filter_input(INPUT_POST, 'token'))){
  debug('送信内容:'.print_r($_POST, true));

  // バリデーションチェック
  debug('バリデーションチェック開始');
  $name = checkAllName('name');
  $mood = (string)filter_input(INPUT_POST, 'mood');
  letterMinMaxB($mood, 0, 140, 'mood');
  $publish = (int)validInput('publish');
  // 画像チェック
  if($_FILES['image']['size'] > 0  && is_int($_FILES['image']['error'])) {
    debug('画像のアップロード確認');
    $image = $_FILES['image'];

    try {
      switch($image['error']){
        case UPLOAD_ERR_OK:
          break;
        case UPLOAD_ERR_NO_FILE:
          throw new RuntimeException('ファイルが選択されていません');
          break;
        case UPLOAD_ERR_INI_SIZE:
          throw new RuntimeException('ファイルサイズが大きすぎます');
          break;
        default:
          throw new RuntimeException('予期せぬエラー、再度お試し下さい');
          break;
      }

      if($image['size'] > 2000000) {
        throw new RuntimeException('ファイルサイズが大きすぎます');
      }

      if(!$ext = array_search(
        @exif_imagetype($image['tmp_name']),
        array(
          IMAGETYPE_GIF,
          IMAGETYPE_JPEG,
          IMAGETYPE_PNG
        ),true
      )) {
        throw new RuntimeException('ファイル形式が正しくありません');
      }

    } catch(RuntimeException $e) {
      error_log('エラー発生(画像バリデーション):'.$e->getMessage());
      $err_msg['image'] = $e->getMessage();
    }
  }

  if(empty($err_msg)){
    debug('バリデーションチェック通過');
    try {
      $dbh = dbConnect();
      $sql = 'UPDATE users SET name = :name, mood = :mood, publish = :publish WHERE id = :id AND delete_flag = 0';
      $data = array(':name'=>$name, ':mood'=>$mood, ':publish'=>$publish, ':id'=>$_SESSION['login_id']);

      // 画像が変更される場合
      if(!empty($image) || !empty($_POST['delete-image'])){
        debug('画像のパス取得＆画像アップロード＆元画像削除');
        $sql = 'SELECT image FROM users WHERE id = :id AND delete_flag = 0';
        $data = array(':id'=>$_SESSION['login_id']);
        $stmt = queryPost($dbh, $sql, $data);
        $res = $stmt->fetch()['image'];
        if($res !== 'image/anonymous.jpeg') {
          debug('元画像削除(パス):'.print_r($res, true));
          unlink($res);
        }

        // 画像初期化の場合
        if(!empty($_POST['delete-image'])) {
          $sql = 'UPDATE users SET name = :name, mood = :mood, publish = :publish, image = :image WHERE id = :id AND delete_flag = 0';
          $data = array(':name'=>$name, ':mood'=>$mood, ':publish'=>$publish, ':image'=>'image/anonymous.jpeg', ':id'=>$_SESSION['login_id']);
        }

        // 画像アップロードの場合
        if(!empty($image)) {
          if(!move_uploaded_file(
            $image['tmp_name'],
            $path = sprintf('image/%s.%s',
            sha1_file($image['tmp_name']),
            $ext
            )
          )) {
            throw new RuntimeException('ファイル保存中にエラーが発生しました。');
          }
          chmod($path, 0644);
          debug('画像アップロード完了');
          $sql = 'UPDATE users SET name = :name, mood = :mood, publish = :publish, image = :image WHERE id = :id AND delete_flag = 0';
          $data = array(':name'=>$name, ':mood'=>$mood, ':publish'=>$publish, ':image'=>$path, ':id'=>$_SESSION['login_id']);
        }

      }

      $stmt = queryPost($dbh, $sql, $data);

      if($stmt){
        debug('変更完了、javascriptでprofile.phpへ遷移');
        $_SESSION['msg'] = 'プロフィールを更新しました';
      }
    } catch(Exception $e) {
      error_log('エラー発生(プロフィール変更保存):'.$e->getMessage());
      $error = 'UnknownError';
    } catch(RuntimeException $e) {
      error_log('エラー発生(プロフィール変更保存):'.$e->getMessage());
      $error[] = array('classname'=>'image', 'message'=>$e->getMessage());
    }

  } else {
    debug('バリデーションエラー');
    debug('エラー箇所:'.print_r($err_msg,true));
    foreach ($err_msg as $key => $val) {
      $error[] = array('classname'=>$key, 'message'=>$val);
    }
  }

} else {
  debug('チェック項目に何らかの不備');
  $error = ('BadAccess');
}

if(!isset($error)) {
  $data = '変更保存完了';
  echo json_encode(compact('data'));
} else {
  http_response_code(400);
  echo json_encode(compact('error'));
}


debug('================画面処理終了================');
