<?php
// セッションを開始
session_start();

// データベース接続情報
$host = 'localhost';
$dbname = 's_yugo'; // DB名
$user = 's_yugo';   // DBユーザー
$password = '9fjrtvAy'; // DBパスワード

// エラーメッセージと成功メッセージを格納する変数
$error = '';
$message = '';

// ログインフォームが送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
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
                header('Location: home.php');
                exit;
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

// 登録完了後のメッセージ表示
if (isset($_GET['registered'])) {
    $message = '新規登録が完了しました。ログインしてください。';
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../layout/css/test.css">
    <title>ログイン</title>
</head>
<body class="login_body">

<div class="container">
    <?php if ($is_logged_in): ?>
        <h1>こんにちは、<?php echo htmlspecialchars($_SESSION['user_name']); ?>さん！</h1>
        <p>ログインに成功しました。</p>
        <?php if ($message): ?>
            <div style="color: green;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <a href="?logout=true">ログアウト</a>
    <?php else: ?>
        <h1>ログイン</h1>
        <p>ユーザー名またはメールアドレスとパスワードを入力してください。</p>

        <?php if ($error): ?>
            <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div style="color: green;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <input type="text" name="identifier" placeholder="ユーザー名またはメールアドレス" required><br><br>
            <input type="password" name="password" placeholder="パスワード" required><br><br>
            <button type="submit" name="login" class="login_button">ログイン</button>
        </form>
        <br>
        <a href="Registry.php">アカウントをお持ちではありませんか？新規登録はこちら</a>
        <a href="before_login.php">戻る</a>
    <?php endif; ?>
</div>

<?php
// ログアウト処理
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

</body>
</html>