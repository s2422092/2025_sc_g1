<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$login_uid = $_SESSION['user_id']; // ログイン中のユーザーID

// DB接続
$host = 'localhost';
$dbname = 's_yugo';
$user = 's_yugo';
$password = '9fjrtvAy';

$uploadDir = 'uploads/';
$savedFiles[] = $uploadDir . basename($filename); // "uploads/ファイル名"


try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔽 ログイン中のユーザーがフォローしているユーザーIDを取得
    $stmt = $pdo->prepare("SELECT followee_uid FROM user_follow WHERE follower_uid = ?");
    $stmt->execute([$login_uid]);
    $followed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $followed_ids = array_map('intval', $followed_ids);

    // 投稿とユーザー情報を取得
    $stmt = $pdo->query("
        SELECT p.post_id, p.post_text, p.coordinateImage_path, u.uid, u.uname, u.profileImage, u.height, u.frame
        FROM post_coordinate p
        JOIN userauth u ON p.uid = u.uid
        ORDER BY p.created_at DESC
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔽 各投稿に「フォロー済み」フラグを追加
    foreach ($posts as &$post) {
        $post['is_following'] = in_array((int)$post['uid'], $followed_ids);
    }

    // 褒め言葉一覧
    $stmt = $pdo->query("SELECT compliment_text FROM compliment_list ORDER BY compliment_id");
    $compliments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 投稿とユーザー情報を取得
    $stmt = $pdo->query("
        SELECT p.post_id, p.post_text, p.coordinateImage_path, u.uid, u.uname, u.profileImage, u.height, u.frame
        FROM post_coordinate p
        JOIN userauth u ON p.uid = u.uid
        ORDER BY p.created_at DESC
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 画像パスを配列に変換
    foreach ($posts as &$post) {
        // PostgreSQL の配列は "{a,b,c}" 形式で返ってくるので処理
        $paths = trim($post['coordinateImage_path'], '{}');
        $post['coordinateImage_array'] = $paths ? explode(',', $paths) : [];
    }


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
            <div>
                <h2>テスト画像の表示</h2>
                <?php
                // 表示したい画像のパスを指定
                $testImagePath = 'uploads/img_68b2a5001b3663.24133752.jpeg'; // ここを好きなファイル名に変更

                // ファイルが存在するかチェック
                if (file_exists(__DIR__ . '/' . $testImagePath)): ?>
                    <img src="<?= htmlspecialchars($testImagePath, ENT_QUOTES) ?>" 
                        alt="テスト画像" 
                        class="post-image"
                        style="width:300px; height:auto;">
                <?php else: ?>
                    <p>ファイルが見つかりません: <?= htmlspecialchars($testImagePath, ENT_QUOTES) ?></p>
                <?php endif; ?>
            </div>
        </div>


        <div class="arrow-right"></div>

        <!-- ユーザー情報・フォロー・コメント欄 -->
        <div class="user-follow-section">
           <div class="user-follow-section">
            <div id="user-info">
                <h2>ユーザー情報</h2>
                <div id="user-details">
                    <!-- JSで切り替える -->
                </div>
            </div>
        </div>

        <div class="photo-scroll">
            <?php foreach ($posts as $index => $post): ?>
                <div class="photo-slide" data-index="<?= $index ?>">
                    <h3><?= htmlspecialchars($post['uname']) ?>さんの投稿</h3>
                    <p><?= htmlspecialchars($post['post_text']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>


        <div class="follow-box">
            <button id="followBtn" class="follow-button">フォロー</button>
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

            <div class="comment-header">
                <h2>ユーザー情報</h2>
                <div id="modal-user-details"></div> <!-- ここに表示 -->
            </div>

            <div class="photo-scroll">
                <?php foreach ($posts as $index => $post): ?>
                    <div class="photo-slide" data-index="<?= $index ?>">
                        <h3><?= htmlspecialchars($post['uname']) ?>さんの投稿</h3>
                        <p><?= htmlspecialchars($post['post_text']) ?></p>
                    </div>
                <?php endforeach; ?>
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

const posts = <?php echo json_encode($posts); ?>;
const scrollContainer = document.querySelector('.photo-scroll');
const followBtn = document.getElementById('followBtn');

function updateUserInfo(index) {
    const post = posts[index];
    const html = `
        <img src="${post.profileImage || 'uploads/default.png'}" alt="プロフィール画像" style="width:80px;height:80px;border-radius:50%;">
        <p><strong>${post.uname}</strong></p>
        <p>身長: ${post.height || '未設定'}</p>
        <p>体型: ${post.frame || '未設定'}</p>
    `;
    document.getElementById('user-details').innerHTML = html;

    // 🔽 フォローボタンの表示を切り替え
    if (post.is_following) {
        followBtn.innerText = 'フォロー済み';
        followBtn.disabled = true; // 連打防止
    } else {
        followBtn.innerText = 'フォロー';
        followBtn.disabled = false;
    }
}

updateUserInfo(0); // 最初の投稿表示

scrollContainer.addEventListener('scroll', () => {
    let index = Math.round(scrollContainer.scrollLeft / (300 + 20));
    if (index < 0) index = 0;
    if (index >= posts.length) index = posts.length - 1;
    updateUserInfo(index);
});

// 🔽 フォローボタンクリック時
followBtn.addEventListener('click', () => {
    let index = Math.round(scrollContainer.scrollLeft / (300 + 20));
    const targetUserId = posts[index].uid;

    fetch('follow.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `target_id=${encodeURIComponent(targetUserId)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            posts[index].is_following = true; // データ更新
            updateUserInfo(index); // ボタン表示を更新
        }
    })
    .catch(err => console.error(err));
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