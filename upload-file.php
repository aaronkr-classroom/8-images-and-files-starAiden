<?php 
$message = '';
// $_SERVER['REQUEST_METHOD']와 'POST'에 따옴표를 추가하여 문법 오류를 해결했습니다.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if ($_FILES['image']['error'] === 0) {
    // 파일을 이동시킬 대상 경로 설정 (uploads 폴더)
    $destination = 'uploads/' . $_FILES['image']['name'];
    // 임시 저장소에서 실제 폴더로 파일 이동
    move_uploaded_file($_FILES['image']['tmp_name'], $destination);

    $message = '<b>File:</b> ' . $_FILES['image']['name'] . '<br>';
    $message .= '<b>Size:</b> ' . $_FILES['image']['size'] . ' bytes';
    
  } else {
    // else 문법과 메시지 할당 방식을 수정했습니다.
    $message = "file could not be upload";
  }
}
?>
<?php include 'includes/header.php' ?>

<?= $message ?>
<form method="POST" action="upload-file.php" enctype="multipart/form-data">
  <label for="image"><b>Upload file:</b></label>
  <input type="file" name="image" accept="image/*" id="image"><br>
  <input type="submit" value="Upload">
</form>

<?php include 'includes/footer.php' ?>