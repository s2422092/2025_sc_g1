<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$userId = $_SESSION['user_id'];
$dbconn = pg_connect("host=localhost dbname=soto user=soto password=IGEGk8Ok");
if (!$dbconn) {
    die("DB接続失敗: " . pg_last_error());
}

// プロフィール更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = $_POST['uname'] ?? '';
    $height = $_POST['height'] ?? '';
    $frame = $_POST['frame'] ?? '';
    $profileImage = $_POST['profileImage'] ?? '';

    $update_sql = "UPDATE userauth SET uname = $1, height = $2, frame = $3, profileImage = $4 WHERE uid = $5";
    $result_up = pg_query_params($dbconn, $update_sql, [$uname, $height, $frame, $profileImage, $userId]);

    if (!$result_up) {
        die("プロフィール更新失敗: " . pg_last_error($dbconn));
    }
}

// ユーザー情報取得
$query_us = "SELECT uid, uname, email, profileImage, height, frame, created_at FROM userauth WHERE uid = $1";
$result_us = pg_query_params($dbconn, $query_us, [$userId]);
$user = pg_fetch_assoc($result_us);

// 投稿履歴取得
$query_ps = "SELECT post_text, coordinateImage_path, created_at FROM post_coordinate WHERE uid = $1 ORDER BY created_at DESC";
$result_ps = pg_query_params($dbconn, $query_ps, [$userId]);
$posts = pg_fetch_all($result_ps);

// フォロー数・フォロワー数
$follow_count = pg_fetch_result(pg_query($dbconn, "SELECT count(*) FROM user_follow WHERE follower_uid = $userId"), 0, 0);
$follower_count = pg_fetch_result(pg_query($dbconn, "SELECT count(*) FROM user_follow WHERE followee_uid = $userId"), 0, 0);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイページ</title>
    <link href="../layout/css/mypage.css" rel="stylesheet">
</head>
<body>
<header>
    <h1>mypage</h1>
    <div class="header-button">
        <a href="home.php">ホームに戻る</a>
    </div>
</header>

<div class="userdata">
    <section class="profile">
        <form method="POST" action="mypage.php">
            <div class="profile-header">
                <label for="profileImageInput">
                    <img src="<?php echo htmlspecialchars($user['profileImage'] ?? 'default_profile_image.png'); ?>" class="profile-icon" id="profilePreview" alt="プロフィール画像">
                </label>
                <input type="file" id="profileImageInput" style="display:none;" accept="image/*">
                <input type="hidden" name="profileImage" id="profileImagePath" value="<?php echo htmlspecialchars($user['profileImage'] ?? ''); ?>">
                <div>
                    <label>ユーザーネーム：</label><br>
                    <input type="text" name="uname" value="<?php echo htmlspecialchars($user['uname'] ?? ''); ?>">
                    <p class="detail-info"><sub>登録日：<?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($user['created_at']))); ?></sub></p>
                </div>
            </div>
            <div class="form-group">
                <label>身長：</label><br>
                <input type="text" name="height" value="<?php echo htmlspecialchars($user['height'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>体格：</label><br>
                <input type="text" name="frame" value="<?php echo htmlspecialchars($user['frame'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <button type="submit">プロフィールを更新</button>
            </div>
        </form>
    </section>

    <div class="follow-stats">
        <div class="follow-item">
            <p class="follow-count"><?php echo $follow_count; ?></p>
            <p class="follow-label">フォロー</p>
        </div>
        <div class="follow-item">
            <p class="follow-count"><?php echo $follower_count; ?></p>
            <p class="follow-label">フォロワー</p>
        </div>
    </div>

    <div class="post-history">
        <h2>投稿履歴</h2>
        <?php if (empty($posts)): ?>
            <p>投稿履歴はありません。</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-item">
                    <img src="<?php echo htmlspecialchars($post['coordinateImage_path'] ?? 'default_coordinate_image.png'); ?>" alt="投稿画像">
                    <p><?php echo htmlspecialchars($post['post_text']); ?></p>
                    <small><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($post['created_at']))); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('profileImageInput').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
            document.getElementById('profileImagePath').value = e.target.result; // base64で保存する場合
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php pg_close($dbconn); ?>
</body>
</html>
