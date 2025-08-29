<?php
$errors = [];
$successMessage = '';
$savedFiles = [];
$comment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // コメントの取得とバリデーション（最大20文字）
    $comment = trim($_POST['comment'] ?? '');
    if (mb_strlen($comment) > 20) {
        $errors[] = "コメントは最大20文字までです。";
    }

    // ファイルアップロードチェック（最大5枚）
    // `required`属性があるので、何らかのファイルが選択される前提とします。
    if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
        $errors[] = "画像が選択されていません。";
    } else {
        $photos = $_FILES['photos'];

        // ファイル数のチェック
        if (count($photos['name']) > 5) {
            $errors[] = "画像は最大5枚まで選択可能です。";
        }

        // エラーがない場合のみ、個々のファイルチェックを行う
        if (empty($errors)) {
            for ($i = 0; $i < count($photos['name']); $i++) {
                // PHPアップロードエラーのチェック
                if ($photos['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = "画像アップロードに失敗しました (" . htmlspecialchars($photos['name'][$i]) . ")。エラーコード: " . $photos['error'][$i];
                    continue; // 次のファイルへ
                }

                // 画像ファイルかどうか簡易チェック（MIMEタイプ）
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $photos['tmp_name'][$i]);
                finfo_close($finfo);

                if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
                    $errors[] = "許可されていないファイル形式です (" . htmlspecialchars($photos['name'][$i]) . ")。許可されるのはJPEG, PNG, GIFです。";
                }
            }
        }
    }

    // 全てのバリデーションを通過した場合のみ、ファイル保存処理を実行
    if (empty($errors)) {
        // 保存先ディレクトリ（存在しなければ作成）
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) { // trueで再帰的にディレクトリを作成
                $errors[] = "アップロードディレクトリの作成に失敗しました。";
            }
        }

        // ディレクトリ作成成功、または既に存在する場合のみファイル保存
        if (empty($errors)) {
            foreach ($photos['name'] as $index => $name) {
                // アップロード時にエラーが発生しなかったファイルのみ処理
                if ($photos['error'][$index] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    do {
                        $filename = uniqid('img_', true) . "." . $ext;
                        $filepath = $uploadDir . $filename;
                    } while (file_exists($filepath)); // ユニークなファイル名が生成されるまで繰り返す

                    if (move_uploaded_file($photos["tmp_name"][$index], $filepath)) {
                        // Webアクセス用パスに変換
                        $savedFiles[] = 'uploads/' . basename($filename);
                    } else {
                        $errors[] = "ファイルの保存に失敗しました (" . htmlspecialchars($name) . ")。";
                    }
                }
            }
        }

        if (empty($errors)) {
            $successMessage = "投稿成功！";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>写真投稿フォーム</title>
    <link rel="stylesheet" href="../layout/css/post.css">
</head>
<body>

<!-- ホームに戻るボタンをbody直下、containerの外に移動し、buttonクラスを適用 -->
<a href="home.php" class="button">ホームに戻る</a>

<div class="container">
    <h1>写真投稿</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <p><strong>エラーが発生しました:</strong></p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif (!empty($successMessage)): ?>
        <div class="success-message">
            <p><strong><?php echo htmlspecialchars($successMessage); ?></strong></p>
            <p>コメント: <?php echo htmlspecialchars($comment); ?></p>
            <?php if (!empty($savedFiles)): ?>
                <p>アップロードした写真:</p>
                <ul class="uploaded-photos">
                    <?php foreach ($savedFiles as $_filePath): ?>
                        <li><img src="<?php echo htmlspecialchars($_filePath); ?>" alt="Uploaded Image"></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>ファイルはアップロードされませんでした。</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <label for="photo">写真を選択してください（最大5枚）</label>
        <input type="file" name="photos[]" id="photo" accept=".jpg,.jpeg,.png,.gif" multiple required>

        <!-- プレビュー表示用のコンテナを追加 -->
        <div id="preview-container" class="preview-container">
            <p id="preview-text" style="display none;">選択された写真:</p>
            <div id="photo-previews" class="photo-previews">
                <!-- プレビュー画像がJavaScriptによってここに追加される -->
            </div>
        </div>

        <label for="comment">コメント（最大20文字）</label>
        <input type="text" name="comment" id="comment" maxlength="20" required value="<?php echo htmlspecialchars($comment); ?>">

        <div class="form-actions">
            <!-- 投稿するボタンはフォーム内に残す -->
            <button type="submit">投稿する</button>
        </div>
    </form>
</div>

<script>
    // 選択可能なファイル数制限とプレビュー表示(クライアント側)
    document.getElementById('photo').addEventListener('change', function(event) {
        const files = event.target.files;
        const maxFiles = 5;
        const photoPreviews = document.getElementById('photo-previews');
        const previewText = document.getElementById('preview-text');

        // 既存のプレビューをクリア
        photoPreviews.innerHTML = '';
        previewText.style.display = 'none'; // プレビューテキストも非表示に

        if (files.length === 0) {
            return; // ファイルが選択されていない場合は何もしない
        }

        if (files.length > maxFiles) {
            alert("写真は最大" + maxFiles + "枚まで選べます。");
            this.value = ""; // ファイル選択をクリア
            return;
        }

        // プレビューテキストを表示
        previewText.style.display = 'block';

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            // ファイルが画像であるかチェック
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = file.name;
                    img.classList.add('preview-image'); // スタイリング用のクラス

                    const imgWrapper = document.createElement('div');
                    imgWrapper.classList.add('preview-image-wrapper');
                    imgWrapper.appendChild(img);

                    photoPreviews.appendChild(imgWrapper);
                };

                reader.readAsDataURL(file); // ファイルをData URLとして読み込む
            } else {
                console.warn("画像ファイルではありません: " + file.name);
                // 必要であれば、ユーザーに通知する処理を追加
            }
        }
    });
</script>

</body>
</html>