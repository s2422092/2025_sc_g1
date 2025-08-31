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

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 🔽 ログイン中ユーザーがフォローしているユーザー一覧を取得
    $stmt = $pdo->prepare("SELECT followee_uid FROM user_follow WHERE follower_uid = ?");
    $stmt->execute([$login_uid]);
    $followed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $followed_ids = array_map('intval', $followed_ids);

    // 投稿とユーザー情報を取得
    $stmt = $pdo->query("
        SELECT p.post_id, p.post_text, p.coordinateImage_path,
               u.uid, u.uname, u.profileImage, u.height, u.frame
        FROM post_coordinate p
        JOIN userauth u ON p.uid = u.uid
        ORDER BY p.created_at DESC
    ");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        // 投稿者がフォローされているか判定
        $post['is_following'] = in_array((int)$post['uid'], $followed_ids);

        // 画像パスを配列化
        $paths = trim($post['coordinateimage_path'], '{}');
        $post['coordinateImage_array'] = $paths ? explode(',', $paths) : [];
    }

    // 褒め言葉一覧
    $stmt = $pdo->query("SELECT compliment_text FROM compliment_list ORDER BY compliment_id");
    $compliments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 各投稿にコメント情報・褒め言葉まとめを追加
    foreach ($posts as &$post) {
        // コメント一覧
        $stmt = $pdo->prepare("
            SELECT pc.post_compliment_id, c.compliment_text, ua.uname
            FROM post_compliment pc
            JOIN compliment_list c ON pc.compliment_id = c.compliment_id
            JOIN userauth ua ON pc.uid = ua.uid
            WHERE pc.post_id = ?
            ORDER BY pc.created_at DESC
        ");
        $stmt->execute([$post['post_id']]);
        $post['compliments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 褒め言葉集計
        $stmt = $pdo->prepare("
            SELECT c.compliment_text, COUNT(pc.compliment_id) AS compliment_count
            FROM post_compliment pc
            JOIN compliment_list c ON pc.compliment_id = c.compliment_id
            WHERE pc.post_id = ?
            GROUP BY c.compliment_text
            ORDER BY compliment_count DESC
        ");
        $stmt->execute([$post['post_id']]);
        $post['compliment_summary'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 褒め言葉ごとのユーザー
        $stmt = $pdo->prepare("
            SELECT c.compliment_text, ua.uname
            FROM post_compliment pc
            JOIN compliment_list c ON pc.compliment_id = c.compliment_id
            JOIN userauth ua ON pc.uid = ua.uid
            WHERE pc.post_id = ?
            ORDER BY c.compliment_text, ua.uname
        ");
        $stmt->execute([$post['post_id']]);
        $compliment_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $post['compliment_users'] = [];
        foreach ($compliment_users as $cu) {
            $post['compliment_users'][$cu['compliment_text']][] = $cu['uname'];
        }
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
            <!-- 写真表示セクション -->
            <div class="photo-section">
                <div>
                    <h2>テスト画像の表示</h2>
                    <img id="main-image" 
                        src="<?= !empty($posts[0]['coordinateImage_array'][0]) 
                                    ? htmlspecialchars($posts[0]['coordinateImage_array'][0], ENT_QUOTES) 
                                    : 'uploads/default.png' ?>" 
                        alt="投稿画像" 
                        class="post-image"
                        style="width:300px; height:auto;">
                </div>
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

                <!-- コメントと褒め言葉まとめを動的に描画 -->
                <div class="compliment-summary"></div>
                <div class="comment-list"></div>
            </div>

        </div>


        <!-- モーダルとして右下に追加する領域 -->
        <div class="comment-modal" id="commentModal">
            <div class="comment-header">
                <h2>コメント欄</h2>
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

                <!-- コメントと褒め言葉まとめを動的に描画 -->
                <div class="compliment-summary"></div>
                <div class="comment-list"></div>
            </div>

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
const scrollContainer = document.querySelector('.photo-scroll');
const followBtn = document.getElementById('followBtn');
const mainImage = document.getElementById('main-image'); 
const posts = <?php echo json_encode($posts); ?>;

let currentPostIndex = 0;
let currentImageIndex = 0;

// ===== モーダル開閉 =====
userFollowSection.addEventListener('dblclick', () => {
  modal.classList.add('active');
});
modal.addEventListener('dblclick', () => {
  modal.classList.remove('active');
});

// ===== 画像切り替え =====
document.querySelector('.arrow-right').addEventListener('click', () => {
  const post = posts[currentPostIndex];
  if (post.coordinateImage_array?.length) {
    currentImageIndex = (currentImageIndex + 1) % post.coordinateImage_array.length;
    mainImage.src = post.coordinateImage_array[currentImageIndex].trim();
  }
});
document.querySelector('.arrow-left').addEventListener('click', () => {
  const post = posts[currentPostIndex];
  if (post.coordinateImage_array?.length) {
    currentImageIndex = (currentImageIndex - 1 + post.coordinateImage_array.length) % post.coordinateImage_array.length;
    mainImage.src = post.coordinateImage_array[currentImageIndex].trim();
  }
});

// ===== コメント＆褒め言葉欄更新 =====
function updateCommentBox(index, container) {
  const post = posts[index];

  // コメント一覧
  const commentList = container.querySelector('.comment-list');
  commentList.innerHTML = "";
  if (post.compliments?.length) {
    post.compliments.forEach(c => {
      const p = document.createElement('p');
      p.textContent = `${c.uname}: ${c.compliment_text}`;
      commentList.appendChild(p);
    });
  } else {
    commentList.innerHTML = "<p>コメントはまだありません</p>";
  }

  // 褒め言葉まとめ
  const summaryContainer = container.querySelector('.compliment-summary');
  summaryContainer.innerHTML = "";

  if (post.compliment_summary?.length) {
    post.compliment_summary.forEach(cs => {
      const div = document.createElement('div');
      div.classList.add('compliment-item');

      let usersHTML = "";
      if (post.compliment_users?.[cs.compliment_text]) {
        post.compliment_users[cs.compliment_text].forEach(user => {
          usersHTML += `<p>${user}</p>`;
        });
      }

      div.innerHTML = `
        <p class="compliment-title">${cs.compliment_text}: ${cs.compliment_count}件</p>
        <div class="compliment-users" style="display:none;">${usersHTML}</div>
      `;
      summaryContainer.appendChild(div);
    });

    // タイトルクリックでユーザー表示切替
    summaryContainer.querySelectorAll('.compliment-title').forEach(item => {
      item.addEventListener('click', () => {
        const usersDiv = item.nextElementSibling;
        usersDiv.style.display = usersDiv.style.display === 'block' ? 'none' : 'block';
      });
    });
  } else {
    summaryContainer.innerHTML = "<p>コメントはまだありません</p>";
  }
}

// ===== ユーザー情報更新 =====
function updateUserInfo(index) {
  currentPostIndex = index;
  currentImageIndex = 0;
  const post = posts[index];

  // ユーザー情報
  const userHtml = `
    <img src="${post.profileImage || 'uploads/default.png'}" style="width:80px;height:80px;border-radius:50%;">
    <p><strong>${post.uname}</strong></p>
    <p>身長: ${post.height || '未設定'}</p>
    <p>体型: ${post.frame || '未設定'}</p>
  `;
  document.getElementById('user-details').innerHTML = userHtml;
  document.getElementById('modal-user-details').innerHTML = userHtml;

  // 画像
  mainImage.src = post.coordinateImage_array?.length ? post.coordinateImage_array[0].trim() : 'uploads/default.png';

  // フォローボタン
  if (post.is_following) {
    followBtn.innerText = 'フォロー済み';
    followBtn.disabled = true;
  } else {
    followBtn.innerText = 'フォロー';
    followBtn.disabled = false;
  }

  // コメント・褒め言葉欄
  updateCommentBox(index, document.querySelector('.comment-box'));
  updateCommentBox(index, document.querySelector('#commentModal .comment-box'));
}

// ===== スクロールで投稿切り替え =====
scrollContainer.addEventListener('scroll', () => {
  let index = Math.round(scrollContainer.scrollLeft / (300 + 20));
  index = Math.max(0, Math.min(index, posts.length - 1));
  updateUserInfo(index);
});

// ===== フォローボタン =====
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
  .catch(console.error);
});

// ===== コメント送信 =====
document.querySelector('.comment-submit').addEventListener('click', () => {
  const compliment = document.getElementById('complimentSelect').value;
  if (!compliment) return alert("褒め言葉を選んでください");

  const post_id = posts[currentPostIndex].post_id;
  fetch('compliment_post.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `post_id=${encodeURIComponent(post_id)}&compliment=${encodeURIComponent(compliment)}`
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.status === 'success') {
      const list = document.querySelector('.comment-list');
      const newComment = document.createElement('p');
      newComment.textContent = `<?php echo $_SESSION['user_name']; ?>: ${compliment}`;
      list.appendChild(newComment);
    }
  })
  .catch(console.error);
});

// ===== 初期表示 =====
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