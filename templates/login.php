<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <title>テキスト</title>
</head>

<div class="container">
    <?php if ($is_logged_in): ?>
        <h1>こんにちは、<?php echo htmlspecialchars($_SESSION['user_name']); ?>さん！</h1>
        <p class="subtitle">ログインに成功しました。</p>
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        <p class="user-info">
            <a href="?logout=true" class="logout-btn">ログアウト</a>
        </p>
    <?php else: ?>
        <h1>ログイン</h1>
        <p class="subtitle">ユーザー名またはメールアドレスとパスワードを入力してください。</p>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <input type="text" name="identifier" placeholder="ユーザー名またはメールアドレス" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit">ログイン</button>
        </form>
    <?php endif; ?>
</div>

<?php
// ログアウト処理
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

</body>
</html> 

<?php

session_start();

// データベース接続情報
$host = 'localhost';
$dbname = 'tamaru'; // データベース名に置き換えてください
$user = 'tamaru'; // ユーザー名に置き換えてください
$password = 'H6lTJUMT'; // パスワードに置き換えてください

// エラーメッセージと成功メッセージを格納する変数
$error = '';
$message = '';

// ログインフォームが送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_identifier = trim($_POST['identifier']);
    $input_password = $_POST['password'];

    try {
        // PostgreSQLデータベースに接続
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // unameまたはemailでユーザーを検索
        $sql = "SELECT * FROM userauth WHERE uname = :identifier OR email = :identifier";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':identifier', $input_identifier, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // パスワードのハッシュを検証
            if (password_verify($input_password, $user['pw'])) {
                // 認証成功
                $_SESSION['user_id'] = $user['uid'];
                $_SESSION['user_name'] = $user['uname'];
                $message = 'ログインに成功しました！';
                // ログイン後のページにリダイレクトする場合は、以下のコメントを外す
                // header('Location: dashboard.php');
                // exit;
            } else {
                // パスワードが一致しない
                $error = 'ユーザー名またはパスワードが正しくありません。';
            }
        } else {
            // ユーザーが見つからない
            $error = 'ユーザー名またはパスワードが正しくありません。';
        }
    } catch (PDOException $e) {
        $error = 'データベース接続エラー: ' . $e->getMessage();
    }
}

// ユーザーがログインしているか確認
$is_logged_in = isset($_SESSION['user_id']);
?>
