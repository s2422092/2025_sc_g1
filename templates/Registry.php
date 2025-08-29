<?php
// PHPのバージョンが古い場合、パスワードハッシュ関数がないため、password_compatライブラリを使用する
// セッションを開始
session_start();

// データベース接続情報
$host = 'localhost';
$dbname = 'tamaru'; // データベース名に置き換えてください
$user = 'tamaru'; // ユーザー名に置き換えてください
$password = 'H6lTJUMT'; // パスワードに置き換えてください

// エラーメッセージを格納する変数
$error = '';
$pdo = null;

// 新規登録フォームが送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $uname = trim($_POST['uname']);
    $email = trim($_POST['email']);
    $pw = $_POST['pw'];
    $confirm_pw = $_POST['confirm_pw'];
    
    // シンプルなバリデーション
    if (empty($uname) || empty($email) || empty($pw) || empty($confirm_pw)) {
        $error = '全てのフィールドを入力してください。';
    } elseif ($pw !== $confirm_pw) {
        $error = 'パスワードが一致しません。';
    } else {
        try {
            // PostgreSQLデータベースに接続
            $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // メールアドレスがすでに登録されていないか確認
            $sql = "SELECT COUNT(*) FROM userauth WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $error = 'このメールアドレスはすでに登録されています。';
            } else {
                // パスワードをハッシュ化
                $hashed_pw = password_hash($pw, PASSWORD_DEFAULT);

                // ユーザーをデータベースに挿入
                $sql = "INSERT INTO userauth (uname, email, pw) VALUES (:uname, :email, :pw)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':uname', $uname, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':pw', $hashed_pw, PDO::PARAM_STR);
                $stmt->execute();

                // 登録後にログイン画面にリダイレクト
                header('Location: login.php?registered=true');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'データベースエラー: ' . $e->getMessage();
        } finally {
            $pdo = null; // 接続を閉じる
        }
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../layout/css/test.css">
    <title>新規登録</title>
</head>
<body class="login_body">

<div class="container">
    <h1>新規登録</h1>
    <p>新しいアカウントを作成します。</p>

    <?php if ($error): ?>
        <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="post">
        <input type="text" name="uname" placeholder="ユーザー名" required><br><br>
        <input type="email" name="email" autocomplete="email" placeholder="メールアドレス" required><br><br>
        <input type="password" name="pw" placeholder="パスワード" required><br><br>
        <input type="password" name="confirm_pw" placeholder="パスワードを再入力" required><br><br>
        <button type="submit" name="register" class="login_button">登録</button>
    </form>
    <br>
    <a href="login.php">すでにアカウントをお持ちですか？ログインはこちら</a>
    <a href="before_login.php">戻る</a>
</div>

</body>
</html>