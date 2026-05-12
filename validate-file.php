<?php
// @TODO
$moved         = false;                                    // 이동 성공 여부 초기화
$message       = '';                                       // 메시지 초기화
$error         = '';                                       // 에러 메시지 초기화
$upload_path   = 'uploads/';                               // 업로드 폴더 경로
$max_size      = 5242880;                                  // 최대 파일 크기 (5MB)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif']; // 허용된 MIME 타입
$allowed_exts  = ['jpg', 'jpeg', 'png', 'gif'];            // 허용된 확장자

// 파일명을 중복되지 않게 생성하는 함수
function create_filename($filename, $upload_path) {
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    // 알파벳과 숫자 이외의 문자를 제거하도록 정규식을 완성했습니다.
    $basename = preg_replace('/[^A-z0-9]/', '', $basename);
    $filename = $basename . '.' . $extension;
    $i = 0; //카운터
    while (file_exists($upload_path . $filename)) {
        $filename = $basename . $i . '.' . $extension;
        $i++;
    }
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {                    // If form submitted
    $error = ($_FILES['image']['error'] === 1) ? 'too big ' : '';  // Check size error

    if ($_FILES['image']['error'] == 0) {                          // If no upload errors
        $error  .= ($_FILES['image']['size'] <= $max_size) ? '' : 'too big '; // Check size
        // Check the media type is in the $allowed_types array
        $type   = mime_content_type($_FILES['image']['tmp_name']);        
        $error .= in_array($type, $allowed_types) ? '' : 'wrong type ';
        // Check the file extension is in the $allowed_exts array
        $ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $error .= in_array($ext, $allowed_exts) ? '' : 'wrong file extension ';

        // If there are no errors create the new filepath and try to move the file
        if (!$error) {
          $filename    = create_filename($_FILES['image']['name'], $upload_path);
          $destination = $upload_path . $filename;
          $moved       = move_uploaded_file($_FILES['image']['tmp_name'], $destination);
        }
    }
    if ($moved === true) {                                            // If it moved
        $message = 'Uploaded:<br><img src="' . $destination . '">';   // Show image
    } else {                                                          // Otherwise
        $message = '<b>Could not upload file:</b> ' . $error;         // Show errors
    }
}
?>
<?php include 'includes/header.php' ?>

<?= $message ?>
  <form method="POST" action="validate-file.php" enctype="multipart/form-data">
    <label for="image"><b>Upload file:</b></label>
    <input type="file" name="image" id="image"><br>
    <input type="submit" value="Upload">
  </form>

<?php include 'includes/footer.php' ?>