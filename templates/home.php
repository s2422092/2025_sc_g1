<?php
session_start(); // セッションを開始

// ログインしていなければログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ホーム</title>
    <link rel="stylesheet" href="../layout/css/home.css">




</head>

<body>

    <header class="main-header">
    <nav class="right">
        <ul>
            <li><a href="home_follow.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'home_follow.php'){ echo 'active'; } ?>">フォロー</a></li>
            <li><a href="home.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'home.php'){ echo 'active'; } ?>">おすすめ</a></li>
        </ul>
    </nav>
  
        <p><a href="logout.php">ログアウト</a></p>
        <h1><?php echo htmlspecialchars($_SESSION['user_name']); ?>さん</h1>
    </header>


    <!-- 写真＋右側 -->
    <div class="main-content">
        <!-- 写真表示セクション -->
        <div class="arrow-left"></div>
        <div class="photo-section">
            <h1>写真の表示</h1>
        </div>
        <div class="arrow-right"></div>

        <!-- ユーザー情報・フォロー・コメント欄 -->
        <div class="user-follow-section">
            <div>
                <h2>ユーザー情報</h2>
            </div>
            <div class="follow-box">
                <h2>フォロー</h2>
            </div>
            <div class="comment-box">
                <div class="comment-header">
                    <h2>コメント欄</h2>
                </div>
                
                <div class="comment-input">
                <textarea class="comment-area" placeholder="コメントを入力"></textarea>
                <button class="comment-submit">投稿</button>
                </div>


                <p>ここに褒め言葉を表示</p>
                <div class="comment-list"></div> <!-- 投稿されたコメントを表示 -->
            </div>
        </div>


        <!-- モーダルとして右下に追加する領域 -->
        <div class="comment-modal" id="commentModal">
            <div class="comment-header">
                <h2>コメント欄</h2>
            </div>
            <div class="comment-input">
                <textarea class="comment-area" placeholder="コメントを入力"></textarea>
                <button class="comment-submit">投稿</button>
            </div>
            <p>ここに褒め言葉を表示</p>
            <div class="comment-list"></div>
        </div>

    </div>

    <nav class="main-nav-under">
        <ul>
            <li><a href="home.php">ホーム</a></li>
            <li><a href="post.php">投稿</a></li>
            <li><a href="mypage.php">マイページ</a></li>
        </ul>
    </nav>
</body>


</html>



<script>
const modal = document.getElementById('commentModal');
const userFollowSection = document.querySelector('.user-follow-section');

// ユーザー情報領域をダブルクリックで開く
userFollowSection.addEventListener('dblclick', () => {
  modal.classList.add('active'); // 出現
});

// モーダルをダブルクリックで閉じる
modal.addEventListener('dblclick', () => {
  modal.classList.remove('active'); // 閉じる
});
</script>



</script>
