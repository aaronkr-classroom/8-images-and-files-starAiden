<?php
$moved         = false;                                        // Initialize
$message       = '';                                           // Initialize
$error         = '';                                           // Initialize
$upload_path   = 'uploads/';                                   // Upload path
$max_size      = 5242880;                                      // Max file size
$allowed_types = ['image/jpeg', 'image/png', 'image/gif',];    // Allowed file types
$allowed_exts  = ['jpeg', 'jpg', 'png', 'gif',];               // Allowed file extensions

function create_filename($filename, $upload_path)              // Function to make filename
{
    $basename   = pathinfo($filename, PATHINFO_FILENAME);      // Get basename
    $extension  = pathinfo($filename, PATHINFO_EXTENSION);     // Get extension
    $basename   = preg_replace('/[^A-z0-9]/', '-', $basename); // Clean basename
    $filename   = $basename . '.' . $extension;                // Set initial filename
    $i          = 0;                                           // Counter
    while (file_exists($upload_path . $filename)) {            // If file exists
        $i        = $i + 1;                                    // Update counter 
        $filename = $basename . $i . '.' . $extension;          // New filepath
    }
    return $filename;                                          // Return filename
}

function crop_and_resize_image_gd($orig_path, $new_path, $new_width, $new_height) // Function to crop and resize
{
    $image_data  = getimagesize($orig_path);                   // 이미지 데이터 가져오기
    $orig_width  = $image_data[0];                             // 원본 너비 (Original width)
    $orig_height = $image_data[1];                             // 원본 높이 (Original height)
    $media_type  = $image_data['mime'];                        // 미디어 타입 (Media type 'mime')
    $orig_ratio  = $orig_width / $orig_height;                 // 원본 비율 계산 (Original ratio)
    $new_ratio   = $new_width / $new_height;                   // 목표 비율 계산 (New ratio)

    // 새로운 크기(select) 및 오프셋 계산하기
    if ($orig_ratio > $new_ratio) {                            // 원본이 더 가로로 긴 경우
        $select_width  = $orig_height * $new_ratio;            // 높이에 맞춘 새로운 선택 너비 계산
        $select_height = $orig_height;                         // 선택 높이는 원본 유지
        $x_offset      = ($orig_width - $select_width) / 2;    // 가로 중앙 x좌표 오프셋 (left)
        $y_offset      = 0;                                    // 세로 오프셋은 0 (top)
    } else {                                                   // 원본이 더 세로로 긴 경우
        $select_width  = $orig_width;                          // 선택 너비는 원본 유지
        $select_height = $orig_width / $new_ratio;             // 너비에 맞춘 새로운 선택 높이 계산
        $x_offset      = 0;                                    // 가로 오프셋은 0 (left)
        $y_offset      = ($orig_height - $select_height) / 2;  // 세로 중앙 y좌표 오프셋 (top)
    }

    // 미디어 타입 확인 및 리소스 생성 (@를 붙여 iCCP 경고 방지)
    switch ($media_type) {                                     // Check media type
        case 'image/jpeg': $orig_img = @imagecreatefromjpeg($orig_path); break;
        case 'image/png':  $orig_img = @imagecreatefrompng($orig_path);  break;
        case 'image/gif':  $orig_img = @imagecreatefromgif($orig_path);  break;
        default: return false;
    }

    $new_img = imagecreatetruecolor($new_width, $new_height);  // Create new image
    
    imagealphablending($new_img, false);                       // Keep transparency
    imagesavealpha($new_img, true);                            // Save alpha channel

    // 계산된 오프셋과 선택 영역(select)을 사용하여 리사이징 실행
    imagecopyresampled($new_img, $orig_img, 0, 0, $x_offset, $y_offset, $new_width, $new_height, $select_width, $select_height);

    switch ($media_type) {                                     // Save by type
        case 'image/jpeg': $result = imagejpeg($new_img, $new_path, 90); break;
        case 'image/png':  $result = imagepng($new_img, $new_path);  break;
        case 'image/gif':  $result = imagegif($new_img, $new_path);  break;
    }

    imagedestroy($orig_img);                                   // Free memory
    imagedestroy($new_img);                                    // Free memory

    return $result;                                            // Return result
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {                    // If form submitted
    $error = ($_FILES['image']['error'] === 1) ? 'too big ' : '';  // Check size error

    if ($_FILES['image']['error'] == 0) {                          // If no upload errors
        $error  .= ($_FILES['image']['size'] <= $max_size) ? '' : 'too big '; // Check size
        $type   = mime_content_type($_FILES['image']['tmp_name']);        
        $error .= in_array($type, $allowed_types) ? '' : 'wrong type ';
        $ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $error .= in_array($ext, $allowed_exts) ? '' : 'wrong file extension ';

        if (!$error) {
            $filename    = create_filename($_FILES['image']['name'], $upload_path);
            $destination = $upload_path . $filename;
            $thumbpath   = $upload_path . 'thumb_' . $filename;
            $moved       = move_uploaded_file($_FILES['image']['tmp_name'], $destination);
            $resized     = crop_and_resize_image_gd($destination, $thumbpath, 200, 200);
        }
    }

    if ($moved === true and $resized === true) {                        // If it moved
        $message = '<img src="' . $thumbpath . '">';                  // Show image
    } else {                                                            // Otherwise
        $message = '<b>Could not upload file</b> ' . $error;            // Show errors
    }
}
?>
<?php include 'includes/header.php' ?>
<?= $message ?>
<form method="POST" action="crop-and-resize-gd.php" enctype="multipart/form-data">
    <label for="image"><b>Upload file:</b></label>
    <input type="file" name="image" accept="image/*" id="image"><br>
    <input type="submit" value="upload">
</form>
<?php include 'includes/footer.php' ?>