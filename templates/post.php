<?php
session_start(); 

$errors = [];
$successMessage = '';
$savedFiles = [];
$comment = '';

// デバッグ出力（問題解決後にコメントアウトまたは削除してください）
// echo "セッション情報: <pre>" . print_r($_SESSION, true) . "</pre>";

function connectDB() {
    $host = 'localhost';
    $dbname = 'tamaru'; // DB名
    $user = 'tamaru';   // DBユーザー
    $password = 'H6lTJUMT'; // DBパスワード

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('データベース接続エラー: ' . $e->getMessage());
    }
}

// ここでセッションを確認 - デバッグ目的でテスト用のuidを設定（最終版では削除すること）
if (!isset($_SESSION['uid'])) {
    $errors[] = "ログインが必要です。";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // セッションにuidがある場合のみ処理を続行
    if (isset($_SESSION['uid'])) {
        $uid = $_SESSION['uid']; 

        // コメントの取得とバリデーション（最大40文字 - テーブル定義に合わせる）
        $comment = trim($_POST['comment'] ?? '');
        if (mb_strlen($comment) > 40) {
            $errors[] = "コメントは最大40文字までです。";
        }

        // ファイルアップロードチェック（最大5枚）
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
            // 既存のディレクトリを使用
            $uploadDir = 'uploads/';
            
            // 指定したディレクトリが存在するか確認
            if (!is_dir($uploadDir)) {
                $errors[] = "指定されたアップロードディレクトリが存在しません。";
            }
            
            // 書き込み権限があるか確認
            if (empty($errors) && !is_writable($uploadDir)) {
                $errors[] = "アップロードディレクトリに書き込み権限がありません。";
            }

            // ディレクトリ存在確認後、ファイル保存
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
                            // 保存先のパスを記録
                            $savedFiles[] = $uploadDir . basename($filename);
                        } else {
                            $errors[] = "ファイルの保存に失敗しました (" . htmlspecialchars($name) . ")。パス: " . $filepath;
                        }
                    }
                }
            }

            if (empty($errors)) {
                try {
                    $pdo = connectDB();
                    
                    // PostgreSQLの配列形式に変換
                    $imagePathsLiteral = "{";
                    foreach ($savedFiles as $index => $path) {
                        if ($index > 0) {
                            $imagePathsLiteral .= ",";
                        }
                        $imagePathsLiteral .= '"' . str_replace('"', '\"', $path) . '"';
                    }
                    $imagePathsLiteral .= "}";

                    $stmt = $pdo->prepare("INSERT INTO post_coordinate (uid, post_text, coordinateImage_path) VALUES (:uid, :post_text, :coordinateImage_path)");
                    
                    $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
                    $stmt->bindParam(':post_text', $comment, PDO::PARAM_STR);
                    $stmt->bindParam(':coordinateImage_path', $imagePathsLiteral, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $successMessage = "投稿成功！";
                    } else {
                        $errors[] = "データベースへの投稿に失敗しました。";
                    }
                } catch (Exception $e) {
                    $errors[] = "エラー: " . $e->getMessage();
                    
                    // データベース保存失敗時はファイルも削除
                    foreach ($savedFiles as $path) {
                        if (file_exists($path)) {
                            unlink($path);
                        }
                    }
                }
            }
        }
    } else {
        $errors[] = "ログインが必要です。";
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
        <input type="text" name="comment" id="comment" maxlength="20" 　placeholder="例：今日のお出かけコーデ"　required value="<?php echo htmlspecialchars($comment); ?>">

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
