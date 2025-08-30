<?php
session_start(); // セッション開始

// ログインしていなければリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// DB接続情報
$host = 'localhost';
$dbname = 's_yugo'; // DB名
$user = 's_yugo';   // DBユーザー
$password = '9fjrtvAy'; // DBパスワード

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // compliment_list の褒め言葉を取得
    $stmt = $pdo->query("SELECT compliment_text FROM compliment_list ORDER BY compliment_id");
    $compliments = $stmt->fetchAll(PDO::FETCH_COLUMN); // 配列で取得

} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
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
        <!-- 🔍 検索ボックス（左側） -->
        <div class="search-box">
            <form action="search.php" method="get">
                <input type="text" name="q" placeholder="検索..." class="search-input">
                <button type="submit" class="search-button">検索</button>
            </form>
        </div>

        <!-- ナビ（従来通り右寄せに見えるが実際は中央寄り） -->
        <nav class="right">
            <ul>
                <li><a href="home_follow.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'home_follow.php'){ echo 'active'; } ?>">フォロー</a></li>
                <li><a href="home.php" class="<?php if(basename($_SERVER['PHP_SELF']) == 'home.php'){ echo 'active'; } ?>">おすすめ</a></li>
            </ul>
        </nav>

        <!-- 絶対配置のユーザー名＆ログアウト -->
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
                    <div id="complimentSelect-wrapper">
                        <select id="complimentSelect">
                            <option value="">褒め言葉を選択</option>
                            <?php foreach ($compliments as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="comment-submit">投稿</button>
                </div>

                <div class="compliment-summary">
                    <div class="compliment-item">
                        <p class="compliment-title">すごい！！: 130件</p>
                        <div class="compliment-users">
                        <p>ユーザー名a</p>
                        <p>ユーザー名b</p>
                        <p>ユーザー名c</p>
                        <p>ユーザー名d</p>
                        <p>ユーザー名e</p>
                        <p>ユーザー名f</p>
                        <p>ユーザー名g</p>
                        <p>ユーザー名h</p>
                        <p>ユーザー名i</p>
                        <p>ユーザー名j</p>
                        <p>ユーザー名k</p>
                        </div>
                    </div>

                    <div class="compliment-item">
                        <p class="compliment-title">素晴らしい！: 120件</p>
                        <div class="compliment-users">
                        <p>ユーザー名d</p>
                        <p>ユーザー名e</p>
                        </div>
                    </div>

                    <div class="compliment-item">
                        <p class="compliment-title">最高！: 95件</p>
                        <div class="compliment-users">
                        <p>ユーザー名f</p>
                        </div>
                    </div>
                </div>

                <div class="comment-list"></div> <!-- 投稿されたコメントを表示 -->
            </div>
        </div>


        <!-- モーダルとして右下に追加する領域 -->
        <div class="comment-modal" id="commentModal">
            <div class="comment-header">
                <h2>コメント欄</h2>
            </div>

            <div class="comment-input">
                <div id="complimentSelect-wrapper">
                    <select id="complimentSelect">
                        <option value="">褒め言葉を選択</option>
                        <?php foreach ($compliments as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="comment-submit">投稿</button>
            </div>



                <div class="compliment-summary">
                    <div class="compliment-item">
                        <p class="compliment-title">すごい！！: 130件</p>
                        <div class="compliment-users">
                        <p>ユーザー名a</p>
                        <p>ユーザー名b</p>
                        <p>ユーザー名c</p>
                        <p>ユーザー名d</p>
                        <p>ユーザー名e</p>
                        <p>ユーザー名f</p>
                        <p>ユーザー名g</p>
                        <p>ユーザー名h</p>
                        <p>ユーザー名i</p>
                        <p>ユーザー名j</p>
                        <p>ユーザー名k</p>
                        </div>
                    </div>

                    <div class="compliment-item">
                        <p class="compliment-title">素晴らしい！: 120件</p>
                        <div class="compliment-users">
                        <p>ユーザー名d</p>
                        <p>ユーザー名e</p>
                        </div>
                    </div>

                    <div class="compliment-item">
                        <p class="compliment-title">最高！: 95件</p>
                        <div class="compliment-users">
                        <p>ユーザー名f</p>
                        </div>
                    </div>
                </div>
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

document.querySelectorAll('.compliment-title').forEach(item => {
    item.addEventListener('click', () => {
      const usersDiv = item.nextElementSibling;
      usersDiv.style.display =
        usersDiv.style.display === 'none' || usersDiv.style.display === ''
          ? 'block'
          : 'none';
    });
  });
</script>



<?php
// ログアウト処理
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: before_login.php');
    exit;
}
?>