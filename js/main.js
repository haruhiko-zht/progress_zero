$(function(){
  // ========================
  // 関数
  // ========================
  // ajax通信のエラー時処理
  function ajaxErrCmd(error) {
    if(typeof(error) === 'object'){
      for(let i = 0; i < Object.keys(error).length; i++) {
        switch(error[i]['classname']) {
          // プロフィール編集
          case 'name':
            $('.js-name-msg').text(error[i]['message']);
            break;
          case 'mood':
            $('.js-mood-msg').text(error[i]['message']);
            break;
          case 'image':
            $('.js-img-msg').text(error[i]['message']);
            break;
          // タスク登録編集
          case 'title':
            $('.js-title-msg').text(error[i]['message']);
            break;
          case 'category':
            $('.js-category-msg').text(error[i]['message']);
            break;
          case 'participant':
            $('.js-participant-msg').text(error[i]['message']);
            break;
          case 'date':
            $('.js-date-msg').text(error[i]['message']);
            break;
          case 'content':
            $('.js-content-msg').text(error[i]['message']);
            break;
          // 進捗報告登録編集
          case 'progress_content':
            pushMessage(error[i]['message']);
            break;
        }
      }
    } else {
      switch(error) {
        case 'NoLoginSession':
          window.location.href = 'index.php';
          break;
        case 'UnknownError':
          pushMessage('予期せぬエラー、時間を開けて再度お試し下さい。');
          break;
        case 'BadAccess':
          pushMessage('不正なアクセスをしている可能性があります、3秒後にメインページへ移動します。');
          setTimeout(function(){
            window.location.href = 'index.php';
          },3000);
          break;
      }
    }
  }

  // ========================
  // イベント
  // ========================
  // スライドメッセージ
  let $jsMsgArea = $('#js-msg-area');
  let sesMsg = $jsMsgArea.text();
  if(sesMsg.replace(/^[\s　]+|[\s　]+$/g,'').length) {
    $jsMsgArea.slideToggle('slow');
    setTimeout(function(){
      $jsMsgArea.slideToggle('slow')
    },3000);
  }
  // スライドメッセージ２
  function pushMessage(str){
    $('.js-notification').text(str).slideToggle();
    setTimeout(function(){
      $('.js-notification').slideToggle().text('');
    },3000);
  }

  // 検索フォーム
  let $jsChangeSearchItem = $('.js-change-search-item');
  $jsChangeSearchItem.on('click', function(){
    if($(this).hasClass('js-category')) {
      $(this).text('キーワード').removeClass('js-category').prev().children('input').val('').attr('placeholder','カテゴリ');
      $(this).siblings('input[value="category"]').prop('checked', true);
    } else {
      $(this).text('カテゴリ').addClass('js-category').prev().children('input').val('').attr('placeholder','キーワード');
      $(this).siblings('input[value="keyword"]').prop('checked', true);
    }
  });

  // フォロー
  let $jsFollow = $('.js-follow') || null;
  let followId,
      token;
  if($jsFollow !== undefined && $jsFollow !== null) {
    $jsFollow.on('click', function(e){
      e.preventDefault();
      let $this = $(this);
      followId = $this.parent().data('pairid') || null;
      token = $this.parent().data('token') || null;
      if(followId !== undefined && followId !== null && token !== undefined && token !== null) {
        $.ajax({
          type:"POST",
          url:"ajaxFollow.php",
          dataType: 'json',
          data: {follow_id : followId, token : token}
        }).done(function(data){
          if($this.hasClass('follow-active')) {
            $this.removeClass('follow-active').children('span').text('フォローする');
            $this.children('i').removeClass('fa-user-minus').addClass('fa-user-plus');
          } else {
            $this.addClass('follow-active').children('span').text('フォロー解除');
            $this.children('i').removeClass('fa-user-plus').addClass('fa-user-minus');
          }
        }).fail(function(msg){
          // console.log('ajax_error');
          // console.log(msg);
          let error = msg.responseJSON.error;
          ajaxErrCmd(error);
        });
      }
    });
  }

  // タスク編集
  let resetSelectBox = $('.js-SelectBox').html();
  let $jsTaskeditorBtn = $('.js-taskeditor-btn');
  let $jsTaskeditorOff = $('.js-taskeditor-off');
  let $jsTaskeditorOn = $('.js-taskeditor-on');
  $jsTaskeditorBtn.on('click', function(){
    $jsTaskeditorOff.hide();
    $jsTaskeditorOn.css('display','inline-block');
    $('.taskDetail .taskDetail-col4 textarea').siblings('div[id*="mceu"]').css('display','block');
    $('.js-SelectBox').SumoSelect({
      placeholder: '参加者を追加する',
      search: true
    });
    $('.js-SelectBox').parent('.SumoSelect').show();
  });
  $('.js-taskeditor-cancel').on('click', function(){
    $jsTaskeditorOff.show();
    $jsTaskeditorOn.hide();
    $('.taskDetail .taskDetail-col4 textarea').siblings('div[id*="mceu"]').css('display','none');
    $('.js-SelectBox').parent().before('<select name="participant[]" multiple="multiple" class="js-SelectBox js-taskeditor-on"></select>');
    $('.js-SelectBox').parent('.SumoSelect').remove();
    $('.js-SelectBox').html(resetSelectBox);
    $('.err_msg').text('');
  });
  $('#js-taskeditor').on('submit', function(e){
    e.preventDefault();
    $('.err_msg').text('');
    let formData = $(this).serialize();
    // let formData = new FormData($(this).get()[0]);
    $.ajax($(this).attr('action'),{
      type: 'POST',
      url: 'ajaxEditTask.php',
      // processData: false,
      // contentType: false,
      dataType: 'json',
      data: formData,
    }).done(function(response){
      window.location.href = 'taskDetail.php' + location.search;
    }).fail(function(msg){
      let error = msg.responseJSON.error;
      // console.log(msg);
      ajaxErrCmd(error);
    });
    return false;
  });

  // 進捗報告エリア
  let $jsProgressCreate = $('.js-progress-create');
  let $jsProgressArea = $('#js-progress-area');
  let $jsprogressCancel = $('.js-progress-cancel');
  $jsProgressCreate.on('click', function(){
    $('.taskDetail .taskDetail-col5 textarea').siblings('div[id*="mceu"]').css('display','block');
    $jsProgressArea.css('display','block');
    $(this).hide();
  });
  $jsprogressCancel.on('click', function(){
    $jsProgressArea.css('display','none');
    $('.taskDetail .taskDetail-col5 textarea').siblings('div[id*="mceu"]').css('display','none');
    $jsProgressCreate.show();
  });

  // 進捗報告送信
  let $jsProgressFrom = $('#js-progress-form');
  $jsProgressFrom.on('submit', function(e){
    e.preventDefault();
    let formData = new FormData($(this).get()[0]);
    $.ajax($(this).attr('action'),{
      type: 'POST',
      url: 'ajaxValidProgress.php',
      processData: false,
      contentType: false,
      dataType: 'json',
      data: formData
    }).done(function(response){
      window.location.href = 'taskDetail.php' + location.search;
    }).fail(function(msg){
      let error = msg.responseJSON.error;
      ajaxErrCmd(error);
    })
  });

  // 進捗報告編集
  let $jsProgresseditorBtn = $('.js-progresseditor-btn');
  let $jsProgresseditorCancel = $('.js-progresseditor-cancel');
  $jsProgresseditorBtn.on('click', function(){
    let $this = $(this);
    let $parent = $this.closest('.js-progresseditor');
    $this.parent().siblings('.both-side').children('div[id*="mceu"]').css('display','block');
    $parent.find('.js-progresseditor-off').hide();
    $parent.find('.js-progresseditor-on').show();
    $this.parent().css('display','block').parent('.both-side').css('display','block');
  });
  $jsProgresseditorCancel.on('click', function(){
    $(this).parent().siblings('.js-progresseditor-off').show();
    $(this).siblings('.js-progresseditor-off').show().parent().siblings('.js-progresseditor-on').hide();
    $(this).hide().siblings('.js-progresseditor-on').hide();
    $(this).parent().siblings('div').children('div[id*="mceu"]').css('display','none');
    $(this).parent().css('display','flex').parent('.both-side').css('display','flex');
    // $('.taskDetail .taskDetail-col6 textarea').siblings('div[id*="mceu"]').css('display','none');
  });
  let $jsProgressEditor = $('.js-progresseditor');
  $jsProgressEditor.on('submit', function(e){
    e.preventDefault();
    let formData = new FormData($(this).get()[0]);
    $.ajax($(this).attr('action'),{
      type: 'POST',
      url: 'ajaxEditProgress.php',
      processData: false,
      contentType: false,
      dataType: 'json',
      data: formData
    }).done(function(response){
      window.location.href = 'taskDetail.php' + location.search;
    }).fail(function(msg){
      let error = msg.responseJSON.error;
      ajaxErrCmd(error);
    })
  });

  // 進捗報告リアクション
  let $jsGoodVal = $('.js-good-val');
  let $jsBadVal = $('.js-bad-val');
  let $jsReaction = $('.js-reaction');
  $jsReaction.on('click', function(){
    let pmid = $(this).parent().data('pmid') || null;
    let token = $(this).parent().data('token') || null;
    let reaction = $(this).data('reaction') || null;
    let $this = $(this);
    if(pmid !== undefined && pmid !== null && reaction !== undefined && reaction !== null && token !== undefined && token !== null) {
      $.ajax({
        type: 'POST',
        url: 'ajaxReaction.php',
        dataType: 'json',
        data: {pm_id:pmid, reaction:reaction, token:token}
      }).done(function(data){
        let response = data;
        let good = response.data[0]['message'];
        let bad = response.data[1]['message'];
        pmid = response.data[2]['message'];
        if($this.children('i').hasClass('fas')){
          $('div[data-pmid='+pmid+']').find('i').removeClass('fas');
        } else {
          $('div[data-pmid='+pmid+']').find('i').removeClass('fas');
          $this.children('i').addClass('fas');
        }
        $jsReaction.parent('div[data-pmid='+pmid+']').find('.js-good-val').text(good);
        $jsReaction.parent('div[data-pmid='+pmid+']').find('.js-bad-val').text(bad);
      }).fail(function(msg){
      });
    }
  });

  // タスク削除
  let $jsDeleteTask = $('.js-delete-task');
  let $jsDeleteCancel = $('.js-delete-cancel');
  let $jsModalSelectArea = $('#js-modal-select-area');
  let $jsModalBg = $('#js-modal-bg');
  $jsDeleteTask.on('click', function(e){
    e.preventDefault();
    $jsModalBg.css('display','flex');
  });
  $jsModalSelectArea.on('click', function(e){
    e.stopPropagation();
  });
  $jsDeleteCancel.on('click', function(e){
    e.preventDefault();
    $jsModalBg.css('display','none');
  });
  $jsModalBg.on('click', function(e){
    e.preventDefault();
    $jsModalBg.css('display','none');

  })

  // 進捗報告削除
  let $jsDeleteProgress = $('.js-delete-progress');
  $jsDeleteProgress.on('click', function(){
    $jsModalBg.css('display','flex');
    $jsModalSelectArea.find('input[type="submit"]').attr('name','delete_progress');
    let pmid = $(this).data('pmid');
    $jsModalSelectArea.find('form').prepend('<input type="hidden" name="pm_id" value="'+pmid+'">');
  });

  // sumoselect
  $('.SelectBox').SumoSelect({
    placeholder: '参加者を追加する',
    search: true
  });
  // タスク追加用のajaxバリデーションチェック
  $('#js-new-task').on('submit', function(e){
    e.preventDefault();
    $('.err_msg').text('');
    let formData = $(this).serialize();
    // let formData = new FormData($(this).get()[0]);
    $.ajax($(this).attr('action'),{
      type: 'POST',
      url: 'ajaxValidNewTask.php',
      // processData: false,
      // contentType: false,
      dataType: 'json',
      data: formData,
    }).done(function(response){
      window.location.href = 'index.php';
    }).fail(function(msg){
      let error = msg.responseJSON.error;
      // console.log(msg);
      ajaxErrCmd(error);
    });
    return false;
  });


  // ========================
  // プロフィール関係
  // ========================
  // プロフィール編集ボタン
  $('.js-prof-edit-btn').on('click',function(e){
    e.preventDefault();
    $('.err_msg').text('');
    $('.js-prof-edit-on').show();
    $('.js-prof-edit-off').hide();
    $('textarea').autosize().trigger('autosize.resize');
  });

  // プロフィール編集キャンセルボタン
  $('.js-prof-edit-cancel').on('click',function(){
    $('.js-prof-edit-on').hide();
    $('.js-prof-edit-off').show();
    $('input[type="file"]').remove();
    $('.js-new-image').remove();
    $('.js-del-prof-img input[type="checkbox"]').prop('checked',false);
    $('.js-drop-area').append('<input type="file" name="image" class="patl">');
    $('.js-drop-area').prepend('<img src="image/anonymous.jpeg" class="js-new-image patl">');
  });

  // プロフィール変更保存
  $('#js-prof-edit-form').on('submit', function(e){
    e.preventDefault();
    $('.err_msg').text('');
    let formData = new FormData($(this).get()[0]);
    $.ajax($(this).attr('action'),{
      type: 'POST',
      url: 'ajaxProfileEditCheck.php',
      processData: false,
      contentType: false,
      dataType: 'json',
      data: formData
    }).done(function(response){
      window.location.href = 'profile.php';
    }).fail(function(msg){
      let error = msg.responseJSON.error;
      ajaxErrCmd(error);
    });
    return false;
  });

  // プロフィール画像ライブプレビュー
  $('.js-drop-area').on('dragover',function(){
    $('.js-drag-effect').css('border','4px dashed #f5f5f5');
  });
  $('.js-drop-area').on('dragleave',function(){
    $('.js-drag-effect').css('border','none');
  });
  $('.js-drop-area').on('drop change', 'input[type="file"]', function(e){
    $('.js-drag-effect').css('border','none');
    $('.js-img-msg').text('');
    $('.js-del-prof-img input[type="checkbox"]').prop('checked',false);
    // オブジェクト取得
    var file = e.target.files[0];
    var reader = new FileReader();
    // 画像でない場合は終了
    if((file.type).indexOf("image") < 0){
      $('.js-img-msg').text('画像ファイルを指定して下さい');
      return;
    }
    reader.onload = (function(file){
      return function(e){
        $('.js-new-image').attr('src', e.target.result).attr('title', file.name).show();
      };
    })(file);
    reader.readAsDataURL(file);
  });

  // プロフィール画像削除チェック
  $('.js-del-prof-img').on('click', function(){
    $('.js-new-image').attr('src', 'image/anonymous.jpeg').show();
    $('file[type="file"]').replaceWith($('file[type="file"]').clone(true));
  });

});
