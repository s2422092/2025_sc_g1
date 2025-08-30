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
                <h2>投稿画像の表示</h2>
                <img id="selectedImage" 
                    src="<?= !empty($posts[0]['coordinateImage_array'][0]) ? htmlspecialchars($posts[0]['coordinateImage_array'][0], ENT_QUOTES) : 'uploads/default.png' ?>" 
                    alt="投稿画像" 
                    class="post-image"
                    style="width:300px; height:auto;">
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
const posts = <?php echo json_encode($posts); ?>;
const scrollContainer = document.querySelector('.photo-scroll');
const followBtn = document.getElementById('followBtn');
const selectedImage = document.getElementById('selectedImage'); // 投稿画像
let currentPostIndex = 0; // 現在の投稿インデックス
let currentImageIndex = 0; // 現在の画像インデックス

// ======== モーダル関連 ========
// ユーザー情報領域をダブルクリックで開く
userFollowSection.addEventListener('dblclick', () => {
  modal.classList.add('active'); // 出現
});
// モーダルをダブルクリックで閉じる
modal.addEventListener('dblclick', () => {
  modal.classList.remove('active'); // 閉じる
});

// ======== お褒めコメント欄の開閉 ========
document.querySelectorAll('.compliment-title').forEach(item => {
    item.addEventListener('click', () => {
      const usersDiv = item.nextElementSibling;
      usersDiv.style.display =
        usersDiv.style.display === 'none' || usersDiv.style.display === ''
          ? 'block'
          : 'none';
    });
});

// ======== ユーザー情報＆画像更新 ========
function updateUserInfo(index) {
    currentPostIndex = index;
    const post = posts[index];

    // ユーザー情報
    const html = `
        <img src="${post.profileImage || 'uploads/default.png'}" alt="プロフィール画像" style="width:80px;height:80px;border-radius:50%;">
        <p><strong>${post.uname}</strong></p>
        <p>投稿ID: ${post.post_id || '不明'}</p>
        <p>身長: ${post.height || '未設定'}</p>
        <p>体型: ${post.frame || '未設定'}</p>
    `;
    document.getElementById('user-details').innerHTML = html;

    // 画像切り替え
    currentImageIndex = 0;
    updateImage();

    // フォローボタン
    if (post.is_following) {
        followBtn.innerText = 'フォロー済み';
        followBtn.disabled = true;
    } else {
        followBtn.innerText = 'フォロー';
        followBtn.disabled = false;
    }

    // インジケータ更新
    updateIndicator();
}

// ======== 画像の切り替え ========
function updateImage() {
    const post = posts[currentPostIndex];
    const images = post.coordinateImage_array || [];

    if (images.length > 0 && images[currentImageIndex]) {
        selectedImage.src = images[currentImageIndex];
    } else {
        selectedImage.src = 'uploads/default.png';
    }
}

// ======== インジケータ（ドット）更新 ========
function updateIndicator() {
    const indicatorContainer = document.getElementById('image-indicator');
    if (!indicatorContainer) return;

    const post = posts[currentPostIndex];
    const images = post.coordinateImage_array || [];
    indicatorContainer.innerHTML = '';

    images.forEach((_, i) => {
        const dot = document.createElement('span');
        dot.classList.add('dot');
        if (i === currentImageIndex) dot.classList.add('active-dot');
        dot.addEventListener('click', () => {
            currentImageIndex = i;
            updateImage();
            updateIndicator();
        });
        indicatorContainer.appendChild(dot);
    });
}

// ======== 自動スライドショー（投稿切り替え） ========
setInterval(() => {
    let newIndex = currentPostIndex + 1;
    if (newIndex >= posts.length) newIndex = 0;
    scrollToIndex(newIndex);
    updateUserInfo(newIndex);
}, 8000); // 8秒ごと

function scrollToIndex(index) {
    scrollContainer.scrollTo({
        left: index * 320, // 幅300+マージン20
        behavior: 'smooth'
    });
}

// ======== スクロール時の表示更新 ========
scrollContainer.addEventListener('scroll', () => {
    let index = Math.round(scrollContainer.scrollLeft / 320);
    if (index < 0) index = 0;
    if (index >= posts.length) index = posts.length - 1;
    updateUserInfo(index);
});

// ======== フォローボタンクリック ========
followBtn.addEventListener('click', () => {
    const targetUserId = posts[currentPostIndex].uid;

    fetch('follow.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `target_id=${encodeURIComponent(targetUserId)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            posts[currentPostIndex].is_following = true;
            updateUserInfo(currentPostIndex);
        }
    })
    .catch(err => console.error(err));
});

// ======== 画像クリックで拡大モーダル表示 ========
selectedImage.addEventListener('click', () => {
    const fullImgModal = document.createElement('div');
    fullImgModal.classList.add('full-img-modal');
    fullImgModal.innerHTML = `
        <div class="full-img-wrapper">
            <img src="${selectedImage.src}" alt="拡大画像">
        </div>
    `;
    document.body.appendChild(fullImgModal);
    fullImgModal.addEventListener('click', () => {
        document.body.removeChild(fullImgModal);
    });
});

// 初期表示
updateUserInfo(0);
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