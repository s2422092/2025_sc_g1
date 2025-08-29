<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <link rel="stylesheet" href="../layout/css/common.css">
</head>
<body>
<h1>マイページへようこそ、<?php echo htmlspecialchars($_SESSION['user_name']); ?>さん！</h1>
<p>ここはあなたのマイページです。</p>
<p><a href="logout.php">ログアウト</a></p>

    <nav class="main-nav-under">
        <ul>
            <li><a href="home.php">ホーム</a></li>
            <li><a href="post.php">投稿</a></li>
            <li><a href="mypage.php">マイページ</a></li>
        </ul>
    </nav>

</body>
</html>