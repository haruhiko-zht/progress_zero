  <!-- jQueryCDN -->
  <script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
  <!-- 自前スクリプト -->
  <script src="js/main.js"></script>
  <!-- autosize -->
  <script src="js/jquery.autosize.min.js"></script>
  <!-- SumoSelect -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.0.2/jquery.sumoselect.min.js"></script>
  <!-- tinymceスクリプト -->
  <script src="js/tinymce/tinymce.min.js"></script>
  <script>
    tinymce.init({
      selector: '.tinymce',
      language: 'ja',
      plugins  : 'autoresize jbimages link autolink preview',
      toolbar  : 'bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link jbimages | preview',
      menubar  : false,
      relative_urls : false,
      element_format : 'html',
    });
    tinymce.init({
      selector: '.tinymce-task',
      language: 'ja',
      plugins  : 'autoresize jbimages link autolink preview',
      toolbar  : 'bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link jbimages | preview',
      menubar  : false,
      relative_urls : false,
      element_format : 'html',
    });
    tinymce.init({
      selector: '.tinymce-progress',
      language: 'ja',
      plugins  : 'autoresize jbimages link autolink preview',
      toolbar  : 'bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link jbimages | preview',
      menubar  : false,
      relative_urls : false,
      element_format : 'html',
    });
    tinymce.init({
      selector: '.tinymce-child',
      language: 'ja',
      plugins  : 'autoresize jbimages link autolink preview',
      toolbar  : 'bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link jbimages | preview',
      menubar  : false,
      relative_urls : false,
      element_format : 'html',
    });
  </script>
  </body>
</html>
